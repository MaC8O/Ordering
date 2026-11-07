<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['id' => 1],
            [
                'restaurant_name' => 'Front Gate Restaurant',
                'phone' => '+60123456789',
                'address' => 'AIU Campus, Jalan Tun Razak, 05200 Alor Setar, Kedah',
                'account_name' => 'Campus Kitchen Enterprise',
                'cutoff_minutes_before_close' => 30,
                'default_delivery_fee' => 1000, // ฿10.00 per food item
                'delivery_fee_cap' => 5000, // ฿50.00 ceiling (0 = no cap)
                'accepting_orders' => true,

                // Weekly preorder batch: collection Sat 20:00 → Sun 09:00,
                // cooked and delivered together Mon 10:00–12:00. Enabled by
                // default; the collection window is managed in Admin → Settings.
                'preorder_enabled' => true,
                'preorder_open_day' => 6,       // Saturday
                'preorder_open_time' => '20:00',
                'preorder_cutoff_day' => 0,     // Sunday
                'preorder_cutoff_time' => '09:00',
                'preorder_fulfillment_day' => 1, // Monday
                'preorder_window_start' => '10:00',
                'preorder_window_end' => '12:00',
                'preorder_delivery_fee' => 0,   // minor units; 0 = free
                'dorms' => [
                    'Residential College A',
                    'Residential College B',
                    'Residential College C',
                    'Residential College D',
                    'Academic Block',
                    'Library',
                    'Main Cafeteria',
                    'Sports Complex',
                    'Admin Building',
                ],
            ],
        );
    }
}
