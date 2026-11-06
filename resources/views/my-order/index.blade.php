@extends('layouts.storefront')

@section('title', __('My Order') . ' — ' . $storeStatus->restaurantName())

@section('content')
    <div class="mx-auto max-w-2xl">
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

                <div class="mb-5 flex items-center justify-between">
                    <h1 class="text-2xl font-bold">{{ __('Your order') }}</h1>
                    <a href="{{ route('my-order.index', ['stop' => 1]) }}" class="text-sm font-semibold text-ink-soft hover:text-brand-700">{{ __('Track another') }} →</a>
                </div>

                {{-- On-the-way banner --}}
                @if ($order->isOutForDelivery())
                    <div class="mb-5 overflow-hidden rounded-2xl bg-gradient-to-br from-teal-600 to-teal-700 p-5 text-white shadow-lg">
                        <div class="flex items-center gap-4">
                            <span class="text-4xl motion-safe:animate-bounce">🛵</span>
                            <div>
                                <p class="text-lg font-bold">{{ __('Your order is on the way!') }}</p>
                                @if ($order->etaLabel())
                                    <p class="text-teal-50/90">{{ __('Arriving :eta.', ['eta' => $order->etaLabel()]) }}</p>
                                @else
                                    <p class="text-teal-50/90">{{ __('The rider is heading to :place now.', ['place' => $order->delivery_dorm]) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif ($order->order_status === 'completed')
                    <div class="mb-5 rounded-2xl bg-green-600 p-5 text-white shadow">
                        <p class="text-lg font-bold">✅ {{ __('Delivered — enjoy your meal!') }}</p>
                    </div>
                @endif

                <div class="card p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm text-ink-soft">{{ __('Order') }}</p>
                            <p class="text-lg font-bold">{{ $order->order_number }}</p>
                        </div>
                        <span class="badge {{ $paymentBadge }}">{{ __('Payment') }}: {{ __($order->payment_status_label) }}</span>
                    </div>

                    @if ($cancelled)
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                            <p class="font-semibold">{{ __('This order was cancelled.') }}</p>
                            @if ($order->admin_note)<p class="mt-1">{{ $order->admin_note }}</p>@endif
                        </div>
                    @else
                        {{-- Progress tracker --}}
                        @php
                            $stepIcons = ['pending' => '🧾', 'confirmed' => '👍', 'preparing' => '🍳', 'out_for_delivery' => '🛵', 'completed' => '🎉'];
                            $pct = count($steps) > 1 ? max(0, $currentIndex) / (count($steps) - 1) * 100 : 0;
                        @endphp
                        <div class="relative mt-7">
                            {{-- connecting track --}}
                            <div class="absolute left-[10%] right-[10%] top-6 h-1.5 rounded-full bg-surface-2"></div>
                            <div class="absolute left-[10%] top-6 h-1.5 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 transition-all duration-500"
                                 style="width: calc(80% * {{ $pct / 100 }})"></div>

                            <ol class="relative flex justify-between">
                                @foreach ($steps as $i => $step)
                                    @php $state = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : 'todo'); @endphp
                                    <li class="flex w-1/5 flex-col items-center text-center">
                                        <span class="grid h-12 w-12 place-items-center rounded-full border-2 bg-surface text-xl transition
                                            @class([
                                                'border-brand-600 bg-brand-600' => $state === 'done',
                                                'border-brand-600 text-brand-700 ring-4 ring-brand-100' => $state === 'current',
                                                'border-line text-ink-soft grayscale' => $state === 'todo',
                                            ])
                                            {{ $state === 'current' && ! $order->isDone() ? 'motion-safe:animate-pulse' : '' }}">
                                            @if ($state === 'done')<span class="text-white">✓</span>@else {{ $stepIcons[$step] }} @endif
                                        </span>
                                        <span class="mt-2 text-[11px] leading-tight sm:text-xs {{ $i <= $currentIndex ? 'font-semibold text-ink' : 'text-ink-soft' }}">
                                            {{ __(\App\Models\Order::ORDER_STATUSES[$step]) }}
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        @if ($order->isOutForDelivery() && $order->etaLabel())
                            <p class="mt-5 rounded-xl bg-teal-50 px-4 py-2.5 text-center text-sm font-semibold text-teal-700">🛵 {{ __('Arriving :eta.', ['eta' => $order->etaLabel()]) }}</p>
                        @endif

                        @unless ($order->isDone())
                            <p class="mt-4 flex items-center justify-center gap-2 text-xs text-ink-soft">
                                <span class="h-2 w-2 animate-pulse rounded-full bg-teal-500"></span>
                                {{ __('This page updates automatically.') }}
                            </p>
                        @endunless
                    @endif

                    <h2 class="mt-6 text-sm font-bold uppercase tracking-wide text-ink-soft">{{ __('Items') }}</h2>
                    <div class="mt-2 divide-y divide-line">
                        @foreach ($order->items as $line)
                            <div class="flex justify-between gap-3 py-2 text-sm">
                                <span>
                                    <span class="font-semibold">{{ $line->quantity }}×</span> {{ $line->item_name_snapshot }}
                                    @if ($line->options_snapshot)<span class="block text-xs font-medium text-brand-700">{{ $line->options_snapshot }}</span>@endif
                                    @if ($line->item_note)<span class="block text-xs text-ink-soft">{{ $line->item_note }}</span>@endif
                                </span>
                                <span class="font-semibold">{{ config('ordering.currency') }} {{ $line->line_total_formatted }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex justify-between border-t border-line pt-2 text-base font-bold">
                        <span>{{ __('Total') }}</span><span>{{ config('ordering.currency') }} {{ $order->total_formatted }}</span>
                    </div>

                    <p class="mt-4 text-sm text-ink-soft">{{ __('Delivery to') }} {{ $order->delivery_dorm }}@if ($order->delivery_date) · {{ $order->delivery_date->format('D, d M Y') }}@endif.</p>
                </div>

                {{-- Auto-refresh while the order is still in progress --}}
                @unless ($order->isDone())
                    <script>
                        setTimeout(() => window.location.reload(), 20000);
                    </script>
                @endunless
            @endif
        @else
            <h1 class="mb-5 text-2xl font-bold">{{ __('My Orders') }}</h1>

            <div x-data="myOrders()" x-init="load()">
                {{-- Orders remembered on this device — tap to track, no typing. --}}
                <div x-show="orders.length" x-cloak class="mb-7">
                    <p class="mb-3 text-sm text-ink-soft">{{ __('Tap an order to see its status.') }}</p>
                    <div class="space-y-3">
                        <template x-for="o in orders" :key="o.number">
                            <div class="card flex items-center gap-2 p-4">
                                <button type="button" @click="track(o)" class="flex flex-1 items-center justify-between gap-3 text-left">
                                    <span>
                                        <span class="block font-bold" x-text="o.number"></span>
                                        <span class="block text-sm text-ink-soft"><span x-text="o.name"></span> · {{ config('ordering.currency') }} <span x-text="o.total"></span></span>
                                    </span>
                                    <span class="text-xl text-brand-700">→</span>
                                </button>
                                <button type="button" @click="forget(o.number)" title="{{ __('Remove') }}"
                                        class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-ink-soft hover:bg-surface-2 hover:text-red-600">✕</button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Manual lookup (different device, or a cleared list). --}}
                <h2 class="mb-2 text-lg font-bold">
                    <span x-show="orders.length" x-cloak>{{ __('Track a different order') }}</span>
                    <span x-show="!orders.length">{{ __('Track your order') }}</span>
                </h2>
                <p class="mb-4 text-sm text-ink-soft">{{ __('Enter your order number and the phone number you used at checkout.') }}</p>

                <form method="POST" action="{{ route('my-order.lookup') }}" class="card p-5">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="order_number" class="label">{{ __('Order number') }}</label>
                            <input id="order_number" name="order_number" value="{{ old('order_number') }}" placeholder="ORD-20261103-001" class="input" required>
                        </div>
                        <div>
                            <label for="phone" class="label">{{ __('Phone number') }}</label>
                            <input id="phone" name="phone" value="{{ old('phone') }}" inputmode="tel" placeholder="+60123456789" class="input" required>
                        </div>
                    </div>
                    @error('order_number') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" class="btn-primary mt-4">{{ __('Look up order') }}</button>
                </form>

                {{-- Hidden form used to track a remembered order in one tap. --}}
                <form x-ref="trackForm" method="POST" action="{{ route('my-order.lookup') }}" class="hidden">
                    @csrf
                    <input type="hidden" name="order_number" x-ref="tnum">
                    <input type="hidden" name="phone" x-ref="tphone">
                </form>
            </div>

            <script>
                function myOrders() {
                    return {
                        orders: [],
                        load() {
                            try { this.orders = JSON.parse(localStorage.getItem('ck_orders_v1') || '[]') || []; }
                            catch (e) { this.orders = []; }
                        },
                        track(o) {
                            this.$refs.tnum.value = o.number;
                            this.$refs.tphone.value = o.phone;
                            this.$refs.trackForm.submit();
                        },
                        forget(num) {
                            this.orders = this.orders.filter((o) => o.number !== num);
                            try { localStorage.setItem('ck_orders_v1', JSON.stringify(this.orders)); } catch (e) {}
                        },
                    };
                }
            </script>
        @endisset
    </div>
@endsection
