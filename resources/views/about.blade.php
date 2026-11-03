@extends('layouts.storefront')

@section('title', 'About — ' . $storeStatus->restaurantName())

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold">{{ $settings->restaurant_name }}</h1>
            <p class="mt-1 text-ink-soft">On-campus food delivery for the AIU community.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="card p-5">
                <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">Contact</h2>
                @if ($settings->phone)<p class="mt-2 font-semibold">{{ $settings->phone }}</p>@endif
                @if ($settings->address)<p class="mt-1 text-sm text-ink-soft">{{ $settings->address }}</p>@endif
            </div>
            <div class="card p-5">
                <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">Status</h2>
                @if ($storeStatus->isOpen())
                    <p class="mt-2 font-semibold text-green-700">● Open — accepting orders</p>
                @else
                    <p class="mt-2 font-semibold text-amber-700">● Closed right now</p>
                    @if ($storeStatus->nextOpeningLabel())
                        <p class="mt-1 text-sm text-ink-soft">Next opening: {{ $storeStatus->nextOpeningLabel() }}</p>
                    @endif
                @endif
            </div>
        </div>

        <div class="card p-5">
            <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">Opening hours</h2>
            <dl class="mt-3 divide-y divide-line">
                @foreach ($schedule as $day)
                    <div class="flex justify-between py-2 text-sm">
                        <dt class="font-semibold">{{ $day->weekday_name }}</dt>
                        <dd class="text-ink-soft">
                            @if ($day->is_open)
                                {{ \Illuminate\Support\Str::of($day->open_time)->substr(0,5) }} – {{ \Illuminate\Support\Str::of($day->close_time)->substr(0,5) }}
                            @else
                                Closed
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-3 text-xs text-ink-soft">Last orders are taken {{ $settings->cutoff_minutes_before_close }} minutes before closing.</p>
        </div>

        <div class="card p-5">
            <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">FAQ</h2>
            <div class="mt-3 space-y-4 text-sm">
                <div>
                    <p class="font-semibold">How do I pay?</p>
                    <p class="text-ink-soft">By QR bank transfer (upload a screenshot of your transfer) or cash on delivery.</p>
                </div>
                <div>
                    <p class="font-semibold">Where do you deliver?</p>
                    <p class="text-ink-soft">Anywhere on the AIU campus. Pick your dorm or building at checkout.</p>
                </div>
                <div>
                    <p class="font-semibold">Can I track my order?</p>
                    <p class="text-ink-soft">Yes — use the <a href="{{ route('my-order.index') }}" class="font-semibold text-brand-700 underline">My Order</a> page with your order number and phone.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
