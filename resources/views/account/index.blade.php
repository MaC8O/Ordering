@extends('layouts.storefront')

@section('title', __('My account') . ' — ' . $storeStatus->restaurantName())

@section('content')
    @php
        $statusBadges = [
            'pending' => 'bg-gray-100 text-gray-700',
            'confirmed' => 'bg-blue-100 text-blue-800',
            'preparing' => 'bg-amber-100 text-amber-800',
            'out_for_delivery' => 'bg-teal-100 text-teal-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-700',
        ];
    @endphp

    <div class="mx-auto max-w-3xl">
        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">{{ __('Hi, :name', ['name' => $customer->name]) }}</h1>
                <p class="text-sm text-ink-soft">{{ $customer->phone }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg px-3 py-2 text-sm font-semibold text-ink-soft hover:bg-surface-2 hover:text-red-600">{{ __('Log out') }}</button>
            </form>
        </div>

        {{-- Loyalty card --}}
        @if ($hasFreeDelivery)
            <div class="mb-7 overflow-hidden rounded-2xl bg-gradient-to-br from-green-600 to-emerald-600 p-6 text-white shadow-lg">
                <div class="flex items-center gap-4">
                    <span class="text-4xl">🎉</span>
                    <div>
                        <p class="text-lg font-bold">{{ __('Free delivery ready!') }}</p>
                        <p class="text-sm text-green-50/90">
                            @if (($available ?? 1) > 1)
                                {{ __('You have :count free deliveries to use — one is applied to each next order.', ['count' => $available]) }}
                            @else
                                {{ __('Your next order ships free. The counter then resets for your next reward.') }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @else
            @php $cycleDone = max(0, $threshold - $remaining); @endphp
            <div class="mb-7 rounded-2xl border border-brand-100 bg-brand-50/60 p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-bold">🚚 {{ __('Free delivery reward') }}</p>
                        <p class="mt-1 text-sm text-ink-soft">
                            {{ trans_choice('{1} Just :count more delivered order until a free delivery.|[2,*] Just :count more delivered orders until a free delivery.', $remaining, ['count' => $remaining]) }}
                        </p>
                    </div>
                    <span class="shrink-0 text-sm font-bold text-brand-700">{{ $cycleDone }}/{{ $threshold }}</span>
                </div>
                {{-- Progress toward the next reward --}}
                <div class="mt-4 h-2.5 w-full overflow-hidden rounded-full bg-white">
                    <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-700 transition-all"
                         style="width: {{ $threshold > 0 ? min(100, round($cycleDone / $threshold * 100)) : 0 }}%"></div>
                </div>
            </div>
        @endif

        {{-- Active orders --}}
        @if ($active->isNotEmpty())
            <section class="mb-8">
                <h2 class="mb-3 flex items-center gap-2 text-lg font-bold">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-teal-500"></span>
                    {{ __('In progress') }}
                </h2>
                <div class="space-y-3">
                    @foreach ($active as $order)
                        <a href="{{ route('order.show', $order->public_token) }}"
                           class="card flex items-center justify-between gap-3 p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="min-w-0">
                                <p class="font-bold">{{ $order->order_number }}</p>
                                <p class="truncate text-sm text-ink-soft">
                                    {{ trans_choice('{1} :count item|[2,*] :count items', $order->items->sum('quantity'), ['count' => $order->items->sum('quantity')]) }}
                                    · {{ config('ordering.currency') }} {{ $order->total_formatted }}
                                </p>
                            </div>
                            <span class="badge {{ $statusBadges[$order->order_status] ?? 'bg-gray-100 text-gray-700' }}">{{ __($order->order_status_label) }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- History --}}
        <section>
            <h2 class="mb-3 text-lg font-bold">{{ __('Order history') }}</h2>

            @if ($history->isEmpty())
                <div class="card p-8 text-center text-sm text-ink-soft">
                    <p>{{ __('No past orders yet.') }}</p>
                    <a href="{{ route('menu.index') }}" class="mt-3 inline-block font-semibold text-brand-700 hover:underline">{{ __('Browse the menu') }} →</a>
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-line bg-surface">
                    <ul class="divide-y divide-line">
                        @foreach ($history as $order)
                            <li>
                                <a href="{{ route('order.show', $order->public_token) }}"
                                   class="block px-4 py-4 transition hover:bg-surface-2">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold">{{ $order->order_number }}</span>
                                                <span class="badge {{ $statusBadges[$order->order_status] ?? 'bg-gray-100 text-gray-700' }}">{{ __($order->order_status_label) }}</span>
                                                @if ($order->is_preorder)<span class="badge bg-indigo-100 text-indigo-800">🗓️</span>@endif
                                            </div>
                                            <p class="mt-0.5 text-sm text-ink-soft">
                                                {{ optional($order->delivery_date)->format('D, d M Y') ?? $order->created_at->format('D, d M Y') }}
                                                · {{ trans_choice('{1} :count item|[2,*] :count items', $order->items->sum('quantity'), ['count' => $order->items->sum('quantity')]) }}
                                            </p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="font-bold">{{ config('ordering.currency') }} {{ $order->total_formatted }}</p>
                                            <span class="text-xs text-brand-700">{{ __('View') }} →</span>
                                        </div>
                                    </div>

                                    {{-- The meals in this order --}}
                                    <ul class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach ($order->items as $line)
                                            <li class="inline-flex items-center gap-1 rounded-lg bg-surface-2 px-2 py-1 text-xs text-ink">
                                                <span class="font-semibold text-brand-700">{{ $line->quantity }}×</span>
                                                <span>{{ $line->item_name_snapshot }}</span>
                                                @if ($line->options_snapshot)<span class="text-ink-soft">· {{ $line->options_snapshot }}</span>@endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>
    </div>
@endsection
