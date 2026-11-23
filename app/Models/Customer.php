<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $casts = ['credit_limit' => 'decimal:2', 'opening_balance' => 'decimal:2'];

    public function transactions(): HasMany { return $this->hasMany(CustomerTransaction::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function receipts(): HasMany { return $this->hasMany(CollectionReceipt::class); }

    /** Adds a `balance` column derived from the ledger (never stored). */
    public function scopeWithBalance(Builder $q): Builder
    {
        return $q->addSelect(['balance' => CustomerTransaction::query()
            ->selectRaw('COALESCE(SUM(debit - credit), 0)')
            ->whereColumn('customer_id', 'customers.id')]);
    }

    public function scopeActive(Builder $q): Builder { return $q->where('status', 'active'); }
}
