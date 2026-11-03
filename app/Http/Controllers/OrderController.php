<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\StoreStatus;

class OrderController extends Controller
{
    public function show(string $order_number, StoreStatus $status)
    {
        $order = Order::with('items')
            ->where('order_number', $order_number)
            ->firstOrFail();

        return view('order.show', [
            'order' => $order,
            'settings' => $status->settings(),
        ]);
    }
}
