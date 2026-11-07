<div class="card card-lift group flex flex-col overflow-hidden ring-1 ring-transparent transition-all duration-300 hover:ring-brand-100"
     data-search="{{ \Illuminate\Support\Str::lower(trim($item->localized_name.' '.$item->name.' '.($item->name_th ?? '').' '.$item->localized_description.' '.($item->hasProteins() ? implode(' ', (array) $item->proteins) : '').' '.(isset($category) ? $category->localized_name : ''))) }}"
     x-show="typeof itemVisible === 'undefined' ? true : itemVisible($el)" x-cloak
     x-data="{ item: { id: {{ $item->id }}, name: @js($item->localized_name), price: {{ $item->price }}, image: @js($item->image_url), isDrink: {{ $item->is_drink ? 'true' : 'false' }} } }">
    <a href="{{ route('menu.show', $item) }}" class="relative block overflow-hidden">
        <x-food-image :item="$item" imgClass="transition duration-500 ease-out group-hover:scale-110" />

        {{-- depth gradient so the price pill stays legible on any image --}}
        <span class="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-black/45 via-black/10 to-transparent"></span>
        {{-- soft sheen on hover --}}
        <span class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-0 transition duration-300 group-hover:opacity-100"></span>

        {{-- floating glass price pill --}}
        <span class="absolute bottom-2.5 left-2.5 inline-flex items-center rounded-full bg-white/90 px-3 py-1 text-sm font-bold text-brand-700 shadow-md ring-1 ring-black/5 backdrop-blur-sm transition duration-300 group-hover:-translate-y-0.5">
            {{ config('ordering.currency') }} {{ $item->price_formatted }}
        </span>
    </a>

    <div class="flex flex-1 flex-col p-4">
        <a href="{{ route('menu.show', $item) }}" class="font-semibold leading-snug transition hover:text-brand-700">{{ $item->localized_name }}</a>
        @if ($item->localized_description)
            <p class="mt-1 line-clamp-2 text-sm text-ink-soft">{{ $item->localized_description }}</p>
        @endif

        @if ($item->hasOptions())
            {{-- Has choices (protein / size / egg) — open the dish to pick them. --}}
            <a href="{{ route('menu.show', $item) }}"
               class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-50 px-3 py-2.5 text-sm font-bold text-brand-700 transition-all duration-200 hover:bg-gradient-to-r hover:from-brand-500 hover:to-brand-600 hover:text-white hover:shadow-lg hover:shadow-brand-500/30">
                <span>{{ __('Choose') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        @else
            <button type="button"
                    x-data="{ bump: false }"
                    @click="if (window.CAN_ORDER) { $store.cart.add(item, 1); bump = true; setTimeout(() => bump = false, 250) }"
                    :disabled="!window.CAN_ORDER"
                    :class="bump ? 'scale-[0.97]' : ''"
                    :title="window.CAN_ORDER ? @js(__('Add')) : @js(__('Ordering is closed right now'))"
                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-50 px-3 py-2.5 text-sm font-bold text-brand-700 transition-all duration-200 hover:bg-gradient-to-r hover:from-brand-500 hover:to-brand-600 hover:text-white hover:shadow-lg hover:shadow-brand-500/30 active:scale-[0.97] disabled:cursor-not-allowed disabled:bg-surface-2 disabled:text-ink-soft disabled:shadow-none disabled:hover:bg-surface-2 disabled:hover:text-ink-soft">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span>{{ __('Add') }}</span>
            </button>
        @endif
    </div>
</div>
