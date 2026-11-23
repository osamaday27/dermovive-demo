<?php

namespace App\Services\Accounting;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/** Debit = customer owes more. Credit = customer owes less. Balance = SUM(debit) - SUM(credit). */
class CustomerLedgerService
{
    public function post(Customer $c, string $type, $debit, $credit, $date, ?string $refNo = null, ?Model $ref = null, ?string $desc = null): CustomerTransaction
    {
        return CustomerTransaction::create([
            'customer_id' => $c->id,
            'trx_date' => Carbon::parse($date)->toDateString(),
            'type' => $type,
            'ref_no' => $refNo,
            'reference_type' => $ref?->getMorphClass(),
            'reference_id' => $ref?->getKey(),
            'description' => $desc,
            'debit' => Money::round($debit),
            'credit' => Money::round($credit),
            'created_by' => auth()->id(),
        ]);
    }

    public function balance(Customer|int $c): string
    {
        $id = $c instanceof Customer ? $c->id : $c;
        $r = CustomerTransaction::where('customer_id', $id)
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
        return Money::round(Money::sub($r->d, $r->c));
    }

    /** @return array{total:string,count:int} money owed to us (positive balances only). */
    public function receivables(): array
    {
        $rows = CustomerTransaction::selectRaw('customer_id, SUM(debit - credit) as bal')->groupBy('customer_id')->get();
        $total = '0'; $count = 0;
        foreach ($rows as $r) {
            if (Money::gt($r->bal, '0')) { $total = Money::add($total, $r->bal); $count++; }
        }
        return ['total' => Money::round($total), 'count' => $count];
    }
}
