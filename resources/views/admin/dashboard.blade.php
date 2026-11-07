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

    {{-- Today snapshot tiles --}}
    <div class="mb-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
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
                <p class="text-2xl font-bold sm:text-3xl">{{ config('ordering.currency') }} {{ number_format($stats['revenue_today'] / 100, 2) }}</p>
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

    {{-- Sales breakdown --}}
    @php($cur = config('ordering.currency'))
    <div class="mb-8">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold">Sales breakdown</h2>
            {{-- Period selector --}}
            <div class="inline-flex rounded-xl border border-line bg-surface p-1 text-sm">
                @foreach ($report['periods'] as $key => $label)
                    <a href="{{ route('admin.dashboard', ['period' => $key]) }}"
                       class="rounded-lg px-3 py-1.5 font-semibold transition {{ $report['period'] === $key ? 'bg-brand-600 text-white shadow' : 'text-ink-soft hover:bg-hover' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        {{-- Headline totals for the selected period --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card p-5">
                <p class="text-sm text-ink-soft">Total revenue</p>
                <p class="mt-1 text-3xl font-bold">{{ $cur }} {{ number_format($report['totals']->total / 100, 2) }}</p>
                <p class="mt-1 text-xs text-ink-soft">{{ $report['periodLabel'] }} · excludes cancelled</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-ink-soft">Orders</p>
                <p class="mt-1 text-3xl font-bold">{{ number_format($report['totals']->orders) }}</p>
                <p class="mt-1 text-xs text-ink-soft">{{ number_format($report['itemsSold']) }} items sold</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-ink-soft">Food vs delivery</p>
                <p class="mt-1 text-xl font-bold">{{ $cur }} {{ number_format($report['totals']->food / 100, 2) }}</p>
                <p class="mt-1 text-xs text-ink-soft">+ {{ $cur }} {{ number_format($report['totals']->delivery / 100, 2) }} delivery</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-ink-soft">Paid vs unpaid</p>
                <p class="mt-1 text-xl font-bold text-green-600">{{ $cur }} {{ number_format($report['totals']->paid / 100, 2) }}</p>
                <p class="mt-1 text-xs text-amber-600">{{ $cur }} {{ number_format($report['totals']->unpaid / 100, 2) }} awaiting payment</p>
            </div>
        </div>

        {{-- Daily revenue chart --}}
        <div class="mt-4 overflow-hidden rounded-2xl border border-line bg-surface">
            <div class="flex items-center justify-between border-b border-line bg-surface-2 px-4 py-3">
                <span class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Daily revenue · last {{ count($report['daily']) }} days</span>
                <span class="text-xs text-ink-soft">{{ $cur }} {{ number_format($report['dailyTotal'] / 100, 2) }} total</span>
            </div>
            <div class="px-3 py-4 sm:px-5">
                <div class="flex h-44 items-end gap-1 sm:gap-1.5">
                    @foreach ($report['daily'] as $d)
                        @php($h = $d['revenue'] > 0 ? max(4, round($d['revenue'] / $report['dailyMax'] * 100)) : 0)
                        <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                            {{-- tooltip --}}
                            <span class="pointer-events-none absolute -top-1 z-10 -translate-y-full whitespace-nowrap rounded-lg bg-ink px-2 py-1 text-[11px] font-semibold text-white opacity-0 shadow transition group-hover:opacity-100">
                                {{ $cur }} {{ number_format($d['revenue'] / 100, 2) }}
                            </span>
                            @if ($h > 0)
                                <div class="w-full rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400 transition-all duration-300 group-hover:from-brand-700 group-hover:to-brand-500" style="height: {{ $h }}%"></div>
                            @else
                                <div class="h-1 w-full rounded-full bg-surface-2"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
                {{-- x-axis labels (thinned on small screens) --}}
                <div class="mt-2 flex gap-1 sm:gap-1.5">
                    @foreach ($report['daily'] as $i => $d)
                        <div class="flex-1 text-center text-[10px] leading-tight text-ink-soft {{ (count($report['daily']) > 10 && $i % 2 !== 0) ? 'hidden sm:block' : '' }}">{{ $d['label'] }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            {{-- Revenue by category --}}
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

            {{-- Top items --}}
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
