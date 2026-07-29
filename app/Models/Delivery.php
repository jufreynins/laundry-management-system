<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\DeliveryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'location_id',
        'delivery_zone_id',
        'type',
        'status',
        'scheduled_at',
        'address',
        'city',
        'state',
        'zip',
        'driver_id',
        'fee',
        'proof_notes',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'type' => DeliveryType::class,
        'status' => DeliveryStatus::class,
        'scheduled_at' => 'datetime',
        'fee' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
