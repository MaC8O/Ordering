<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Order slip') }} — {{ $orders->count() === 1 ? $orders->first()->order_number : $orders->count() }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Noto Sans Thai', ui-monospace, 'Courier New', monospace;
            color: #000; margin: 0; padding: 16px;
        }
        /* One slip per page; sized for an 80mm receipt but centred and
           readable on A4 too. */
        .ticket { width: 72mm; max-width: 100%; margin: 0 auto; }
        .ticket + .ticket { margin-top: 24px; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .muted { color: #444; font-size: 12px; margin: 0; }
        hr { border: none; border-top: 1px dashed #000; margin: 10px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; font-size: 13px; margin: 3px 0; }
        .item-name { flex: 1; }
        .total { font-weight: bold; font-size: 15px; }
        .opts { font-size: 12px; color: #222; margin: 1px 0 4px; padding-left: 4px; }
        .note { font-size: 12px; font-style: italic; color: #333; margin: 1px 0 4px; padding-left: 4px; }
        .btns { max-width: 320px; margin: 16px auto 0; display: flex; gap: 8px; }
        button { flex: 1; padding: 10px; font-size: 13px; cursor: pointer; }

        @media print {
            .btns { display: none; }
            body { padding: 0; }
            /* Each slip starts on its own page when printing several at once. */
            .ticket { page-break-after: always; }
            .ticket:last-child { page-break-after: auto; }
        }
        @media screen {
            .ticket {
                border: 1px solid #ddd; border-radius: 8px; padding: 16px;
                box-shadow: 0 1px 4px rgba(0,0,0,.08);
            }
            body { background: #f4f4f5; }
        }
    </style>
</head>
<body onload="window.print()">
    @foreach ($orders as $order)
        @include('admin.orders.partials.slip', ['order' => $order, 'settings' => $settings, 'lang' => $lang])
    @endforeach

    <div class="btns">
        <button onclick="window.print()">{{ __('Print') }}</button>
        <button onclick="window.close()">{{ __('Close') }}</button>
    </div>
</body>
</html>
