<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZone extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryZoneFactory> */
    use HasFactory;

    protected $fillable = [
        'location_id',
        'name',
        'description',
        'fee',
        'active',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
