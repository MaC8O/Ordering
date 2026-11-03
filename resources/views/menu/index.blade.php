@extends('layouts.storefront')

@section('title', $storeStatus->restaurantName() . ' — Menu')

@section('content')
    {{-- Hero --}}
    <section class="relative mb-7 overflow-hidden rounded-3xl px-6 py-11 text-white sm:px-10 sm:py-16"
             style="background-image: linear-gradient(135deg, #ea580c 0%, #9a3412 60%, #7c2d12 100%);">
        {{-- decorative glow blobs --}}
        <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-brand-400/40 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 right-24 h-56 w-56 rounded-full bg-teal-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute right-6 top-6 hidden select-none text-[7rem] leading-none opacity-20 sm:block">🍜</div>

        <div class="relative max-w-xl">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-wide backdrop-blur">
                @if ($storeStatus->isOpen())
                    <span class="h-2 w-2 rounded-full bg-green-300 shadow-[0_0_8px_2px_rgba(134,239,172,0.7)]"></span> Open now
                @else
                    <span class="h-2 w-2 rounded-full bg-amber-300"></span> Closed{{ $storeStatus->nextOpeningLabel() ? ' · opens ' . $storeStatus->nextOpeningLabel() : '' }}
                @endif
            </span>
            <h1 class="mt-4 text-3xl font-bold leading-[1.1] sm:text-5xl">Authentic Thai,<br>delivered to your dorm.</h1>
            <p class="mt-4 max-w-md text-brand-50/90">Noodles, fried rice, and spicy basil favourites — built fresh and brought to your door. Pay by QR transfer or cash on delivery.</p>
            <a href="#menu-start" class="btn-secondary mt-6 !bg-white !text-brand-700">Browse the menu</a>
        </div>
    </section>

    {{-- Search --}}
    <form method="GET" action="{{ route('menu.index') }}" class="mb-5">
        <div class="relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-soft" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="search" name="q" value="{{ $search }}" placeholder="Search the menu…" class="input pl-11">
        </div>
    </form>

    @if ($categories->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-5xl">🍽️</p>
            <h2 class="mt-3 text-lg font-bold">No items found</h2>
            <p class="mt-1 text-sm text-ink-soft">
                @if ($search !== '')
                    Nothing matched “{{ $search }}”. <a href="{{ route('menu.index') }}" class="font-semibold text-brand-700 underline">Clear search</a>.
                @else
                    The menu is being updated. Please check back soon.
                @endif
            </p>
        </div>
    @else
        <div x-data="{ cat: 'all' }" id="menu-start">
            {{-- Category chips --}}
            <div class="sticky top-[60px] z-30 -mx-4 mb-6 flex gap-2 overflow-x-auto bg-cream/80 px-4 py-2 backdrop-blur [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <button type="button" @click="cat = 'all'"
                        :class="cat === 'all' ? 'chip-active' : 'chip-idle'"
                        class="chip">All</button>
                @foreach ($categories as $category)
                    <button type="button" @click="cat = '{{ $category->id }}'"
                            :class="cat === '{{ $category->id }}' ? 'chip-active' : 'chip-idle'"
                            class="chip">{{ $category->name }}</button>
                @endforeach
            </div>

            {{-- Item grid, grouped by category --}}
            @foreach ($categories as $category)
                <section x-show="cat === 'all' || cat === '{{ $category->id }}'" class="mb-10">
                    <h2 class="mb-4 flex items-center gap-2.5 text-xl font-bold">
                        <span class="h-5 w-1.5 rounded-full bg-gradient-to-b from-brand-400 to-brand-600"></span>
                        {{ $category->name }}
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
