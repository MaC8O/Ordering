<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class MyOrderController extends Controller
{
    public function index()
    {
        return view('my-order.index', [
            'order' => null,
        ]);
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'order_number' => ['required', 'string'],
            'phone' => ['required', 'string'],
        ]);

        $order = Order::with('items')
            ->where('order_number', trim($data['order_number']))
            ->where('customer_phone', trim($data['phone']))
            ->first();

        if (! $order) {
            return back()
                ->withInput()
                ->withErrors(['order_number' => 'No order found with that number and phone.']);
        }

        return view('my-order.index', [
            'order' => $order,
        ]);
    }
}
