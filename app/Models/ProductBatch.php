<?php

namespace App\Models;

use App\Enums\ExpiryStatus;
use App\Support\Dt;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        
        'unit_cost' => 'decimal:2', 'initial_qty' => 'decimal:3', 'available_qty' => 'decimal:3',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }

    public function expiryStatus(): ExpiryStatus { return ExpiryStatus::for($this->expiry_date); }
    public function isExpired(): bool { return $this->expiryStatus() === ExpiryStatus::Expired; }

    protected function mfgDate(): Attribute { return Dt::attr(); }

    protected function expiryDate(): Attribute { return Dt::attr(); }
}
