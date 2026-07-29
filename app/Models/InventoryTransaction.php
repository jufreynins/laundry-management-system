<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryTransactionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'inventory_item_id',
        'type',
        'quantity',
        'quantity_after',
        'reason',
        'recorded_by',
        'created_at',
    ];

    protected $casts = [
        'type' => InventoryTransactionType::class,
        'quantity' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
