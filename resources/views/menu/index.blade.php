@extends('layouts.storefront')

@section('title', $storeStatus->restaurantName() . ' — ' . __('Menu'))

@section('content')
    <style>
        @keyframes fd-shine {
            0% { transform: translateX(-120%) skewX(-12deg); }
            55%, 100% { transform: translateX(120%) skewX(-12deg); }
        }
    </style>

    {{-- Hero --}}
    <section class="relative mb-8 overflow-hidden rounded-3xl px-6 py-12 text-white shadow-xl shadow-brand-900/10 sm:px-10 sm:py-16"
             style="background-image: linear-gradient(135deg, #fb923c 0%, #ea580c 42%, #9a3412 100%);">
        {{-- decorative glow blobs --}}
        <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-amber-200/40 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-28 left-10 h-64 w-64 rounded-full bg-rose-500/25 blur-3xl"></div>
        {{-- subtle dotted texture --}}
        <div class="pointer-events-none absolute inset-0 opacity-[0.08]"
             style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 22px 22px;"></div>

        {{-- floating food, fills the right side --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 right-0 hidden w-2/5 select-none md:block">
            <span class="hero-float absolute right-16 top-9 text-6xl" style="animation-delay: 0s">🍜</span>
            <span class="hero-float absolute right-44 top-24 text-4xl" style="animation-delay: .7s">🌶️</span>
            <span class="hero-float absolute right-10 top-44 text-5xl" style="animation-delay: 1.3s">🍚</span>
            <span class="hero-float absolute bottom-12 right-32 text-4xl" style="animation-delay: 1.9s">🥢</span>
        </div>

        <div class="relative max-w-xl">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-wide ring-1 ring-white/20 backdrop-blur">
                @if ($storeStatus->isOpen())
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-300 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-green-300"></span>
                    </span>
                    {{ __('Open now') }}
                @else
                    <span class="h-2 w-2 rounded-full bg-amber-300"></span> {{ __('Closed') }}{{ $storeStatus->nextOpeningLabelLocalized() ? ' · ' . $storeStatus->nextOpeningLabelLocalized() : '' }}
                @endif
            </span>

            <h1 class="mt-5 text-4xl font-extrabold leading-[1.08] tracking-tight drop-shadow-sm sm:text-5xl">
                {{ __('Authentic Thai,') }}<br>
                <span class="bg-gradient-to-r from-amber-100 to-white bg-clip-text text-transparent">{{ __('delivered to your dorm.') }}</span>
            </h1>

            <p class="mt-4 max-w-md text-base leading-relaxed text-white/90">{{ __('Noodles, fried rice, and spicy basil favourites — built fresh and brought to your door. Pay by QR transfer or cash on delivery.') }}</p>

            {{-- Free-delivery loyalty callout — adapts to the visitor --}}
            @php
                $fdCustomer = auth('customer')->user();
                $fdThreshold = \App\Models\Customer::freeDeliveryThreshold();
            @endphp
            <div class="group relative mt-6 inline-flex max-w-md items-center gap-3 overflow-hidden rounded-2xl bg-gradient-to-r from-amber-300 via-yellow-200 to-amber-300 px-4 py-3 text-brand-900 shadow-lg shadow-black/15 ring-1 ring-white/50">
                {{-- shine sweep --}}
                <span class="pointer-events-none absolute inset-0 -skew-x-12 bg-gradient-to-r from-transparent via-white/80 to-transparent motion-safe:animate-[fd-shine_4s_ease-in-out_infinite]"></span>
                <span class="relative grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white/70 text-2xl shadow-inner motion-safe:animate-bounce">🛵</span>
                <div class="relative leading-tight">
                    @if ($fdCustomer && $fdCustomer->hasFreeDelivery())
                        <p class="text-sm font-extrabold uppercase tracking-wide">🎉 {{ __('Free delivery unlocked!') }}</p>
                        <p class="text-xs font-semibold text-brand-800/80">{{ __('Enjoy free delivery on every order.') }}</p>
                    @elseif ($fdCustomer)
                        @php $fdLeft = max(0, $fdThreshold - $fdCustomer->deliveredOrdersCount()); @endphp
                        <p class="text-sm font-extrabold uppercase tracking-wide">🎁 {{ __('Free delivery, forever') }}</p>
                        <p class="text-xs font-semibold text-brand-800/80">{{ trans_choice('{1} Just :count more delivered order and your delivery is free, forever.|[2,*] :count more delivered orders and your delivery is free, forever.', $fdLeft, ['count' => $fdLeft]) }}</p>
                    @else
                        <p class="text-sm font-extrabold uppercase tracking-wide">🎁 {{ __('Free delivery, forever') }}</p>
                        <p class="text-xs font-semibold text-brand-800/80">
                            {{ __('Order :count times and your delivery is free, forever.', ['count' => $fdThreshold]) }}
                            <a href="{{ route('register') }}" class="font-bold underline underline-offset-2 hover:text-brand-950">{{ __('Sign up free') }}</a>
                        </p>
                    @endif
                </div>
            </div>

            <div class="mt-7 flex flex-wrap items-center gap-3">
                <a href="#menu-start" class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 font-bold text-brand-700 shadow-lg shadow-brand-900/20 transition-transform duration-200 hover:-translate-y-0.5 hover:bg-amber-50">
                    {{ __('Browse the menu') }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0-6-6m6 6-6 6"/></svg>
                </a>
            </div>

            <div class="mt-7 flex flex-wrap gap-2 text-xs font-semibold">
                <span class="rounded-full bg-white/15 px-3 py-1.5 ring-1 ring-white/15 backdrop-blur">⚡ {{ __('Fast campus delivery') }}</span>
                <span class="rounded-full bg-white/15 px-3 py-1.5 ring-1 ring-white/15 backdrop-blur">💳 {{ __('QR or cash') }}</span>
                <span class="rounded-full bg-white/15 px-3 py-1.5 ring-1 ring-white/15 backdrop-blur">🌶️ {{ __('Made fresh to order') }}</span>
            </div>
        </div>
    </section>

    {{-- Search --}}
    <form method="GET" action="{{ route('menu.index') }}" class="mb-5">
        <div class="relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-soft" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search the menu…') }}" class="input pl-11">
        </div>
    </form>

    @if ($categories->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-5xl">🍽️</p>
            <h2 class="mt-3 text-lg font-bold">{{ __('No items found') }}</h2>
            <p class="mt-1 text-sm text-ink-soft">
                @if ($search !== '')
                    {{ __('Nothing matched') }} “{{ $search }}”. <a href="{{ route('menu.index') }}" class="font-semibold text-brand-700 underline">{{ __('Clear search') }}</a>.
                @else
                    {{ __('The menu is being updated. Please check back soon.') }}
                @endif
            </p>
        </div>
    @else
        <div x-data="{ cat: 'all' }" id="menu-start">
            {{-- Category chips --}}
            <div class="sticky top-[60px] z-30 -mx-4 mb-6 flex flex-wrap gap-2 bg-cream/85 px-4 py-3 backdrop-blur">
                <button type="button" @click="cat = 'all'"
                        :class="cat === 'all' ? 'chip-active' : 'chip-idle'"
                        class="chip">{{ __('All') }}</button>
                @foreach ($categories as $category)
                    <button type="button" @click="cat = '{{ $category->id }}'"
                            :class="cat === '{{ $category->id }}' ? 'chip-active' : 'chip-idle'"
                            class="chip">{{ $category->localized_name }}</button>
                @endforeach
            </div>

            {{-- Item grid, grouped by category --}}
            @foreach ($categories as $category)
                <section x-show="cat === 'all' || cat === '{{ $category->id }}'" class="reveal mb-10">
                    <h2 class="mb-4 flex items-center gap-2.5 text-xl font-bold">
                        <span class="h-5 w-1.5 rounded-full bg-gradient-to-b from-brand-400 to-brand-600"></span>
                        {{ $category->localized_name }}
                        <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700">{{ $category->availableItems->count() }}</span>
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach ($category->availableItems as $item)
                            @include('menu.partials.item-card', ['item' => $item])
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
@endsection
