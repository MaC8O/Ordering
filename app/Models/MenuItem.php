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
        'large_price',
        'has_egg_addon',
        'has_spice_note',
        'proteins',
        'protein_prices',
        'image_path',
        'is_available',
        'is_drink',
        'display_order',
    ];

    protected $casts = [
        'price' => 'integer',
        'large_price' => 'integer',
        'has_egg_addon' => 'boolean',
        'has_spice_note' => 'boolean',
        'proteins' => 'array',
        'protein_prices' => 'array',
        'is_available' => 'boolean',
        'is_drink' => 'boolean',
        'display_order' => 'integer',
    ];

    public function hasSizes(): bool
    {
        return $this->large_price !== null && $this->large_price > $this->price;
    }

    public function hasProteins(): bool
    {
        return is_array($this->proteins) && count($this->proteins) > 0;
    }

    /**
     * Whether this dish carries its own price for each protein choice (set via
     * the admin menu editor). When true, the selected protein — not the
     * dish-level `price` — decides what the customer pays.
     */
    public function hasProteinPrices(): bool
    {
        return is_array($this->protein_prices) && count($this->protein_prices) > 0;
    }

    /**
     * The unit price (in the smallest currency unit) for a given protein and
     * size, before any egg add-on. Falls back to the dish-level price when the
     * dish has no per-protein pricing or the protein is unknown. A protein with
     * no large price uses its normal price for the large size.
     */
    public function priceForProtein(?string $protein, string $size = 'normal'): int
    {
        $prices = $this->protein_prices[$protein] ?? null;

        if (is_array($prices) && isset($prices['normal'])) {
            $normal = (int) $prices['normal'];
            $large = isset($prices['large']) && $prices['large'] !== null
                ? (int) $prices['large']
                : $normal;

            return $size === 'large' ? $large : $normal;
        }

        return $size === 'large' && $this->large_price !== null
            ? (int) $this->large_price
            : (int) $this->price;
    }

    /**
     * Public URL for the dish photo, cache-busted with the file's modified
     * time so a replaced image (same filename) is never served stale from a
     * browser or CDN cache. Null when no image file is present.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        $path = public_path($this->image_path);

        if (! is_file($path)) {
            return null;
        }

        return asset($this->image_path).'?v='.filemtime($path);
    }

    /**
     * Whether the dish needs the customer to open its detail page to pick
     * options (protein / size / egg) rather than being a one-tap add.
     */
    public function hasOptions(): bool
    {
        return $this->hasProteins() || $this->hasSizes() || $this->has_egg_addon;
    }

    public function getLargePriceFormattedAttribute(): ?string
    {
        return $this->large_price !== null ? number_format($this->large_price / 100, 2) : null;
    }

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
