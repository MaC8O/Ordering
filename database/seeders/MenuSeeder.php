<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('data/menu.json');

        if (! is_file($path)) {
            $this->command?->warn('data/menu.json not found — skipping menu seed.');

            return;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data) || ! isset($data['categories'])) {
            $this->command?->warn('data/menu.json is malformed — skipping menu seed.');

            return;
        }

        foreach ($data['categories'] as $catIndex => $cat) {
            $category = Category::updateOrCreate(
                ['name' => $cat['name']],
                [
                    'name_th' => $cat['name_th'] ?? null,
                    'display_order' => $catIndex,
                    'is_active' => true,
                ],
            );

            foreach ($cat['items'] ?? [] as $itemIndex => $item) {
                $imagePath = null;
                if (! empty($item['image_filename'])) {
                    $candidate = 'uploads/menu/' . $item['image_filename'];
                    // Only set the path when the file actually exists; otherwise
                    // the storefront shows a styled placeholder.
                    if (is_file(public_path($candidate))) {
                        $imagePath = $candidate;
                    }
                }

                MenuItem::updateOrCreate(
                    ['name' => $item['name'], 'category_id' => $category->id],
                    [
                        'name_th' => $item['name_th'] ?? null,
                        'description' => $item['description'] ?? null,
                        'description_th' => $item['description_th'] ?? null,
                        'price' => (int) round(($item['price'] ?? 0) * 100),
                        'image_path' => $imagePath,
                        'is_available' => true,
                        'display_order' => $itemIndex,
                    ],
                );
            }
        }
    }
}
