<?php

namespace App\Services\Accounting;

use App\Models\CustomerTransaction;
use App\Support\Money;

class StatementService
{
    public function build(int $customerId, ?string $from, ?string $to, ?string $type = null): array
    {
        $base = CustomerTransaction::where('customer_id', $customerId);

        $opening = '0';
        if ($from) {
            $r = (clone $base)->where('trx_date', '<', $from)
                ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
            $opening = Money::sub($r->d, $r->c);
        }

        $txs = (clone $base)
            ->when($from, fn ($q) => $q->where('trx_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('trx_date', '<=', $to))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('trx_date')->orderBy('id')->get();

        $running = $opening; $td = '0'; $tc = '0'; $rows = [];
        foreach ($txs as $tx) {
            $running = Money::sub(Money::add($running, $tx->debit), $tx->credit);
            $td = Money::add($td, $tx->debit);
            $tc = Money::add($tc, $tx->credit);
            $rows[] = ['tx' => $tx, 'running' => Money::round($running)];
        }

        return [
            'rows' => $rows,
            'opening' => Money::round($opening),
            'debit' => Money::round($td),
            'credit' => Money::round($tc),
            'closing' => Money::round(Money::sub(Money::add($opening, $td), $tc)),
        ];
    }
}
