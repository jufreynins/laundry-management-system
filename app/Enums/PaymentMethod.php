<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case EXTERNAL = 'external';

    public function label(): string
    {
        return match($this) {
            self::CASH => 'Cash',
            self::EXTERNAL => 'External / Manual (check, card terminal, etc.)',
        };
    }
}
