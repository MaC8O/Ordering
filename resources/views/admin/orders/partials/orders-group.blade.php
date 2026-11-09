{{--
    Renders one group of orders (mobile cards + desktop table) for the admin
    orders list. Expects $orders (a collection or paginator page). Selection is
    driven by the parent page's bulkOrders() Alpine component; the select-all
    controls here act on this group's ids only.
--}}
@php $groupIds = $orders->pluck('id')->values(); @endphp

{{-- Mobile: select-all + tap-friendly cards --}}
<div class="sm:hidden">
    <label class="mb-3 flex items-center gap-2 text-sm font-semibold">
        <input type="checkbox" class="h-5 w-5 accent-brand-600"
               @change="toggleGroupAll(@js($groupIds), $event.target.checked)" :checked="allSelectedIn(@js($groupIds))">
        Select all ({{ $orders->count() }})
    </label>
    <div class="space-y-3">
        @foreach ($orders as $order)
            <div class="card flex items-start gap-3 p-4" :class="isSelected({{ $order->id }}) ? 'ring-2 ring-brand-400' : ''">
                <input type="checkbox" class="mt-1 h-5 w-5 shrink-0 accent-brand-600"
                       @change="toggle({{ $order->id }})" :checked="isSelected({{ $order->id }})">
                <a href="{{ route('admin.orders.show', $order) }}" class="block min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $order->order_number }}</p>
                            <p class="truncate text-sm">{{ $order->customer_name }}</p>
                            <p class="text-xs text-ink-soft">{{ $order->customer_phone }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            @include('admin.partials.status-badge', ['order' => $order])
                            <p class="mt-1 text-xs text-ink-soft">{{ $order->created_at->format('d M, H:i') }}</p>
                        </div>
                    </div>
                    @if ($order->scheduledDeliveryLabel())
                        <p class="mt-2 text-sm"><span class="text-ink-soft">Delivery</span> <span class="font-semibold">🕒 {{ $order->scheduledDeliveryLabel() }}</span></p>
                    @endif
                    <div class="mt-3 flex items-center justify-between gap-3 border-t border-line pt-3 text-sm">
                        <span class="text-ink-soft">{{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }}</span>
                        <span class="flex items-center gap-1.5">
                            @include('admin.partials.payment-badge', ['order' => $order])
                            <span class="font-semibold">{{ config('ordering.currency') }} {{ $order->total_formatted }}</span>
                        </span>
                    </div>
                    @if ($order->isCash() && $order->payment_status === 'pending')
                        <p class="mt-2"><span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">💵 Cash — collect on delivery</span></p>
                    @endif
                </a>
            </div>
        @endforeach
    </div>
</div>

{{-- Desktop: table --}}
<div class="hidden overflow-x-auto rounded-2xl border border-line bg-surface sm:block">
    <table class="w-full text-left text-sm">
        <thead class="bg-surface-2 text-xs uppercase tracking-wide text-ink-soft">
            <tr>
                <th class="w-10 px-4 py-3"><input type="checkbox" class="h-4 w-4 align-middle accent-brand-600"
                    @change="toggleGroupAll(@js($groupIds), $event.target.checked)" :checked="allSelectedIn(@js($groupIds))" title="Select all"></th>
                <th class="px-4 py-3">Order</th>
                <th class="px-4 py-3">Time</th>
                <th class="px-4 py-3">Scheduled</th>
                <th class="px-4 py-3">Customer</th>
                <th class="px-4 py-3">Items</th>
                <th class="px-4 py-3">Total</th>
                <th class="px-4 py-3">Payment</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @foreach ($orders as $order)
                <tr class="cursor-pointer hover:bg-brand-50/50" :class="isSelected({{ $order->id }}) ? 'bg-brand-50/60' : ''" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                    <td class="px-4 py-3" @click.stop>
                        <input type="checkbox" class="h-4 w-4 align-middle accent-brand-600" @change="toggle({{ $order->id }})" :checked="isSelected({{ $order->id }})">
                    </td>
                    <td class="px-4 py-3 font-semibold">{{ $order->order_number }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-ink-soft">{{ $order->created_at->format('d M, H:i') }}</td>
                    <td class="whitespace-nowrap px-4 py-3">{{ $order->scheduledDeliveryLabel() ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $order->customer_name }}<span class="block text-xs text-ink-soft">{{ $order->customer_phone }}</span></td>
                    <td class="px-4 py-3">{{ $order->items_count }}</td>
                    <td class="whitespace-nowrap px-4 py-3 font-semibold">{{ config('ordering.currency') }} {{ $order->total_formatted }}</td>
                    <td class="px-4 py-3">
                        @include('admin.partials.payment-badge', ['order' => $order])<span class="ml-1 text-xs text-ink-soft">{{ strtoupper($order->payment_method) }}</span>
                        @if ($order->hasReceipt())
                            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800" title="Payment receipt attached">
                                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 5a3 3 0 0 1 6 0v6a5 5 0 0 1-10 0V7a1 1 0 1 1 2 0v4a3 3 0 0 0 6 0V5a1 1 0 1 0-2 0v6a1 1 0 1 1-2 0V5Z" clip-rule="evenodd"/></svg>
                                Receipt
                            </span>
                        @elseif ($order->payment_method === 'qr')
                            <span class="mt-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800" title="No payment receipt uploaded">No receipt</span>
                        @endif
                        @if ($order->isCash() && $order->payment_status === 'pending')
                            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800" title="Collect cash on delivery">💵 Collect on delivery</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">@include('admin.partials.status-badge', ['order' => $order])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
