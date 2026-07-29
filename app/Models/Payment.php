<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'location_id',
        'method',
        'status',
        'amount',
        'reference_note',
        'recorded_by',
        'voided_at',
        'voided_by',
        'void_reason',
        'idempotency_key',
        'provider',
        'provider_transaction_id',
        'provider_customer_id',
        'payment_method_brand',
        'last_four',
        'receipt_url',
    ];

    protected $casts = [
        'method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'amount' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Payment $payment) {
            $year = $payment->created_at?->year ?? now()->year;
            $sequence = \App\Services\SequenceGenerator::next("payment-{$year}");

            $payment->forceFill([
                'payment_reference' => sprintf('PAY-%d-%06d', $year, $sequence),
            ])->saveQuietly();
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function refundedAmount(): string
    {
        return (string) $this->refunds()->sum('amount');
    }

    public function refundableAmount(): string
    {
        if ($this->status === PaymentStatus::VOIDED) {
            return '0.00';
        }

        return bcsub((string) $this->amount, $this->refundedAmount(), 2);
    }
}
