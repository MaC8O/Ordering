@extends('layouts.storefront')

@section('title', $item->name . ' — ' . $storeStatus->restaurantName())

@section('content')
    <a href="{{ route('menu.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-ink-soft hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        Back to menu
    </a>

    <div class="grid gap-6 md:grid-cols-2"
         x-data="{
            qty: 1,
            note: '',
            item: { id: {{ $item->id }}, name: @js($item->name), price: {{ $item->price }}, image: @js($item->image_path ? asset($item->image_path) : null) },
            addToCart() {
                if (!window.STORE_OPEN) return;
                $store.cart.add(this.item, this.qty, this.note);
            }
         }">
        <div class="overflow-hidden rounded-2xl">
            <x-food-image :item="$item" rounded="rounded-2xl" />
        </div>

        <div class="flex flex-col">
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-600">{{ $item->category->name }}</p>
            <h1 class="mt-1 text-2xl font-bold">{{ $item->name }}</h1>
            <p class="mt-2 text-2xl font-bold text-brand-700">{{ config('ordering.currency') }} {{ $item->price_formatted }}</p>

            @if ($item->description)
                <p class="mt-4 text-ink-soft">{{ $item->description }}</p>
            @endif

            <div class="mt-6">
                <label class="label">Quantity</label>
                <div class="inline-flex items-center rounded-xl border border-black/10 bg-white">
                    <button type="button" @click="qty = Math.max(1, qty - 1)" class="grid h-11 w-11 place-items-center text-xl font-bold text-ink-soft hover:text-brand-700" aria-label="Decrease">−</button>
                    <input type="number" min="1" max="20" x-model.number="qty" @change="qty = Math.max(1, Math.min(20, parseInt(qty)||1))" class="h-11 w-14 border-x border-black/10 text-center font-semibold outline-none">
                    <button type="button" @click="qty = Math.min(20, qty + 1)" class="grid h-11 w-11 place-items-center text-xl font-bold text-ink-soft hover:text-brand-700" aria-label="Increase">+</button>
                </div>
                <p class="mt-1 text-xs text-ink-soft">Up to 20 per item.</p>
            </div>

            <div class="mt-4">
                <label for="note" class="label">Note <span class="font-normal text-ink-soft">(optional)</span></label>
                <textarea id="note" x-model="note" rows="2" maxlength="200" class="input" placeholder="e.g. no onions, extra spicy"></textarea>
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <button type="button" @click="addToCart()" :disabled="!window.STORE_OPEN"
                        :title="window.STORE_OPEN ? '' : 'Ordering is closed right now'"
                        class="btn-primary flex-1 disabled:bg-black/20 disabled:text-white/70">
                    <span x-show="window.STORE_OPEN">Add to cart · <span x-text="window.CURRENCY + ' ' + window.money(item.price * qty)"></span></span>
                    <span x-show="!window.STORE_OPEN">Ordering closed</span>
                </button>
                <a href="{{ route('cart.index') }}" class="btn-secondary">View cart</a>
            </div>
        </div>
    </div>
@endsection
