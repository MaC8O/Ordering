<div class="card group flex flex-col overflow-hidden"
     x-data="{ item: { id: {{ $item->id }}, name: @js($item->name), price: {{ $item->price }}, image: @js($item->image_path ? asset($item->image_path) : null) } }">
    <a href="{{ route('menu.show', $item) }}" class="block">
        <x-food-image :item="$item" />
    </a>
    <div class="flex flex-1 flex-col p-4">
        <a href="{{ route('menu.show', $item) }}" class="font-semibold leading-snug hover:text-brand-700">{{ $item->name }}</a>
        @if ($item->description)
            <p class="mt-1 line-clamp-2 text-sm text-ink-soft">{{ $item->description }}</p>
        @endif
        <div class="mt-3 flex items-center justify-between gap-2 pt-1">
            <span class="text-lg font-bold text-ink">{{ config('ordering.currency') }} {{ $item->price_formatted }}</span>

            <button type="button"
                    x-data="{ bump: false }"
                    @click="if (window.STORE_OPEN) { $store.cart.add(item, 1); bump = true; setTimeout(() => bump = false, 250) }"
                    :disabled="!window.STORE_OPEN"
                    :class="bump ? 'scale-110' : ''"
                    :title="window.STORE_OPEN ? 'Add to cart' : 'Ordering is closed right now'"
                    class="btn-primary !px-4 !py-2 disabled:bg-black/20 disabled:text-white/70">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add
            </button>
        </div>
    </div>
</div>
