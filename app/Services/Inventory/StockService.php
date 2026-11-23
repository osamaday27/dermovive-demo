<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Exceptions\BusinessException;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function addBatch(Product $p, array $d): ProductBatch
    {
        return DB::transaction(function () use ($p, $d) {
            $qty = Money::round(Money::num($d['qty']), 3);
            if (Money::lt($qty, '0')) { throw new BusinessException(t('errors.invalid_qty')); }
            $b = ProductBatch::create([
                'product_id' => $p->id,
                'warehouse_id' => Warehouse::default()->id,
                'batch_no' => $d['batch_no'],
                'mfg_date' => ! empty($d['mfg_date']) ? Carbon::parse($d['mfg_date'])->toDateString() : null,
                'expiry_date' => Carbon::parse($d['expiry_date'])->toDateString(),
                'unit_cost' => Money::round($d['unit_cost'] ?? $p->purchase_price),
                'initial_qty' => $qty,
                'available_qty' => $qty,
            ]);
            $this->movement($b, StockMovementType::Opening, $qty, '0', null, null, t('inv.opening_stock'), $d['moved_at'] ?? now());
            return $b;
        });
    }

    /** Positive qty = stock in, negative = stock out. Never allows negative stock. */
    public function adjust(int $batchId, string $direction, $qty, string $note): ProductBatch
    {
        return DB::transaction(function () use ($batchId, $direction, $qty, $note) {
            $b = ProductBatch::whereKey($batchId)->lockForUpdate()->firstOrFail();
            $qty = Money::round(Money::num($qty), 3);
            if (! Money::gt($qty, '0')) { throw new BusinessException(t('errors.invalid_qty')); }
            if ($direction === 'out') {
                if (Money::gt($qty, $b->available_qty)) { throw new BusinessException(t('errors.negative_stock')); }
                $b->available_qty = Money::round(Money::sub($b->available_qty, $qty), 3);
                $b->save();
                $this->movement($b, StockMovementType::AdjustmentOut, '0', $qty, null, null, $note);
            } else {
                $b->available_qty = Money::round(Money::add($b->available_qty, $qty), 3);
                $b->save();
                $this->movement($b, StockMovementType::AdjustmentIn, $qty, '0', null, null, $note);
            }
            return $b;
        });
    }

    /** Deduct a sold quantity from a batch (called inside the sale transaction). */
    public function deduct(int $batchId, string $qty, Model $ref, string $refNo, $at = null): void
    {
        $b = ProductBatch::whereKey($batchId)->lockForUpdate()->firstOrFail();
        if (Money::gt($qty, $b->available_qty)) { throw new BusinessException(t('errors.negative_stock')); }
        $b->available_qty = Money::round(Money::sub($b->available_qty, $qty), 3);
        $b->save();
        $this->movement($b, StockMovementType::Sale, '0', $qty, $ref, $refNo, null, $at ?? now());
    }

    /**
     * FEFO allocation: earliest-expiry sellable batch first, never expired, never more than available.
     * $reserved tracks quantities already promised to earlier lines of the same invoice.
     * @return array<int,array{0:ProductBatch,1:string}>
     */
    public function allocateFefo(Product $p, string $qty, ?int $batchId, Carbon $date, array &$reserved): array
    {
        if ($batchId) {
            $b = ProductBatch::where('product_id', $p->id)->whereKey($batchId)->lockForUpdate()->first();
            if (! $b) { throw new BusinessException(t('errors.batch_not_found')); }
            if ($b->expiry_date->lt($date)) { throw new BusinessException(t('errors.batch_expired', ['batch' => $b->batch_no])); }
            $avail = Money::sub($b->available_qty, $reserved[$b->id] ?? '0');
            if (Money::lt($avail, $qty)) { throw new BusinessException(t('errors.insufficient_stock', ['product' => $p->localized_name, 'available' => q($avail)])); }
            $reserved[$b->id] = Money::add($reserved[$b->id] ?? '0', $qty);
            return [[$b, Money::round($qty, 3)]];
        }

        $batches = ProductBatch::where('product_id', $p->id)->where('available_qty', '>', 0)
            ->where('expiry_date', '>=', $date->toDateString())
            ->orderBy('expiry_date')->orderBy('id')->lockForUpdate()->get();

        $left = $qty; $out = []; $totalAvail = '0';
        foreach ($batches as $b) {
            $avail = Money::sub($b->available_qty, $reserved[$b->id] ?? '0');
            if (! Money::gt($avail, '0')) { continue; }
            $totalAvail = Money::add($totalAvail, $avail);
            if (! Money::gt($left, '0')) { continue; }
            $take = Money::lt($avail, $left) ? $avail : $left;
            $out[] = [$b, Money::round($take, 3)];
            $reserved[$b->id] = Money::add($reserved[$b->id] ?? '0', $take);
            $left = Money::sub($left, $take);
        }
        if (Money::gt($left, '0.0005')) {
            $expiredExists = ProductBatch::where('product_id', $p->id)->where('available_qty', '>', 0)->where('expiry_date', '<', $date->toDateString())->exists();
            throw new BusinessException(t($expiredExists && ! Money::gt($totalAvail, '0') ? 'errors.only_expired' : 'errors.insufficient_stock',
                ['product' => $p->localized_name, 'available' => q($totalAvail)]));
        }
        return $out;
    }

    public function movement(ProductBatch $b, StockMovementType $type, $in, $out, ?Model $ref, ?string $refNo, ?string $note = null, $at = null): StockMovement
    {
        return StockMovement::create([
            'product_id' => $b->product_id,
            'batch_id' => $b->id,
            'type' => $type->value,
            'qty_in' => $in,
            'qty_out' => $out,
            'balance_after' => $b->available_qty,
            'reference_type' => $ref?->getMorphClass(),
            'reference_id' => $ref?->getKey(),
            'ref_no' => $refNo,
            'note' => $note,
            'user_id' => auth()->id(),
            'moved_at' => $at ?? now(),
        ]);
    }
}
