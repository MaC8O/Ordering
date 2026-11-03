<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
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
    </script>
</head>
<body class="min-h-screen flex flex-col">
    {{-- Sticky top nav --}}
    <header class="sticky top-0 z-40 border-b border-line bg-cream/90 backdrop-blur">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('menu.index') }}" class="flex items-center gap-2 font-bold text-ink">
                @if (file_exists(public_path('logo.png')))
                    <img src="{{ asset('logo.png') }}" alt="{{ $storeStatus->restaurantName() }}" class="h-8 w-8 rounded-lg object-cover">
                @else
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-600 text-white">🍽️</span>
                @endif
                <span class="hidden sm:inline">{{ $storeStatus->restaurantName() }}</span>
            </a>

            <div class="flex items-center gap-1 sm:gap-2">
                @include('partials.theme-toggle')
                <a href="{{ route('menu.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-ink-soft hover:bg-hover {{ request()->routeIs('menu.*') ? 'text-brand-700' : '' }}">Menu</a>
                <a href="{{ route('my-order.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-ink-soft hover:bg-hover {{ request()->routeIs('my-order.*') ? 'text-brand-700' : '' }}">My Order</a>
                <a href="{{ route('about') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-ink-soft hover:bg-hover {{ request()->routeIs('about') ? 'text-brand-700' : '' }}">About</a>
                <a href="{{ route('cart.index') }}" class="relative rounded-lg px-3 py-2 text-sm font-semibold text-ink hover:bg-hover" aria-label="Cart">
                    <span class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-.534 2.3-3.429 2.74-5.5H6.91m0 0L5.106 5.272M7.5 14.25 5.106 5.272" /><circle cx="9" cy="20" r="1" /><circle cx="18" cy="20" r="1" /></svg>
                        <span class="hidden sm:inline">Cart</span>
                    </span>
                    <span x-data x-show="$store.cart.count > 0" x-cloak
                          class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-brand-600 px-1 text-[11px] font-bold text-white"
                          x-text="$store.cart.count"></span>
                </a>
            </div>
        </nav>
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

    <footer class="border-t border-line bg-surface">
        <div class="mx-auto max-w-6xl px-4 py-8 text-sm text-ink-soft">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="font-semibold text-ink">{{ $storeStatus->restaurantName() }}</p>
                <p>@if ($storeSettings->phone) Call us: {{ $storeSettings->phone }} @endif</p>
            </div>
            <p class="mt-3 text-xs text-ink-soft/70">On-campus delivery only · Pay by QR transfer or cash on delivery.</p>
        </div>
    </footer>

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
