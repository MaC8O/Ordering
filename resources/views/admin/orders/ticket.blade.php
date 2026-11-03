<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: ui-monospace, 'Courier New', monospace; color: #000; margin: 0; padding: 16px; }
        .ticket { max-width: 320px; margin: 0 auto; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .muted { color: #444; font-size: 12px; }
        hr { border: none; border-top: 1px dashed #000; margin: 10px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; font-size: 13px; margin: 3px 0; }
        .item-name { flex: 1; }
        .total { font-weight: bold; font-size: 15px; }
        .note { font-size: 12px; font-style: italic; color: #333; }
        .btns { max-width: 320px; margin: 16px auto 0; display: flex; gap: 8px; }
        button { flex: 1; padding: 10px; font-size: 13px; cursor: pointer; }
        @media print { .btns { display: none; } body { padding: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="ticket">
        <h1>{{ $settings->restaurant_name }}</h1>
        <p class="muted">KITCHEN TICKET</p>
        <hr>
        <div class="row"><span>Order</span><strong>{{ $order->order_number }}</strong></div>
        <div class="row"><span>Time</span><span>{{ $order->created_at->format('d M, H:i') }}</span></div>
        <div class="row"><span>Payment</span><span>{{ strtoupper($order->payment_method) }} · {{ $order->payment_status_label }}</span></div>
        <hr>
        <div class="row"><span>{{ $order->customer_name }}</span><span>{{ $order->customer_phone }}</span></div>
        <div class="row"><span>{{ $order->delivery_dorm }}</span><span>Room {{ $order->delivery_room }}</span></div>
        @if ($order->preferred_time)<div class="row"><span>Preferred</span><span>{{ \Illuminate\Support\Str::of($order->preferred_time)->substr(0,5) }}</span></div>@endif
        @if ($order->delivery_notes)<p class="note">Delivery: {{ $order->delivery_notes }}</p>@endif
        <hr>
        @foreach ($order->items as $line)
            <div class="row">
                <span class="item-name">{{ $line->quantity }}× {{ $line->item_name_snapshot }}</span>
                <span>{{ config('ordering.currency') }} {{ $line->line_total_formatted }}</span>
            </div>
            @if ($line->item_note)<p class="note">↳ {{ $line->item_note }}</p>@endif
        @endforeach
        <hr>
        <div class="row"><span>Subtotal</span><span>{{ config('ordering.currency') }} {{ $order->subtotal_formatted }}</span></div>
        <div class="row"><span>Delivery</span><span>{{ config('ordering.currency') }} {{ $order->delivery_fee_formatted }}</span></div>
        <div class="row total"><span>TOTAL</span><span>{{ config('ordering.currency') }} {{ $order->total_formatted }}</span></div>
        @if ($order->notes)<hr><p class="note">Notes: {{ $order->notes }}</p>@endif
        <hr>
        <p class="muted" style="text-align:center">Thank you!</p>
    </div>
    <div class="btns">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
