<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable implements AuthenticatableContract
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * All orders stamped with this customer's id (set at checkout while logged in).
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Every order that belongs to this customer, whether it was placed while
     * logged in (customer_id) or as a guest using this same phone number
     * before/after registering. Newest first.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Order>
     */
    public function orderHistoryQuery()
    {
        return Order::query()
            ->where(function ($q) {
                $q->where('customer_id', $this->id)
                    ->orWhere('customer_phone', $this->phone);
            })
            ->latest();
    }

    /**
     * How many of this customer's orders have actually been delivered.
     * Only these count toward the free-delivery reward.
     */
    public function deliveredOrdersCount(): int
    {
        return $this->orderHistoryQuery()
            ->where('order_status', 'completed')
            ->count();
    }

    /**
     * How many delivered orders earn ONE free delivery. The reward is a
     * recurring punch-card: every N delivered orders unlocks a single free
     * delivery, which then resets — it is not free-forever.
     */
    public static function freeDeliveryThreshold(): int
    {
        return (int) config('ordering.loyalty.free_delivery_after', 7);
    }

    /**
     * Total free-delivery rewards this customer has earned over their lifetime
     * (one per N delivered orders).
     */
    public function freeDeliveriesEarned(): int
    {
        return intdiv($this->deliveredOrdersCount(), self::freeDeliveryThreshold());
    }

    /**
     * Rewards already redeemed — orders flagged as a free-delivery reward.
     * Cancelled orders don't consume the reward.
     */
    public function freeDeliveriesUsed(): int
    {
        return $this->orderHistoryQuery()
            ->where('is_free_delivery_reward', true)
            ->where('order_status', '!=', 'cancelled')
            ->count();
    }

    /**
     * Unredeemed rewards available to spend right now.
     */
    public function freeDeliveriesAvailable(): int
    {
        return max(0, $this->freeDeliveriesEarned() - $this->freeDeliveriesUsed());
    }

    /**
     * Whether a free delivery is available to redeem on the next order.
     */
    public function hasFreeDelivery(): bool
    {
        return $this->freeDeliveriesAvailable() > 0;
    }

    /**
     * Delivered orders still needed before the next free delivery unlocks
     * (0 when one is already available to redeem).
     */
    public function ordersUntilNextReward(): int
    {
        if ($this->hasFreeDelivery()) {
            return 0;
        }

        $threshold = self::freeDeliveryThreshold();

        return $threshold - ($this->deliveredOrdersCount() % $threshold);
    }
}
