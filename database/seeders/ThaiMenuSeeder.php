<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Seeds the menu from document/Menu_data.md, grouped into a few clean sections.
 * Protein variants are collapsed into one dish with a "proteins" choice, so the
 * menu shows one appetising card per dish instead of a card per protein.
 * Keeps Drinks; retires any older placeholder categories.
 */
class ThaiMenuSeeder extends Seeder
{
    public function run(): void
    {
        // section => [th, [ dish: [slug, name, normal, large, egg, spice, proteins[], desc] ] ]
        $sections = [
            'Stir-Fries' => ['ผัด', [
                ['pad-kra-pao', 'Spicy Basil Stir-Fry (Pad Kra Pao)', 30, 50, true, true,
                    ['Pork', 'Chicken', 'Beef', 'Seafood'], 'Holy-basil stir-fry over rice — a Thai classic.'],
                ['garlic-pepper', 'Garlic & Pepper Stir-Fry', 30, 50, true, true,
                    ['Pork', 'Crispy Calamari', 'Shrimp'], 'Savoury stir-fry with garlic and cracked peppercorns.'],
                ['pad-prik', 'Stir-Fried Chilli (Pad Prik)', 30, 50, true, true,
                    ['Beef', 'Chicken', 'Pork'], 'Stir-fried with fresh chillies and vegetables.'],
                ['chilli-salt', 'Chilli Salt Stir-Fry (Pad Prik Gleua)', 30, 50, true, true,
                    ['Chicken', 'Beef'], 'Crispy, salty-spicy chilli-salt stir-fry.'],
            ]],
            'Rice Dishes' => ['ข้าว', [
                ['omelet-rice', 'Omelet with Rice (Khao Kai Jeow)', 30, 40, false, true,
                    ['Pork', 'Chicken', 'Vegetable', 'Shrimp'], 'Fluffy Thai omelet served over steamed rice.'],
                ['crispy-rice-chicken', 'Crispy Rice Chicken (Khao Man Gai Thod)', 30, 50, true, true,
                    [], 'Golden fried chicken over fragrant rice.'],
                ['crispy-chicken-kale', 'Crispy Chicken with Kale (Gai Thod Kana)', 30, 50, true, true,
                    [], 'Crispy chicken tossed with crunchy kale.'],
                ['fried-rice', 'Fried Rice (Khao Pad)', 30, 50, true, true,
                    ['Pork', 'Chicken', 'Seafood', 'Beef', 'Vegetables', 'Mixed Meat'], 'Wok-fried rice, your choice of protein.'],
                ['tom-yum-fried-rice', 'Tom Yum Fried Rice (Khao Pad Tom Yum)', 30, 50, true, true,
                    ['Seafood', 'Pork', 'Chicken', 'Beef'], 'Fried rice with zingy, spicy tom-yum flavour.'],
            ]],
            'Noodles' => ['เส้น', [
                ['pad-kee-mao', 'Spicy Fried Noodles (Pad Kee Mao)', 30, 50, false, false,
                    ['Plain', 'Seafood', 'Pork'], 'Bold "drunken" stir-fried noodles with herbs.'],
                ['fried-noodles-senyai', 'Fried Noodles (Pad Mee / Sen Yai)', 30, 40, false, false,
                    ['Pork', 'Beef', 'Chicken', 'Seafood'], 'Stir-fried noodles with egg and greens.'],
                ['pad-thai', 'Thai Fried Noodles (Pad Thai)', 40, 50, false, false,
                    ['Pork', 'Chicken', 'Beef', 'Seafood'], 'The iconic sweet-savoury Thai noodle stir-fry.'],
            ]],
            'Soups & Suki' => ['ต้ม & สุกี้', [
                ['noodle-soup', 'Noodle Soup (Kuay Teow)', 30, 40, false, true,
                    ['Pork', 'Beef'], 'Comforting clear-broth noodle soup.'],
                ['tom-yum-noodle-soup', 'Tom Yum Noodle Soup', 30, 40, false, true,
                    ['Pork', 'Beef', 'Seafood'], 'Hot-and-sour tom-yum noodle soup.'],
                ['mee-nam', 'Fried Noodles with Soup (Mee Nam)', 30, 40, false, true,
                    ['Pork', 'Beef', 'Chicken', 'Seafood'], 'Fried noodles served in a rich soup.'],
                ['suki', 'Suki (Thai Sukiyaki)', 30, 50, false, true,
                    ['Plain', 'Seafood', 'Pork', 'Beef', 'Chicken'], 'Thai-style sukiyaki with glass noodles and veg.'],
            ]],
        ];

        $keptNames = ['Drinks'];
        $order = 0;

        foreach ($sections as $sectionName => [$sectionTh, $dishes]) {
            $keptNames[] = $sectionName;

            $category = Category::updateOrCreate(
                ['name' => $sectionName],
                ['name_th' => $sectionTh, 'display_order' => $order++, 'is_active' => true],
            );

            foreach ($dishes as $i => [$slug, $name, $normal, $large, $egg, $spice, $proteins, $desc]) {
                $image = 'uploads/menu/' . $slug . '.jpg';

                MenuItem::updateOrCreate(
                    ['name' => $name, 'category_id' => $category->id],
                    [
                        'description' => $desc,
                        'price' => $normal * 100,
                        'large_price' => $large * 100,
                        'has_egg_addon' => $egg,
                        'has_spice_note' => $spice,
                        'proteins' => $proteins ?: null,
                        'image_path' => is_file(public_path($image)) ? $image : null,
                        'is_available' => true,
                        'is_drink' => false,
                        'display_order' => $i,
                    ],
                );
            }
        }

        // Drinks stays, at the end of the menu.
        Category::where('name', 'Drinks')->update(['display_order' => 99]);

        // Retire every other (older) category and soft-delete its items.
        foreach (Category::whereNotIn('name', $keptNames)->get() as $old) {
            $old->update(['is_active' => false]);
            MenuItem::where('category_id', $old->id)->delete();
        }
    }
}
