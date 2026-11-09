@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Orders</h1>
        <a href="{{ route('admin.orders.create') }}" class="btn-primary !py-2 text-sm">+ New order</a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.orders.index') }}" class="card mb-5 p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label class="label">Search</label>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Order #, name, phone" class="input">
            </div>
            <div>
                <label class="label">Status</label>
                <select name="status" class="input">
                    <option value="">All</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Payment</label>
                <select name="payment_method" class="input">
                    <option value="">All</option>
                    <option value="qr" @selected(($filters['payment_method'] ?? '') === 'qr')>QR transfer</option>
                    <option value="cash" @selected(($filters['payment_method'] ?? '') === 'cash')>Cash</option>
                </select>
            </div>
            <div>
                <label class="label">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input">
            </div>
            <div>
                <label class="label">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input">
            </div>
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit" class="btn-primary !py-2">Filter</button>
            <a href="{{ route('admin.orders.index') }}" class="btn-ghost !py-2">Reset</a>
        </div>
    </form>

    <div data-live-refresh="orders">
    @if ($orders->isEmpty())
        <div class="card p-8 text-center text-sm text-ink-soft">No orders match these filters.</div>
    @else
        {{-- Mobile: tap-friendly cards --}}
        <div class="space-y-3 sm:hidden">
            @foreach ($orders as $order)
                <a href="{{ route('admin.orders.show', $order) }}" class="card block p-4 active:bg-brand-50/50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $order->order_number }}</p>
                            <p class="truncate text-sm">{{ $order->customer_name }}</p>
                            <p class="text-xs text-ink-soft">{{ $order->customer_phone }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            @include('admin.partials.status-badge', ['order' => $order])
                            <p class="mt-1 text-xs text-ink-soft">{{ $order->created_at->format('d M, H:i') }}</p>
                        </div>
                    </div>
                    @if ($order->scheduledDeliveryLabel())
                        <p class="mt-2 text-sm"><span class="text-ink-soft">Delivery</span> <span class="font-semibold">🕒 {{ $order->scheduledDeliveryLabel() }}</span></p>
                    @endif
                    <div class="mt-3 flex items-center justify-between gap-3 border-t border-line pt-3 text-sm">
                        <span class="text-ink-soft">{{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }}</span>
                        <span class="flex items-center gap-1.5">
                            @include('admin.partials.payment-badge', ['order' => $order])
                            <span class="font-semibold">{{ config('ordering.currency') }} {{ $order->total_formatted }}</span>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="hidden overflow-x-auto rounded-2xl border border-line bg-surface sm:block">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-2 text-xs uppercase tracking-wide text-ink-soft">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Time</th>
                        <th class="px-4 py-3">Scheduled</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Items</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Payment</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($orders as $order)
                        <tr class="cursor-pointer hover:bg-brand-50/50" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td class="px-4 py-3 font-semibold">{{ $order->order_number }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-soft">{{ $order->created_at->format('d M, H:i') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $order->scheduledDeliveryLabel() ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $order->customer_name }}<span class="block text-xs text-ink-soft">{{ $order->customer_phone }}</span></td>
                            <td class="px-4 py-3">{{ $order->items_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold">{{ config('ordering.currency') }} {{ $order->total_formatted }}</td>
                            <td class="px-4 py-3">@include('admin.partials.payment-badge', ['order' => $order])<span class="ml-1 text-xs text-ink-soft">{{ strtoupper($order->payment_method) }}</span></td>
                            <td class="px-4 py-3">@include('admin.partials.status-badge', ['order' => $order])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
    </div>

    @include('admin.partials.live-feed')
@endsection
