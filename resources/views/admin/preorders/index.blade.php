@extends('layouts.admin')

@section('title', 'Preorders')

@section('content')
    @php
        $selectedCarbon = \Illuminate\Support\Carbon::parse($selectedDate);
        $currency = config('ordering.currency');
    @endphp

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">Preorder batch</h1>
            <p class="text-sm text-ink-soft">
                Cooking &amp; delivery for <span class="font-semibold text-ink">{{ $selectedCarbon->format('l, d M Y') }}</span>
                · {{ $preorder->timeWindow() }}
            </p>
        </div>
        @unless ($preorder->enabled())
            <span class="badge bg-amber-100 text-amber-800">Preorders are turned off in settings</span>
        @endunless
    </div>

    {{-- Batch date switcher --}}
    @if (count($batchDates) > 1)
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach ($batchDates as $d)
                @php $c = \Illuminate\Support\Carbon::parse($d); @endphp
                <a href="{{ route('admin.preorders.index', ['date' => $d]) }}"
                   class="chip {{ $d === $selectedDate ? 'chip-active' : 'chip-idle' }}">
                    {{ $c->format('D, d M') }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($orders->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-5xl">🗓️</p>
            <h2 class="mt-3 text-lg font-bold">No preorders for this batch yet</h2>
            <p class="mt-1 text-sm text-ink-soft">Orders placed during the collection window will appear here for prep.</p>
        </div>
    @else
        {{-- Stats --}}
        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="card p-4"><p class="text-xs uppercase tracking-wide text-ink-soft">Orders</p><p class="mt-1 text-2xl font-bold">{{ $orders->count() }}</p></div>
            <div class="card p-4"><p class="text-xs uppercase tracking-wide text-ink-soft">Dishes to cook</p><p class="mt-1 text-2xl font-bold">{{ $totalDishes }}</p></div>
            <div class="card p-4"><p class="text-xs uppercase tracking-wide text-ink-soft">Paid</p><p class="mt-1 text-2xl font-bold">{{ $paidCount }}<span class="text-base font-semibold text-ink-soft">/{{ $orders->count() }}</span></p></div>
            <div class="card p-4"><p class="text-xs uppercase tracking-wide text-ink-soft">Revenue</p><p class="mt-1 text-2xl font-bold">{{ $currency }} {{ number_format($revenue / 100, 2) }}</p></div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[22rem_1fr]">
            {{-- Prep list (aggregated) --}}
            <div class="card h-max p-5">
                <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">Kitchen prep list</h2>
                <p class="mt-1 text-xs text-ink-soft">Total of each dish across the whole batch.</p>
                <ul class="mt-3 divide-y divide-line">
                    @foreach ($prep as $name => $qty)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <span class="text-sm font-medium">{{ $name }}</span>
                            <span class="grid h-7 min-w-7 place-items-center rounded-lg bg-brand-50 px-2 text-sm font-bold text-brand-700">{{ $qty }}×</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Orders in this batch --}}
            <div class="card p-5">
                <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">Orders in this batch</h2>
                <div class="mt-3 divide-y divide-line">
                    @foreach ($orders as $order)
                        <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between gap-3 py-3 transition hover:bg-surface-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold">{{ $order->order_number }}</span>
                                    @include('admin.partials.payment-badge', ['order' => $order])
                                </div>
                                <p class="mt-0.5 truncate text-sm text-ink-soft">
                                    {{ $order->customer_name }} · {{ $order->items->sum('quantity') }} {{ \Illuminate\Support\Str::plural('item', $order->items->sum('quantity')) }}
                                    · {{ $order->delivery_dorm }}
                                </p>
                            </div>
                            <span class="shrink-0 font-bold">{{ $currency }} {{ $order->total_formatted }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection
