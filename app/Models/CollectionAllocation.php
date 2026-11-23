<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionAllocation extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'decimal:2'];

    public function receipt(): BelongsTo { return $this->belongsTo(CollectionReceipt::class, 'receipt_id'); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
}
