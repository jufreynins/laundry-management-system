<?php

namespace App\Enums;

enum UserRole: string
{
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case CASHIER = 'cashier';
    case LAUNDRY_STAFF = 'staff';
    case DRIVER = 'driver';
    case ACCOUNTANT = 'accountant';

    public function label(): string
    {
        return match($this) {
            self::OWNER => 'Owner',
            self::MANAGER => 'Manager',
            self::CASHIER => 'Cashier',
            self::LAUNDRY_STAFF => 'Laundry Staff',
            self::DRIVER => 'Driver',
            self::ACCOUNTANT => 'Accountant',
        };
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::OWNER, self::MANAGER]);
    }
}
