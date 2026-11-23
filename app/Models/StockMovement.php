<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use Immutable;

    protected $guarded = ['id'];
    protected $casts = ['moved_at' => 'datetime', 'qty_in' => 'decimal:3', 'qty_out' => 'decimal:3', 'balance_after' => 'decimal:3'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function batch(): BelongsTo { return $this->belongsTo(ProductBatch::class, 'batch_id'); }
}
