@extends('layouts.storefront')

@section('title', $storeStatus->restaurantName() . ' — ' . __('Menu'))

@section('content')
    <style>
        @keyframes fd-shine {
            0% { transform: translateX(-120%) skewX(-12deg); }
            55%, 100% { transform: translateX(120%) skewX(-12deg); }
        }
        /* Slow gold sheen that drifts across the hero headline */
        @keyframes hero-sheen { to { background-position: 200% center; } }
        .hero-sheen-text, .hero-sheen-text-2 {
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }
        .hero-sheen-text {
            background-image: linear-gradient(100deg, #ffffff 12%, #fde68a 32%, #ffffff 50%, #fde68a 68%, #ffffff 88%);
            animation: hero-sheen 7s linear infinite;
        }
        .hero-sheen-text-2 {
            background-image: linear-gradient(100deg, #fff7ed 10%, #fed7aa 38%, #ffffff 58%, #fecdd3 92%);
            animation: hero-sheen 9s linear infinite;
        }
        /* draw the underline on once the hero appears */
        @keyframes hero-underline { from { stroke-dashoffset: 320; } to { stroke-dashoffset: 0; } }
        .hero-underline path { stroke-dasharray: 320; animation: hero-underline 1.1s .25s ease-out both; }
        @media (prefers-reduced-motion: reduce) {
            .hero-sheen-text, .hero-sheen-text-2 { animation: none; }
            .hero-underline path { animation: none; stroke-dashoffset: 0; }
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

            <h1 class="mt-5 text-4xl font-extrabold leading-[1.04] tracking-tight drop-shadow-[0_2px_14px_rgba(0,0,0,0.18)] sm:text-5xl lg:text-6xl">
                <span class="relative inline-block">
                    <span class="hero-sheen-text">{{ __('Authentic Thai,') }}</span>
                    {{-- hand-drawn underline accent --}}
                    <svg aria-hidden="true" class="hero-underline absolute -bottom-2 left-0 h-3 w-full text-amber-300" viewBox="0 0 300 12" fill="none" preserveAspectRatio="none">
                        <path d="M3 8C70 2 150 11 297 5" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                </span>
                <br>
                <span class="hero-sheen-text-2">{{ __('delivered to your dorm.') }}</span>
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
                        <p class="text-sm font-extrabold uppercase tracking-wide">🎉 {{ __('Free delivery ready!') }}</p>
                        <p class="text-xs font-semibold text-brand-800/80">{{ __('Your next order ships free.') }}</p>
                    @elseif ($fdCustomer)
                        @php $fdLeft = $fdCustomer->ordersUntilNextReward(); @endphp
                        <p class="text-sm font-extrabold uppercase tracking-wide">🎁 {{ __('Free delivery reward') }}</p>
                        <p class="text-xs font-semibold text-brand-800/80">{{ trans_choice('{1} Just :count more delivered order until a free delivery.|[2,*] Just :count more delivered orders until a free delivery.', $fdLeft, ['count' => $fdLeft]) }}</p>
                    @else
                        <p class="text-sm font-extrabold uppercase tracking-wide">🎁 {{ __('Free delivery reward') }}</p>
                        <p class="text-xs font-semibold text-brand-800/80">
                            {{ __('Order :count times and your next delivery is free.', ['count' => $fdThreshold]) }}
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

    {{-- Preorder window open --}}
    @if ($preorderStatus->isOpen())
        <a href="{{ route('cart.index') }}" class="group mb-6 flex items-center gap-4 overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-700 p-4 text-white shadow-lg shadow-indigo-500/20 transition hover:-translate-y-0.5 sm:p-5">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-white/15 text-2xl backdrop-blur">🗓️</span>
            <div class="min-w-0 flex-1">
                <p class="font-bold">{{ __('Preorder is open!') }}</p>
                <p class="truncate text-sm text-indigo-50/90">{{ __('Reserve your meal for :window.', ['window' => $preorderStatus->windowLabel()]) }}</p>
            </div>
            <span class="hidden shrink-0 items-center gap-1 rounded-full bg-white px-4 py-2 text-sm font-bold text-indigo-700 sm:inline-flex">{{ __('Preorder now') }} →</span>
        </a>
    @endif

    <div x-data="menuBoard(@js($search))" id="menu-start">
        {{-- Search (live as you type; also works as a normal search if JS is off) --}}
        <form method="GET" action="{{ route('menu.index') }}" class="mb-5" @submit.prevent>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-soft" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="search" name="q" x-model="q" placeholder="{{ __('Search the menu…') }}" class="input pl-11 pr-10" autocomplete="off">
                <button type="button" x-show="q.length > 0" x-cloak @click="q = ''" aria-label="{{ __('Clear search') }}"
                        class="absolute right-3 top-1/2 grid h-6 w-6 -translate-y-1/2 place-items-center rounded-full text-ink-soft hover:bg-hover">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
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
            {{-- Live "no results" (when a search matches nothing) --}}
            <div x-show="noResults" x-cloak class="card p-10 text-center">
                <p class="text-5xl">🍽️</p>
                <h2 class="mt-3 text-lg font-bold">{{ __('No items found') }}</h2>
                <p class="mt-1 text-sm text-ink-soft">
                    {{ __('Nothing matched') }} “<span x-text="q"></span>”.
                    <button type="button" @click="q = ''" class="font-semibold text-brand-700 underline">{{ __('Clear search') }}</button>.
                </p>
            </div>

            {{-- Category chips --}}
            <div x-show="!noResults" class="sticky top-[60px] z-30 -mx-4 mb-6 flex gap-2 overflow-x-auto bg-cream/85 px-4 py-3 backdrop-blur [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <button type="button" @click="cat = 'all'"
                        :class="cat === 'all' ? 'chip-active' : 'chip-idle'"
                        class="chip shrink-0">{{ __('All') }}</button>
                @foreach ($categories as $category)
                    <button type="button" @click="cat = '{{ $category->id }}'"
                            :class="cat === '{{ $category->id }}' ? 'chip-active' : 'chip-idle'"
                            class="chip shrink-0">{{ $category->localized_name }}</button>
                @endforeach
            </div>

            {{-- Item grid, grouped by category --}}
            @foreach ($categories as $category)
                <section x-show="catVisible('{{ $category->id }}')" x-cloak data-cat="{{ $category->id }}" class="reveal mb-10">
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
        @endif
    </div>

    <script>
        function menuBoard(initialQ) {
            return {
                q: initialQ || '',
                cat: 'all',
                _norm(s) { return (s || '').toString().toLowerCase().trim(); },
                match(el) {
                    const q = this._norm(this.q);
                    if (!q) return true;
                    return (el.dataset.search || '').includes(q);
                },
                itemVisible(el) { return this.match(el); },
                catVisible(id) {
                    if (!(this.cat === 'all' || this.cat === String(id))) return false;
                    if (!this._norm(this.q)) return true;
                    const sec = this.$root.querySelector('[data-cat="' + id + '"]');
                    if (!sec) return true;
                    return [...sec.querySelectorAll('[data-search]')].some(el => this.match(el));
                },
                get noResults() {
                    if (!this._norm(this.q)) return false;
                    const cards = [...this.$root.querySelectorAll('[data-search]')];
                    return cards.length > 0 && !cards.some(el => this.match(el));
                },
            };
        }
    </script>
@endsection
