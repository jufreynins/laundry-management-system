<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    /** @use HasFactory<\Database\Factories\RefundFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'order_id',
        'amount',
        'reason',
        'processed_by',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Refund $refund) {
            $year = $refund->created_at?->year ?? now()->year;
            $sequence = \App\Services\SequenceGenerator::next("refund-{$year}");

            $refund->forceFill([
                'refund_reference' => sprintf('REF-%d-%06d', $year, $sequence),
            ])->saveQuietly();
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
