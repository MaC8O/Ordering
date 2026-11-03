<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderNumberGenerator
{
    /**
     * Generate a daily-reset, human-readable order number: ORD-YYYYMMDD-NNN.
     * Counts existing orders created today and increments.
     */
    public function generate(): string
    {
        $today = Carbon::today();
        $datePart = $today->format('Ymd');

        $countToday = Order::whereDate('created_at', $today)->count();

        // Guard against collisions (e.g. concurrent inserts) by probing.
        do {
            $countToday++;
            $candidate = sprintf('ORD-%s-%03d', $datePart, $countToday);
        } while (Order::where('order_number', $candidate)->exists());

        return $candidate;
    }
}
