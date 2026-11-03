@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Dashboard</h1>

        {{-- Manual accepting-orders override --}}
        <form method="POST" action="{{ route('admin.accepting.toggle') }}">
            @csrf
            <div class="flex items-center gap-3 rounded-xl border border-line bg-surface px-4 py-2">
                <span class="text-sm font-semibold">Accepting orders</span>
                @if ($settings->accepting_orders)
                    <span class="badge bg-green-100 text-green-800">ON</span>
                    <button class="btn-ghost !px-3 !py-1.5 text-sm text-red-600">Turn off</button>
                @else
                    <span class="badge bg-red-100 text-red-800">OFF</span>
                    <button class="btn-primary !px-3 !py-1.5 text-sm">Turn on</button>
                @endif
            </div>
        </form>
    </div>

    @unless ($storeStatus->isOpen())
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <span class="font-semibold">Storefront is currently closed.</span>
            {{ $storeStatus->nextOpeningLabel() ? 'Next scheduled opening: ' . $storeStatus->nextOpeningLabel() . '.' : '' }}
            @unless ($storeStatus->scheduledOpenNow()) You can only switch "Accepting orders" on during a scheduled open window. @endunless
        </div>
    @endunless

    {{-- Stat tiles --}}
    <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card p-5">
            <p class="text-sm text-ink-soft">Today's orders</p>
            <p class="mt-1 text-3xl font-bold">{{ $stats['orders_today'] }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-soft">Today's revenue</p>
            <p class="mt-1 text-3xl font-bold">{{ config('ordering.currency') }} {{ number_format($stats['revenue_today'] / 100, 2) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-soft">Pending payment</p>
            <p class="mt-1 text-3xl font-bold text-amber-600">{{ $stats['pending_payment'] }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-soft">Preparing</p>
            <p class="mt-1 text-3xl font-bold text-brand-600">{{ $stats['preparing'] }}</p>
        </div>
    </div>

    {{-- Today's orders --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold">Today's orders</h2>
        <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">All orders →</a>
    </div>

    @if ($orders->isEmpty())
        <div class="card mt-3 p-8 text-center text-sm text-ink-soft">No orders yet today.</div>
    @else
        <div class="mt-3 overflow-hidden rounded-2xl border border-line bg-surface">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-2 text-xs uppercase tracking-wide text-ink-soft">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Time</th>
                        <th class="hidden px-4 py-3 sm:table-cell">Customer</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Payment</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($orders as $order)
                        <tr class="cursor-pointer hover:bg-brand-50/50" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td class="px-4 py-3 font-semibold">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ $order->created_at->format('H:i') }}</td>
                            <td class="hidden px-4 py-3 sm:table-cell">{{ $order->customer_name }}</td>
                            <td class="px-4 py-3 font-semibold">{{ config('ordering.currency') }} {{ $order->total_formatted }}</td>
                            <td class="px-4 py-3">@include('admin.partials.payment-badge', ['order' => $order])</td>
                            <td class="px-4 py-3">@include('admin.partials.status-badge', ['order' => $order])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
