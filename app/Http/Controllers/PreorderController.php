<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Services\CartParser;
use App\Services\OrderNumberGenerator;
use App\Services\PreorderStatus;
use App\Services\StoreStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PreorderController extends Controller
{
    /**
     * Show the preorder checkout (reuses the checkout view in "preorder mode":
     * the delivery date is locked to the batch day and payment is upfront by QR).
     */
    public function create(StoreStatus $status, PreorderStatus $preorder)
    {
        if (! $preorder->isOpen()) {
            return redirect()->route('menu.index')
                ->with('error', __('Preordering is closed right now.'));
        }

        $settings = $status->settings();
        $customer = Auth::guard('customer')->user();
        $freeDelivery = $customer?->hasFreeDelivery() ?? false;

        return view('checkout.index', [
            'settings' => $settings,
            'dorms' => (array) ($settings->dorms ?? []),
            'storeStatus' => $status,
            'customer' => $customer,
            'freeDelivery' => $freeDelivery,
            'deliveryFeeMinor' => $freeDelivery ? 0 : $preorder->deliveryFee(),
            'preorderMode' => true,
            'preorder' => $preorder,
        ]);
    }

    public function store(Request $request, PreorderStatus $preorder, OrderNumberGenerator $numbers)
    {
        // Server-side gate — never trust the client that the window is open.
        if (! $preorder->isOpen()) {
            return redirect()->route('menu.index')
                ->with('error', __('Preordering is closed right now.'));
        }

        // Dorm must be one the restaurant delivers to (server-side, like checkout).
        $dorms = array_values((array) ($preorder->settings()->dorms ?? []));
        $dormRule = $dorms ? [\Illuminate\Validation\Rule::in($dorms)] : [];

        $request->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'delivery_dorm' => ['required', 'string', 'min:2', 'max:120', ...$dormRule],
            'delivery_notes' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            // Preorders are paid upfront by QR transfer to lock in the batch.
            'payment_screenshot' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . config('ordering.screenshot_max_kb')],
            'cart' => ['required', 'string'],
        ], [
            'customer_phone.regex' => __('Enter a valid phone number (digits only, 7–15 characters, optional leading +).'),
            'payment_screenshot.required' => __('Please upload a screenshot of your transfer.'),
        ]);

        $cart = CartParser::parse($request->input('cart'));
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', __('Your cart is empty.'));
        }

        $built = \App\Services\OrderLines::build($cart);
        if ($built['missing']) {
            return redirect()->route('cart.index')
                ->with('error', __('One or more items are no longer available. Please review your cart.'));
        }
        $lines = $built['lines'];
        $subtotal = $built['subtotal'];

        $customer = Auth::guard('customer')->user();
        $freeDelivery = $customer?->hasFreeDelivery() ?? false;
        $deliveryFee = $freeDelivery ? 0 : $preorder->deliveryFee();
        $total = $subtotal + $deliveryFee;
        $fulfillmentDate = $preorder->fulfillmentDate();

        $order = DB::transaction(function () use ($request, $numbers, $lines, $subtotal, $deliveryFee, $total, $customer, $fulfillmentDate) {
            $order = Order::create([
                'order_number' => $numbers->generate(),
                'customer_id' => $customer?->id,
                'customer_name' => $request->string('customer_name'),
                'customer_phone' => $request->string('customer_phone'),
                'delivery_dorm' => $request->string('delivery_dorm'),
                'delivery_room' => null,
                'delivery_date' => $fulfillmentDate,
                'delivery_notes' => $request->input('delivery_notes'),
                'preferred_time' => null,
                'payment_method' => 'qr',
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'is_preorder' => true,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'notes' => $request->input('notes'),
            ]);

            $order->items()->createMany($lines);

            return $order;
        });

        $order->storeReceipt($request->file('payment_screenshot'));

        return redirect()->route('order.show', $order->public_token);
    }
}
