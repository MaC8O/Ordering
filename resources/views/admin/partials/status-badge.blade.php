@php
    $cls = [
        'pending' => 'bg-gray-100 text-gray-700',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'preparing' => 'bg-brand-100 text-brand-800',
        'out_for_delivery' => 'bg-indigo-100 text-indigo-800',
        'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
    ][$order->order_status] ?? 'bg-gray-100 text-gray-700';
@endphp
<span class="badge {{ $cls }}">{{ $order->order_status_label }}</span>
