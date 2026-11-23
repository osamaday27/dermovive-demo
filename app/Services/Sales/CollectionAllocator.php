<?php

namespace App\Services\Sales;

use App\Models\Invoice;
use App\Support\Money;
use Carbon\Carbon;

class CollectionAllocator
{
    /** Oldest-invoice-first suggestion. @return array<int,string> invoiceId => amount */
    public function suggest(int $customerId, $amount, ?Carbon $asOf = null): array
    {
        $left = Money::round(Money::num($amount)); $out = [];
        $invoices = Invoice::where('customer_id', $customerId)->whereColumn('paid_amount', '<', 'grand_total')
            ->when($asOf, fn ($q) => $q->where('invoice_date', '<=', $asOf->toDateString()))
            ->orderBy('invoice_date')->orderBy('id')->get();
        foreach ($invoices as $inv) {
            if (! Money::gt($left, '0')) { break; }
            $take = Money::lt($inv->remaining, $left) ? $inv->remaining : $left;
            $out[$inv->id] = Money::round($take);
            $left = Money::sub($left, $take);
        }
        return $out;
    }
}
