<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class DrinkSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['name' => 'Drinks'],
            [
                'name_th' => 'เครื่องดื่ม',
                'display_order' => (int) Category::max('display_order') + 1,
                'is_active' => true,
            ],
        );

        // [name, name_th, price (baht), slug, description, description_th]
        $drinks = [
            ['Thai Milk Tea', 'ชานมไทยเย็น', 20, 'thai-milk-tea', 'Rich brewed Thai tea with creamy milk over ice.', 'ชาไทยเข้มข้นกับนมเย็นชื่นใจ'],
            ['Iced Coffee', 'กาแฟเย็น', 30, 'iced-coffee', 'Smooth iced coffee with a touch of milk.', 'กาแฟเย็นกลมกล่อม'],
            ['Green Tea', 'ชาเขียวเย็น', 20, 'green-tea', 'Refreshing iced green tea.', 'ชาเขียวเย็นสดชื่น'],
            ['Lemon Tea', 'ชามะนาว', 20, 'lemon-tea', 'Iced tea with a bright squeeze of lemon.', 'ชาเย็นรสมะนาวสดชื่น'],
            ['Iced Cocoa', 'โกโก้เย็น', 20, 'iced-cocoa', 'Chilled chocolate cocoa with milk.', 'โกโก้เย็นหอมนุ่ม'],
            ['Black Tea', 'ชาดำเย็น', 20, 'black-tea', 'Classic iced black tea.', 'ชาดำเย็นรสเข้ม'],
            ['Pink Milk', 'นมเย็น (นมชมพู)', 20, 'pink-milk', 'Sweet pink sala milk over ice.', 'นมเย็นสีชมพูหอมหวาน'],
            ['Red Soda', 'น้ำแดงมะนาวโซดา', 20, 'red-soda', 'Fizzy red sala soda, ice cold.', 'น้ำแดงโซดาซ่าเย็น'],
        ];

        foreach ($drinks as $i => [$name, $nameTh, $baht, $slug, $desc, $descTh]) {
            MenuItem::updateOrCreate(
                ['category_id' => $category->id, 'name' => $name],
                [
                    'name_th' => $nameTh,
                    'description' => $desc,
                    'description_th' => $descTh,
                    'price' => $baht * 100,
                    'image_path' => 'uploads/drinks/' . $slug . '.svg',
                    'is_available' => true,
                    'is_drink' => true,
                    'display_order' => $i,
                ],
            );
        }
    }
}
