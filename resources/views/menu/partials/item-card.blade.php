<div class="card card-lift group flex flex-col overflow-hidden"
     x-data="{ item: { id: {{ $item->id }}, name: @js($item->localized_name), price: {{ $item->price }}, image: @js($item->image_path ? asset($item->image_path) : null) } }">
    <a href="{{ route('menu.show', $item) }}" class="relative block overflow-hidden">
        <x-food-image :item="$item" />
        <span class="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black/25 to-transparent opacity-0 transition group-hover:opacity-100"></span>
    </a>
    <div class="flex flex-1 flex-col p-4">
        <a href="{{ route('menu.show', $item) }}" class="font-semibold leading-snug transition hover:text-brand-700">{{ $item->localized_name }}</a>
        @if ($item->localized_description)
            <p class="mt-1 line-clamp-2 text-sm text-ink-soft">{{ $item->localized_description }}</p>
        @endif
        <div class="mt-3 flex items-center justify-between gap-2 pt-1">
            <span class="price">{{ config('ordering.currency') }} {{ $item->price_formatted }}</span>

            <button type="button"
                    x-data="{ bump: false }"
                    @click="if (window.STORE_OPEN) { $store.cart.add(item, 1); bump = true; setTimeout(() => bump = false, 250) }"
                    :disabled="!window.STORE_OPEN"
                    :class="bump ? 'scale-110' : ''"
                    :title="window.STORE_OPEN ? @js(__('Add')) : @js(__('Ordering is closed right now'))"
                    aria-label="{{ __('Add') }}"
                    class="add-fab">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            </button>
        </div>
    </div>
</div>
