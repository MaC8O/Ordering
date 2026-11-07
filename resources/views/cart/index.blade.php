@extends('layouts.storefront')

@section('title', __('Your cart') . ' — ' . $storeStatus->restaurantName())

@section('content')
    <h1 class="mb-5 text-2xl font-bold">{{ __('Your cart') }}</h1>

    <div x-data x-cloak>
        {{-- Empty state --}}
        <template x-if="$store.cart.items.length === 0">
            <div class="card p-10 text-center">
                <p class="text-5xl">🛒</p>
                <h2 class="mt-3 text-lg font-bold">{{ __('Your cart is empty') }}</h2>
                <p class="mt-1 text-sm text-ink-soft">{{ __('Add something tasty from the menu to get started.') }}</p>
                <a href="{{ route('menu.index') }}" class="btn-primary mt-5">{{ __('Browse the menu') }}</a>
            </div>
        </template>

        {{-- Line items --}}
        <template x-if="$store.cart.items.length > 0">
            <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
                <div class="space-y-3">
                    <template x-for="line in $store.cart.items" :key="line.key">
                        <div class="card flex gap-3 p-3">
                            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-brand-50">
                                <template x-if="line.image">
                                    <img :src="line.image" :alt="line.name" class="h-full w-full object-cover">
                                </template>
                                <template x-if="!line.image">
                                    <div class="grid h-full w-full place-items-center bg-gradient-to-br from-brand-100 to-brand-300 text-2xl">🍴</div>
                                </template>
                            </div>

                            <div class="flex flex-1 flex-col">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-semibold leading-snug" x-text="line.name"></p>
                                        <div x-show="line.protein || line.size === 'large' || (line.egg && line.egg !== 'none')" x-cloak class="mt-1 flex flex-wrap gap-1">
                                            <template x-if="line.protein"><span class="rounded-md bg-brand-50 px-1.5 py-0.5 text-[11px] font-semibold text-brand-700" x-text="line.protein"></span></template>
                                            <span x-show="line.size === 'large'" class="rounded-md bg-surface-2 px-1.5 py-0.5 text-[11px] font-medium text-ink-soft">{{ __('Large') }}</span>
                                            <span x-show="line.egg === 'fried'" class="rounded-md bg-surface-2 px-1.5 py-0.5 text-[11px] font-medium text-ink-soft">{{ __('Fried egg') }}</span>
                                            <span x-show="line.egg === 'half-boiled'" class="rounded-md bg-surface-2 px-1.5 py-0.5 text-[11px] font-medium text-ink-soft">{{ __('Half-boiled egg') }}</span>
                                        </div>
                                    </div>
                                    <button type="button" @click="$store.cart.remove(line.key)" class="shrink-0 text-ink-soft hover:text-red-600" aria-label="Remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>
                                <p x-show="line.note" class="mt-0.5 text-xs text-ink-soft" x-text="line.note"></p>

                                <div class="mt-auto flex items-center justify-between gap-2 pt-2">
                                    <div class="inline-flex items-center rounded-lg border border-line">
                                        <button type="button" @click="$store.cart.decrement(line.key)" class="grid h-9 w-9 place-items-center font-bold text-ink-soft hover:text-brand-700">−</button>
                                        <span class="w-8 text-center text-sm font-semibold" x-text="line.qty"></span>
                                        <button type="button" @click="$store.cart.increment(line.key)" class="grid h-9 w-9 place-items-center font-bold text-ink-soft hover:text-brand-700">+</button>
                                    </div>
                                    <span class="font-bold" x-text="window.CURRENCY + ' ' + window.money(line.price * line.qty)"></span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="$store.cart.clear()" class="btn-ghost text-sm">{{ __('Clear cart') }}</button>
                </div>

                {{-- Summary --}}
                <div class="h-max lg:sticky lg:top-24">
                    <div class="card p-5">
                        <h2 class="text-lg font-bold">{{ __('Summary') }}</h2>
                        <div class="mt-4 flex items-center justify-between text-sm">
                            <span class="text-ink-soft">{{ __('Subtotal') }}</span>
                            <span class="font-semibold" x-text="window.CURRENCY + ' ' + window.money($store.cart.subtotal)"></span>
                        </div>
                        <p class="mt-1 text-xs text-ink-soft">{{ __('Delivery fee is calculated at checkout.') }}</p>
                        <p x-show="$store.cart.items.some(i => i.isDrink) && !$store.cart.items.every(i => i.isDrink)" x-cloak class="mt-2 flex items-center gap-1.5 rounded-lg bg-green-50 px-2.5 py-1.5 text-xs font-medium text-green-700">🥤 {{ __('Your drinks are delivered free with your food order.') }}</p>
                        <p x-show="$store.cart.items.length && $store.cart.items.every(i => i.isDrink)" x-cloak class="mt-2 flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-800">🥤 {{ __('A delivery fee applies to drinks-only orders.') }}</p>

                        @if ($preorderStatus->isOpen())
                            <a href="{{ route('preorder.create') }}" class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-700 px-4 py-3 font-bold text-white shadow-lg shadow-indigo-500/30 transition hover:-translate-y-0.5">
                                🗓️ {{ __('Preorder for :day', ['day' => $preorderStatus->fulfillmentAt()->locale(app()->getLocale())->isoFormat('dddd')]) }}
                            </a>
                            <p class="mt-2 text-center text-xs text-ink-soft">{{ __('Delivered :window.', ['window' => $preorderStatus->windowLabel()]) }}</p>
                        @endif

                        @if ($storeStatus->isOpen())
                            <a href="{{ route('checkout.index') }}" class="btn-primary mt-3 w-full">{{ __('Checkout') }}</a>
                        @elseif (! $preorderStatus->isOpen())
                            <button type="button" disabled class="btn-primary mt-5 w-full bg-black/20 text-white/70" title="{{ __('Ordering is closed right now') }}">{{ __('Ordering closed') }}</button>
                            <p class="mt-2 text-center text-xs text-ink-soft">
                                @if ($storeStatus->nextOpeningLabelLocalized())
                                    {{ __("We're closed right now.") }} {{ __('Next opening') }}: {{ $storeStatus->nextOpeningLabelLocalized() }}.
                                @else
                                    {{ __("We're closed right now.") }}
                                @endif
                            </p>
                        @endif

                        <a href="{{ route('menu.index') }}" class="btn-ghost mt-2 w-full">{{ __('Add more items') }}</a>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection
