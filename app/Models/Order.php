<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const ORDER_STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'out_for_delivery' => 'Out for delivery',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'Pending',
        'verified' => 'Verified',
        'failed' => 'Failed',
    ];

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'delivery_dorm',
        'delivery_room',
        'delivery_notes',
        'preferred_time',
        'payment_method',
        'payment_screenshot_path',
        'payment_status',
        'order_status',
        'eta_at',
        'subtotal',
        'delivery_fee',
        'total',
        'notes',
        'admin_note',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'delivery_fee' => 'integer',
        'total' => 'integer',
        'eta_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getSubtotalFormattedAttribute(): string
    {
        return number_format($this->subtotal / 100, 2);
    }

    public function getDeliveryFeeFormattedAttribute(): string
    {
        return number_format($this->delivery_fee / 100, 2);
    }

    public function getTotalFormattedAttribute(): string
    {
        return number_format($this->total / 100, 2);
    }

    public function getOrderStatusLabelAttribute(): string
    {
        return self::ORDER_STATUSES[$this->order_status] ?? ucfirst($this->order_status);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? ucfirst($this->payment_status);
    }

    public function isOutForDelivery(): bool
    {
        return $this->order_status === 'out_for_delivery';
    }

    public function isDone(): bool
    {
        return in_array($this->order_status, ['completed', 'cancelled'], true);
    }

    /**
     * Whole minutes until the estimated arrival (0 when due/overdue), or null.
     */
    public function etaMinutesLeft(): ?int
    {
        if (! $this->eta_at) {
            return null;
        }

        return max(0, (int) ceil(now()->diffInSeconds($this->eta_at, false) / 60));
    }

    /**
     * Friendly arrival line, e.g. "around 18:45 · in about 12 min".
     */
    public function etaLabel(): ?string
    {
        if (! $this->eta_at) {
            return null;
        }

        $time = $this->eta_at->format('H:i');
        $mins = $this->etaMinutesLeft();

        if ($mins === null) {
            return "around {$time}";
        }
        if ($mins <= 0) {
            return "around {$time} · arriving any moment";
        }

        return "around {$time} · in about {$mins} min";
    }
}
