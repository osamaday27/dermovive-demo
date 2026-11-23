<?php

namespace Tests\Feature;

use App\Actions\Customers\SaveCustomerAction;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Accounting\StatementService;
use App\Services\Reports\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fixtures;
use Tests\TestCase;

class CustomerAndReportTest extends TestCase
{
    use RefreshDatabase, Fixtures;

    public function test_customer_create_update_and_opening_balance_in_ledger(): void
    {
        $c = $this->customer(['opening_balance' => '500']);
        $this->assertMatchesRegularExpression('/^CUS-\d{4}$/', $c->code);
        $this->assertSame('500.00', app(CustomerLedgerService::class)->balance($c));
        app(SaveCustomerAction::class)->execute(['name' => 'Renamed', 'category' => 'retail', 'credit_limit' => '10', 'payment_terms_days' => 5, 'opening_balance' => '9999'], $c->id);
        $this->assertSame('Renamed', $c->fresh()->name);
        $this->assertSame('500.00', app(CustomerLedgerService::class)->balance($c)); // opening balance immutable
    }

    public function test_statement_closing_balance_matches_live_balance(): void
    {
        $p = $this->product(); $this->batch($p, 20); $c = $this->customer(['opening_balance' => '100', 'credit_limit' => '9999']);
        $this->sale($c, $p, 2);
        $st = app(StatementService::class)->build($c->id, null, null);
        $this->assertSame(app(CustomerLedgerService::class)->balance($c), $st['closing']);
        $this->assertSame('328.00', $st['closing']);
    }

    public function test_sales_profit_inventory_and_receivables_reports(): void
    {
        $p = $this->product(['min_stock' => '50']); $this->batch($p, 10, 365, '40.00'); $this->batch($p, 4, -3, '40.00', 'OLD');
        $c = $this->customer(['credit_limit' => '9999']); $acc = $this->account();
        $this->sale($c, $p, 2, ['payment_method' => 'cash', 'treasury_account_id' => $acc->id]); // net 200, vat 28, total 228

        $r = app(ReportService::class);
        [$f, $t] = [today(), today()];
        $s = $r->sales($f, $t);
        $this->assertSame('228.00', $s['total']); $this->assertSame('200.00', $s['net']); $this->assertSame('28.00', $s['vat']); $this->assertSame(1, $s['count']);

        $pr = $r->profit($f, $t);
        $this->assertSame('80.00', $pr['cogs']);      // 2 x historical cost 40
        $this->assertSame('120.00', $pr['gross']);
        $this->assertSame('60.00', $pr['margin']);

        $inv = $r->inventory();
        $this->assertSame(1, $inv['low_stock']); $this->assertSame(1, $inv['expired']);
        $this->assertSame('0.00', $r->receivables()['total']);

        $this->sale($c, $p, 1); // deferred 114
        $this->assertSame('114.00', $r->receivables()['total']);
    }
}
