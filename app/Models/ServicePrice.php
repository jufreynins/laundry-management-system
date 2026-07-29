<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePrice extends Model
{
    protected $fillable = [
        'service_id',
        'location_id',
        'price',
        'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
