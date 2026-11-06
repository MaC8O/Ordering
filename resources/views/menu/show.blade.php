@extends('layouts.storefront')

@section('title', $item->localized_name . ' — ' . $storeStatus->restaurantName())

@section('content')
    @php
        $currency = config('ordering.currency');
        $eggFee = (int) config('ordering.egg_addon_fee');
    @endphp

    <a href="{{ route('menu.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-ink-soft hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        {{ __('Back to menu') }}
    </a>

    <div class="grid gap-6 md:grid-cols-2"
         x-data="{
            qty: 1,
            note: '',
            size: 'normal',
            egg: 'none',
            proteins: @js($item->proteins ?? []),
            protein: @js(($item->proteins[0] ?? null)),
            base: {{ $item->price }},
            large: {{ $item->large_price ?? 'null' }},
            eggFee: {{ $eggFee }},
            hasSizes: {{ $item->hasSizes() ? 'true' : 'false' }},
            hasEgg: {{ $item->has_egg_addon ? 'true' : 'false' }},
            hasProteins: {{ $item->hasProteins() ? 'true' : 'false' }},
            meta: { id: {{ $item->id }}, name: @js($item->localized_name), image: @js($item->image_path ? asset($item->image_path) : null), isDrink: {{ $item->is_drink ? 'true' : 'false' }} },
            get unitPrice() {
                let p = (this.hasSizes && this.size === 'large' && this.large) ? this.large : this.base;
                if (this.hasEgg && this.egg !== 'none') p += this.eggFee;
                return p;
            },
            addToCart() {
                if (!window.CAN_ORDER) return;
                $store.cart.add({
                    id: this.meta.id, name: this.meta.name, image: this.meta.image, isDrink: this.meta.isDrink,
                    price: this.unitPrice,
                    protein: this.hasProteins ? this.protein : null,
                    size: this.hasSizes ? this.size : null,
                    egg: this.hasEgg ? this.egg : 'none',
                }, this.qty, this.note);
            }
         }">
        <div class="overflow-hidden rounded-2xl">
            <x-food-image :item="$item" rounded="rounded-2xl" />
        </div>

        <div class="flex flex-col">
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-600">{{ $item->category->localized_name }}</p>
            <h1 class="mt-1 text-2xl font-bold">{{ $item->localized_name }}</h1>
            <p class="mt-2 text-2xl font-bold text-brand-700">
                {{ $currency }} {{ $item->price_formatted }}@if ($item->hasSizes())<span class="text-base font-semibold text-ink-soft"> – {{ $item->large_price_formatted }}</span>@endif
            </p>

            @if ($item->localized_description)
                <p class="mt-4 text-ink-soft">{{ $item->localized_description }}</p>
            @endif

            {{-- Protein choice --}}
            @if ($item->hasProteins())
                <div class="mt-6">
                    <label class="label">{{ __('Choose your protein') }}</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($item->proteins as $p)
                            <button type="button" @click="protein = @js($p)"
                                    :class="protein === @js($p) ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-line text-ink'"
                                    class="rounded-xl border px-4 py-2.5 text-sm font-semibold transition">{{ __($p) }}</button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Size --}}
            @if ($item->hasSizes())
                <div class="mt-6">
                    <label class="label">{{ __('Size') }}</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="size = 'normal'"
                                :class="size === 'normal' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-line text-ink'"
                                class="flex items-center justify-between rounded-xl border px-4 py-3 text-sm font-semibold transition">
                            <span>{{ __('Normal') }}</span><span>{{ $currency }} {{ $item->price_formatted }}</span>
                        </button>
                        <button type="button" @click="size = 'large'"
                                :class="size === 'large' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-line text-ink'"
                                class="flex items-center justify-between rounded-xl border px-4 py-3 text-sm font-semibold transition">
                            <span>{{ __('Large') }}</span><span>{{ $currency }} {{ $item->large_price_formatted }}</span>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Egg add-on --}}
            @if ($item->has_egg_addon)
                <div class="mt-4">
                    <label class="label">{{ __('Add an egg') }} <span class="font-normal text-ink-soft">(+{{ $currency }} {{ number_format($eggFee / 100, 2) }})</span></label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="egg = 'none'" :class="egg === 'none' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-line text-ink'" class="rounded-xl border px-2 py-2.5 text-sm font-semibold transition">{{ __('No egg') }}</button>
                        <button type="button" @click="egg = 'fried'" :class="egg === 'fried' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-line text-ink'" class="rounded-xl border px-2 py-2.5 text-sm font-semibold transition">{{ __('Fried egg') }}</button>
                        <button type="button" @click="egg = 'half-boiled'" :class="egg === 'half-boiled' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-line text-ink'" class="rounded-xl border px-2 py-2.5 text-sm font-semibold transition">{{ __('Half-boiled') }}</button>
                    </div>
                </div>
            @endif

            {{-- Quantity --}}
            <div class="mt-4">
                <label class="label">{{ __('Quantity') }}</label>
                <div class="inline-flex items-center rounded-xl border border-line bg-surface">
                    <button type="button" @click="qty = Math.max(1, qty - 1)" class="grid h-11 w-11 place-items-center text-xl font-bold text-ink-soft hover:text-brand-700" aria-label="Decrease">−</button>
                    <input type="number" min="1" max="20" x-model.number="qty" @change="qty = Math.max(1, Math.min(20, parseInt(qty)||1))" class="h-11 w-14 border-x border-line text-center font-semibold outline-none">
                    <button type="button" @click="qty = Math.min(20, qty + 1)" class="grid h-11 w-11 place-items-center text-xl font-bold text-ink-soft hover:text-brand-700" aria-label="Increase">+</button>
                </div>
            </div>

            {{-- Note / spiciness --}}
            <div class="mt-4">
                <label for="note" class="label">
                    @if ($item->has_spice_note) {{ __('How spicy would you like it?') }} @else {{ __('Note') }} @endif
                    <span class="font-normal text-ink-soft">{{ __('(optional)') }}</span>
                </label>
                <textarea id="note" x-model="note" rows="2" maxlength="200" class="input"
                          placeholder="{{ $item->has_spice_note ? __('e.g. mild, medium, extra spicy, no spice') : __('e.g. no onions, extra spicy') }}"></textarea>
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <button type="button" @click="addToCart()" :disabled="!window.CAN_ORDER"
                        :title="window.CAN_ORDER ? '' : @js(__('Ordering is closed right now'))"
                        class="btn-primary flex-1 disabled:bg-black/20 disabled:text-white/70">
                    <span x-show="window.CAN_ORDER">{{ __('Add to cart') }} · <span x-text="window.CURRENCY + ' ' + window.money(unitPrice * qty)"></span></span>
                    <span x-show="!window.CAN_ORDER">{{ __('Ordering closed') }}</span>
                </button>
                <a href="{{ route('cart.index') }}" class="btn-secondary">{{ __('View cart') }}</a>
            </div>
        </div>
    </div>
@endsection
