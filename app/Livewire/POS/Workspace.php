<?php

namespace App\Livewire\POS;

use App\Actions\Sales\CompleteSaleAction;
use App\Enums\PaymentMethod;
use App\Exceptions\BusinessException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Setting;
use App\Models\Category;
use App\Models\TreasuryAccount;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Sales\InvoiceCalculator;
use App\Support\Money;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** UI only: all money / stock logic is delegated to CompleteSaleAction + InvoiceCalculator. */
class Workspace extends Component
{
    public string $search = '';
    public string $category = 'all';
    public string $customer_id = '';
    public string $invoice_date = '';
    public array $lines = [];
    public string $invoice_discount = '0';
    public string $payment_method = 'cash';
    public string $paid_amount = '';
    public string $treasury_account_id = '';
    public bool $credit_override = false;
    public string $notes = '';
    public string $token = '';
    #[Locked] public ?int $printInvoiceId = null;

    public function mount(): void
    {
        $this->invoice_date = today()->toDateString();
        $this->token = (string) Str::uuid();
        $this->treasury_account_id = (string) $this->defaultAccount('cash');
    }

    private function defaultAccount(string $method): ?int
    {
        $type = match ($method) { 'cash' => 'cash', 'card', 'bank_transfer' => 'bank', default => null };
        return $type ? TreasuryAccount::where('type', $type)->where('is_active', true)->orderBy('id')->value('id') : null;
    }

    public function updatedPaymentMethod(string $v): void
    {
        $this->treasury_account_id = (string) $this->defaultAccount($v);
        if ($v === 'credit') { $this->paid_amount = ''; }
    }

    public function addProduct(int $id): void
    {
        $p = Product::active()->find($id);
        if (! $p) { return; }
        foreach ($this->lines as $i => $l) {
            if ($l['product_id'] === $p->id && $l['batch_id'] === '') {
                $this->lines[$i]['qty'] = q(Money::add($l['qty'], '1'));
                return;
            }
        }
        $this->lines[] = ['product_id' => $p->id, 'batch_id' => '', 'qty' => '1', 'unit' => $p->unit,
            'discount' => '0', 'vat' => (string) (float) $p->vat_rate];
    }

    public function addByCode(): void
    {
        $term = trim($this->search);
        if ($term === '') { return; }
        $p = Product::active()->where(fn ($q) => $q->where('barcode', $term)->orWhere('sku', $term))->first();
        if ($p) { $this->addProduct($p->id); $this->search = ''; }
        else { $this->dispatch('notify', message: t('pos.not_found'), type: 'error'); }
    }

    public function inc(int $i): void { $this->lines[$i]['qty'] = q(Money::add($this->lines[$i]['qty'], '1')); }

    public function dec(int $i): void
    {
        if (Money::gt($this->lines[$i]['qty'], '1')) { $this->lines[$i]['qty'] = q(Money::sub($this->lines[$i]['qty'], '1')); }
        else { $this->remove($i); }
    }

    public function remove(int $i): void
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines);
    }

    public function clear(): void { $this->resetInvoice(); }

    private function resetInvoice(): void
    {
        $this->lines = []; $this->invoice_discount = '0'; $this->paid_amount = ''; $this->notes = ''; $this->credit_override = false;
        $this->customer_id = ''; $this->token = (string) Str::uuid(); $this->resetErrorBag();
    }

    private function clamp($v, string $min = '0', string $max = '100'): string
    {
        $v = Money::num($v);
        return Money::lt($v, $min) ? $min : (Money::gt($v, $max) ? $max : $v);
    }

    public function totals(): array
    {
        $in = [];
        foreach ($this->lines as $k => $l) {
            $in[$k] = ['qty' => $this->clamp($l['qty'], '0', '10000000'), 'price' => Product::whereKey($l['product_id'])->value('selling_price') ?? '0',
                'discount' => $this->clamp($l['discount']), 'vat' => Money::num($l['vat'])];
        }
        return app(InvoiceCalculator::class)->calculate($in, $this->clamp($this->invoice_discount));
    }

    public function completeSale(CompleteSaleAction $action): void
    {
        $this->resetErrorBag();
        if ($this->customer_id === '') { $this->addError('business', t('errors.invalid_customer')); return; }
        try {
            $invoice = $action->execute([
                'customer_id' => (int) $this->customer_id,
                'date' => $this->invoice_date,
                'lines' => array_map(fn ($l) => [
                    'product_id' => $l['product_id'], 'batch_id' => $l['batch_id'] === '' ? null : (int) $l['batch_id'],
                    'qty' => $l['qty'], 'unit' => $l['unit'], 'discount' => $l['discount'], 'vat' => $l['vat'],
                ], $this->lines),
                'invoice_discount_pct' => $this->invoice_discount,
                'payment_method' => $this->payment_method,
                'paid_amount' => $this->paid_amount,
                'treasury_account_id' => $this->treasury_account_id === '' ? null : (int) $this->treasury_account_id,
                'credit_override' => $this->credit_override,
                'notes' => $this->notes ?: null,
                'idempotency_key' => $this->token,
            ]);
        } catch (BusinessException $e) {
            $this->addError('business', $e->getMessage());
            return;
        }
        $this->printInvoiceId = $invoice->id;
        $this->dispatch('notify', message: t('pos.sale_completed', ['no' => $invoice->invoice_no]), type: 'success');
        $this->resetInvoice();
    }

    public function closePrint(): void { $this->printInvoiceId = null; }

    public function render()
    {
        $term = trim($this->search);
        $products = Product::active()->with('category')
            ->withSum(['batches as sellable' => fn ($q) => $q->where('expiry_date', '>=', today()->toDateString())], 'available_qty')
            ->withMin(['batches as next_expiry' => fn ($q) => $q->where('available_qty', '>', 0)->where('expiry_date', '>=', today()->toDateString())], 'expiry_date')
            ->when($this->category !== 'all', fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $this->category)))
            ->when($term !== '', fn ($q) => $q->where(function ($w) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $w->where('name_en', 'like', $like)->orWhere('name_ar', 'like', $like)->orWhere('name_fr', 'like', $like)
                  ->orWhere('sku', 'like', $like)->orWhere('barcode', 'like', $like);
            }))
            ->orderBy('name_en')->limit(30)->get();

        $ids = collect($this->lines)->pluck('product_id')->all();
        $lineProducts = Product::whereIn('id', $ids)->get()->keyBy('id');
        $batches = ProductBatch::whereIn('product_id', $ids)->where('available_qty', '>', 0)
            ->where('expiry_date', '>=', today()->toDateString())->orderBy('expiry_date')->get()->groupBy('product_id');

        $totals = $this->totals();
        $customer = $this->customer_id !== '' ? Customer::withBalance()->find((int) $this->customer_id) : null;
        $paid = $this->payment_method === 'credit' ? '0' : ($this->paid_amount === '' ? $totals['grand_total'] : Money::round(Money::num($this->paid_amount)));
        $remaining = Money::round(Money::sub($totals['grand_total'], $paid));
        $exceeds = $customer && Money::gt($remaining, '0') && Money::gt(Money::add($customer->balance, $remaining), $customer->credit_limit);

        return view('livewire.pos.workspace', [
            'products' => $products, 'categories' => Category::orderBy('id')->get(),
            'customers' => Customer::active()->orderBy('name')->get(['id', 'code', 'name']),
            'accounts' => TreasuryAccount::where('is_active', true)->orderBy('id')->get(),
            'lineProducts' => $lineProducts, 'batches' => $batches, 'totals' => $totals, 'customer' => $customer,
            'paid' => $paid, 'remaining' => $remaining, 'exceeds' => $exceeds,
            'canOverride' => Setting::get('allow_credit_override') === '1',
            'vatRates' => Setting::vatRates(), 'methods' => PaymentMethod::cases(),
            'printInvoice' => $this->printInvoiceId ? Invoice::with(['customer', 'items.product', 'items.batch', 'payments.account'])->find($this->printInvoiceId) : null,
            'nextInvoiceNo' => 'INV-'.str_pad((string) ((int) (\DB::table('document_sequences')->where('type', 'INV')->value('last_number')) + 1), 6, '0', STR_PAD_LEFT),
        ])->title(t('nav.pos'));
    }
}
