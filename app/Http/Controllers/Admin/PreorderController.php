<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PreorderStatus;
use Illuminate\Http\Request;

class PreorderController extends Controller
{
    /**
     * Kitchen prep view: the upcoming preorder batch, with an aggregated
     * item-count prep list and the orders feeding it.
     */
    public function index(Request $request, PreorderStatus $preorder)
    {
        // Every batch date that has preorders, newest first, for the date switcher.
        $batchDates = Order::query()
            ->where('is_preorder', true)
            ->where('order_status', '!=', 'cancelled')
            ->whereNotNull('delivery_date')
            ->selectRaw('DATE(delivery_date) as d')
            ->distinct()
            ->orderByDesc('d')
            ->pluck('d')
            ->all();

        // Which batch are we viewing? A valid ?date wins; otherwise the next
        // upcoming batch that has orders, else the computed fulfillment date.
        $selected = $request->input('date');
        if (! in_array($selected, $batchDates, true)) {
            $upcoming = collect($batchDates)
                ->filter(fn ($d) => $d >= now()->toDateString())
                ->sort()
                ->first();
            $selected = $upcoming ?? $preorder->fulfillmentDate();
        }

        $orders = Order::query()
            ->with('items')
            ->where('is_preorder', true)
            ->where('order_status', '!=', 'cancelled')
            ->whereDate('delivery_date', $selected)
            ->latest()
            ->get();

        // Aggregate the prep list: total quantity per dish across the batch.
        $prep = $orders
            ->flatMap->items
            ->groupBy('item_name_snapshot')
            ->map(fn ($lines) => $lines->sum('quantity'))
            ->sortDesc();

        return view('admin.preorders.index', [
            'preorder' => $preorder,
            'batchDates' => $batchDates,
            'selectedDate' => $selected,
            'orders' => $orders,
            'prep' => $prep,
            'totalDishes' => $prep->sum(),
            'paidCount' => $orders->where('payment_status', 'verified')->count(),
            'revenue' => $orders->sum('total'),
        ]);
    }
}
