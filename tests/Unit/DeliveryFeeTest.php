<?php

namespace Tests\Unit;

use App\Services\DeliveryFee;
use PHPUnit\Framework\TestCase;

class DeliveryFeeTest extends TestCase
{
    private const BASE = 1000; // ฿10.00 in satang

    public function test_fee_scales_per_food_unit(): void
    {
        $this->assertSame(1000, DeliveryFee::compute(self::BASE, 1, 0)); // 1 food
        $this->assertSame(2000, DeliveryFee::compute(self::BASE, 2, 0)); // 2 food
        $this->assertSame(3000, DeliveryFee::compute(self::BASE, 3, 0)); // 3 food
    }

    public function test_drinks_ride_free_alongside_food(): void
    {
        $this->assertSame(1000, DeliveryFee::compute(self::BASE, 1, 5)); // 1 food + 5 drinks
        $this->assertSame(2000, DeliveryFee::compute(self::BASE, 2, 3)); // 2 food + 3 drinks
    }

    public function test_drinks_only_order_pays_a_flat_base_fee(): void
    {
        $this->assertSame(1000, DeliveryFee::compute(self::BASE, 0, 1)); // 1 drink
        $this->assertSame(1000, DeliveryFee::compute(self::BASE, 0, 3)); // 3 drinks, still flat
    }

    public function test_empty_order_is_free(): void
    {
        $this->assertSame(0, DeliveryFee::compute(self::BASE, 0, 0));
    }

    public function test_cap_limits_large_orders(): void
    {
        $cap = 5000; // ฿50

        $this->assertSame(4000, DeliveryFee::compute(self::BASE, 4, 0, $cap)); // under cap
        $this->assertSame(5000, DeliveryFee::compute(self::BASE, 5, 0, $cap)); // at cap
        $this->assertSame(5000, DeliveryFee::compute(self::BASE, 9, 0, $cap)); // over cap → clamped
    }

    public function test_cap_of_zero_is_disabled(): void
    {
        $this->assertSame(9000, DeliveryFee::compute(self::BASE, 9, 0, 0)); // no clamp
    }
}
