<?php

namespace App\Actions\Collections;

use App\Enums\CollectionMethod;
use App\Enums\LedgerType;
use App\Enums\TreasuryTxType;
use App\Exceptions\BusinessException;
use App\Models\CollectionAllocation;
use App\Models\CollectionReceipt;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\TreasuryAccount;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Accounting\DocumentNumberService;
use App\Services\Treasury\TreasuryService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecordCollectionAction
{
    public function __construct(private CustomerLedgerService $ledger, private TreasuryService $treasury, private DocumentNumberService $docs)
    {
    }

    /** @param array{customer_id:int,date:string,amount:mixed,method:string,treasury_account_id:int,allocations?:array<int,mixed>,notes?:?string,idempotency_key:string} $d */
    public function execute(array $d): CollectionReceipt
    {
        return DB::transaction(function () use ($d) {
            if ($existing = CollectionReceipt::where('idempotency_key', $d['idempotency_key'])->first()) {
                return $existing; // same payment never recorded twice
            }

            $customer = Customer::whereKey($d['customer_id'])->lockForUpdate()->first();
            if (! $customer) { throw new BusinessException(t('errors.invalid_customer')); }

            $amount = Money::round(Money::num($d['amount']));
            if (! Money::gt($amount, '0')) { throw new BusinessException(t('errors.invalid_amount')); }
            if (Money::gt($amount, $this->ledger->balance($customer))) { throw new BusinessException(t('errors.collection_exceeds')); }

            $method = CollectionMethod::from($d['method']);
            $account = TreasuryAccount::where('is_active', true)->find($d['treasury_account_id'] ?? 0);
            if (! $account) { throw new BusinessException(t('errors.invalid_account')); }
            $date = Carbon::parse($d['date'])->startOfDay();

            // validate allocations against locked invoices
            $allocs = []; $sum = '0';
            $invoices = Invoice::where('customer_id', $customer->id)->whereIn('id', array_keys($d['allocations'] ?? []))->lockForUpdate()->get()->keyBy('id');
            foreach (($d['allocations'] ?? []) as $invoiceId => $a) {
                $a = Money::round(Money::num($a));
                if (! Money::gt($a, '0')) { continue; }
                $inv = $invoices[$invoiceId] ?? null;
                if (! $inv) { throw new BusinessException(t('errors.invalid_invoice')); }
                if (Money::gt($a, $inv->remaining)) { throw new BusinessException(t('errors.alloc_exceeds_invoice', ['no' => $inv->invoice_no])); }
                $allocs[$invoiceId] = $a;
                $sum = Money::add($sum, $a);
            }
            if (Money::gt($sum, $amount)) { throw new BusinessException(t('errors.alloc_exceeds_amount')); }

            $receipt = CollectionReceipt::create([
                'receipt_no' => $this->docs->next('RCT'),
                'idempotency_key' => $d['idempotency_key'],
                'customer_id' => $customer->id,
                'receipt_date' => $date->toDateString(),
                'amount' => $amount,
                'method' => $method->value,
                'treasury_account_id' => $account->id,
                'status' => 'posted',
                'notes' => $d['notes'] ?? null,
                'user_id' => $d['user_id'] ?? auth()->id(),
            ]);

            foreach ($allocs as $invoiceId => $a) {
                CollectionAllocation::create(['receipt_id' => $receipt->id, 'invoice_id' => $invoiceId, 'amount' => $a]);
                $inv = $invoices[$invoiceId];
                $inv->paid_amount = Money::round(Money::add($inv->paid_amount, $a));
                $inv->refreshStatus();
                $inv->save();
            }
            $rest = Money::round(Money::sub($amount, $sum));
            if (Money::gt($rest, '0')) {   // account-level payment
                CollectionAllocation::create(['receipt_id' => $receipt->id, 'invoice_id' => null, 'amount' => $rest]);
            }

            $this->ledger->post($customer, LedgerType::Collection->value, '0', $amount, $date, $receipt->receipt_no, $receipt, t('stmt.collection_desc', ['no' => $receipt->receipt_no]));
            $this->treasury->post($account, TreasuryTxType::Collection, $amount, '0', $date, t('treas.collection_desc', ['no' => $receipt->receipt_no, 'customer' => $customer->name]), $receipt, 'receipt:'.$receipt->id, null, null, $receipt->receipt_no);

            return $receipt;
        });
    }
}
