<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyRewardTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): Customer
    {
        return Customer::create(['name' => 'Ann', 'phone' => '0111', 'password' => 'secret123']);
    }

    private function deliveredOrders(Customer $c, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            Order::create([
                'order_number' => 'D-'.$c->id.'-'.$i.'-'.uniqid(),
                'customer_id' => $c->id,
                'customer_name' => $c->name,
                'customer_phone' => $c->phone,
                'delivery_dorm' => 'A',
                'delivery_room' => '1',
                'payment_method' => 'cash',
                'payment_status' => 'verified',
                'order_status' => 'completed',
                'subtotal' => 5000,
                'delivery_fee' => 1000,
                'total' => 6000,
            ]);
        }
    }

    public function test_reward_unlocks_every_threshold_and_is_not_forever(): void
    {
        config()->set('ordering.loyalty.free_delivery_after', 7);
        $c = $this->customer();

        $this->deliveredOrders($c, 6);
        $this->assertFalse($c->hasFreeDelivery());
        $this->assertSame(1, $c->ordersUntilNextReward());

        // 7th delivered order unlocks one reward.
        $this->deliveredOrders($c, 1);
        $this->assertTrue($c->hasFreeDelivery());
        $this->assertSame(1, $c->freeDeliveriesAvailable());
    }

    public function test_redeeming_consumes_the_reward_and_resets(): void
    {
        config()->set('ordering.loyalty.free_delivery_after', 7);
        $c = $this->customer();
        $this->deliveredOrders($c, 7);

        $this->assertTrue($c->hasFreeDelivery());

        // Redeem it by flagging an order as the reward.
        Order::create([
            'order_number' => 'R-1',
            'customer_id' => $c->id,
            'customer_name' => $c->name,
            'customer_phone' => $c->phone,
            'delivery_dorm' => 'A',
            'delivery_room' => '1',
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'subtotal' => 5000,
            'delivery_fee' => 0,
            'total' => 5000,
            'is_free_delivery_reward' => true,
        ]);

        // Reward consumed; back to counting toward the next one.
        $this->assertFalse($c->hasFreeDelivery());
        $this->assertSame(0, $c->freeDeliveriesAvailable());
        $this->assertSame(7, $c->ordersUntilNextReward());
    }
}
