<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemFactory> */
    use HasFactory;

    protected $fillable = [
        'location_id',
        'supplier_id',
        'name',
        'unit',
        'current_quantity',
        'reorder_threshold',
        'cost_per_unit',
        'active',
    ];

    protected $casts = [
        'current_quantity' => 'decimal:2',
        'reorder_threshold' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class)->orderByDesc('created_at');
    }

    public function isBelowReorderThreshold(): bool
    {
        return bccomp((string) $this->current_quantity, (string) $this->reorder_threshold, 2) <= 0;
    }
}
