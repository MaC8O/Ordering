@extends('layouts.storefront')

@section('title', 'Order ' . $order->order_number)

@section('content')
    @php
        $paymentBadge = [
            'pending' => 'bg-amber-100 text-amber-800',
            'verified' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
        ][$order->payment_status] ?? 'bg-gray-100 text-gray-800';
    @endphp

    <div class="mx-auto max-w-2xl">
        <div class="card overflow-hidden">
            <div class="bg-gradient-to-br from-brand-600 to-brand-800 px-6 py-8 text-center text-white">
                <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-full bg-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                </div>
                <h1 class="text-xl font-bold">Order placed!</h1>
                <p class="mt-1 text-brand-50/90">Thank you, {{ $order->customer_name }}.</p>
                <p class="mt-4 inline-block rounded-full bg-white/15 px-4 py-1.5 text-sm">Order number</p>
                <p class="text-2xl font-bold tracking-wide">{{ $order->order_number }}</p>
            </div>

            <div class="p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="badge {{ $paymentBadge }}">Payment: {{ $order->payment_status_label }}</span>
                    <span class="badge bg-brand-100 text-brand-800">{{ $order->order_status_label }}</span>
                </div>

                @if ($order->isOutForDelivery())
                    <div class="mt-4 flex items-center gap-3 rounded-xl bg-gradient-to-br from-teal-600 to-teal-700 p-4 text-white">
                        <span class="text-3xl motion-safe:animate-bounce">🛵</span>
                        <div>
                            <p class="font-bold">On the way!</p>
                            @if ($order->etaLabel())<p class="text-sm text-teal-50/90">Arriving {{ $order->etaLabel() }}.</p>@endif
                        </div>
                        <a href="{{ route('my-order.index') }}" class="ml-auto rounded-lg bg-white/15 px-3 py-1.5 text-sm font-semibold hover:bg-white/25">Track</a>
                    </div>
                @endif

                @if ($order->payment_method === 'cash')
                    <div class="mt-4 rounded-xl border border-brand-100 bg-brand-50/60 p-4 text-sm">
                        Please prepare <span class="font-bold text-brand-700">{{ config('ordering.currency') }} {{ $order->total_formatted }}</span> in cash for the rider.
                    </div>
                @else
                    <div class="mt-4 rounded-xl border border-brand-100 bg-brand-50/60 p-4 text-sm">
                        We’ll verify your transfer shortly. You can track progress on the
                        <a href="{{ route('my-order.index') }}" class="font-semibold text-brand-700 underline">My Order</a> page.
                    </div>
                @endif

                @if ($order->admin_note)
                    <div class="mt-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        <span class="font-semibold">Note from the restaurant:</span> {{ $order->admin_note }}
                    </div>
                @endif

                {{-- Items --}}
                <h2 class="mt-6 text-sm font-bold uppercase tracking-wide text-ink-soft">Items</h2>
                <div class="mt-2 divide-y divide-line">
                    @foreach ($order->items as $line)
                        <div class="flex items-start justify-between gap-3 py-2 text-sm">
                            <span>
                                <span class="font-semibold">{{ $line->quantity }}×</span> {{ $line->item_name_snapshot }}
                                @if ($line->item_note)<span class="block text-xs text-ink-soft">{{ $line->item_note }}</span>@endif
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

                {{-- Delivery --}}
                <h2 class="mt-6 text-sm font-bold uppercase tracking-wide text-ink-soft">Delivery to</h2>
                <p class="mt-1 text-sm">{{ $order->delivery_dorm }}, Room {{ $order->delivery_room }}</p>
                <p class="text-sm text-ink-soft">{{ $order->customer_phone }}</p>
                @if ($order->preferred_time)<p class="text-sm text-ink-soft">Preferred time: {{ \Illuminate\Support\Str::of($order->preferred_time)->substr(0,5) }}</p>@endif
                @if ($order->delivery_notes)<p class="mt-1 text-sm text-ink-soft">“{{ $order->delivery_notes }}”</p>@endif

                @if ($settings->phone)
                    <p class="mt-6 text-sm text-ink-soft">Need help? Call {{ $settings->restaurant_name }} at <a href="tel:{{ $settings->phone }}" class="font-semibold text-brand-700">{{ $settings->phone }}</a>.</p>
                @endif

                <a href="{{ route('menu.index') }}" class="btn-secondary mt-6 w-full">Back to menu</a>
            </div>
        </div>
    </div>

    {{-- Order succeeded: clear the client cart. --}}
    <script>
        try { localStorage.removeItem('ck_cart_v1'); } catch (e) {}
        document.addEventListener('alpine:init', () => {
            if (window.Alpine && Alpine.store('cart')) { Alpine.store('cart').items = []; }
        });
    </script>
@endsection
