<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'menu_item_id',
        'item_name_snapshot',
        'options_snapshot',
        'item_price_snapshot',
        'item_note',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'item_price_snapshot' => 'integer',
        'quantity' => 'integer',
        'line_total' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function getLineTotalFormattedAttribute(): string
    {
        return number_format($this->line_total / 100, 2);
    }

    public function getItemPriceFormattedAttribute(): string
    {
        return number_format($this->item_price_snapshot / 100, 2);
    }
}
