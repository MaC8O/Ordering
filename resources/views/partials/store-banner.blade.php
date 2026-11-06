@if (! $storeStatus->isOpen())
    <div class="border-b border-amber-200 bg-amber-50">
        <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3 text-sm text-amber-900">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            <p class="font-semibold">
                @if (! $storeStatus->manualOverrideOn())
                    {{ __("We're not accepting orders right now.") }}
                @elseif ($storeStatus->nextOpeningLabelLocalized())
                    {{ __("We're closed right now.") }} {{ __('Next opening') }}: {{ $storeStatus->nextOpeningLabelLocalized() }}.
                @else
                    {{ __("We're closed right now.") }} {{ __('Please check back later.') }}
                @endif
            </p>
        </div>
    </div>
@endif
