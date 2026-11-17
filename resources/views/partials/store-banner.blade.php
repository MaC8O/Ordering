@if (! $storeStatus->isOpen())
    @if ($preorderStatus->isOpen())
        {{-- Store closed, but the weekly preorder batch is open — turn the
             "closed" moment into an invitation rather than a dead end. --}}
        <a href="{{ route('preorder.create') }}" class="group block border-b border-violet-400/50 bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-700 text-white transition hover:from-indigo-500 hover:via-violet-500 hover:to-purple-600">
            <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:gap-4 sm:py-5">
                {{-- Icon + text: both lines share the same left edge (the text block
                     is its own column, so line two never tucks under the icon). --}}
                <div class="flex min-w-0 items-center gap-3 sm:flex-1 sm:gap-4">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white/15 text-xl backdrop-blur">🗓️</span>
                    <div class="min-w-0 text-sm leading-snug">
                        <p class="min-w-0">
                            <span class="font-bold">{{ __('Preorder is open') }}</span>
                            <span class="font-normal text-indigo-50/90">· {{ $preorderStatus->windowLabel() }}</span>
                        </p>
                        @if ($preorderStatus->closesAtLabel())
                            <p class="mt-0.5 text-[0.8125rem] text-white/75">{{ __('Order before :deadline', ['deadline' => $preorderStatus->closesAtLabel()]) }}</p>
                        @endif
                    </div>
                </div>

                {{-- Actions: countdown + button. Side by side, full width on mobile. --}}
                <div class="flex w-full items-center gap-2 sm:w-auto sm:gap-3">
                    @include('partials.preorder-deadline', ['variant' => 'banner'])
                    <span class="inline-flex flex-1 shrink-0 items-center justify-center rounded-full bg-white px-4 py-2 text-sm font-bold text-indigo-700 shadow-sm transition group-hover:-translate-y-0.5 sm:flex-none">{{ __('Preorder now') }} →</span>
                </div>
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
