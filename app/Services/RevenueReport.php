<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds an organised sales/revenue summary for the admin dashboard:
 * headline totals, a breakdown by menu category, and the top-selling items.
 * Cancelled orders are always excluded. All money values are in minor units
 * (satang); divide by 100 for baht.
 */
class RevenueReport
{
    public const PERIODS = [
        'today' => 'Today',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        'all' => 'All time',
    ];

    /**
     * @return array{period:string, periodLabel:string, periods:array<string,string>,
     *   totals:object, itemsSold:int, byCategory:\Illuminate\Support\Collection,
     *   topItems:\Illuminate\Support\Collection}
     */
    public function build(string $period = 'today'): array
    {
        if (! array_key_exists($period, self::PERIODS)) {
            $period = 'today';
        }

        $start = match ($period) {
            'today' => Carbon::today(),
            '7d' => Carbon::today()->subDays(6),
            '30d' => Carbon::today()->subDays(29),
            default => null, // all time
        };

        $scopeOrders = fn ($q) => $q
            ->where('order_status', '!=', 'cancelled')
            ->when($start, fn ($qq) => $qq->where('created_at', '>=', $start));

        $totals = DB::table('orders')
            ->tap($scopeOrders)
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as food')
            ->selectRaw('COALESCE(SUM(delivery_fee), 0) as delivery')
            ->selectRaw('COALESCE(SUM(total), 0) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'verified' THEN total ELSE 0 END), 0) as paid")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status <> 'verified' THEN total ELSE 0 END), 0) as unpaid")
            ->first();

        $itemsSold = (int) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.order_status', '!=', 'cancelled')
            ->when($start, fn ($q) => $q->where('orders.created_at', '>=', $start))
            ->sum('order_items.quantity');

        $categoryExpr = "COALESCE(categories.name, 'Other')";

        $byCategory = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->leftJoin('categories', 'categories.id', '=', 'menu_items.category_id')
            ->where('orders.order_status', '!=', 'cancelled')
            ->when($start, fn ($q) => $q->where('orders.created_at', '>=', $start))
            ->groupBy(DB::raw($categoryExpr))
            ->selectRaw("$categoryExpr as category")
            ->selectRaw('SUM(order_items.quantity) as qty')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->orderByDesc('revenue')
            ->get();

        $topItems = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.order_status', '!=', 'cancelled')
            ->when($start, fn ($q) => $q->where('orders.created_at', '>=', $start))
            ->groupBy('order_items.item_name_snapshot')
            ->selectRaw('order_items.item_name_snapshot as name')
            ->selectRaw('SUM(order_items.quantity) as qty')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        return [
            'period' => $period,
            'periodLabel' => self::PERIODS[$period],
            'periods' => self::PERIODS,
            'totals' => $totals,
            'itemsSold' => $itemsSold,
            'byCategory' => $byCategory,
            'topItems' => $topItems,
        ];
    }
}
