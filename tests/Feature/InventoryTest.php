<?php

namespace Tests\Feature;

use App\Enums\ExpiryStatus;
use App\Exceptions\BusinessException;
use App\Models\InvoiceItem;
use App\Models\StockMovement;
use App\Services\Inventory\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fixtures;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase, Fixtures;

    public function test_product_and_batch_can_be_created(): void
    {
        $p = $this->product();
        $b = $this->batch($p, 20);
        $this->assertDatabaseHas('products', ['id' => $p->id]);
        $this->assertSame('20.000', $b->fresh()->available_qty);
        $this->assertDatabaseHas('stock_movements', ['batch_id' => $b->id, 'type' => 'opening']);
    }

    public function test_expiry_status_thresholds(): void
    {
        $this->assertSame(ExpiryStatus::Expired, ExpiryStatus::for(today()->subDay()));
        $this->assertSame(ExpiryStatus::Critical, ExpiryStatus::for(today()->addDays(10)));
        $this->assertSame(ExpiryStatus::Warning, ExpiryStatus::for(today()->addDays(60)));
        $this->assertSame(ExpiryStatus::Valid, ExpiryStatus::for(today()->addDays(200)));
    }

    public function test_fefo_consumes_earliest_expiry_first(): void
    {
        $p = $this->product(); $c = $this->customer();
        $late = $this->batch($p, 10, 200); $soon = $this->batch($p, 5, 30);
        $inv = $this->sale($c, $p, 7);
        $items = InvoiceItem::where('invoice_id', $inv->id)->get();
        $this->assertCount(2, $items);
        $this->assertSame('0.000', $soon->fresh()->available_qty);
        $this->assertSame('8.000', $late->fresh()->available_qty);
    }

    public function test_expired_batches_cannot_be_sold(): void
    {
        $p = $this->product(); $c = $this->customer();
        $this->batch($p, 10, -1);
        $this->expectException(BusinessException::class);
        $this->sale($c, $p, 1);
    }

    public function test_negative_stock_is_prevented(): void
    {
        $p = $this->product(); $c = $this->customer(); $b = $this->batch($p, 3);
        try { $this->sale($c, $p, 5); $this->fail('should throw'); } catch (BusinessException) {}
        $this->assertSame('3.000', $b->fresh()->available_qty);
        $this->expectException(BusinessException::class);
        app(StockService::class)->adjust($b->id, 'out', 5, 'test');
    }

    public function test_adjustment_is_recorded(): void
    {
        $p = $this->product(); $b = $this->batch($p, 10);
        app(StockService::class)->adjust($b->id, 'out', 4, 'damaged');
        $this->assertSame('6.000', $b->fresh()->available_qty);
        $this->assertSame(1, StockMovement::where('batch_id', $b->id)->where('type', 'adjustment_out')->count());
    }
}
