<?php

namespace Tests\Feature;

use App\Exceptions\BusinessException;
use App\Models\TreasuryTransaction;
use App\Services\Treasury\TreasuryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fixtures;
use Tests\TestCase;

class TreasuryTest extends TestCase
{
    use RefreshDatabase, Fixtures;

    public function test_cash_in_and_out(): void
    {
        $t = app(TreasuryService::class); $a = $this->account('Main Cashbox', 'cash', '1000');
        $t->cashIn($a->id, 500, today(), 'deposit');
        $t->cashOut($a->id, 200, today(), 'rent');
        $this->assertSame('1300.00', $t->balance($a));
    }

    public function test_cannot_spend_more_than_balance(): void
    {
        $t = app(TreasuryService::class); $a = $this->account('Main Cashbox', 'cash', '100');
        $this->expectException(BusinessException::class);
        $t->cashOut($a->id, 101, today(), 'too much');
    }

    public function test_transfer_creates_linked_rows_and_checks_source_balance(): void
    {
        $t = app(TreasuryService::class); $a = $this->account('Main Cashbox', 'cash', '1000'); $b = $this->account('Bank Account', 'bank', '0');
        [$out, $in] = $t->transfer($a->id, $b->id, 400, today());
        $this->assertSame($out->transfer_group_id, $in->transfer_group_id);
        $this->assertSame('600.00', $t->balance($a));
        $this->assertSame('400.00', $t->balance($b));
        $this->expectException(BusinessException::class);
        $t->transfer($a->id, $b->id, 601, today());
    }

    public function test_reversal_restores_balance_once_and_posted_rows_are_immutable(): void
    {
        $t = app(TreasuryService::class); $a = $this->account('Main Cashbox', 'cash', '1000');
        $tx = $t->cashOut($a->id, 300, today(), 'mistake');
        $t->reverse($tx->id);
        $this->assertSame('1000.00', $t->balance($a));
        try { $t->reverse($tx->id); $this->fail(); } catch (BusinessException) {}

        $this->expectException(\LogicException::class);
        TreasuryTransaction::find($tx->id)->update(['outflow' => 1]);
    }

    public function test_balance_equals_sum_of_ledger(): void
    {
        $t = app(TreasuryService::class); $a = $this->account('Main Cashbox', 'cash', '250');
        $t->cashIn($a->id, 50, today(), 'x'); $t->cashOut($a->id, 80, today(), 'y');
        $sum = TreasuryTransaction::where('account_id', $a->id)->selectRaw('SUM(inflow) - SUM(outflow) as b')->value('b');
        $this->assertEquals($t->balance($a), number_format((float) $sum, 2, '.', ''));
    }
}
