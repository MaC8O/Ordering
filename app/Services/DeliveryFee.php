<?php

namespace App\Services;

/**
 * Computes the delivery fee from a per-item base rate.
 *
 * Rule (minor units / satang throughout):
 *   - base × (total item units) — every item counts, food and drinks alike.
 *   - Empty order → 0.
 *
 * A total cap (> 0) keeps large orders from being gouged; 0 disables it.
 *
 * Keeping this in one place means the server (authoritative) and the checkout
 * UI (preview) bill by the exact same rule. Food and drink counts are passed
 * separately so callers need not pre-sum them (and so the signature is stable).
 */
class DeliveryFee
{
    public static function compute(int $baseFee, int $foodQty, int $drinkQty, int $cap = 0): int
    {
        $qty = $foodQty + $drinkQty;
        if ($qty <= 0) {
            return 0;
        }

        $fee = $baseFee * $qty;

        return $cap > 0 ? min($fee, $cap) : $fee;
    }
}
