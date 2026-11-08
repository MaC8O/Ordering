<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MenuItemController extends Controller
{
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'name_th' => ['nullable', 'string', 'max:120'],
            'category_id' => ['required', 'exists:categories,id'],
            // Dish-level prices are used only when the dish has no protein
            // options; otherwise per-protein prices (proteins_json) govern.
            'price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'large_price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'proteins_json' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
            'description_th' => ['nullable', 'string', 'max:500'],
            'is_available' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . config('ordering.menu_image_max_kb')],
        ];
    }

    /**
     * Resolve the dish's price fields from the request. When the dish has
     * protein rows, each protein carries its own Normal / Large price and the
     * dish-level `price` / `large_price` become a derived summary (cheapest
     * Normal, dearest Large) used for the menu card and the detail-page range.
     *
     * @return array{price:int, large_price:?int, proteins:?array, protein_prices:?array}
     */
    protected function priceData(Request $request): array
    {
        $rows = json_decode((string) $request->input('proteins_json', ''), true);
        $rows = is_array($rows) ? $rows : [];

        $proteins = [];
        $proteinPrices = [];
        $normals = [];
        $larges = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '' || isset($proteinPrices[$name])) {
                continue; // skip blank or duplicate protein names
            }

            if (! is_numeric($row['normal'] ?? null)) {
                throw ValidationException::withMessages([
                    'proteins_json' => "Enter a Normal price for \"{$name}\".",
                ]);
            }

            $normal = (int) round(((float) $row['normal']) * 100);
            $large = (isset($row['large']) && $row['large'] !== '' && is_numeric($row['large']))
                ? (int) round(((float) $row['large']) * 100)
                : null;

            $name = mb_substr($name, 0, 40);
            $proteins[] = $name;
            $proteinPrices[$name] = ['normal' => $normal, 'large' => $large];
            $normals[] = $normal;
            if ($large !== null) {
                $larges[] = $large;
            }
        }

        // Dish has protein options → prices come from the protein rows.
        if (! empty($proteins)) {
            return [
                'price' => min($normals),
                'large_price' => ! empty($larges) ? max($larges) : null,
                'proteins' => $proteins,
                'protein_prices' => $proteinPrices,
            ];
        }

        // No proteins → plain dish-level Normal / Large price.
        if (! is_numeric($request->input('price'))) {
            throw ValidationException::withMessages([
                'price' => 'A price is required.',
            ]);
        }

        $price = (int) round(((float) $request->input('price')) * 100);
        $large = is_numeric($request->input('large_price'))
            ? (int) round(((float) $request->input('large_price')) * 100)
            : null;

        return [
            'price' => $price,
            'large_price' => $large,
            'proteins' => null,
            'protein_prices' => null,
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $prices = $this->priceData($request);

        $item = MenuItem::create([
            'name' => $data['name'],
            'name_th' => $data['name_th'] ?? null,
            'category_id' => $data['category_id'],
            'price' => $prices['price'],
            'large_price' => $prices['large_price'],
            'proteins' => $prices['proteins'],
            'protein_prices' => $prices['protein_prices'],
            'description' => $data['description'] ?? null,
            'description_th' => $data['description_th'] ?? null,
            'is_available' => $request->boolean('is_available', true),
            'display_order' => (int) (MenuItem::where('category_id', $data['category_id'])->max('display_order') ?? 0) + 1,
        ]);

        if ($request->hasFile('image')) {
            $item->update(['image_path' => $this->storeImage($request, $item)]);
        }

        return back()->with('success', 'Menu item added.');
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $data = $request->validate($this->rules());
        $prices = $this->priceData($request);

        $menuItem->update([
            'name' => $data['name'],
            'name_th' => $data['name_th'] ?? null,
            'category_id' => $data['category_id'],
            'price' => $prices['price'],
            'large_price' => $prices['large_price'],
            'proteins' => $prices['proteins'],
            'protein_prices' => $prices['protein_prices'],
            'description' => $data['description'] ?? null,
            'description_th' => $data['description_th'] ?? null,
            'is_available' => $request->boolean('is_available', true),
        ]);

        if ($request->hasFile('image')) {
            $this->deleteImage($menuItem->image_path);
            $menuItem->update(['image_path' => $this->storeImage($request, $menuItem)]);
        }

        return back()->with('success', 'Menu item updated.');
    }

    public function toggle(MenuItem $menuItem)
    {
        $menuItem->update(['is_available' => ! $menuItem->is_available]);

        return back()->with('success', 'Availability updated.');
    }

    public function destroy(MenuItem $menuItem)
    {
        // Soft delete — keeps order history intact.
        $menuItem->delete();

        return back()->with('success', 'Menu item removed.');
    }

    protected function storeImage(Request $request, MenuItem $item): string
    {
        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $filename = Str::slug($item->name) . '-' . $item->id . '.' . $ext;

        $dir = public_path('uploads/menu');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file->move($dir, $filename);

        return 'uploads/menu/' . $filename;
    }

    protected function deleteImage(?string $path): void
    {
        if ($path && is_file(public_path($path))) {
            @unlink(public_path($path));
        }
    }
}
