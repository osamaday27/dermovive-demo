<?php

namespace Tests\Feature;

use App\Exceptions\BusinessException;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Sales\InvoiceCalculator;
use App\Services\Treasury\TreasuryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fixtures;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase, Fixtures;

    public function test_discount_and_vat_calculation(): void
    {
        $r = app(InvoiceCalculator::class)->calculate([['qty' => 2, 'price' => 100, 'discount' => 10, 'vat' => 14]]);
        $this->assertSame('200.00', $r['subtotal']);
        $this->assertSame('20.00', $r['discount_total']);
        $this->assertSame('180.00', $r['taxable']);
        $this->assertSame('25.20', $r['vat_total']);
        $this->assertSame('205.20', $r['grand_total']);
    }

    public function test_invoice_level_discount_is_applied_before_vat(): void
    {
        $r = app(InvoiceCalculator::class)->calculate([['qty' => 2, 'price' => 100, 'discount' => 10, 'vat' => 14]], 10);
        $this->assertSame('38.00', $r['discount_total']);
        $this->assertSame('162.00', $r['taxable']);
        $this->assertSame('22.68', $r['vat_total']);
        $this->assertSame('184.68', $r['grand_total']);
    }

    public function test_fully_paid_cash_sale_updates_stock_ledger_and_treasury(): void
    {
        $p = $this->product(); $b = $this->batch($p, 10); $c = $this->customer(); $acc = $this->account();
        $inv = $this->sale($c, $p, 2, ['payment_method' => 'cash', 'treasury_account_id' => $acc->id]);
        $this->assertSame('228.00', $inv->grand_total);
        $this->assertSame('paid', $inv->status);
        $this->assertSame('8.000', $b->fresh()->available_qty);
        $this->assertSame('0.00', app(CustomerLedgerService::class)->balance($c));
        $this->assertSame('228.00', app(TreasuryService::class)->balance($acc));
        $this->assertDatabaseHas('stock_movements', ['batch_id' => $b->id, 'type' => 'sale', 'ref_no' => $inv->invoice_no]);
    }

    public function test_partial_payment(): void
    {
        $p = $this->product(); $this->batch($p, 10); $c = $this->customer(); $acc = $this->account();
        $inv = $this->sale($c, $p, 2, ['payment_method' => 'cash', 'paid_amount' => '100', 'treasury_account_id' => $acc->id]);
        $this->assertSame('partial', $inv->status);
        $this->assertSame('128.00', app(CustomerLedgerService::class)->balance($c));
        $this->assertSame('100.00', app(TreasuryService::class)->balance($acc));
    }

    public function test_deferred_sale_does_not_touch_treasury(): void
    {
        $p = $this->product(); $this->batch($p, 10); $c = $this->customer(); $acc = $this->account();
        $inv = $this->sale($c, $p, 1);
        $this->assertSame('unpaid', $inv->status);
        $this->assertSame('114.00', app(CustomerLedgerService::class)->balance($c));
        $this->assertSame('0.00', app(TreasuryService::class)->balance($acc));
    }

    public function test_credit_limit_is_enforced_and_override_is_explicit(): void
    {
        $p = $this->product(); $this->batch($p, 50); $c = $this->customer(['credit_limit' => '100']);
        try { $this->sale($c, $p, 1); $this->fail('limit not enforced'); } catch (BusinessException) {}
        $this->assertSame(0, Invoice::count());

        // flag alone is not enough: override must be enabled in settings
        try { $this->sale($c, $p, 1, ['credit_override' => true]); $this->fail('override without setting'); } catch (BusinessException) {}

        Setting::put('allow_credit_override', '1');
        $inv = $this->sale($c, $p, 1, ['credit_override' => true]);
        $this->assertTrue($inv->credit_override);
    }

    public function test_duplicate_submission_creates_one_invoice(): void
    {
        $p = $this->product(); $b = $this->batch($p, 10); $c = $this->customer();
        $a = $this->sale($c, $p, 2, ['idempotency_key' => 'same-key']);
        $b2 = $this->sale($c, $p, 2, ['idempotency_key' => 'same-key']);
        $this->assertSame($a->id, $b2->id);
        $this->assertSame(1, Invoice::count());
        $this->assertSame('8.000', $b->fresh()->available_qty);
    }

    public function test_client_cannot_override_price(): void
    {
        $p = $this->product(); $this->batch($p, 5); $c = $this->customer();
        $inv = $this->sale($c, $p, 1, ['lines' => [['product_id' => $p->id, 'qty' => 1, 'price' => '1', 'discount' => 0, 'vat' => '14']]]);
        $this->assertSame('114.00', $inv->grand_total);
    }
}
