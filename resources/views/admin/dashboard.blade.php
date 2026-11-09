@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @php($cur = config('ordering.currency'))

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

    {{-- ============ 1. DELIVERY EARNINGS — top priority ============ --}}
    <h2 class="mb-3 text-lg font-bold">🛵 Delivery earnings</h2>
    <div data-live-refresh="delivery-stats" class="mb-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <div class="card p-4 sm:p-5">
            <p class="text-xs font-medium text-ink-soft sm:text-sm">Delivery fees — today</p>
            <p class="mt-1 text-2xl font-bold text-teal-600 sm:text-3xl">{{ $cur }} {{ number_format($delivery['today'] / 100, 2) }}</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-xs font-medium text-ink-soft sm:text-sm">Delivery fees — this week</p>
            <p class="mt-1 text-2xl font-bold text-teal-600 sm:text-3xl">{{ $cur }} {{ number_format($delivery['week'] / 100, 2) }}</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-xs font-medium text-ink-soft sm:text-sm">Delivery fees — this month</p>
            <p class="mt-1 text-2xl font-bold text-teal-600 sm:text-3xl">{{ $cur }} {{ number_format($delivery['month'] / 100, 2) }}</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-xs font-medium text-ink-soft sm:text-sm">Deliveries completed today</p>
            <p class="mt-1 text-2xl font-bold sm:text-3xl">{{ $delivery['completed_today'] }}</p>
        </div>
    </div>

    {{-- ============ 2. REVENUE BREAKDOWN — food vs delivery ============ --}}
    <div class="mb-8">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold">Revenue breakdown</h2>
            {{-- Period selector --}}
            <div class="inline-flex rounded-xl border border-line bg-surface p-1 text-sm">
                @foreach ($report['periods'] as $key => $label)
                    <a href="{{ route('admin.dashboard', ['period' => $key]) }}"
                       class="rounded-lg px-3 py-1.5 font-semibold transition {{ $report['period'] === $key ? 'bg-brand-600 text-white shadow' : 'text-ink-soft hover:bg-hover' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        {{-- Food vs delivery vs combined --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="card p-5">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-brand-500"></span>
                    <p class="text-sm text-ink-soft">Food revenue</p>
                </div>
                <p class="mt-1 text-2xl font-bold">{{ $cur }} {{ number_format($report['totals']->food / 100, 2) }}</p>
                <p class="mt-1 text-xs text-ink-soft">Subtotal of items sold · {{ $report['periodLabel'] }}</p>
            </div>
            <div class="card p-5">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-teal-500"></span>
                    <p class="text-sm text-ink-soft">Delivery revenue</p>
                </div>
                <p class="mt-1 text-2xl font-bold text-teal-600">{{ $cur }} {{ number_format($report['totals']->delivery / 100, 2) }}</p>
                <p class="mt-1 text-xs text-ink-soft">Delivery fees collected · {{ $report['periodLabel'] }}</p>
            </div>
            <div class="card bg-ink p-5 text-white">
                <p class="text-sm text-white/70">Total combined revenue</p>
                <p class="mt-1 text-2xl font-bold">{{ $cur }} {{ number_format($report['totals']->total / 100, 2) }}</p>
                <p class="mt-1 text-xs text-white/70">{{ number_format($report['totals']->orders) }} orders · excludes cancelled</p>
            </div>
        </div>

        {{-- Daily food vs delivery chart (two bars per day) --}}
        <div class="mt-4 overflow-hidden rounded-2xl border border-line bg-surface">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line bg-surface-2 px-4 py-3">
                <span class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Daily revenue · last {{ count($report['daily']) }} days</span>
                <div class="flex items-center gap-4 text-xs text-ink-soft">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-brand-500"></span> Food</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-teal-500"></span> Delivery</span>
                </div>
            </div>
            <div class="px-3 py-4 sm:px-5">
                <div class="flex h-44 items-end gap-1 sm:gap-1.5">
                    @foreach ($report['daily'] as $d)
                        @php($fh = $d['food'] > 0 ? max(3, round($d['food'] / $report['dailyBarMax'] * 100)) : 0)
                        @php($dh = $d['delivery'] > 0 ? max(3, round($d['delivery'] / $report['dailyBarMax'] * 100)) : 0)
                        <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                            <span class="pointer-events-none absolute -top-1 z-10 -translate-y-full whitespace-nowrap rounded-lg bg-ink px-2 py-1 text-[11px] font-semibold text-white opacity-0 shadow transition group-hover:opacity-100">
                                🍽️ {{ $cur }} {{ number_format($d['food'] / 100, 2) }} · 🛵 {{ $cur }} {{ number_format($d['delivery'] / 100, 2) }}
                            </span>
                            <div class="flex h-full w-full items-end justify-center gap-0.5">
                                @if ($fh > 0)
                                    <div class="w-1/2 max-w-[0.65rem] rounded-t bg-gradient-to-t from-brand-600 to-brand-400 transition-all duration-300" style="height: {{ $fh }}%"></div>
                                @else
                                    <div class="h-1 w-1/2 max-w-[0.65rem] rounded-full bg-surface-2"></div>
                                @endif
                                @if ($dh > 0)
                                    <div class="w-1/2 max-w-[0.65rem] rounded-t bg-gradient-to-t from-teal-600 to-teal-400 transition-all duration-300" style="height: {{ $dh }}%"></div>
                                @else
                                    <div class="h-1 w-1/2 max-w-[0.65rem] rounded-full bg-surface-2"></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-2 flex gap-1 sm:gap-1.5">
                    @foreach ($report['daily'] as $i => $d)
                        <div class="flex-1 text-center text-[10px] leading-tight text-ink-soft {{ (count($report['daily']) > 10 && $i % 2 !== 0) ? 'hidden sm:block' : '' }}">{{ $d['label'] }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Revenue by category + top items (unchanged) --}}
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="overflow-hidden rounded-2xl border border-line bg-surface">
                <div class="border-b border-line bg-surface-2 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-ink-soft">Revenue by category</div>
                @if ($report['byCategory']->isEmpty())
                    <div class="p-6 text-center text-sm text-ink-soft">No sales in this period.</div>
                @else
                    @php($catMax = max(1, $report['byCategory']->max('revenue')))
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase tracking-wide text-ink-soft">
                            <tr>
                                <th class="px-4 py-2">Category</th>
                                <th class="px-4 py-2 text-right">Sold</th>
                                <th class="px-4 py-2 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($report['byCategory'] as $row)
                                <tr>
                                    <td class="px-4 py-2.5">
                                        <div class="font-semibold">{{ $row->category }}</div>
                                        <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-surface-2">
                                            <div class="h-full rounded-full bg-brand-500" style="width: {{ round($row->revenue / $catMax * 100) }}%"></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-ink-soft">{{ number_format($row->qty) }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold">{{ $cur }} {{ number_format($row->revenue / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl border border-line bg-surface">
                <div class="border-b border-line bg-surface-2 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-ink-soft">Top-selling items</div>
                @if ($report['topItems']->isEmpty())
                    <div class="p-6 text-center text-sm text-ink-soft">No sales in this period.</div>
                @else
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase tracking-wide text-ink-soft">
                            <tr>
                                <th class="px-4 py-2">Item</th>
                                <th class="px-4 py-2 text-right">Sold</th>
                                <th class="px-4 py-2 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($report['topItems'] as $row)
                                <tr>
                                    <td class="px-4 py-2.5 font-semibold">{{ $row->name }}</td>
                                    <td class="px-4 py-2.5 text-right text-ink-soft">{{ number_format($row->qty) }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold">{{ $cur }} {{ number_format($row->revenue / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- ============ 3. DELIVERY PERFORMANCE ============ --}}
    <h2 class="mb-3 text-lg font-bold">Delivery performance</h2>
    <div data-live-refresh="delivery-performance" class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <p class="text-sm text-ink-soft">Average delivery fee / order</p>
            <p class="mt-1 text-2xl font-bold text-teal-600">{{ $cur }} {{ number_format($delivery['avg_fee'] / 100, 2) }}</p>
            <p class="mt-1 text-xs text-ink-soft">Across today's orders</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-soft">Most popular hall today</p>
            @if ($delivery['popular_dorm'])
                <p class="mt-1 truncate text-2xl font-bold" title="{{ $delivery['popular_dorm'] }}">{{ $delivery['popular_dorm'] }}</p>
                <p class="mt-1 text-xs text-ink-soft">{{ $delivery['popular_dorm_count'] }} {{ \Illuminate\Support\Str::plural('order', $delivery['popular_dorm_count']) }} today</p>
            @else
                <p class="mt-1 text-2xl font-bold text-ink-soft">—</p>
                <p class="mt-1 text-xs text-ink-soft">No orders yet today</p>
            @endif
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-soft">Pending delivery right now</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ $delivery['pending_now'] }}</p>
            <p class="mt-1 text-xs text-ink-soft">Confirmed · preparing · out for delivery</p>
        </div>
    </div>

    {{-- ============ 4. ORDER STATUS SUMMARY (today) ============ --}}
    <h2 class="mb-3 text-lg font-bold">Today at a glance</h2>
    <div data-live-refresh="tiles" class="mb-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <div class="card flex items-center gap-3 p-4 sm:p-5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12A1.125 1.125 0 0 1 19.743 21H4.257a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-xs font-medium text-ink-soft sm:text-sm">Today's orders</p>
                <p class="text-2xl font-bold sm:text-3xl">{{ $stats['orders_today'] }}</p>
            </div>
        </div>
        <div class="card flex items-center gap-3 p-4 sm:p-5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-green-50 text-green-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-xs font-medium text-ink-soft sm:text-sm">Today's revenue</p>
                <p class="text-2xl font-bold sm:text-3xl">{{ $cur }} {{ number_format($stats['revenue_today'] / 100, 2) }}</p>
            </div>
        </div>
        <div class="card flex items-center gap-3 p-4 sm:p-5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-xs font-medium text-ink-soft sm:text-sm">Pending payment</p>
                <p class="text-2xl font-bold text-amber-600 sm:text-3xl">{{ $stats['pending_payment'] }}</p>
            </div>
        </div>
        <div class="card flex items-center gap-3 p-4 sm:p-5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-xs font-medium text-ink-soft sm:text-sm">Preparing</p>
                <p class="text-2xl font-bold text-indigo-600 sm:text-3xl">{{ $stats['preparing'] }}</p>
            </div>
        </div>
    </div>

    {{-- Today's orders --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold">Today's orders</h2>
        <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">All orders →</a>
    </div>

    <div data-live-refresh="today-orders">
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
                        <th class="px-4 py-3">Delivery</th>
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
                            <td class="whitespace-nowrap px-4 py-3 text-teal-700">{{ $cur }} {{ $order->delivery_fee_formatted }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $cur }} {{ $order->total_formatted }}</td>
                            <td class="px-4 py-3">@include('admin.partials.payment-badge', ['order' => $order])</td>
                            <td class="px-4 py-3">@include('admin.partials.status-badge', ['order' => $order])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    </div>

    @include('admin.partials.live-feed')
@endsection
