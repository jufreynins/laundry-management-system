<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case SCHEDULED = 'scheduled';
    case EN_ROUTE = 'en_route';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::SCHEDULED => 'Scheduled',
            self::EN_ROUTE => 'En Route',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED]);
    }
}
