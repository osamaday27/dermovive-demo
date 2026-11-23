<?php

namespace Tests\Feature;

use App\Actions\Collections\RecordCollectionAction;
use App\Exceptions\BusinessException;
use App\Models\CollectionReceipt;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Treasury\TreasuryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fixtures;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use RefreshDatabase, Fixtures;

    private function collect(array $o): CollectionReceipt
    {
        return app(RecordCollectionAction::class)->execute($o + ['date' => today()->toDateString(), 'method' => 'cash', 'notes' => null, 'idempotency_key' => uniqid('c', true)]);
    }

    public function test_collection_updates_invoice_ledger_and_treasury(): void
    {
        $p = $this->product(); $this->batch($p, 20); $c = $this->customer(['credit_limit' => '5000']); $acc = $this->account();
        $inv = $this->sale($c, $p, 2); // 228 unpaid
        $r = $this->collect(['customer_id' => $c->id, 'amount' => '100', 'treasury_account_id' => $acc->id, 'allocations' => [$inv->id => '100']]);

        $this->assertSame('partial', $inv->fresh()->status);
        $this->assertSame('100.00', $inv->fresh()->paid_amount);
        $this->assertSame('128.00', app(CustomerLedgerService::class)->balance($c));
        $this->assertSame('100.00', app(TreasuryService::class)->balance($acc));
        $this->assertSame(1, $r->allocations()->count());

        $this->collect(['customer_id' => $c->id, 'amount' => '128', 'treasury_account_id' => $acc->id, 'allocations' => [$inv->id => '128']]);
        $this->assertSame('paid', $inv->fresh()->status);
        $this->assertSame('0.00', app(CustomerLedgerService::class)->balance($c));
    }

    public function test_multi_invoice_allocation_and_account_level_remainder(): void
    {
        $p = $this->product(); $this->batch($p, 20); $c = $this->customer(['credit_limit' => '5000', 'opening_balance' => '50']); $acc = $this->account();
        $a = $this->sale($c, $p, 1); $b = $this->sale($c, $p, 1); // 114 + 114 + 50 opening
        $r = $this->collect(['customer_id' => $c->id, 'amount' => '250', 'treasury_account_id' => $acc->id, 'allocations' => [$a->id => '114', $b->id => '100']]);
        $this->assertSame('paid', $a->fresh()->status);
        $this->assertSame('partial', $b->fresh()->status);
        $this->assertSame('36.00', number_format((float) $r->allocations()->whereNull('invoice_id')->sum('amount'), 2, '.', ''));
        $this->assertSame('28.00', app(CustomerLedgerService::class)->balance($c));
    }

    public function test_cannot_collect_more_than_outstanding_or_allocate_more_than_invoice(): void
    {
        $p = $this->product(); $this->batch($p, 20); $c = $this->customer(); $acc = $this->account();
        $inv = $this->sale($c, $p, 1); // 114
        try { $this->collect(['customer_id' => $c->id, 'amount' => '500', 'treasury_account_id' => $acc->id]); $this->fail(); } catch (BusinessException) {}
        try { $this->collect(['customer_id' => $c->id, 'amount' => '100', 'treasury_account_id' => $acc->id, 'allocations' => [$inv->id => '200']]); $this->fail(); } catch (BusinessException) {}
        $this->assertSame(0, CollectionReceipt::count());
    }

    public function test_same_payment_is_never_recorded_twice(): void
    {
        $p = $this->product(); $this->batch($p, 20); $c = $this->customer(); $acc = $this->account();
        $inv = $this->sale($c, $p, 1);
        $o = ['customer_id' => $c->id, 'amount' => '50', 'treasury_account_id' => $acc->id, 'allocations' => [$inv->id => '50'], 'idempotency_key' => 'once'];
        $this->collect($o); $this->collect($o);
        $this->assertSame(1, CollectionReceipt::count());
        $this->assertSame('50.00', app(TreasuryService::class)->balance($acc));
    }
}
