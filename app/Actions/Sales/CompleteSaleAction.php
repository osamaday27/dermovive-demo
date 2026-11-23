<?php

namespace App\Actions\Sales;

use App\Enums\LedgerType;
use App\Enums\PaymentMethod;
use App\Enums\ProductUnit;
use App\Enums\TreasuryTxType;
use App\Exceptions\BusinessException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TreasuryAccount;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Accounting\DocumentNumberService;
use App\Services\Inventory\StockService;
use App\Services\Sales\InvoiceCalculator;
use App\Services\Treasury\TreasuryService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The whole checkout runs in ONE transaction:
 * validate -> FEFO allocate -> calculate -> invoice + items -> stock deduction + movements
 * -> customer ledger -> payments -> treasury inflows.
 */
class CompleteSaleAction
{
    public function __construct(
        private StockService $stock,
        private InvoiceCalculator $calc,
        private CustomerLedgerService $ledger,
        private TreasuryService $treasury,
        private DocumentNumberService $docs,
    ) {
    }

    /**
     * @param array{customer_id:int,date:string,lines:array,invoice_discount_pct?:mixed,payment_method:string,paid_amount?:mixed,
     *   treasury_account_id?:?int,credit_override?:bool,skip_credit_check?:bool,notes?:?string,idempotency_key:string,user_id?:?int} $d
     */
    public function execute(array $d): Invoice
    {
        return DB::transaction(function () use ($d) {
            // Prevent duplicate submission (double click / retry)
            if ($existing = Invoice::where('idempotency_key', $d['idempotency_key'])->first()) {
                return $existing;
            }

            $customer = Customer::whereKey($d['customer_id'])->lockForUpdate()->first();
            if (! $customer || $customer->status !== 'active') { throw new BusinessException(t('errors.invalid_customer')); }
            if (empty($d['lines'])) { throw new BusinessException(t('errors.empty_invoice')); }

            $date = Carbon::parse($d['date'])->startOfDay();
            $vatRates = array_map('floatval', Setting::vatRates());
            $invDiscount = Money::num($d['invoice_discount_pct'] ?? 0);
            if (Money::lt($invDiscount, '0') || Money::gt($invDiscount, '100')) { throw new BusinessException(t('errors.invalid_discount')); }

            // 1) validate lines + FEFO allocation (one invoice row per batch used)
            $rows = []; $reserved = [];
            foreach ($d['lines'] as $l) {
                $product = Product::active()->find($l['product_id'] ?? 0);
                if (! $product) { throw new BusinessException(t('errors.invalid_product')); }
                $qty = Money::round(Money::num($l['qty'] ?? 0), 3);
                if (! Money::gt($qty, '0')) { throw new BusinessException(t('errors.invalid_qty')); }
                $disc = Money::num($l['discount'] ?? 0);
                if (Money::lt($disc, '0') || Money::gt($disc, '100')) { throw new BusinessException(t('errors.invalid_discount')); }
                $vat = Money::num($l['vat'] ?? $product->vat_rate);
                if (! in_array((float) $vat, $vatRates, true)) { throw new BusinessException(t('errors.invalid_vat')); }
                $price = Money::round($product->selling_price);   // price always comes from the catalogue, never from the client

                foreach ($this->stock->allocateFefo($product, $qty, $l['batch_id'] ?? null, $date, $reserved) as [$batch, $portion]) {
                    $rows[] = ['product' => $product, 'batch' => $batch, 'qty' => $portion, 'unit' => ProductUnit::tryFrom($l['unit'] ?? '')?->value ?? $product->unit,
                        'price' => $price, 'discount' => $disc, 'vat' => $vat];
                }
            }

            // 2) totals
            $calc = $this->calc->calculate(array_map(fn ($r) => ['qty' => $r['qty'], 'price' => $r['price'], 'discount' => $r['discount'], 'vat' => $r['vat']], $rows), $invDiscount);
            $grand = $calc['grand_total'];

            // 3) payment
            $method = PaymentMethod::from($d['payment_method']);
            $paidRaw = $d['paid_amount'] ?? '';
            $paid = $method === PaymentMethod::Credit ? '0' : (($paidRaw === '' || $paidRaw === null) ? $grand : Money::round(Money::num($paidRaw)));
            if (Money::lt($paid, '0')) { throw new BusinessException(t('errors.invalid_amount')); }
            if (Money::gt($paid, $grand)) { throw new BusinessException(t('errors.overpaid')); }
            $remaining = Money::round(Money::sub($grand, $paid));

            // 4) credit limit
            $override = false;
            if (Money::gt($remaining, '0') && empty($d['skip_credit_check'])) {
                $projected = Money::add($this->ledger->balance($customer), $remaining);
                if (Money::gt($projected, $customer->credit_limit)) {
                    if (empty($d['credit_override']) || Setting::get('allow_credit_override') !== '1') {
                        throw new BusinessException(t('errors.credit_limit', ['limit' => m($customer->credit_limit), 'projected' => m($projected)]));
                    }
                    $override = true;
                }
            }

            $account = null;
            if (Money::gt($paid, '0')) {
                $account = TreasuryAccount::where('is_active', true)->find($d['treasury_account_id'] ?? 0);
                if (! $account) { throw new BusinessException(t('errors.invalid_account')); }
            }

            // 5) invoice + items
            $invoice = Invoice::create([
                'invoice_no' => $this->docs->next('INV'),
                'idempotency_key' => $d['idempotency_key'],
                'customer_id' => $customer->id,
                'invoice_date' => $date->toDateString(),
                'subtotal' => $calc['subtotal'],
                'discount_pct' => $invDiscount,
                'discount_total' => $calc['discount_total'],
                'taxable_amount' => $calc['taxable'],
                'vat_total' => $calc['vat_total'],
                'grand_total' => $grand,
                'paid_amount' => $paid,
                'payment_method' => $method->value,
                'status' => Invoice::statusFor($grand, $paid)->value,
                'credit_override' => $override,
                'notes' => $d['notes'] ?? null,
                'user_id' => $d['user_id'] ?? auth()->id(),
            ]);

            foreach ($rows as $i => $r) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $r['product']->id,
                    'batch_id' => $r['batch']->id,
                    'unit' => $r['unit'],
                    'quantity' => $r['qty'],
                    'unit_price' => $r['price'],
                    'discount_pct' => $r['discount'],
                    'vat_rate' => $r['vat'],
                    'unit_cost' => $r['batch']->unit_cost,   // historical cost snapshot
                    'discount_amount' => $calc['lines'][$i]['discount_amount'],
                    'vat_amount' => $calc['lines'][$i]['vat_amount'],
                    'line_total' => $calc['lines'][$i]['line_total'],
                ]);
                $this->stock->deduct($r['batch']->id, $r['qty'], $invoice, $invoice->invoice_no, $date->copy()->setTime(12, 0));
            }

            // 6) customer ledger: debit full invoice, credit what was paid now
            $this->ledger->post($customer, LedgerType::Invoice->value, $grand, '0', $date, $invoice->invoice_no, $invoice, t('stmt.sales_invoice_desc', ['no' => $invoice->invoice_no]));

            // 7) payment + treasury inflow (only money actually received)
            if ($account) {
                $payment = InvoicePayment::create([
                    'invoice_id' => $invoice->id, 'method' => $method->value, 'amount' => $paid,
                    'treasury_account_id' => $account->id, 'paid_at' => $date->toDateString(),
                ]);
                $this->ledger->post($customer, LedgerType::InvoicePayment->value, '0', $paid, $date, $invoice->invoice_no, $invoice, t('stmt.payment_desc', ['no' => $invoice->invoice_no]));
                $this->treasury->post($account, TreasuryTxType::SalePayment, $paid, '0', $date, t('treas.sale_payment_desc', ['no' => $invoice->invoice_no]), $payment, 'invoice_payment:'.$payment->id, null, null, $invoice->invoice_no);
            }

            return $invoice;
        });
    }
}
