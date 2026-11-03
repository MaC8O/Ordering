<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // 0 = Sunday ... 6 = Saturday. Open every day 10:00–21:00 by default.
        for ($weekday = 0; $weekday <= 6; $weekday++) {
            Schedule::updateOrCreate(
                ['weekday' => $weekday],
                [
                    'is_open' => true,
                    'open_time' => '10:00',
                    'close_time' => '21:00',
                ],
            );
        }
    }
}
