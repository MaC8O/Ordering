<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'restaurant_name',
        'phone',
        'address',
        'account_name',
        'qr_image_path',
        'cutoff_minutes_before_close',
        'default_delivery_fee',
        'accepting_orders',
        'dorms',
    ];

    protected $casts = [
        'cutoff_minutes_before_close' => 'integer',
        'default_delivery_fee' => 'integer',
        'accepting_orders' => 'boolean',
        'dorms' => 'array',
    ];

    /**
     * There is only ever one settings row (singleton).
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    public function getDeliveryFeeFormattedAttribute(): string
    {
        return number_format($this->default_delivery_fee / 100, 2);
    }
}
