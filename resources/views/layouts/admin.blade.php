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
    <header class="sticky top-0 z-40 border-b border-line bg-surface">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-2 px-3 py-3 sm:gap-4 sm:px-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 font-bold">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-600 text-white">🍽️</span>
                <span class="hidden sm:inline">{{ config('app.name') }} Admin</span>
            </a>

            <nav class="flex items-center gap-0.5 text-sm sm:gap-1">
                @php
                    $navItems = [
                        'admin.dashboard' => 'Dashboard',
                        'admin.orders.index' => 'Orders',
                        'admin.menu.index' => 'Menu',
                        'admin.settings.edit' => 'Settings',
                    ];
                @endphp
                @foreach ($navItems as $route => $label)
                    <a href="{{ route($route) }}"
                       class="rounded-lg px-2 py-2 font-semibold hover:bg-hover sm:px-3 {{ request()->routeIs(str_replace('.index', '.*', str_replace('.edit','.*',$route))) || request()->routeIs($route) ? 'bg-brand-50 text-brand-700' : 'text-ink-soft' }}">
                        {{ $label }}
                    </a>
                @endforeach
                <a href="{{ route('menu.index') }}" target="_blank" class="hidden rounded-lg px-3 py-2 font-semibold text-ink-soft hover:bg-hover sm:inline">View site ↗</a>
                @include('partials.theme-toggle')
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg px-2 py-2 font-semibold text-red-600 hover:bg-red-50 sm:px-3">Log out</button>
                </form>
            </nav>
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
