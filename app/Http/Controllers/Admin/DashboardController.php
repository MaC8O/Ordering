<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Services\RevenueReport;
use App\Services\StoreStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, StoreStatus $status, RevenueReport $revenue)
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        $todayOrders = Order::whereDate('created_at', $today)->get();
        $todayActive = $todayOrders->where('order_status', '!=', 'cancelled');

        $stats = [
            'orders_today' => $todayOrders->count(),
            'revenue_today' => $todayActive->sum('total'),
            'pending_payment' => $todayOrders
                ->where('payment_status', 'pending')
                ->whereNotIn('order_status', ['cancelled'])
                ->count(),
            'preparing' => Order::where('order_status', 'preparing')->count(),
        ];

        // Delivery-service earnings, tracked separately from food sales.
        $deliverySince = fn (Carbon $from) => (int) Order::where('order_status', '!=', 'cancelled')
            ->where('created_at', '>=', $from)
            ->sum('delivery_fee');

        // Busiest hall today (by number of active orders).
        $dormCounts = $todayActive
            ->filter(fn ($o) => filled($o->delivery_dorm))
            ->groupBy('delivery_dorm')
            ->map->count()
            ->sortDesc();

        $delivery = [
            'today' => (int) $todayActive->sum('delivery_fee'),
            'week' => $deliverySince($weekStart),
            'month' => $deliverySince($monthStart),
            // Deliveries marked delivered today (status last changed today).
            'completed_today' => Order::where('order_status', 'completed')
                ->whereDate('updated_at', $today)
                ->count(),
            // Orders somewhere in the delivery pipeline right now.
            'pending_now' => Order::whereIn('order_status', ['confirmed', 'preparing', 'out_for_delivery'])->count(),
            'avg_fee' => $todayActive->count() > 0 ? (int) round($todayActive->avg('delivery_fee')) : 0,
            'popular_dorm' => $dormCounts->keys()->first(),
            'popular_dorm_count' => (int) ($dormCounts->first() ?? 0),
        ];

        $orders = Order::whereDate('created_at', $today)
            ->withCount('items')
            ->latest()
            ->get();

        $report = $revenue->build((string) $request->query('period', 'today'));

        return view('admin.dashboard', [
            'stats' => $stats,
            'delivery' => $delivery,
            'orders' => $orders,
            'storeStatus' => $status,
            'settings' => $status->settings(),
            'report' => $report,
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
