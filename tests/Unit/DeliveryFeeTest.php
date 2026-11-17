<?php

namespace Tests\Unit;

use App\Services\DeliveryFee;
use PHPUnit\Framework\TestCase;

class DeliveryFeeTest extends TestCase
{
    private const BASE = 1000; // ฿10.00 in satang

    public function test_fee_scales_per_item_unit(): void
    {
        $this->assertSame(1000, DeliveryFee::compute(self::BASE, 1, 0)); // 1 item
        $this->assertSame(2000, DeliveryFee::compute(self::BASE, 2, 0)); // 2 items
        $this->assertSame(3000, DeliveryFee::compute(self::BASE, 3, 0)); // 3 items
    }

    public function test_drinks_count_toward_the_fee_alongside_food(): void
    {
        $this->assertSame(6000, DeliveryFee::compute(self::BASE, 1, 5)); // 1 food + 5 drinks = 6
        $this->assertSame(5000, DeliveryFee::compute(self::BASE, 2, 3)); // 2 food + 3 drinks = 5
    }

    public function test_drinks_only_order_is_billed_per_drink(): void
    {
        $this->assertSame(1000, DeliveryFee::compute(self::BASE, 0, 1)); // 1 drink
        $this->assertSame(3000, DeliveryFee::compute(self::BASE, 0, 3)); // 3 drinks
    }

    public function test_empty_order_is_free(): void
    {
        $this->assertSame(0, DeliveryFee::compute(self::BASE, 0, 0));
    }

    public function test_cap_limits_large_orders(): void
    {
        $cap = 5000; // ฿50

        $this->assertSame(4000, DeliveryFee::compute(self::BASE, 2, 2, $cap)); // 4 items, under cap
        $this->assertSame(5000, DeliveryFee::compute(self::BASE, 4, 1, $cap)); // 5 items, at cap
        $this->assertSame(5000, DeliveryFee::compute(self::BASE, 6, 3, $cap)); // 9 items, over cap → clamped
    }

    public function test_cap_of_zero_is_disabled(): void
    {
        $this->assertSame(9000, DeliveryFee::compute(self::BASE, 6, 3, 0)); // 9 items, no clamp
    }
}
