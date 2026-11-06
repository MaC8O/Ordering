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
        'preorder_enabled',
        'preorder_open_day',
        'preorder_open_time',
        'preorder_cutoff_day',
        'preorder_cutoff_time',
        'preorder_fulfillment_day',
        'preorder_window_start',
        'preorder_window_end',
        'preorder_delivery_fee',
    ];

    protected $casts = [
        'cutoff_minutes_before_close' => 'integer',
        'default_delivery_fee' => 'integer',
        'accepting_orders' => 'boolean',
        'dorms' => 'array',
        'preorder_enabled' => 'boolean',
        'preorder_open_day' => 'integer',
        'preorder_cutoff_day' => 'integer',
        'preorder_fulfillment_day' => 'integer',
        'preorder_delivery_fee' => 'integer',
    ];

    public function getPreorderDeliveryFeeFormattedAttribute(): string
    {
        return number_format($this->preorder_delivery_fee / 100, 2);
    }

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
