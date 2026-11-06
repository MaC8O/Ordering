@extends('layouts.storefront')

@section('title', __('About') . ' — ' . $storeStatus->restaurantName())

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold">{{ $settings->restaurant_name }}</h1>
            <p class="mt-1 text-ink-soft">{{ __('On-campus food delivery for the AIU community.') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="card p-5">
                <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">{{ __('Contact') }}</h2>
                @if ($settings->phone)<p class="mt-2 font-semibold">{{ $settings->phone }}</p>@endif
                @if ($settings->address)<p class="mt-1 text-sm text-ink-soft">{{ $settings->address }}</p>@endif
            </div>
            <div class="card p-5">
                <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">{{ __('Status') }}</h2>
                @if ($storeStatus->isOpen())
                    <p class="mt-2 font-semibold text-green-700">● {{ __('Open — accepting orders') }}</p>
                @else
                    <p class="mt-2 font-semibold text-amber-700">● {{ __('Closed right now') }}</p>
                    @if ($storeStatus->nextOpeningLabelLocalized())
                        <p class="mt-1 text-sm text-ink-soft">{{ __('Next opening') }}: {{ $storeStatus->nextOpeningLabelLocalized() }}</p>
                    @endif
                @endif
            </div>
        </div>

        <div class="card p-5">
            <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">{{ __('Opening hours') }}</h2>
            <dl class="mt-3 divide-y divide-line">
                @foreach ($schedule as $day)
                    <div class="flex justify-between py-2 text-sm">
                        <dt class="font-semibold">{{ __($day->weekday_name) }}</dt>
                        <dd class="text-ink-soft">
                            @if ($day->is_open)
                                {{ \Illuminate\Support\Str::of($day->open_time)->substr(0,5) }} – {{ \Illuminate\Support\Str::of($day->close_time)->substr(0,5) }}
                            @else
                                {{ __('Closed') }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-3 text-xs text-ink-soft">{{ __('Last orders are taken :minutes minutes before closing.', ['minutes' => $settings->cutoff_minutes_before_close]) }}</p>
        </div>

        <div class="card p-5">
            <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">{{ __('FAQ') }}</h2>
            <div class="mt-3 space-y-4 text-sm">
                <div>
                    <p class="font-semibold">{{ __('How do I pay?') }}</p>
                    <p class="text-ink-soft">{{ __('By QR bank transfer (upload a screenshot of your transfer) or cash on delivery.') }}</p>
                </div>
                <div>
                    <p class="font-semibold">{{ __('Where do you deliver?') }}</p>
                    <p class="text-ink-soft">{{ __('Anywhere on the AIU campus. Pick your dorm or building at checkout.') }}</p>
                </div>
                <div>
                    <p class="font-semibold">{{ __('Can I track my order?') }}</p>
                    <p class="text-ink-soft">{{ __('Yes — use the') }} <a href="{{ route('my-order.index') }}" class="font-semibold text-brand-700 underline">{{ __('My Order') }}</a> {{ __('page with your order number and phone.') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
