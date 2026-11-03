<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MenuItemController extends Controller
{
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'category_id' => ['required', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_available' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . config('ordering.menu_image_max_kb')],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $item = MenuItem::create([
            'name' => $data['name'],
            'category_id' => $data['category_id'],
            'price' => (int) round($data['price'] * 100),
            'description' => $data['description'] ?? null,
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

        $menuItem->update([
            'name' => $data['name'],
            'category_id' => $data['category_id'],
            'price' => (int) round($data['price'] * 100),
            'description' => $data['description'] ?? null,
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
