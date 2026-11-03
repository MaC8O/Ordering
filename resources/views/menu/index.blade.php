@extends('layouts.storefront')

@section('title', $storeStatus->restaurantName() . ' — Menu')

@section('content')
    {{-- Hero --}}
    <section class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 to-brand-800 px-6 py-10 text-white sm:px-10 sm:py-14">
        <div class="max-w-xl">
            <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-brand-100">AIU Campus Delivery</p>
            <h1 class="text-3xl font-bold leading-tight sm:text-4xl">Good food, delivered to your dorm.</h1>
            <p class="mt-3 text-brand-50/90">Browse the menu, build your cart, and check out in under a minute. Pay by QR transfer or cash on delivery.</p>
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
        <div x-data="{ cat: 'all' }">
            {{-- Category chips --}}
            <div class="mb-5 -mx-4 flex gap-2 overflow-x-auto px-4 pb-1">
                <button type="button" @click="cat = 'all'"
                        :class="cat === 'all' ? 'border-brand-600 bg-brand-600 text-white' : 'border-black/10 bg-white text-ink-soft'"
                        class="chip">All</button>
                @foreach ($categories as $category)
                    <button type="button" @click="cat = '{{ $category->id }}'"
                            :class="cat === '{{ $category->id }}' ? 'border-brand-600 bg-brand-600 text-white' : 'border-black/10 bg-white text-ink-soft'"
                            class="chip">{{ $category->name }}</button>
                @endforeach
            </div>

            {{-- Item grid, grouped by category --}}
            @foreach ($categories as $category)
                <section x-show="cat === 'all' || cat === '{{ $category->id }}'" class="mb-8">
                    <h2 class="mb-3 text-lg font-bold">{{ $category->name }}</h2>
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
