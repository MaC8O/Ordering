@php
    $cls = [
        'pending' => 'bg-amber-100 text-amber-800',
        'verified' => 'bg-green-100 text-green-800',
        'failed' => 'bg-red-100 text-red-800',
    ][$order->payment_status] ?? 'bg-gray-100 text-gray-800';
@endphp
<span class="badge {{ $cls }}">{{ $order->payment_status_label }}</span>
