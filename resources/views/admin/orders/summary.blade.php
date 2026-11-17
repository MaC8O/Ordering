<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Summary sheet') }} — {{ $printedAt->format('d M Y, H:i') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Noto Sans Thai', ui-sans-serif, system-ui, 'Segoe UI', Arial, sans-serif;
            color: #000; margin: 0; padding: 14px; font-size: 12px; line-height: 1.4;
        }
        /* Single fluid column: fills A4 width, and reflows narrow enough to stay
           readable when sent to an 80mm receipt printer. */
        .sheet { width: 190mm; max-width: 100%; margin: 0 auto; }

        h1 { font-size: 18px; margin: 0 0 2px; }
        h2 {
            font-size: 13px; margin: 14px 0 6px; text-transform: uppercase;
            letter-spacing: .04em; border-bottom: 2px solid #000; padding-bottom: 3px;
        }
        .muted { color: #444; }
        .meta { font-size: 12px; margin: 2px 0 0; }
        .meta strong { font-size: 13px; }
        .badge {
            display: inline-block; border: 1px solid #000; border-radius: 4px;
            padding: 1px 6px; font-weight: 700; font-size: 11px;
        }
        hr { border: none; border-top: 1px dashed #888; margin: 10px 0; }

        /* Kitchen totals */
        .cat { font-weight: 700; margin: 10px 0 2px; font-size: 12px; text-decoration: underline; }
        .dish { display: flex; gap: 8px; align-items: baseline; padding: 3px 0; border-bottom: 1px dotted #ccc; }
        .dish .qty { font-weight: 700; min-width: 34px; font-size: 13px; }
        .dish .name { flex: 1; }
        .dish .opts { color: #333; font-size: 11px; }
        .spice { font-size: 11px; font-style: italic; color: #333; margin: 1px 0 3px 34px; }

        /* Order list */
        .hall { font-weight: 700; margin: 10px 0 3px; font-size: 12px; background: #eee; padding: 2px 6px; }
        .order { padding: 5px 0; border-bottom: 1px dotted #ccc; page-break-inside: avoid; }
        .order-head { display: flex; flex-wrap: wrap; gap: 4px 10px; align-items: baseline; }
        .order-head .num { font-weight: 700; font-size: 13px; }
        .order-line { margin: 1px 0; }
        .order-line .label { color: #555; }
        .items { margin: 2px 0 0; }
        .items .li { display: inline; }
        .note { font-style: italic; color: #333; }

        /* Footer */
        .footer { margin-top: 14px; border-top: 2px solid #000; padding-top: 8px; display: flex; flex-wrap: wrap; gap: 6px 24px; font-weight: 700; }

        .btns { max-width: 320px; margin: 18px auto 0; display: flex; gap: 8px; }
        button { flex: 1; padding: 10px; font-size: 13px; cursor: pointer; }

        @media print {
            @page { size: A4; margin: 10mm; }
            .btns { display: none; }
            body { padding: 0; font-size: 11px; }
            .sheet { width: 100%; }
        }
        @media screen {
            body { background: #f4f4f5; }
            .sheet {
                background: #fff; border: 1px solid #ddd; border-radius: 8px;
                padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.08);
            }
        }
    </style>
</head>
<body onload="window.print()">
@php
    $currency = config('ordering.currency');

    // Translate a stored option string ("Chicken · Large · Fried egg") token by
    // token on the Thai sheet; English keeps the order-time snapshot as-is.
    $tOpts = function (?string $opts) use ($lang) {
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

    // Dish / category names in the chosen language, falling back to English.
    $dishName = fn (array $g) => $lang === 'th' ? ($g['dish_th'] ?: $g['dish']) : $g['dish'];
    $catName = fn (array $g) => $g['category']
        ? ($lang === 'th' ? ($g['category_th'] ?: $g['category']) : $g['category'])
        : '—';

    $batchLabel = match ($batchType) {
        'preorder' => __('Preorder batch'),
        'regular' => __('Regular orders'),
        default => __('Orders'),
    };
@endphp

<div class="sheet">
    {{-- 1. Header --}}
    <h1>{{ $settings->restaurant_name }}</h1>
    <p class="muted">{{ __('Summary sheet') }}</p>
    <p class="meta">
        <span class="badge">{{ $batchLabel }}</span>
        &nbsp;·&nbsp; <strong>{{ $orders->count() }}</strong> {{ trans_choice('{1} order|[2,*] orders', $orders->count()) }}
        @if ($batchType === 'preorder' && $batchDate)
            &nbsp;·&nbsp; {{ __('Delivery') }}: {{ $batchDate->locale($lang)->isoFormat('ddd, D MMM YYYY') }}
        @endif
    </p>
    <p class="meta muted">{{ __('Printed') }}: {{ $printedAt->locale($lang)->isoFormat('D MMM YYYY, HH:mm') }}</p>

    {{-- 2. Kitchen totals --}}
    <h2>{{ __('Kitchen totals') }}</h2>
    @php $lastCat = null; @endphp
    @foreach ($kitchen as $g)
        @if ($catName($g) !== $lastCat)
            <div class="cat">{{ $catName($g) }}</div>
            @php $lastCat = $catName($g); @endphp
        @endif
        <div class="dish">
            <span class="qty">{{ $g['qty'] }}×</span>
            <span class="name">
                {{ $dishName($g) }}
                @if ($g['options'])<span class="opts">— {{ $tOpts($g['options']) }}</span>@endif
            </span>
        </div>
        @foreach ($g['notes'] as $n)
            <div class="spice">{{ $n['number'] }}: {{ $n['note'] }}</div>
        @endforeach
    @endforeach

    {{-- 3. Order list (sorted by hall) --}}
    <h2>{{ __('Order list') }}</h2>
    @php $lastHall = null; @endphp
    @foreach ($orders as $order)
        @php $hall = $order->delivery_dorm ?: '—'; @endphp
        @if ($hall !== $lastHall)
            <div class="hall">{{ __('Hall') }}: {{ $hall }}</div>
            @php $lastHall = $hall; @endphp
        @endif
        <div class="order">
            <div class="order-head">
                <span class="num">{{ $order->order_number }}</span>
                <span>{{ $order->customer_name }}</span>
                <span class="muted">{{ $order->customer_phone }}</span>
                @if ($order->delivery_room)
                    <span class="muted">{{ __('Room') }} {{ $order->delivery_room }}</span>
                @endif
            </div>
            <div class="order-line items">
                @foreach ($order->items as $line)
                    @php
                        $nm = $lang === 'th' ? ($line->menuItem?->name_th ?: $line->item_name_snapshot) : $line->item_name_snapshot;
                        $op = $tOpts($line->options_snapshot);
                    @endphp
                    <span class="li"><strong>{{ $line->quantity }}×</strong> {{ $nm }}@if ($op) ({{ $op }})@endif
                        @if ($line->item_note)<span class="note">[{{ $line->item_note }}]</span>@endif</span>@unless ($loop->last) · @endunless
                @endforeach
            </div>
            @if ($order->delivery_notes)
                <div class="order-line"><span class="label">{{ __('Delivery') }}:</span> <span class="note">{{ $order->delivery_notes }}</span></div>
            @endif
        </div>
    @endforeach

    {{-- 4. Footer --}}
    <div class="footer">
        <span>{{ __('Total food value') }}: {{ $currency }} {{ number_format($totalFood / 100, 2) }}</span>
        <span>{{ __('Total items') }}: {{ $totalItems }}</span>
    </div>
</div>

<div class="btns">
    <button onclick="window.print()">{{ __('Print') }}</button>
    <button onclick="window.close()">{{ __('Close') }}</button>
</div>
</body>
</html>
