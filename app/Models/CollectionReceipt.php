<?php

namespace App\Models;

use App\Support\Dt;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionReceipt extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'decimal:2'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class)->withTrashed(); }
    public function account(): BelongsTo { return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id'); }
    public function allocations(): HasMany { return $this->hasMany(CollectionAllocation::class, 'receipt_id'); }

    protected function receiptDate(): Attribute { return Dt::attr(); }
}
