@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
    <h1 class="mb-5 text-2xl font-bold">Orders</h1>

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

    @if ($orders->isEmpty())
        <div class="card p-8 text-center text-sm text-ink-soft">No orders match these filters.</div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-black/5 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-black/[0.02] text-xs uppercase tracking-wide text-ink-soft">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Time</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Items</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Payment</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/5">
                    @foreach ($orders as $order)
                        <tr class="cursor-pointer hover:bg-brand-50/50" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td class="px-4 py-3 font-semibold">{{ $order->order_number }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-soft">{{ $order->created_at->format('d M, H:i') }}</td>
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
@endsection
