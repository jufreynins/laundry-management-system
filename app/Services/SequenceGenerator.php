<?php

namespace App\Services;

use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

class SequenceGenerator
{
    /**
     * Atomically reserve and return the next number for the given sequence key.
     * Safe under concurrent requests via row-level locking within a transaction.
     */
    public static function next(string $key): int
    {
        return DB::transaction(function () use ($key) {
            $sequence = NumberSequence::where('key', $key)->lockForUpdate()->first();

            if (!$sequence) {
                $sequence = NumberSequence::create(['key' => $key, 'next_value' => 1]);
                $sequence = NumberSequence::where('key', $key)->lockForUpdate()->first();
            }

            $value = $sequence->next_value;
            $sequence->increment('next_value');

            return $value;
        });
    }

    public static function formatted(string $prefix, string $key, int $padding = 6): string
    {
        $value = self::next($key);

        return sprintf('%s-%0'.$padding.'d', $prefix, $value);
    }

    public static function formattedWithYear(string $prefix, string $keyBase, int $padding = 6): string
    {
        $year = now()->year;
        $value = self::next("{$keyBase}-{$year}");

        return sprintf('%s-%d-%0'.$padding.'d', $prefix, $year, $value);
    }
}
