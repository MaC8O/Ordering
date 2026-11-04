<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\OrderNumberGenerator;
use App\Services\StoreStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index(StoreStatus $status)
    {
        $settings = $status->settings();

        return view('checkout.index', [
            'settings' => $settings,
            'dorms' => (array) ($settings->dorms ?? []),
            'storeStatus' => $status,
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
        $cart = $this->parseCart($request->input('cart'));

        if (empty($cart)) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $items = MenuItem::query()
            ->available()
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        // Any requested item that is gone or unavailable rejects the whole order.
        $missing = array_diff(array_keys($cart), $items->keys()->all());
        if (! empty($missing)) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'One or more items are no longer available. Please review your cart.');
        }

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
        $maxQty = (int) config('ordering.max_qty', 20);

        // 4. Build order lines with price snapshots, totalled server-side.
        $lines = [];
        $subtotal = 0;

        foreach ($cart as $id => $entry) {
            $menuItem = $items[$id];
            $qty = max(1, min($maxQty, (int) $entry['qty']));
            $lineTotal = $menuItem->price * $qty;
            $subtotal += $lineTotal;

            $lines[] = [
                'menu_item_id' => $menuItem->id,
                'item_name_snapshot' => $menuItem->name,
                'item_price_snapshot' => $menuItem->price,
                'item_note' => $entry['note'] !== '' ? mb_substr($entry['note'], 0, 200) : null,
                'quantity' => $qty,
                'line_total' => $lineTotal,
            ];
        }

        $deliveryFee = (int) $settings->default_delivery_fee;
        $total = $subtotal + $deliveryFee;

        // 5. Persist order + items atomically, then store any screenshot.
        $order = DB::transaction(function () use ($request, $numbers, $lines, $subtotal, $deliveryFee, $total) {
            $order = Order::create([
                'order_number' => $numbers->generate(),
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
                'notes' => $request->input('notes'),
            ]);

            $order->items()->createMany($lines);

            return $order;
        });

        // 6. QR screenshot upload. Named by the unguessable token (never the
        //    sequential order number) so receipts can't be found by guessing.
        if ($request->input('payment_method') === 'qr' && $request->hasFile('payment_screenshot')) {
            $file = $request->file('payment_screenshot');
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $filename = $order->public_token . '.' . $ext;

            $dir = public_path('uploads/payments');
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $file->move($dir, $filename);

            $order->update(['payment_screenshot_path' => 'uploads/payments/' . $filename]);
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
