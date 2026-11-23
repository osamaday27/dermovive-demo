<?php

namespace App\Models;

use App\Support\Dt;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerTransaction extends Model
{
    use Immutable;

    protected $guarded = ['id'];
    protected $casts = ['debit' => 'decimal:2', 'credit' => 'decimal:2'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    protected function trxDate(): Attribute { return Dt::attr(); }
}
