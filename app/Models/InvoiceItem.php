<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'discount_pct' => 'decimal:2', 'vat_rate' => 'decimal:2',
        'unit_cost' => 'decimal:2', 'discount_amount' => 'decimal:2', 'vat_amount' => 'decimal:2', 'line_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function batch(): BelongsTo { return $this->belongsTo(ProductBatch::class, 'batch_id'); }
}
