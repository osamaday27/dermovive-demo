<?php

namespace App\Models;

use App\Support\Dt;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'decimal:2'];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function account(): BelongsTo { return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id'); }

    protected function paidAt(): Attribute { return Dt::attr(); }
}
