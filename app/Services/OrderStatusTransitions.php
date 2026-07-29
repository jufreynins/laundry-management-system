<?php

namespace App\Services;

use App\Enums\OrderStatus;

class OrderStatusTransitions
{
    /**
     * Centralized map of permitted status transitions.
     * Owner-authorized overrides bypass this map but must be logged separately.
     */
    private const MAP = [
        'draft' => ['checked_in', 'cancelled'],
        'checked_in' => ['tagged', 'on_hold', 'cancelled'],
        'tagged' => ['sorting', 'on_hold', 'cancelled'],
        'sorting' => ['washing', 'on_hold', 'cancelled'],
        'washing' => ['drying', 'on_hold', 'cancelled'],
        'drying' => ['finishing', 'on_hold', 'cancelled'],
        'finishing' => ['quality_check', 'on_hold', 'cancelled'],
        'quality_check' => ['ready_for_pickup', 'finishing', 'on_hold', 'cancelled'],
        'ready_for_pickup' => ['out_for_delivery', 'completed', 'on_hold', 'cancelled'],
        'out_for_delivery' => ['completed', 'on_hold', 'cancelled'],
        'on_hold' => ['checked_in', 'tagged', 'sorting', 'washing', 'drying', 'finishing', 'quality_check', 'ready_for_pickup', 'out_for_delivery', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    public static function isAllowed(OrderStatus $from, OrderStatus $to): bool
    {
        return in_array($to->value, self::MAP[$from->value] ?? [], true);
    }

    public static function allowedNext(OrderStatus $from): array
    {
        return self::MAP[$from->value] ?? [];
    }
}
