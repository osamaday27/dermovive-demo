<?php

namespace App\Models;

use App\Enums\ExpiryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    /** SQL fragment: total stock of a product (kept in one place). */
    public const STOCK_SQL = '(select COALESCE(sum(available_qty),0) from product_batches where product_batches.product_id = products.id)';

    protected $guarded = ['id'];
    protected $casts = ['purchase_price' => 'decimal:2', 'selling_price' => 'decimal:2', 'vat_rate' => 'decimal:2', 'min_stock' => 'decimal:3'];

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function batches(): HasMany { return $this->hasMany(ProductBatch::class); }
    public function movements(): HasMany { return $this->hasMany(StockMovement::class); }

    public function scopeActive(Builder $q): Builder { return $q->where('status', 'active'); }

    public function getLocalizedNameAttribute(): string
    {
        return $this->{'name_'.app()->getLocale()} ?: $this->name_en;
    }

    /** Worst expiry status among batches that still hold stock (needs `batches` loaded or loads them). */
    public function worstExpiry(): ?ExpiryStatus
    {
        $worst = null;
        foreach ($this->batches as $b) {
            if ((float) $b->available_qty <= 0) { continue; }
            $s = $b->expiryStatus();
            if (! $worst || $s->severity() > $worst->severity()) { $worst = $s; }
        }
        return $worst;
    }
}
