<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Support\Money;
use App\Support\Dt;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $casts = [
        'credit_override' => 'boolean',
        'subtotal' => 'decimal:2', 'discount_pct' => 'decimal:2', 'discount_total' => 'decimal:2', 'taxable_amount' => 'decimal:2',
        'vat_total' => 'decimal:2', 'grand_total' => 'decimal:2', 'paid_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class)->withTrashed(); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function payments(): HasMany { return $this->hasMany(InvoicePayment::class); }
    public function allocations(): HasMany { return $this->hasMany(CollectionAllocation::class); }

    public function getRemainingAttribute(): string
    {
        return Money::round(Money::sub($this->grand_total, $this->paid_amount));
    }

    public static function statusFor($grand, $paid): PaymentStatus
    {
        if (Money::gte($paid, $grand)) { return PaymentStatus::Paid; }
        return Money::gt($paid, '0') ? PaymentStatus::Partial : PaymentStatus::Unpaid;
    }

    public function refreshStatus(): void
    {
        $this->status = self::statusFor($this->grand_total, $this->paid_amount)->value;
    }

    protected function invoiceDate(): Attribute { return Dt::attr(); }
}
