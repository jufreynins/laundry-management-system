<?php

namespace App\Models;

use App\Enums\PricingType;
use App\Enums\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'pricing_type',
        'base_price',
        'minimum_charge',
        'taxable',
        'rush_eligible',
        'estimated_duration_minutes',
        'active',
    ];

    protected $casts = [
        'category' => ServiceCategory::class,
        'pricing_type' => PricingType::class,
        'base_price' => 'decimal:2',
        'minimum_charge' => 'decimal:2',
        'taxable' => 'boolean',
        'rush_eligible' => 'boolean',
        'active' => 'boolean',
    ];

    public function servicePrices(): HasMany
    {
        return $this->hasMany(ServicePrice::class);
    }

    public function priceForLocation(int $locationId): string
    {
        $override = $this->servicePrices()
            ->where('location_id', $locationId)
            ->where('active', true)
            ->value('price');

        return $override !== null ? (string) $override : (string) $this->base_price;
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
