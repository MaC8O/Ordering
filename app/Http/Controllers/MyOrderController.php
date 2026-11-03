<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class MyOrderController extends Controller
{
    public function index(Request $request)
    {
        // "Track another order" clears the remembered order.
        if ($request->boolean('stop')) {
            $request->session()->forget(['tracked_order_number', 'tracked_order_phone']);

            return redirect()->route('my-order.index');
        }

        $order = null;

        // If we're already tracking an order (set after a successful lookup),
        // reload it fresh each visit so the status stays current.
        $number = $request->session()->get('tracked_order_number');
        $phone = $request->session()->get('tracked_order_phone');

        if ($number && $phone) {
            $order = Order::with('items')
                ->where('order_number', $number)
                ->where('customer_phone', $phone)
                ->first();

            if (! $order) {
                $request->session()->forget(['tracked_order_number', 'tracked_order_phone']);
            }
        }

        return view('my-order.index', ['order' => $order]);
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'order_number' => ['required', 'string'],
            'phone' => ['required', 'string'],
        ]);

        $number = trim($data['order_number']);
        $phone = trim($data['phone']);

        $order = Order::where('order_number', $number)
            ->where('customer_phone', $phone)
            ->first();

        if (! $order) {
            return back()
                ->withInput()
                ->withErrors(['order_number' => 'No order found with that number and phone.']);
        }

        // Remember it, then redirect (PRG) so the page can safely auto-refresh.
        $request->session()->put('tracked_order_number', $number);
        $request->session()->put('tracked_order_phone', $phone);

        return redirect()->route('my-order.index');
    }
}
