<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'location_id',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'operational_consent',
        'marketing_consent',
        'notes',
        'active',
    ];

    protected $casts = [
        'operational_consent' => 'boolean',
        'marketing_consent' => 'boolean',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (Customer $customer) {
            $customer->forceFill([
                'customer_number' => sprintf('CUS-%06d', $customer->id),
            ])->saveQuietly();
        });
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('customer_number', 'like', "%{$term}%");
        });
    }

    public static function findPossibleDuplicates(int $locationId, string $name, string $phone): \Illuminate\Support\Collection
    {
        return static::where('location_id', $locationId)
            ->where(function ($q) use ($name, $phone) {
                $q->where('phone', $phone)
                    ->orWhere('name', $name);
            })
            ->get();
    }
}
