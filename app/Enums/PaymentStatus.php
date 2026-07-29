<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case COMPLETED = 'completed';
    case VOIDED = 'voided';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    public function label(): string
    {
        return match($this) {
            self::COMPLETED => 'Completed',
            self::VOIDED => 'Voided',
            self::REFUNDED => 'Refunded',
            self::PARTIALLY_REFUNDED => 'Partially Refunded',
        };
    }
}
