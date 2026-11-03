<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Services\StoreStatus;

class AboutController extends Controller
{
    public function index(StoreStatus $status)
    {
        $schedule = Schedule::orderByRaw('(weekday + 6) % 7')->get(); // Mon..Sun order

        return view('about', [
            'settings' => $status->settings(),
            'schedule' => $schedule,
        ]);
    }
}
