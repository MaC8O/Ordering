<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\MenuItem;
use App\Services\OrderNumberGenerator;
use App\Services\StoreStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function index(StoreStatus $status)
    {
        $settings = $status->settings();
        $customer = Auth::guard('customer')->user();

        return view('checkout.index', [
            'settings' => $settings,
            'dorms' => (array) ($settings->dorms ?? []),
            'storeStatus' => $status,
            'customer' => $customer,
            'freeDelivery' => $customer?->hasFreeDelivery() ?? false,
        ]);
    }

    public function store(CheckoutRequest $request, StoreStatus $status, OrderNumberGenerator $numbers)
    {
        // 1. Server-side open/close gate — never trust the client.
        if (! $status->isOpen()) {
            return redirect()
                ->route('cart.index')
                ->with('error', $status->closedReason() ?? 'Ordering is currently closed.');
        }

        // 2. Parse & validate the cart payload (sent from localStorage).
        $cart = \App\Services\CartParser::parse($request->input('cart'));

        if (empty($cart)) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        // Build order lines with option-aware price snapshots, totalled
        // server-side. Any item that is gone or unavailable rejects the order.
        $built = \App\Services\OrderLines::build($cart);
        if ($built['missing']) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'One or more items are no longer available. Please review your cart.');
        }
        $lines = $built['lines'];
        $subtotal = $built['subtotal'];

        // 3. Validate preferred delivery time against the chosen date's hours.
        if ($request->filled('preferred_time')) {
            $error = $this->validatePreferredTime(
                $request->input('preferred_time'),
                $request->input('delivery_date'),
                $status
            );
            if ($error) {
                return back()->withInput()->withErrors(['preferred_time' => $error]);
            }
        }

        $settings = $status->settings();

        // Loyalty reward: a registered customer earns one free delivery per N
        // delivered orders; it's redeemed on this order and resets. Decided
        // server-side so the client can never grant itself a free delivery.
        $customer = Auth::guard('customer')->user();
        $freeDelivery = $customer?->hasFreeDelivery() ?? false;

        // Per-item delivery: base rate × food units, drinks free alongside food,
        // flat base for a drinks-only order. Computed server-side so the client
        // can never understate it.
        $deliveryFee = $freeDelivery
            ? 0
            : \App\Services\DeliveryFee::compute(
                (int) $settings->default_delivery_fee,
                (int) $built['food_qty'],
                (int) $built['drink_qty'],
                (int) $settings->delivery_fee_cap,
            );
        $total = $subtotal + $deliveryFee;

        // 5. Persist order + items atomically (retrying on the rare order-number
        //    collision so a concurrent order is never lost), then store any screenshot.
        $order = $numbers->createOrder([
            'customer_id' => $customer?->id,
            'customer_name' => $request->string('customer_name'),
            'customer_phone' => $request->string('customer_phone'),
            'delivery_dorm' => $request->string('delivery_dorm'),
            'delivery_room' => null,
            'delivery_date' => $request->input('delivery_date'),
            'delivery_notes' => $request->input('delivery_notes'),
            'preferred_time' => $request->input('preferred_time'),
            'payment_method' => $request->string('payment_method'),
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'is_free_delivery_reward' => $freeDelivery,
            'notes' => $request->input('notes'),
        ], $lines);

        // 6. QR receipt upload. Stored by Order::storeReceipt(), which names the
        //    file by the unguessable token so receipts can't be found by guessing.
        if ($request->input('payment_method') === 'qr' && $request->hasFile('payment_screenshot')) {
            $order->storeReceipt($request->file('payment_screenshot'));
        }

        return redirect()->route('order.show', $order->public_token);
    }

    /**
     * Normalise the client cart JSON into [id => ['qty' => int, 'note' => string]].
     *
     * @return array<int, array{qty:int, note:string}>
     */
    protected function parseCart(?string $json): array
    {
        $decoded = json_decode((string) $json, true);

        if (! is_array($decoded)) {
            return [];
        }

        $cart = [];
        foreach ($decoded as $row) {
            if (! is_array($row) || ! isset($row['id'])) {
                continue;
            }
            $id = (int) $row['id'];
            $qty = (int) ($row['qty'] ?? 0);
            if ($id <= 0 || $qty <= 0) {
                continue;
            }
            $note = isset($row['note']) ? trim((string) $row['note']) : '';

            // Merge duplicate ids.
            if (isset($cart[$id])) {
                $cart[$id]['qty'] += $qty;
            } else {
                $cart[$id] = ['qty' => $qty, 'note' => $note];
            }
        }

        return $cart;
    }

    protected function validatePreferredTime(string $time, ?string $date, StoreStatus $status): ?string
    {
        try {
            $preferred = Carbon::createFromFormat('H:i', $time);
        } catch (\Throwable) {
            return 'Enter a valid time.';
        }

        $now = Carbon::now();

        // Anchor the preferred time to the chosen delivery date (fall back to today).
        try {
            $day = $date ? Carbon::createFromFormat('Y-m-d', $date)->startOfDay() : $now->copy()->startOfDay();
        } catch (\Throwable) {
            $day = $now->copy()->startOfDay();
        }

        $preferredAt = $day->copy()->setTime($preferred->hour, $preferred->minute, 0);

        // Only guard against the past when the delivery is for today.
        if ($preferredAt->lessThan($now)) {
            return 'Preferred time cannot be in the past.';
        }

        // Check against the opening hours for that weekday.
        $row = \App\Models\Schedule::where('weekday', (int) $day->dayOfWeek)->first();

        if ($row && $row->is_open) {
            [$ch, $cm] = array_pad(explode(':', $row->close_time), 2, '0');
            $close = $day->copy()->setTime((int) $ch, (int) $cm, 0);
            if ($preferredAt->greaterThan($close)) {
                return 'Preferred time must be before we close that day (' . substr($row->close_time, 0, 5) . ').';
            }
        }

        return null;
    }
}
