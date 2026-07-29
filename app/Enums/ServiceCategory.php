<?php

namespace App\Enums;

enum ServiceCategory: string
{
    case WASH_FOLD = 'wash_fold';
    case WASH_PRESS = 'wash_press';
    case DRY_CLEANING = 'dry_cleaning';
    case ALTERATIONS = 'alterations';
    case SHOE_CLEANING = 'shoe_cleaning';
    case DELIVERY = 'delivery';
    case RUSH = 'rush';
    case ADD_ON = 'add_on';

    public function label(): string
    {
        return match($this) {
            self::WASH_FOLD => 'Wash and Fold',
            self::WASH_PRESS => 'Wash and Press',
            self::DRY_CLEANING => 'Dry Cleaning',
            self::ALTERATIONS => 'Alterations',
            self::SHOE_CLEANING => 'Shoe Cleaning',
            self::DELIVERY => 'Pickup and Delivery',
            self::RUSH => 'Rush Service',
            self::ADD_ON => 'Add-On',
        };
    }
}
