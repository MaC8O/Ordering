@extends('layouts.storefront')

@section('title', 'My Order — ' . $storeStatus->restaurantName())

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="mb-2 text-2xl font-bold">Track your order</h1>
        <p class="mb-5 text-sm text-ink-soft">Enter your order number and the phone number you used at checkout.</p>

        <form method="POST" action="{{ route('my-order.lookup') }}" class="card p-5">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="order_number" class="label">Order number</label>
                    <input id="order_number" name="order_number" value="{{ old('order_number') }}" placeholder="ORD-20261103-001" class="input" required>
                </div>
                <div>
                    <label for="phone" class="label">Phone number</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" inputmode="tel" placeholder="+60123456789" class="input" required>
                </div>
            </div>
            @error('order_number') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <button type="submit" class="btn-primary mt-4">Look up order</button>
        </form>

        @isset($order)
            @if ($order)
                @php
                    $steps = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'completed'];
                    $currentIndex = array_search($order->order_status, $steps, true);
                    $cancelled = $order->order_status === 'cancelled';
                    $paymentBadge = [
                        'pending' => 'bg-amber-100 text-amber-800',
                        'verified' => 'bg-green-100 text-green-800',
                        'failed' => 'bg-red-100 text-red-800',
                    ][$order->payment_status] ?? 'bg-gray-100 text-gray-800';
                @endphp

                <div class="card mt-6 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm text-ink-soft">Order</p>
                            <p class="text-lg font-bold">{{ $order->order_number }}</p>
                        </div>
                        <span class="badge {{ $paymentBadge }}">Payment: {{ $order->payment_status_label }}</span>
                    </div>

                    @if ($cancelled)
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                            <p class="font-semibold">This order was cancelled.</p>
                            @if ($order->admin_note)<p class="mt-1">{{ $order->admin_note }}</p>@endif
                        </div>
                    @else
                        {{-- Progress tracker --}}
                        <ol class="mt-5 space-y-3">
                            @foreach ($steps as $i => $step)
                                <li class="flex items-center gap-3">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-bold
                                        {{ $i <= $currentIndex ? 'bg-brand-600 text-white' : 'bg-black/10 text-ink-soft' }}">
                                        {{ $i < $currentIndex ? '✓' : $i + 1 }}
                                    </span>
                                    <span class="text-sm {{ $i <= $currentIndex ? 'font-semibold text-ink' : 'text-ink-soft' }}">
                                        {{ \App\Models\Order::ORDER_STATUSES[$step] }}
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    <h2 class="mt-6 text-sm font-bold uppercase tracking-wide text-ink-soft">Items</h2>
                    <div class="mt-2 divide-y divide-black/5">
                        @foreach ($order->items as $line)
                            <div class="flex justify-between gap-3 py-2 text-sm">
                                <span><span class="font-semibold">{{ $line->quantity }}×</span> {{ $line->item_name_snapshot }}</span>
                                <span class="font-semibold">{{ config('ordering.currency') }} {{ $line->line_total_formatted }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex justify-between border-t border-black/5 pt-2 text-base font-bold">
                        <span>Total</span><span>{{ config('ordering.currency') }} {{ $order->total_formatted }}</span>
                    </div>

                    <p class="mt-4 text-sm text-ink-soft">Delivery to {{ $order->delivery_dorm }}, Room {{ $order->delivery_room }}.</p>
                </div>
            @endif
        @endisset
    </div>
@endsection
