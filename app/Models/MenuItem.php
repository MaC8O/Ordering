<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'name_th',
        'description',
        'description_th',
        'price',
        'image_path',
        'is_available',
        'display_order',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_available' => 'boolean',
        'display_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Price formatted for display, e.g. 1250 -> "12.50".
     */
    public function getPriceFormattedAttribute(): string
    {
        return number_format($this->price / 100, 2);
    }

    /**
     * Item name / description in the current locale, falling back to English.
     */
    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'th' && $this->name_th
            ? $this->name_th
            : $this->name;
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'th' && $this->description_th
            ? $this->description_th
            : $this->description;
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
}
