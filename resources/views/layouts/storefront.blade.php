<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $storeStatus->restaurantName())</title>
    <link rel="icon" href="{{ file_exists(public_path('logo.png')) ? asset('logo.png') : asset('favicon.ico') }}">
    @include('partials.theme-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        window.CURRENCY = @json(config('ordering.currency'));
        window.STORE_OPEN = @json($storeStatus->isOpen());
        window.PREORDER_OPEN = @json($preorderStatus->isOpen());
        // Items can be added whenever regular ordering OR the preorder window is open.
        window.CAN_ORDER = window.STORE_OPEN || window.PREORDER_OPEN;
        window.PROMPTPAY_ID = @json(config('ordering.promptpay_id'));
    </script>
</head>
<body class="min-h-screen flex flex-col">
    {{-- Sticky top nav --}}
    <header x-data="{ navOpen: false }" class="sticky top-0 z-40 border-b border-line bg-cream/90 backdrop-blur">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('menu.index') }}" class="logo-link group flex min-w-0 items-center gap-2 font-bold text-ink">
                @if (file_exists(public_path('logo.png')))
                    <img src="{{ asset('logo.png') }}" alt="{{ $storeStatus->restaurantName() }}" class="logo-mark h-8 w-8 shrink-0 rounded-lg object-cover shadow-sm">
                @else
                    <span class="logo-mark grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-sm">🍜</span>
                @endif
                <span class="truncate bg-gradient-to-r from-brand-700 to-brand-500 bg-clip-text text-transparent transition-opacity group-hover:opacity-80">{{ $storeStatus->restaurantName() }}</span>
            </a>

            <div class="flex items-center gap-1 sm:gap-2">
                @include('partials.lang-toggle')
                @include('partials.theme-toggle')

                {{-- Primary links: inline from md up --}}
                <div class="hidden items-center gap-1 md:flex md:gap-2">
                    <a href="{{ route('menu.index') }}" class="nav-link rounded-lg px-3 py-2 text-sm font-semibold hover:bg-hover {{ request()->routeIs('menu.*') ? 'is-active text-brand-700' : 'text-ink-soft' }}">{{ __('Menu') }}</a>
                    <a href="{{ route('my-order.index') }}" class="nav-link rounded-lg px-3 py-2 text-sm font-semibold hover:bg-hover {{ request()->routeIs('my-order.*') ? 'is-active text-brand-700' : 'text-ink-soft' }}">{{ __('My Order') }}</a>
                    @if ($preorderStatus->isOpen())
                        <a href="{{ route('preorder.create') }}" class="nav-link inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-bold hover:bg-indigo-50 {{ request()->routeIs('preorder.*') ? 'is-active text-indigo-700' : 'text-indigo-600' }} dark:text-indigo-300 dark:hover:bg-indigo-500/10">🗓️ {{ __('Preorder') }}</a>
                    @endif
                    <a href="{{ route('about') }}" class="nav-link rounded-lg px-3 py-2 text-sm font-semibold hover:bg-hover {{ request()->routeIs('about') ? 'is-active text-brand-700' : 'text-ink-soft' }}">{{ __('About') }}</a>
                    @auth('customer')
                        <a href="{{ route('account.index') }}" class="nav-link rounded-lg px-3 py-2 text-sm font-semibold hover:bg-hover {{ request()->routeIs('account.*') ? 'is-active text-brand-700' : 'text-ink-soft' }}">{{ __('Account') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="nav-link rounded-lg px-3 py-2 text-sm font-semibold hover:bg-hover {{ request()->routeIs('login') || request()->routeIs('register') ? 'is-active text-brand-700' : 'text-ink-soft' }}">{{ __('Log in') }}</a>
                    @endauth
                </div>

                {{-- Cart (always visible) --}}
                <a href="{{ route('cart.index') }}" class="relative rounded-lg px-2 py-2 text-sm font-semibold text-ink hover:bg-hover sm:px-3" aria-label="{{ __('Cart') }}">
                    <span class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-.534 2.3-3.429 2.74-5.5H6.91m0 0L5.106 5.272M7.5 14.25 5.106 5.272" /><circle cx="9" cy="20" r="1" /><circle cx="18" cy="20" r="1" /></svg>
                        <span class="hidden md:inline">{{ __('Cart') }}</span>
                    </span>
                    <span x-data x-show="$store.cart.count > 0" x-cloak
                          class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-brand-600 px-1 text-[11px] font-bold text-white"
                          x-text="$store.cart.count"></span>
                </a>

                {{-- Hamburger: below md --}}
                <button type="button" @click="navOpen = ! navOpen" :aria-expanded="navOpen" aria-label="{{ __('Menu') }}"
                        class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-ink hover:bg-hover md:hidden">
                    <svg x-show="! navOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
                    <svg x-show="navOpen" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </nav>

        {{-- Mobile dropdown panel --}}
        <div x-show="navOpen" x-cloak x-transition.origin.top @click.outside="navOpen = false"
             class="border-t border-line bg-cream px-3 pb-3 pt-1 md:hidden">
            @php
                $mob = 'block rounded-lg px-3 py-2.5 text-base font-semibold hover:bg-hover';
            @endphp
            <a href="{{ route('menu.index') }}" class="{{ $mob }} {{ request()->routeIs('menu.*') ? 'text-brand-700' : 'text-ink' }}">{{ __('Menu') }}</a>
            <a href="{{ route('my-order.index') }}" class="{{ $mob }} {{ request()->routeIs('my-order.*') ? 'text-brand-700' : 'text-ink' }}">{{ __('My Order') }}</a>
            @if ($preorderStatus->isOpen())
                <a href="{{ route('preorder.create') }}" class="{{ $mob }} text-indigo-600 dark:text-indigo-300">🗓️ {{ __('Preorder') }}</a>
            @endif
            <a href="{{ route('about') }}" class="{{ $mob }} {{ request()->routeIs('about') ? 'text-brand-700' : 'text-ink' }}">{{ __('About') }}</a>
            @auth('customer')
                <a href="{{ route('account.index') }}" class="{{ $mob }} {{ request()->routeIs('account.*') ? 'text-brand-700' : 'text-ink' }}">{{ __('Account') }}</a>
            @else
                <a href="{{ route('login') }}" class="{{ $mob }} {{ request()->routeIs('login') || request()->routeIs('register') ? 'text-brand-700' : 'text-ink' }}">{{ __('Log in') }}</a>
            @endauth
        </div>
    </header>

    {{-- Closed banner --}}
    @include('partials.store-banner')

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
        @if (session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
        @endif

        @yield('content')
    </main>

    <footer class="mt-6 border-t border-line bg-surface">
        <div class="mx-auto max-w-6xl px-4 py-9 text-sm text-ink-soft">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="max-w-sm">
                    <a href="{{ route('menu.index') }}" class="logo-link group inline-flex items-center gap-2 text-base font-bold text-ink">
                        @if (file_exists(public_path('logo.png')))
                            <img src="{{ asset('logo.png') }}" alt="{{ $storeStatus->restaurantName() }}" class="logo-mark h-7 w-7 rounded-lg object-cover">
                        @else
                            <span class="logo-mark grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-white">🍜</span>
                        @endif
                        {{ $storeStatus->restaurantName() }}
                    </a>
                    <p class="mt-2 text-xs leading-relaxed text-ink-soft/80">{{ __('On-campus delivery only · Pay by QR transfer or cash on delivery.') }}</p>
                    <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 ring-1 ring-brand-100">
                        🛵 {{ __('Delivered by') }} <span class="font-extrabold tracking-tight">GateGo</span>
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:items-end">
                    <nav class="flex flex-wrap gap-x-4 gap-y-1 font-semibold">
                        <a href="{{ route('menu.index') }}" class="hover:text-brand-700">{{ __('Menu') }}</a>
                        <a href="{{ route('my-order.index') }}" class="hover:text-brand-700">{{ __('My Order') }}</a>
                        @if ($preorderStatus->isOpen())
                            <a href="{{ route('preorder.create') }}" class="font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">{{ __('Preorder') }}</a>
                        @endif
                        <a href="{{ route('about') }}" class="hover:text-brand-700">{{ __('About') }}</a>
                    </nav>
                    @if ($storeSettings->phone)
                        <p class="text-xs">{{ __('Call us:') }} <a href="tel:{{ $storeSettings->phone }}" class="font-semibold text-brand-700">{{ $storeSettings->phone }}</a></p>
                    @endif
                </div>
            </div>

            <div class="mt-7 flex flex-col gap-2 border-t border-line pt-5 text-xs text-ink-soft/70 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} {{ $storeStatus->restaurantName() }}. {{ __('All rights reserved.') }}</p>
                <p>{{ __('Made with') }} <span class="heart-beat text-brand-600" aria-hidden="true">&hearts;</span> {{ __('on campus.') }}</p>
            </div>
        </div>
    </footer>

    {{-- Scroll-to-top: appears after scrolling down, smooth-scrolls to top --}}
    <button type="button"
            x-data="{ shown: false }"
            x-init="shown = window.scrollY > 400; window.addEventListener('scroll', () => shown = window.scrollY > 400, { passive: true })"
            x-show="shown" x-cloak x-transition.opacity
            @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
            aria-label="{{ __('Back to top') }}"
            class="fixed bottom-5 right-5 z-50 grid h-12 w-12 place-items-center rounded-full bg-brand-600 text-white shadow-lg shadow-brand-900/25 ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:bg-brand-700 active:scale-95">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/></svg>
    </button>

    {{-- Toasts --}}
    <div x-data class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4">
        <template x-for="toast in $store.toasts.items" :key="toast.id">
            <div class="pointer-events-auto flex items-center gap-3 rounded-full px-5 py-3 text-sm font-semibold text-white shadow-lg"
                 :class="toast.type === 'info' ? 'bg-ink' : 'bg-brand-600'"
                 x-transition.opacity.duration.200ms>
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

    <style>[x-cloak]{display:none !important;}</style>
</body>
</html>
