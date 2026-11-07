@if (! $storeStatus->isOpen())
    @if ($preorderStatus->isOpen())
        {{-- Store closed, but the weekly preorder batch is open — turn the
             "closed" moment into an invitation rather than a dead end. --}}
        <a href="{{ route('preorder.create') }}" class="group block border-b border-indigo-300/60 bg-gradient-to-r from-indigo-600 to-violet-700 text-white transition hover:from-indigo-500 hover:to-violet-600">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3 text-sm">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white/15 text-lg backdrop-blur">🗓️</span>
                <p class="min-w-0 font-semibold">
                    {{ __('Preorder is open!') }}
                    <span class="font-normal text-indigo-50/90">{{ __('Reserve your meal for :window.', ['window' => $preorderStatus->windowLabel()]) }}</span>
                </p>
                <span class="ml-auto hidden shrink-0 items-center rounded-full bg-white px-3.5 py-1.5 text-xs font-bold text-indigo-700 shadow-sm transition group-hover:-translate-y-0.5 sm:inline-flex">{{ __('Preorder now') }} →</span>
            </div>
        </a>
    @else
        <div class="border-b border-amber-200 bg-amber-50">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3 text-sm text-amber-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <p class="font-semibold">
                    @if ($storeStatus->nextOpeningLabelLocalized())
                        {{ __("The kitchen's resting right now.") }} {{ __('Next opening') }}: {{ $storeStatus->nextOpeningLabelLocalized() }}.
                    @else
                        {{ __("The kitchen's resting right now — we'll be back soon!") }}
                    @endif
                </p>
            </div>
        </div>
    @endif
@endif
