@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number)

@section('content')
    <a href="{{ route('admin.orders.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-ink-soft hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        All orders
    </a>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">{{ $order->order_number }}</h1>
            <p class="text-sm text-ink-soft">Placed {{ $order->created_at->format('d M Y, H:i') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @include('admin.partials.payment-badge', ['order' => $order])
            @include('admin.partials.status-badge', ['order' => $order])
            <a href="{{ route('admin.orders.ticket', $order) }}" target="_blank" class="btn-secondary !py-2 text-sm">Print ticket</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        {{-- Left: items + customer --}}
        <div class="space-y-6">
            <div class="card p-5">
                <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">Items</h2>
                <div class="mt-3 divide-y divide-line">
                    @foreach ($order->items as $line)
                        <div class="flex items-start justify-between gap-3 py-2 text-sm">
                            <span>
                                <span class="font-semibold">{{ $line->quantity }}×</span> {{ $line->item_name_snapshot }}
                                <span class="block text-xs text-ink-soft">{{ config('ordering.currency') }} {{ $line->item_price_formatted }} each</span>
                                @if ($line->item_note)<span class="block text-xs italic text-ink-soft">“{{ $line->item_note }}”</span>@endif
                            </span>
                            <span class="whitespace-nowrap font-semibold">{{ config('ordering.currency') }} {{ $line->line_total_formatted }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 space-y-1 border-t border-line pt-3 text-sm">
                    <div class="flex justify-between"><span class="text-ink-soft">Subtotal</span><span>{{ config('ordering.currency') }} {{ $order->subtotal_formatted }}</span></div>
                    <div class="flex justify-between"><span class="text-ink-soft">Delivery fee</span><span>{{ config('ordering.currency') }} {{ $order->delivery_fee_formatted }}</span></div>
                    <div class="flex justify-between text-base font-bold"><span>Total</span><span>{{ config('ordering.currency') }} {{ $order->total_formatted }}</span></div>
                </div>
            </div>

            <div class="card p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">Customer & delivery</h2>
                    @if ($loyalty)
                        @if ($loyalty['has_free_delivery'])
                            <span class="badge bg-green-100 text-green-800">🎉 Free delivery · {{ $loyalty['delivered'] }} delivered</span>
                        @else
                            <span class="badge bg-brand-100 text-brand-700">★ Member · {{ $loyalty['delivered'] }}/{{ $loyalty['threshold'] }} to free</span>
                        @endif
                    @else
                        <span class="badge bg-gray-100 text-gray-600">Guest</span>
                    @endif
                </div>

                @if ($loyalty)
                    <div class="mt-3 rounded-xl border p-3 text-sm {{ $loyalty['has_free_delivery'] ? 'border-green-200 bg-green-50' : 'border-brand-100 bg-brand-50/60' }}">
                        @if ($loyalty['has_free_delivery'])
                            <p class="font-semibold text-green-800">This customer qualifies for free delivery on every order.</p>
                        @else
                            <p class="font-semibold text-ink">{{ $loyalty['remaining'] }} more delivered {{ \Illuminate\Support\Str::plural('order', $loyalty['remaining']) }} until free delivery unlocks.</p>
                            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-white">
                                <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-700"
                                     style="width: {{ $loyalty['threshold'] > 0 ? min(100, round($loyalty['delivered'] / $loyalty['threshold'] * 100)) : 0 }}%"></div>
                            </div>
                        @endif
                        <p class="mt-2 text-xs text-ink-soft">
                            {{ $loyalty['delivered'] }} delivered · {{ $loyalty['total_orders'] }} total {{ \Illuminate\Support\Str::plural('order', $loyalty['total_orders']) }}
                            @unless ($loyalty['linked']) · <span class="italic">matched by phone number</span>@endunless
                        </p>
                    </div>
                @endif

                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-soft">Name</dt><dd class="font-semibold">{{ $order->customer_name }}</dd></div>
                    <div><dt class="text-ink-soft">Phone</dt><dd class="font-semibold"><a href="tel:{{ $order->customer_phone }}" class="text-brand-700">{{ $order->customer_phone }}</a></dd></div>
                    <div class="sm:col-span-2"><dt class="text-ink-soft">Dorm / building / address</dt><dd class="font-semibold">{{ $order->delivery_dorm }}</dd></div>
                    @if ($order->delivery_date)<div><dt class="text-ink-soft">Delivery date</dt><dd class="font-semibold">{{ $order->delivery_date->format('D, d M Y') }}</dd></div>@endif
                    @if ($order->preferred_time)<div><dt class="text-ink-soft">Preferred time</dt><dd class="font-semibold">{{ \Illuminate\Support\Str::of($order->preferred_time)->substr(0,5) }}</dd></div>@endif
                </dl>
                @if ($order->delivery_notes)<p class="mt-3 rounded-lg bg-surface-2 p-3 text-sm"><span class="text-ink-soft">Delivery notes:</span> {{ $order->delivery_notes }}</p>@endif
                @if ($order->notes)<p class="mt-2 rounded-lg bg-surface-2 p-3 text-sm"><span class="text-ink-soft">Order notes:</span> {{ $order->notes }}</p>@endif
            </div>
        </div>

        {{-- Right: actions --}}
        <div class="space-y-6">
            {{-- Payment --}}
            <div class="card p-5" x-data="{ lightbox: false }">
                <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">Payment</h2>
                <p class="mt-2 text-sm">Method: <span class="font-semibold">{{ $order->payment_method === 'qr' ? 'QR bank transfer' : 'Cash on delivery' }}</span></p>

                @if ($order->payment_method === 'qr')
                    @if ($order->payment_screenshot_path && file_exists(public_path($order->payment_screenshot_path)))
                        <button type="button" @click="lightbox = true" class="mt-3 block w-full overflow-hidden rounded-xl border">
                            <img src="{{ asset($order->payment_screenshot_path) }}" alt="Payment screenshot" class="max-h-56 w-full object-contain bg-surface-2">
                        </button>
                        <p class="mt-1 text-xs text-ink-soft">Tap to view full size.</p>

                        <div x-show="lightbox" x-cloak @click="lightbox = false" @keydown.escape.window="lightbox = false"
                             class="fixed inset-0 z-50 grid place-items-center bg-black/80 p-4">
                            <img src="{{ asset($order->payment_screenshot_path) }}" alt="Payment screenshot" class="max-h-[90vh] max-w-full rounded-lg">
                        </div>
                    @else
                        <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">No screenshot uploaded.</p>
                    @endif
                @else
                    <p class="mt-3 rounded-lg bg-surface-2 p-3 text-sm">Collect {{ config('ordering.currency') }} {{ $order->total_formatted }} in cash on delivery.</p>
                @endif

                <form method="POST" action="{{ route('admin.orders.payment', $order) }}" class="mt-4 space-y-2">
                    @csrf @method('PATCH')
                    <input type="text" name="admin_note" maxlength="200" value="{{ $order->admin_note }}" placeholder="Note for customer (optional)" class="input !py-2 text-sm">
                    <div class="flex flex-wrap gap-2">
                        <button name="payment_status" value="verified" class="btn-primary !py-2 text-sm">Mark verified</button>
                        <button name="payment_status" value="pending" class="btn-secondary !py-2 text-sm">Set pending</button>
                        <button name="payment_status" value="failed" class="btn-ghost !py-2 text-sm text-red-600">Mark failed</button>
                    </div>
                </form>
                <p class="mt-2 text-xs text-ink-soft">Verifying a pending order moves it to Confirmed. Marking failed cancels it.</p>
            </div>

            {{-- Status --}}
            <div class="card p-5" x-data="{ status: @js($order->order_status) }">
                <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">Order status</h2>

                @if ($order->isOutForDelivery() && $order->eta_at)
                    <p class="mt-2 rounded-lg bg-teal-50 px-3 py-2 text-sm font-semibold text-teal-700">
                        🛵 Out for delivery — ETA {{ $order->etaLabel() }}
                    </p>
                @endif

                <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-3 space-y-2">
                    @csrf @method('PATCH')
                    <div class="flex gap-2">
                        <select name="order_status" x-model="status" class="input !py-2 text-sm">
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}" @selected($order->order_status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="btn-primary !py-2 text-sm">Update</button>
                    </div>
                    {{-- ETA only matters when sending it out for delivery --}}
                    <label x-show="status === 'out_for_delivery'" x-cloak class="flex items-center gap-2 text-sm text-ink-soft">
                        Arriving in
                        <input type="number" name="eta_minutes" min="1" max="240" value="{{ $order->etaMinutesLeft() ?? 20 }}" class="input !w-20 !py-1.5 text-center">
                        minutes
                    </label>
                    <p x-show="status === 'out_for_delivery' && '{{ $order->order_status }}' !== 'out_for_delivery'" x-cloak class="flex items-center gap-1.5 text-xs text-teal-700">
                        <span>📲</span> The customer gets an SMS with the arrival time when you send this out.
                    </p>
                </form>

                @if ($order->order_status !== 'cancelled')
                    <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="mt-3"
                          onsubmit="return confirm('Cancel this order?')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="admin_note" value="Cancelled by the restaurant.">
                        <button class="btn-ghost !py-2 text-sm text-red-600">Cancel order</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
