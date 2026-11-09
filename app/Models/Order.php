<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Give every order an unguessable public token for its URL.
        static::creating(function (Order $order) {
            if (empty($order->public_token)) {
                $order->public_token = Str::random(32);
            }
        });
    }

    public const ORDER_STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'out_for_delivery' => 'Out for delivery',
        'completed' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'Pending',
        'verified' => 'Verified',
        'failed' => 'Failed',
    ];

    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_name',
        'customer_phone',
        'delivery_dorm',
        'delivery_room',
        'delivery_date',
        'delivery_notes',
        'preferred_time',
        'payment_method',
        'payment_screenshot_path',
        'payment_status',
        'order_status',
        'is_preorder',
        'is_free_delivery_reward',
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
        'delivery_date' => 'date',
        'is_preorder' => 'boolean',
        'is_free_delivery_reward' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Whether the customer may (re)upload a payment receipt: QR orders that are
     * still awaiting a valid payment and haven't been completed or cancelled.
     */
    public function canUploadReceipt(): bool
    {
        return $this->payment_method === 'qr'
            && in_array($this->payment_status, ['pending', 'failed'], true)
            && ! $this->isDone();
    }

    /**
     * Save a payment receipt image, named by the unguessable public token so it
     * can't be found by guessing, replacing any earlier receipt. Centralised so
     * checkout and the customer re-upload path behave identically.
     */
    public function storeReceipt(UploadedFile $file): void
    {
        // Derive the extension from the DETECTED content type, never from the
        // client-supplied filename: a valid image named "x.phtml" would other-
        // wise be stored as an executable in a web-served directory (RCE). Any
        // type that slipped past validation falls back to a harmless ".jpg".
        $ext = match ($file->getMimeType()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg', // image/jpeg and anything unexpected
        };
        $filename = $this->public_token . '.' . $ext;

        $dir = public_path('uploads/payments');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Drop a previous receipt that had a different extension, so a
        // re-upload never leaves a stale orphan image behind.
        if ($this->payment_screenshot_path && $this->payment_screenshot_path !== 'uploads/payments/' . $filename) {
            $old = public_path($this->payment_screenshot_path);
            if (is_file($old)) {
                @unlink($old);
            }
        }

        $file->move($dir, $filename);

        $this->update(['payment_screenshot_path' => 'uploads/payments/' . $filename]);
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
     * The scheduled delivery time the admin set (stored in eta_at), formatted
     * for display: just the time when it's today, otherwise date + time. Used
     * on the orders list and the customer's tracking page.
     */
    public function scheduledDeliveryLabel(): ?string
    {
        if (! $this->eta_at) {
            return null;
        }

        return $this->eta_at->isToday()
            ? $this->eta_at->format('H:i')
            : $this->eta_at->format('D, d M · H:i');
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
