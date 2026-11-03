<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Services\StoreStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(StoreStatus $status)
    {
        $today = Carbon::today();

        $todayOrders = Order::whereDate('created_at', $today)->get();

        $stats = [
            'orders_today' => $todayOrders->count(),
            'revenue_today' => $todayOrders
                ->where('order_status', '!=', 'cancelled')
                ->sum('total'),
            'pending_payment' => $todayOrders
                ->where('payment_status', 'pending')
                ->whereNotIn('order_status', ['cancelled'])
                ->count(),
            'preparing' => Order::where('order_status', 'preparing')->count(),
        ];

        $orders = Order::whereDate('created_at', $today)
            ->withCount('items')
            ->latest()
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'orders' => $orders,
            'storeStatus' => $status,
            'settings' => $status->settings(),
        ]);
    }

    public function toggleAccepting(Request $request, StoreStatus $status)
    {
        $settings = Setting::current();
        $turnOn = ! $settings->accepting_orders;

        // Turning ON is only allowed during a scheduled open window.
        if ($turnOn && ! $status->scheduledOpenNow()) {
            return back()->with('error', 'You can only start accepting orders during a scheduled open window.');
        }

        $settings->update(['accepting_orders' => $turnOn]);

        return back()->with('success', $turnOn
            ? 'You are now accepting orders.'
            : 'Ordering is now closed.');
    }
}
