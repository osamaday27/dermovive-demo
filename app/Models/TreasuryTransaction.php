<?php

namespace App\Models;

use App\Support\Dt;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryTransaction extends Model
{
    use Immutable;

    protected $guarded = ['id'];
    protected $casts = ['inflow' => 'decimal:2', 'outflow' => 'decimal:2'];

    public function account(): BelongsTo { return $this->belongsTo(TreasuryAccount::class, 'account_id'); }

    protected function txDate(): Attribute { return Dt::attr(); }
}
