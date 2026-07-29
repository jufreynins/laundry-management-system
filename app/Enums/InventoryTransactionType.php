<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case RECEIVED = 'received';
    case USED = 'used';
    case WASTE = 'waste';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match($this) {
            self::RECEIVED => 'Stock Received',
            self::USED => 'Used in Production',
            self::WASTE => 'Waste / Damage',
            self::ADJUSTMENT => 'Manual Adjustment',
        };
    }

    public function increasesStock(): bool
    {
        return $this === self::RECEIVED;
    }
}
