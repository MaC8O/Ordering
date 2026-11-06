<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Setting;
use App\Services\SmsService;
use App\Services\StoreStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->withCount('items');

        if ($request->filled('status')) {
            $query->where('order_status', $request->input('status'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('order_number', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('customer_phone', 'like', "%{$q}%");
            });
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'payment_method', 'payment_status', 'from', 'to', 'q']),
            'statuses' => Order::ORDER_STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
        ]);
    }

    public function show(Order $order)
    {
        $order->load('items', 'customer');

        // Resolve the customer behind this order: the registered account it was
        // placed under, or (for a guest order) an account that shares the phone.
        $customer = $order->customer
            ?: Customer::where('phone', $order->customer_phone)->first();

        $loyalty = null;
        if ($customer) {
            $delivered = $customer->deliveredOrdersCount();
            $threshold = Customer::freeDeliveryThreshold();
            $loyalty = [
                'registered' => true,
                'linked' => (bool) $order->customer_id,
                'total_orders' => $customer->orderHistoryQuery()->count(),
                'delivered' => $delivered,
                'threshold' => $threshold,
                'remaining' => max(0, $threshold - $delivered),
                'has_free_delivery' => $delivered >= $threshold,
            ];
        }

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => Order::ORDER_STATUSES,
            'loyalty' => $loyalty,
        ]);
    }

    public function ticket(Order $order, StoreStatus $status)
    {
        $order->load('items');

        return view('admin.orders.ticket', [
            'order' => $order,
            'settings' => $status->settings(),
        ]);
    }

    public function updateStatus(Request $request, Order $order, SmsService $sms)
    {
        $data = $request->validate([
            'order_status' => ['required', Rule::in(array_keys(Order::ORDER_STATUSES))],
            'eta_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
        ]);

        $wasOutForDelivery = $order->order_status === 'out_for_delivery';
        $updates = ['order_status' => $data['order_status']];

        if ($data['order_status'] === 'out_for_delivery') {
            // Set an estimated arrival time (admin's minutes, or a 20-min default).
            $updates['eta_at'] = now()->addMinutes((int) ($data['eta_minutes'] ?? 20));
        } elseif (in_array($data['order_status'], ['completed', 'cancelled'], true)) {
            $updates['eta_at'] = null;
        }

        $order->update($updates);

        $message = 'Order status updated to ' . Order::ORDER_STATUSES[$data['order_status']] . '.';

        // Text the customer when the order first goes out for delivery.
        if ($data['order_status'] === 'out_for_delivery' && ! $wasOutForDelivery) {
            $message .= ' ' . $this->sendOnTheWaySms($order, $sms);
        }

        return back()->with('success', $message);
    }

    protected function sendOnTheWaySms(Order $order, SmsService $sms): string
    {
        $settings = Setting::current();
        $eta = $order->etaLabel();
        $url = route('order.show', $order->public_token);

        $body = "Hi {$order->customer_name}, your {$settings->restaurant_name} order "
            . "{$order->order_number} is on the way!"
            . ($eta ? " Arriving {$eta}." : '')
            . " Track: {$url}";

        $result = $sms->send($order->customer_phone, $body);

        if ($result['stub']) {
            return 'SMS logged (Twilio not configured — set TWILIO_* in .env to send for real).';
        }
        if ($result['sent']) {
            return 'An SMS was sent to the customer.';
        }

        return 'Could not send the SMS (' . ($result['error'] ?? 'unknown error') . ').';
    }

    public function updatePayment(Request $request, Order $order)
    {
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(array_keys(Order::PAYMENT_STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:200'],
        ]);

        $updates = ['payment_status' => $data['payment_status']];

        if (array_key_exists('admin_note', $data)) {
            $updates['admin_note'] = $data['admin_note'];
        }

        // Verifying payment advances a still-pending order to confirmed.
        if ($data['payment_status'] === 'verified' && $order->order_status === 'pending') {
            $updates['order_status'] = 'confirmed';
        }

        // A failed payment cancels the order.
        if ($data['payment_status'] === 'failed') {
            $updates['order_status'] = 'cancelled';
        }

        $order->update($updates);

        return back()->with('success', 'Payment status updated.');
    }

    public function cancel(Request $request, Order $order)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:200'],
        ]);

        $order->update([
            'order_status' => 'cancelled',
            'admin_note' => $data['admin_note'] ?? $order->admin_note,
        ]);

        return back()->with('success', 'Order cancelled.');
    }
}
