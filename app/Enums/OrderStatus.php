<?php

namespace App\Enums;

enum OrderStatus: string
{
    case DRAFT = 'draft';
    case CHECKED_IN = 'checked_in';
    case TAGGED = 'tagged';
    case SORTING = 'sorting';
    case WASHING = 'washing';
    case DRYING = 'drying';
    case FINISHING = 'finishing';
    case QUALITY_CHECK = 'quality_check';
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case ON_HOLD = 'on_hold';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::CHECKED_IN => 'Checked In',
            self::TAGGED => 'Tagged',
            self::SORTING => 'Sorting',
            self::WASHING => 'Washing',
            self::DRYING => 'Drying',
            self::FINISHING => 'Finishing',
            self::QUALITY_CHECK => 'Quality Check',
            self::READY_FOR_PICKUP => 'Ready for Pickup',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::ON_HOLD => 'On Hold',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    /**
     * Simplified status shown to customers on the public tracking page.
     * Internal production steps (sorting, washing, drying, etc.) are
     * intentionally collapsed into "In Progress" — customers don't need
     * (and staff shouldn't expose) internal workflow granularity.
     */
    public function customerLabel(): string
    {
        return match($this) {
            self::DRAFT, self::CHECKED_IN, self::TAGGED, self::SORTING,
            self::WASHING, self::DRYING, self::FINISHING, self::QUALITY_CHECK => 'In Progress',
            self::READY_FOR_PICKUP => 'Ready for Pickup',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::ON_HOLD => 'On Hold',
        };
    }
}
