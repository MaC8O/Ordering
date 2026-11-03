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
                'restaurant_name' => 'Campus Kitchen',
                'phone' => '+60123456789',
                'address' => 'AIU Campus, Jalan Tun Razak, 05200 Alor Setar, Kedah',
                'account_name' => 'Campus Kitchen Enterprise',
                'cutoff_minutes_before_close' => 30,
                'default_delivery_fee' => 1000, // ฿10.00
                'accepting_orders' => true,
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
