<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Seeds the real menu from document/Menu_data.md: 16 categories with their
 * items, Normal/Large sizes, egg add-on and spiciness-note flags. Replaces the
 * old placeholder food menu (deactivated + soft-deleted) but keeps Drinks.
 */
class ThaiMenuSeeder extends Seeder
{
    public function run(): void
    {
        // slug, en, th, normal, large, egg, spice, [items...]
        $categories = [
            ['pad-kra-pao', 'Spicy Basil Stir-Fry (Pad Kra Pao)', 'ผัดกะเพรา', 30, 50, true, true, [
                'Spicy Fried Pork with Basil Leaves', 'Spicy Fried Chicken with Basil Leaves',
                'Spicy Fried Meat with Basil Leaves', 'Spicy Fried Seafood with Basil Leaves',
            ]],
            ['omelet-rice', 'Omelet with Rice (Khao Kai Jeow)', 'ข้าวไข่เจียว', 30, 40, false, true, [
                'Omelet Pork with Rice', 'Omelet Chicken with Rice',
                'Omelet Vegetable with Rice', 'Omelet Shrimp with Rice',
            ]],
            ['crispy-rice-chicken', 'Crispy Rice Chicken (Khao Man Gai Thod)', 'ข้าวมันไก่ทอด', 30, 50, true, true, [
                'Crispy Rice Chicken',
            ]],
            ['crispy-chicken-kale', 'Crispy Chicken with Kale (Gai Thod Kana)', 'ไก่ทอดคะน้า', 30, 50, true, true, [
                'Crispy Chicken with Kale',
            ]],
            ['garlic-pepper', 'Garlic & Pepper Stir-Fry', 'ผัดกระเทียมพริกไทย', 30, 50, true, true, [
                'Stir-Fried Pork with Garlic and Peppercorns', 'Crispy Fried Calamari', 'Stir-Fried Shrimp with Garlic',
            ]],
            ['pad-kee-mao', 'Spicy Fried Noodles (Pad Kee Mao)', 'ผัดขี้เมา', 30, 50, false, false, [
                'Spicy Fried Noodles', 'Spicy Fried Noodles with Seafood', 'Spicy Fried Noodles with Pork',
            ]],
            ['pad-prik', 'Stir-Fried Chilli (Pad Prik)', 'ผัดพริก', 30, 50, true, true, [
                'Stir-Fried Chilli with Beef', 'Stir-Fried Chilli with Chicken', 'Stir-Fried Chilli with Pork',
            ]],
            ['tom-yum-fried-rice', 'Tom Yum Fried Rice (Khao Pad Tom Yum)', 'ข้าวผัดต้มยำ', 30, 50, true, true, [
                'Tom Yum Fried Rice with Seafood', 'Tom Yum Fried Rice with Pork',
                'Tom Yum Fried Rice with Chicken', 'Tom Yum Fried Rice with Beef',
            ]],
            ['suki', 'Suki (Thai Sukiyaki)', 'สุกี้', 30, 50, false, true, [
                'Suki (plain / nam)', 'Suki with Seafood', 'Suki with Pork', 'Suki with Beef', 'Suki with Chicken',
            ]],
            ['chilli-salt', 'Chilli Salt Stir-Fry (Pad Prik Gleua)', 'ผัดพริกเกลือ', 30, 50, true, true, [
                'Fried Chilli Salt Chicken', 'Fried Chilli Salt Beef',
            ]],
            ['fried-noodles-senyai', 'Fried Noodles (Pad Mee / Sen Yai)', 'ผัดเส้นใหญ่', 30, 40, false, false, [
                'Fried Noodles with Pork', 'Fried Noodles with Meat',
                'Fried Noodles with Chicken', 'Fried Noodles with Seafood',
            ]],
            ['pad-thai', 'Thai Fried Noodles (Pad Thai)', 'ผัดไทย', 40, 50, false, false, [
                'Pad Thai with Pork', 'Pad Thai with Chicken', 'Pad Thai with Meat', 'Pad Thai with Seafood',
            ]],
            ['fried-rice', 'Fried Rice (Khao Pad)', 'ข้าวผัด', 30, 50, true, true, [
                'Fried Rice with Pork', 'Fried Rice with Chicken', 'Fried Rice with Seafood',
                'Fried Rice with Meat', 'Fried Rice with Vegetables', 'Fried Rice with Mixed Meat',
            ]],
            ['noodle-soup', 'Noodle Soup (Kuay Teow)', 'ก๋วยเตี๋ยว', 30, 40, false, true, [
                'Pork Noodles', 'Meat Noodles',
            ]],
            ['tom-yum-noodle-soup', 'Tom Yum Noodle Soup', 'ก๋วยเตี๋ยวต้มยำ', 30, 40, false, true, [
                'Tom Yum Pork / Meat Noodles', 'Pork / Meat Seafood Noodles',
            ]],
            ['mee-nam', 'Fried Noodles with Soup (Mee Nam)', 'หมี่น้ำ', 30, 40, false, true, [
                'Fried Noodles with Pork and Soup', 'Fried Noodles with Meat and Soup',
                'Fried Noodles with Chicken and Soup', 'Fried Noodles with Seafood and Soup',
            ]],
        ];

        $keptNames = ['Drinks'];

        foreach ($categories as $order => [$slug, $en, $th, $normal, $large, $egg, $spice, $items]) {
            $keptNames[] = $en;

            $category = Category::updateOrCreate(
                ['name' => $en],
                ['name_th' => $th, 'display_order' => $order, 'is_active' => true],
            );

            $image = 'uploads/menu/' . $slug . '.jpg';
            $imagePath = is_file(public_path($image)) ? $image : null;

            foreach ($items as $i => $itemName) {
                MenuItem::updateOrCreate(
                    ['name' => $itemName, 'category_id' => $category->id],
                    [
                        'price' => $normal * 100,
                        'large_price' => $large * 100,
                        'has_egg_addon' => $egg,
                        'has_spice_note' => $spice,
                        'image_path' => $imagePath,
                        'is_available' => true,
                        'is_drink' => false,
                        'display_order' => $i,
                    ],
                );
            }
        }

        // Push Drinks to the end of the menu.
        Category::where('name', 'Drinks')->update(['display_order' => 99]);

        // Retire the old placeholder food menu: deactivate those categories and
        // soft-delete their items (order history keeps its own snapshots).
        $oldCategories = Category::whereNotIn('name', $keptNames)->get();
        foreach ($oldCategories as $old) {
            $old->update(['is_active' => false]);
            MenuItem::where('category_id', $old->id)->delete();
        }
    }
}
