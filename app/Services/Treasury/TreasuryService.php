<?php

namespace App\Services\Treasury;

use App\Enums\TreasuryTxType;
use App\Exceptions\BusinessException;
use App\Models\TreasuryAccount;
use App\Models\TreasuryTransaction;
use App\Services\Accounting\DocumentNumberService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** The ONLY place that writes treasury rows. Balances are always derived from the ledger. */
class TreasuryService
{
    public function __construct(private DocumentNumberService $docs)
    {
    }

    public function balance(TreasuryAccount|int $a): string
    {
        $id = $a instanceof TreasuryAccount ? $a->id : $a;
        $r = TreasuryTransaction::where('account_id', $id)
            ->selectRaw('COALESCE(SUM(inflow),0) as i, COALESCE(SUM(outflow),0) as o')->first();
        return Money::round(Money::sub($r->i, $r->o));
    }

    public function openAccount(array $attrs): TreasuryAccount
    {
        return DB::transaction(function () use ($attrs) {
            $acc = TreasuryAccount::firstOrCreate(['name' => $attrs['name']], $attrs);
            if (Money::gt($acc->opening_balance, '0')) {
                $this->post($acc, TreasuryTxType::Opening, $acc->opening_balance, '0', Carbon::today()->subDays(120), t('treas.opening_balance'), null, 'opening:'.$acc->id);
            }
            return $acc;
        });
    }

    public function post(TreasuryAccount $a, TreasuryTxType $type, $in, $out, $date, string $desc, ?Model $source = null,
        ?string $dedupe = null, ?string $group = null, ?int $reversalOf = null, ?string $ref = null): TreasuryTransaction
    {
        if ($dedupe && ($ex = TreasuryTransaction::where('dedupe_key', $dedupe)->first())) {
            return $ex; // duplicate-entry protection
        }
        return TreasuryTransaction::create([
            'tx_no' => $this->docs->next('TRX'),
            'account_id' => $a->id,
            'tx_date' => Carbon::parse($date)->toDateString(),
            'type' => $type->value,
            'description' => $desc,
            'reference' => $ref,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'dedupe_key' => $dedupe,
            'transfer_group_id' => $group,
            'inflow' => Money::round($in),
            'outflow' => Money::round($out),
            'reversal_of_id' => $reversalOf,
            'user_id' => auth()->id(),
        ]);
    }

    public function cashIn(int $accountId, $amount, $date, string $desc, ?string $ref = null): TreasuryTransaction
    {
        return DB::transaction(function () use ($accountId, $amount, $date, $desc, $ref) {
            $acc = $this->lockedAccount($accountId);
            $amount = $this->positive($amount);
            return $this->post($acc, TreasuryTxType::CashIn, $amount, '0', $date, $desc, null, null, null, null, $ref);
        });
    }

    public function cashOut(int $accountId, $amount, $date, string $desc, ?string $ref = null): TreasuryTransaction
    {
        return DB::transaction(function () use ($accountId, $amount, $date, $desc, $ref) {
            $acc = $this->lockedAccount($accountId);
            $amount = $this->positive($amount);
            $this->assertCovered($acc, $amount);
            return $this->post($acc, TreasuryTxType::CashOut, '0', $amount, $date, $desc, null, null, null, null, $ref);
        });
    }

    /** @return array{0:TreasuryTransaction,1:TreasuryTransaction} */
    public function transfer(int $fromId, int $toId, $amount, $date, ?string $note = null): array
    {
        if ($fromId === $toId) { throw new BusinessException(t('errors.same_account')); }
        return DB::transaction(function () use ($fromId, $toId, $amount, $date, $note) {
            $ids = [$fromId, $toId]; sort($ids); // consistent lock order -> no deadlocks
            $locked = collect($ids)->mapWithKeys(fn ($id) => [$id => $this->lockedAccount($id)]);
            $amount = $this->positive($amount);
            $this->assertCovered($locked[$fromId], $amount);
            $g = (string) Str::uuid();
            $desc = $note ?: t('treas.transfer');
            $out = $this->post($locked[$fromId], TreasuryTxType::TransferOut, '0', $amount, $date, $desc.' → '.$locked[$toId]->name, null, null, $g);
            $in = $this->post($locked[$toId], TreasuryTxType::TransferIn, $amount, '0', $date, $desc.' ← '.$locked[$fromId]->name, null, null, $g);
            return [$out, $in];
        });
    }

    /** Correct a manual entry with an opposite (reversal) row. System-generated entries are not reversible here. */
    public function reverse(int $txId): void
    {
        DB::transaction(function () use ($txId) {
            $tx = TreasuryTransaction::whereKey($txId)->lockForUpdate()->firstOrFail();
            $manual = [TreasuryTxType::CashIn->value, TreasuryTxType::CashOut->value, TreasuryTxType::TransferIn->value, TreasuryTxType::TransferOut->value];
            if (! in_array($tx->type, $manual, true)) { throw new BusinessException(t('errors.not_reversible')); }

            $legs = $tx->transfer_group_id ? TreasuryTransaction::where('transfer_group_id', $tx->transfer_group_id)->orderBy('id')->get() : collect([$tx]);
            foreach ($legs as $leg) {
                if (TreasuryTransaction::where('reversal_of_id', $leg->id)->exists()) { throw new BusinessException(t('errors.already_reversed')); }
            }
            foreach ($legs->sortBy('account_id') as $leg) {
                $acc = $this->lockedAccount($leg->account_id);
                if (Money::gt($leg->inflow, '0')) { $this->assertCovered($acc, $leg->inflow); }
            }
            foreach ($legs as $leg) {
                $acc = TreasuryAccount::findOrFail($leg->account_id);
                $this->post($acc, TreasuryTxType::Reversal, $leg->outflow, $leg->inflow, today(), t('treas.reversal_of', ['no' => $leg->tx_no]), null, 'reversal:'.$leg->id, null, $leg->id);
            }
        });
    }

    private function lockedAccount(int $id): TreasuryAccount
    {
        $a = TreasuryAccount::whereKey($id)->lockForUpdate()->first();
        if (! $a || ! $a->is_active) { throw new BusinessException(t('errors.invalid_account')); }
        return $a;
    }

    private function positive($amount): string
    {
        $amount = Money::round(Money::num($amount));
        if (! Money::gt($amount, '0')) { throw new BusinessException(t('errors.invalid_amount')); }
        return $amount;
    }

    private function assertCovered(TreasuryAccount $a, string $amount): void
    {
        if (Money::gt($amount, $this->balance($a))) { throw new BusinessException(t('errors.insufficient_treasury', ['account' => $a->name])); }
    }
}
