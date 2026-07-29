<?php

namespace App\Models;

use App\Enums\IntakeChannel;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'location_id',
        'intake_channel',
        'status',
        'intake_at',
        'promised_at',
        'rush',
        'assigned_user_id',
        'created_by',
        'weight_lbs',
        'item_count',
        'customer_declared_item_count',
        'bag_count',
        'stain_notes',
        'garment_flags',
        'customer_acknowledged',
        'customer_instructions',
        'internal_notes',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'tip_amount',
        'total',
        'amount_paid',
        'balance_due',
    ];

    protected $casts = [
        'intake_channel' => IntakeChannel::class,
        'status' => OrderStatus::class,
        'intake_at' => 'datetime',
        'promised_at' => 'datetime',
        'rush' => 'boolean',
        'garment_flags' => 'array',
        'customer_acknowledged' => 'boolean',
        'weight_lbs' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tip_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (Order $order) {
            $year = $order->intake_at?->year ?? now()->year;
            $sequence = \App\Services\SequenceGenerator::next("order-{$year}");

            $order->forceFill([
                'order_number' => sprintf('LND-%d-%06d', $year, $sequence),
            ])->saveQuietly();
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(OrderPhoto::class);
    }

    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }
}
