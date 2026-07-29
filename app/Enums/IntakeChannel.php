<?php

namespace App\Enums;

enum IntakeChannel: string
{
    case WALK_IN = 'walk_in';
    case PICKUP = 'pickup';
    case DELIVERY = 'delivery';
    case COMMERCIAL = 'commercial';

    public function label(): string
    {
        return match($this) {
            self::WALK_IN => 'Walk-In',
            self::PICKUP => 'Pickup',
            self::DELIVERY => 'Delivery',
            self::COMMERCIAL => 'Commercial Account',
        };
    }
}
