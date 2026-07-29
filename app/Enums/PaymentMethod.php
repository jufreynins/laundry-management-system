<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case EXTERNAL = 'external';
    case ONLINE_CARD = 'online_card';

    public function label(): string
    {
        return match($this) {
            self::CASH => 'Cash',
            self::EXTERNAL => 'External / Manual (check, card terminal, etc.)',
            self::ONLINE_CARD => 'Online Card Payment',
        };
    }
}
