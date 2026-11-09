<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Setting;
use App\Services\DeliveryFee;
use App\Services\OrderLines;
use App\Services\OrderNumberGenerator;
use App\Services\SmsService;
use App\Services\StoreStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Statuses offered for the bulk action bar on the orders list — the forward
     * operational steps, labelled from the canonical map.
     */
    private const BULK_STATUSES = ['confirmed', 'preparing', 'out_for_delivery', 'completed', 'cancelled'];

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

        if ($request->filled('dorm')) {
            $query->where('delivery_dorm', $request->input('dorm'));
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
            'filters' => $request->only(['status', 'payment_method', 'payment_status', 'dorm', 'from', 'to', 'q']),
            'statuses' => Order::ORDER_STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
            'dorms' => array_values((array) (Setting::current()->dorms ?? [])),
            'bulkStatuses' => self::BULK_STATUSES,
        ]);
    }

    /**
     * Lightweight JSON snapshot polled by the admin dashboard / orders page to
     * detect new orders without a manual refresh. Returns the id and number of
     * the most recent order, plus how many still need attention.
     */
    public function feed()
    {
        $latest = Order::latest('id')->first(['id', 'order_number']);

        return response()->json([
            'latest_id' => $latest?->id ?? 0,
            'latest_number' => $latest?->order_number,
            'pending' => Order::whereIn('order_status', ['pending_review', 'pending', 'confirmed', 'preparing'])->count(),
        ]);
    }

    /**
     * Form to log an order by hand — e.g. a customer who transferred by QR but
     * never completed checkout, so no order was created automatically.
     */
    public function create(StoreStatus $status)
    {
        $settings = $status->settings();

        // Menu items the admin can pick, with the option data the picker needs.
        $items = MenuItem::available()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('display_order')
            ->get()
            ->map(fn (MenuItem $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'category' => $i->category?->name ?? '—',
                'price' => (int) $i->price,
                'proteins' => $i->hasProteins() ? array_values($i->proteins) : [],
                'proteinPrices' => $i->protein_prices ?: (object) [],
                'hasSizes' => $i->hasSizes(),
                'largePrice' => $i->large_price !== null ? (int) $i->large_price : null,
                'hasEgg' => (bool) $i->has_egg_addon,
                'isDrink' => (bool) $i->is_drink,
            ])
            ->values();

        return view('admin.orders.create', [
            'menuItems' => $items,
            'dorms' => (array) ($settings->dorms ?? []),
            'settings' => $settings,
            'statuses' => Order::ORDER_STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
            'eggFee' => (int) config('ordering.egg_addon_fee'),
        ]);
    }

    /**
     * Persist a hand-entered order. Prices are computed server-side with the
     * same services the storefront uses, so totals stay consistent.
     */
    public function store(Request $request, OrderNumberGenerator $numbers, StoreStatus $status)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'delivery_dorm' => ['required', 'string', 'min:2', 'max:120'],
            'delivery_date' => ['required', 'date_format:Y-m-d'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'delivery_notes' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in(['qr', 'cash'])],
            'payment_status' => ['required', Rule::in(array_keys(Order::PAYMENT_STATUSES))],
            'order_status' => ['required', Rule::in(array_keys(Order::ORDER_STATUSES))],
            'delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:' . (int) config('ordering.max_qty', 20)],
            'items.*.protein' => ['nullable', 'string', 'max:40'],
            'items.*.size' => ['nullable', Rule::in(['normal', 'large'])],
            'items.*.egg' => ['nullable', Rule::in(['none', 'fried', 'half-boiled'])],
            'items.*.note' => ['nullable', 'string', 'max:200'],
        ], [
            'customer_phone.regex' => 'Enter a valid phone number (digits only, 7–15 characters, optional leading +).',
            'items.required' => 'Add at least one item to the order.',
        ]);

        // Shape the items like a parsed cart so we can reuse OrderLines.
        $cart = array_map(fn ($line) => [
            'id' => (int) $line['id'],
            'qty' => (int) $line['qty'],
            'note' => $line['note'] ?? '',
            'protein' => $line['protein'] ?? null,
            'size' => ($line['size'] ?? null) === 'large' ? 'large' : null,
            'egg' => in_array($line['egg'] ?? null, ['fried', 'half-boiled'], true) ? $line['egg'] : 'none',
        ], $data['items']);

        $built = OrderLines::build($cart);
        if ($built['missing']) {
            return back()->withInput()->with('error', 'One or more items are no longer available.');
        }

        $settings = $status->settings();

        // Delivery fee: admin override if given, else the standard computation.
        $deliveryFee = $request->filled('delivery_fee')
            ? (int) round(((float) $data['delivery_fee']) * 100)
            : DeliveryFee::compute(
                (int) $settings->default_delivery_fee,
                (int) $built['food_qty'],
                (int) $built['drink_qty'],
                (int) $settings->delivery_fee_cap,
            );

        $total = $built['subtotal'] + $deliveryFee;

        $order = $numbers->createOrder([
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'delivery_dorm' => $data['delivery_dorm'],
            'delivery_date' => $data['delivery_date'],
            'preferred_time' => $data['preferred_time'] ?? null,
            'delivery_notes' => $data['delivery_notes'] ?? null,
            'payment_method' => $data['payment_method'],
            'payment_status' => $data['payment_status'],
            'order_status' => $data['order_status'],
            'subtotal' => $built['subtotal'],
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'notes' => $data['notes'] ?? null,
            'admin_note' => 'Created manually by admin.',
        ], $built['lines']);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Manual order ' . $order->order_number . ' created.');
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
                'remaining' => $customer->ordersUntilNextReward(),
                'available' => $customer->freeDeliveriesAvailable(),
                'has_free_delivery' => $customer->hasFreeDelivery(),
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

    /**
     * Update several orders to one status at once from the orders-list bulk bar.
     * Mirrors the single-order rules: going out for delivery sets a default ETA
     * and texts each newly-dispatched customer; completing/cancelling clears the
     * ETA. SMS sending is best-effort so one failure can't abort the batch.
     */
    public function bulkUpdateStatus(Request $request, SmsService $sms)
    {
        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer'],
            'order_status' => ['required', Rule::in(self::BULK_STATUSES)],
        ]);

        $status = $data['order_status'];
        $orders = Order::whereIn('id', $data['order_ids'])->get();

        $newlyDispatched = [];

        foreach ($orders as $order) {
            $wasOutForDelivery = $order->order_status === 'out_for_delivery';
            $updates = ['order_status' => $status];

            if ($status === 'out_for_delivery') {
                $updates['eta_at'] = now()->addMinutes(20);
            } elseif (in_array($status, ['completed', 'cancelled'], true)) {
                $updates['eta_at'] = null;
            }

            $order->update($updates);

            if ($status === 'out_for_delivery' && ! $wasOutForDelivery) {
                $newlyDispatched[] = $order;
            }
        }

        // Text each customer first sent out for delivery (best-effort).
        $smsSent = 0;
        foreach ($newlyDispatched as $order) {
            try {
                $this->sendOnTheWaySms($order, $sms);
                $smsSent++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Bulk status: on-the-way SMS failed.', [
                    'order_number' => $order->order_number,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        $count = $orders->count();
        $message = 'Updated ' . $count . ' ' . \Illuminate\Support\Str::plural('order', $count)
            . ' to ' . Order::ORDER_STATUSES[$status] . '.';
        if ($smsSent > 0) {
            $message .= ' Sent ' . $smsSent . ' on-the-way ' . \Illuminate\Support\Str::plural('SMS', $smsSent) . '.';
        }

        return back()->with('success', $message);
    }

    /**
     * Set or clear the scheduled delivery time the customer should expect.
     * Stored in eta_at so it drives the same arrival displays. An empty value
     * clears the schedule. Works for both regular orders and preorders.
     */
    public function updateSchedule(Request $request, Order $order)
    {
        $data = $request->validate([
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $scheduledAt = $data['scheduled_at'] ?? null;

        $order->update([
            'eta_at' => $scheduledAt
                ? \Illuminate\Support\Carbon::parse($scheduledAt)
                : null,
        ]);

        return back()->with('success', $order->eta_at
            ? 'Scheduled delivery time saved.'
            : 'Scheduled delivery time cleared.');
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

        // Verifying payment advances a still-pending (or under-review) order to
        // confirmed — so accepting a reviewed order also clears the review flag.
        if ($data['payment_status'] === 'verified' && in_array($order->order_status, ['pending', 'pending_review'], true)) {
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

    /**
     * Permanently delete a single cancelled order (and its items). Only cancelled
     * orders may be removed; anything else is refused so live orders are never
     * lost by mistake. This is a hard delete — there is no recovery.
     */
    public function destroy(Order $order)
    {
        if ($order->order_status !== 'cancelled') {
            return back()->with('error', 'Only cancelled orders can be deleted.');
        }

        $number = $order->order_number;
        $this->hardDeleteOrder($order);

        return redirect()
            ->route('admin.orders.index')
            ->with('success', "Order {$number} permanently deleted.");
    }

    /**
     * Permanently delete the cancelled orders among the selected ids. Orders that
     * are not cancelled are skipped, and the message reports deleted vs skipped.
     */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer'],
        ]);

        $orders = Order::whereIn('id', $data['order_ids'])->get();

        $deleted = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            if ($order->order_status === 'cancelled') {
                $this->hardDeleteOrder($order);
                $deleted++;
            } else {
                $skipped++;
            }
        }

        $message = $deleted . ' ' . \Illuminate\Support\Str::plural('order', $deleted) . ' deleted';
        if ($skipped > 0) {
            $message .= ', ' . $skipped . ' skipped (only cancelled orders can be deleted)';
        }
        $message .= '.';

        return back()->with($deleted > 0 ? 'success' : 'error', $message);
    }

    /**
     * Hard-delete an order: drop its stored payment receipt file, then remove the
     * order and its items together in a transaction.
     */
    protected function hardDeleteOrder(Order $order): void
    {
        if ($order->payment_screenshot_path) {
            $path = public_path($order->payment_screenshot_path);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        DB::transaction(function () use ($order) {
            $order->items()->delete();
            $order->delete();
        });
    }
}
