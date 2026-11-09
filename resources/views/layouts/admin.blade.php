<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @include('partials.theme-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream">
    @php
        $navItems = [
            'admin.dashboard' => 'Dashboard',
            'admin.orders.index' => 'Orders',
            'admin.preorders.index' => 'Preorders',
            'admin.menu.index' => 'Menu',
            'admin.settings.edit' => 'Settings',
        ];
        $navActive = fn ($route) => request()->routeIs(str_replace('.index', '.*', str_replace('.edit', '.*', $route))) || request()->routeIs($route);
    @endphp
    <header x-data="{ navOpen: false }" class="sticky top-0 z-40 border-b border-line bg-surface">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-2 px-3 py-3 sm:gap-4 sm:px-4">
            <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-2 font-bold">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-600 text-white">🍽️</span>
                <span class="truncate">{{ config('app.name') }} <span class="hidden sm:inline">Admin</span></span>
            </a>

            {{-- Desktop nav: inline from md up --}}
            <nav class="hidden items-center gap-0.5 text-sm md:flex lg:gap-1">
                @foreach ($navItems as $route => $label)
                    <a href="{{ route($route) }}"
                       class="rounded-lg px-2 py-2 font-semibold hover:bg-hover lg:px-3 {{ $navActive($route) ? 'bg-brand-50 text-brand-700' : 'text-ink-soft' }}">
                        {{ $label }}
                    </a>
                @endforeach
                <a href="{{ route('menu.index') }}" target="_blank" class="rounded-lg px-2 py-2 font-semibold text-ink-soft hover:bg-hover lg:px-3">View site ↗</a>
                @include('partials.theme-toggle')
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg px-2 py-2 font-semibold text-red-600 hover:bg-red-50 lg:px-3">Log out</button>
                </form>
            </nav>

            {{-- Mobile controls: theme toggle + hamburger, below md --}}
            <div class="flex items-center gap-1 md:hidden">
                @include('partials.theme-toggle')
                <button type="button" @click="navOpen = ! navOpen" :aria-expanded="navOpen" aria-label="Menu"
                        class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-ink hover:bg-hover">
                    <svg x-show="! navOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
                    <svg x-show="navOpen" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Mobile dropdown panel --}}
        <div x-show="navOpen" x-cloak x-transition.origin.top @click.outside="navOpen = false"
             class="border-t border-line bg-surface px-3 pb-3 pt-1 md:hidden">
            @php $mob = 'block rounded-lg px-3 py-2.5 text-base font-semibold hover:bg-hover'; @endphp
            @foreach ($navItems as $route => $label)
                <a href="{{ route($route) }}" @click="navOpen = false"
                   class="{{ $mob }} {{ $navActive($route) ? 'bg-brand-50 text-brand-700' : 'text-ink' }}">{{ $label }}</a>
            @endforeach
            <a href="{{ route('menu.index') }}" target="_blank" @click="navOpen = false" class="{{ $mob }} text-ink-soft">View site ↗</a>
            <form method="POST" action="{{ route('admin.logout') }}" class="mt-1 border-t border-line pt-1">
                @csrf
                <button type="submit" class="{{ $mob }} w-full text-left text-red-600 hover:bg-red-50">Log out</button>
            </form>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl px-4 py-6">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    {{-- Toasts (shared store) --}}
    <div x-data class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4">
        <template x-for="toast in $store.toasts.items" :key="toast.id">
            <div class="pointer-events-auto rounded-full bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-lg" x-text="toast.message"></div>
        </template>
    </div>

    <style>[x-cloak]{display:none !important;}</style>
</body>
</html>
