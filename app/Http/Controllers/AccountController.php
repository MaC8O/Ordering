<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\StoreStatus;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index(StoreStatus $storeStatus)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $orders = $customer->orderHistoryQuery()->with('items')->get();

        // In-progress orders vs. finished ones (delivered or cancelled).
        $active = $orders->reject(fn ($o) => $o->isDone())->values();
        $history = $orders->filter(fn ($o) => $o->isDone())->values();

        $delivered = $orders->where('order_status', 'completed')->count();
        $threshold = Customer::freeDeliveryThreshold();

        return view('account.index', [
            'customer' => $customer,
            'active' => $active,
            'history' => $history,
            'delivered' => $delivered,
            'threshold' => $threshold,
            'remaining' => max(0, $threshold - $delivered),
            'hasFreeDelivery' => $delivered >= $threshold,
            'storeStatus' => $storeStatus,
        ]);
    }
}
