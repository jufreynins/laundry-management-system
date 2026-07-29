<?php

namespace App\Enums;

enum PricingType: string
{
    case PER_POUND = 'per_pound';
    case PER_ITEM = 'per_item';
    case FLAT_FEE = 'flat_fee';
    case HOURLY = 'hourly';
    case CUSTOM_QUOTE = 'custom_quote';

    public function label(): string
    {
        return match($this) {
            self::PER_POUND => 'Per Pound',
            self::PER_ITEM => 'Per Item',
            self::FLAT_FEE => 'Flat Fee',
            self::HOURLY => 'Hourly',
            self::CUSTOM_QUOTE => 'Custom Quote',
        };
    }
}
