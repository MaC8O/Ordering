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
     * The number of delivered orders needed to unlock free delivery.
     */
    public static function freeDeliveryThreshold(): int
    {
        return (int) config('ordering.loyalty.free_delivery_after', 6);
    }

    /**
     * Once a customer has reached the threshold of delivered orders, every
     * future order ships free — permanently.
     */
    public function hasFreeDelivery(): bool
    {
        return $this->deliveredOrdersCount() >= self::freeDeliveryThreshold();
    }
}
