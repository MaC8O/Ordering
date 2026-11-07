<?php

namespace App\Services;

/**
 * Computes the delivery fee from a per-item base rate.
 *
 * Rule (minor units / satang throughout):
 *   - Any food in the order  → base × (number of food units). Drinks ride free.
 *   - Drinks-only order      → a single flat base fee, regardless of drink count.
 *   - Empty order            → 0.
 *
 * A total cap (> 0) keeps large orders from being gouged; 0 disables it.
 *
 * Keeping this in one place means the server (authoritative) and the checkout
 * UI (preview) bill by the exact same rule.
 */
class DeliveryFee
{
    public static function compute(int $baseFee, int $foodQty, int $drinkQty, int $cap = 0): int
    {
        if ($foodQty > 0) {
            $fee = $baseFee * $foodQty;
        } elseif ($drinkQty > 0) {
            $fee = $baseFee;
        } else {
            return 0;
        }

        return $cap > 0 ? min($fee, $cap) : $fee;
    }
}
