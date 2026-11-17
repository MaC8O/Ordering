{{--
    A single order slip. Rendered once per order (one slip per printed page).

    Expects: $order (with items + items.menuItem loaded), $settings, $lang ('en'|'th').
    The surrounding page sets the app locale to $lang, so __() and Carbon
    localisation already resolve to the chosen slip language.
--}}
@php
    $currency = config('ordering.currency');
    // Translate the stored option string ("Chicken · Large · Fried egg") token
    // by token on the Thai slip; English keeps the order-time snapshot as-is.
    $translateOptions = function (?string $opts) use ($lang) {
        if (! $opts) {
            return null;
        }
        if ($lang !== 'th') {
            return $opts;
        }

        return collect(explode(' · ', $opts))
            ->map(fn ($token) => __(trim($token)))
            ->implode(' · ');
    };
@endphp

<div class="ticket">
    <h1>{{ $settings->restaurant_name }}</h1>
    <p class="muted">{{ __('ORDER SLIP') }}</p>
    <hr>

    <div class="row"><span>{{ __('Order') }}</span><strong>{{ $order->order_number }}</strong></div>
    <div class="row"><span>{{ __('Time') }}</span><span>{{ $order->created_at->locale($lang)->isoFormat('D MMM YYYY, HH:mm') }}</span></div>
    @if ($order->is_preorder)
        <div class="row"><span>{{ __('Type') }}</span><span>{{ __('Preorder') }}</span></div>
    @endif
    <hr>

    {{-- Customer + delivery --}}
    <div class="row"><span>{{ __('Name') }}</span><span>{{ $order->customer_name }}</span></div>
    <div class="row"><span>{{ __('Phone') }}</span><span>{{ $order->customer_phone }}</span></div>
    <div class="row"><span>{{ __('Hall') }}</span><span>{{ $order->delivery_dorm }}</span></div>
    @if ($order->delivery_room)
        <div class="row"><span>{{ __('Room') }}</span><span>{{ $order->delivery_room }}</span></div>
    @endif
    @if ($order->delivery_date)
        <div class="row"><span>{{ __('Date') }}</span><span>{{ $order->delivery_date->locale($lang)->isoFormat('ddd, D MMM YYYY') }}</span></div>
    @endif
    @if ($order->preferred_time)
        <div class="row"><span>{{ __('Preferred') }}</span><span>{{ \Illuminate\Support\Str::of($order->preferred_time)->substr(0, 5) }}</span></div>
    @endif
    @if ($order->delivery_notes)
        <p class="note">{{ __('Delivery') }}: {{ $order->delivery_notes }}</p>
    @endif
    <hr>

    {{-- Items --}}
    @foreach ($order->items as $line)
        @php
            $dishName = $lang === 'th'
                ? ($line->menuItem?->name_th ?: $line->item_name_snapshot)
                : $line->item_name_snapshot;
            $opts = $translateOptions($line->options_snapshot);
        @endphp
        <div class="row">
            <span class="item-name"><strong>{{ $line->quantity }}×</strong> {{ $dishName }}</span>
            <span>{{ $currency }} {{ $line->line_total_formatted }}</span>
        </div>
        @if ($opts)<p class="opts">↳ {{ $opts }}</p>@endif
        @if ($line->item_note)<p class="note">↳ {{ $line->item_note }}</p>@endif
    @endforeach
    <hr>

    {{-- Money --}}
    <div class="row"><span>{{ __('Food subtotal') }}</span><span>{{ $currency }} {{ $order->subtotal_formatted }}</span></div>
    <div class="row"><span>{{ __('Delivery fee') }}</span><span>{{ $currency }} {{ $order->delivery_fee_formatted }}</span></div>
    @php($deliveryBreakdown = $order->deliveryFeeBreakdown())
    @if ($deliveryBreakdown)
        <p class="opts">↳ {{ $deliveryBreakdown['qty'] }} {{ $deliveryBreakdown['qty'] === 1 ? __('item') : __('items') }} × {{ $currency }} {{ number_format($deliveryBreakdown['unit'] / 100, 2) }} = {{ $currency }} {{ $order->delivery_fee_formatted }}</p>
    @endif
    <div class="row total"><span>{{ __('TOTAL') }}</span><span>{{ $currency }} {{ $order->total_formatted }}</span></div>
    <hr>

    {{-- Payment --}}
    <div class="row"><span>{{ __('Payment method') }}</span><span>{{ $order->payment_method === 'cash' ? __('Cash') : __('QR transfer') }}</span></div>
    <div class="row"><span>{{ __('Payment status') }}</span><span>{{ __($order->payment_status_label) }}</span></div>

    @if ($order->notes)
        <hr>
        <p class="note">{{ __('Notes') }}: {{ $order->notes }}</p>
    @endif

    <hr>
    <p class="muted" style="text-align:center">{{ __('Thank you!') }}</p>
</div>
