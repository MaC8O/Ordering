@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Orders</h1>
        <a href="{{ route('admin.orders.create') }}" class="btn-primary !py-2 text-sm">+ New order</a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.orders.index') }}" class="card mb-5 p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="label">Search</label>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Order #, name, phone" class="input">
            </div>
            <div>
                <label class="label">Hall</label>
                <select name="dorm" class="input">
                    <option value="">All halls</option>
                    @foreach ($dorms as $dorm)
                        <option value="{{ $dorm }}" @selected(($filters['dorm'] ?? '') === $dorm)>{{ $dorm }}</option>
                    @endforeach
                    @if (($filters['dorm'] ?? '') !== '' && ! in_array($filters['dorm'], $dorms, true))
                        {{-- A hall no longer in settings but still used by older orders. --}}
                        <option value="{{ $filters['dorm'] }}" selected>{{ $filters['dorm'] }}</option>
                    @endif
                </select>
            </div>
            <div>
                <label class="label">Status</label>
                <select name="status" class="input">
                    <option value="">All</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Payment</label>
                <select name="payment_method" class="input">
                    <option value="">All</option>
                    <option value="qr" @selected(($filters['payment_method'] ?? '') === 'qr')>QR transfer</option>
                    <option value="cash" @selected(($filters['payment_method'] ?? '') === 'cash')>Cash</option>
                </select>
            </div>
            <div>
                <label class="label">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input">
            </div>
            <div>
                <label class="label">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input">
            </div>
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit" class="btn-primary !py-2">Filter</button>
            <a href="{{ route('admin.orders.index') }}" class="btn-ghost !py-2">Reset</a>
        </div>
    </form>

    <div x-data="bulkOrders()">
        {{-- Bulk action bar: appears when one or more orders are selected --}}
        <div x-show="selected.length > 0" x-cloak
             class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface/95 px-4 py-3 shadow-[0_-8px_24px_-18px_rgba(0,0,0,0.45)] backdrop-blur">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-2">
                <span class="text-sm font-semibold" x-text="`${selected.length} selected`"></span>

                {{-- Set status on all selected --}}
                <form method="POST" action="{{ route('admin.orders.bulkStatus') }}"
                      @submit="if (bulkStatus === 'cancelled' && ! confirm(`Cancel ${selected.length} order(s)? This can't be undone.`)) $event.preventDefault()"
                      class="flex flex-wrap items-center gap-2">
                    @csrf @method('PATCH')
                    <template x-for="id in selected" :key="id"><input type="hidden" name="order_ids[]" :value="id"></template>
                    <select name="order_status" x-model="bulkStatus" required class="input !w-auto !py-2 text-sm">
                        <option value="">Set status…</option>
                        @foreach ($bulkStatuses as $key)
                            <option value="{{ $key }}">{{ $statuses[$key] }}</option>
                        @endforeach
                    </select>
                    <button type="submit" :disabled="! bulkStatus" class="btn-primary !py-2 text-sm disabled:opacity-50"
                            x-text="`Update ${selected.length} ${selected.length === 1 ? 'order' : 'orders'}`"></button>
                </form>

                {{-- Delete the cancelled orders among the selection (others are skipped) --}}
                <form method="POST" action="{{ route('admin.orders.bulkDelete') }}"
                      @submit="if (! confirm(`Delete the cancelled orders among ${selected.length} selected? This permanently removes them and can't be undone.`)) $event.preventDefault()"
                      class="flex items-center gap-2">
                    @csrf @method('DELETE')
                    <template x-for="id in selected" :key="id"><input type="hidden" name="order_ids[]" :value="id"></template>
                    <button type="submit" class="btn-ghost !py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Delete selected</button>
                </form>

                <button type="button" @click="clear()" class="btn-ghost !py-2 text-sm">Clear</button>
            </div>
        </div>

        <div data-live-refresh="orders" :class="selected.length > 0 ? 'pb-24' : ''">
        @if ($orders->isEmpty())
            <div class="card p-8 text-center text-sm text-ink-soft">No orders match these filters.</div>
        @else
            {{-- Mobile: select-all + tap-friendly cards --}}
            <div class="sm:hidden">
                <label class="mb-3 flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" class="h-5 w-5 accent-brand-600" @change="toggleAll($event.target.checked)" :checked="allSelected">
                    Select all on this page
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
                                @if ($order->payment_method === 'cash' && $order->payment_status === 'pending')
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
                            <th class="w-10 px-4 py-3"><input type="checkbox" class="h-4 w-4 align-middle accent-brand-600" @change="toggleAll($event.target.checked)" :checked="allSelected" title="Select all"></th>
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
                                    @if ($order->payment_method === 'cash' && $order->payment_status === 'pending')
                                        <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800" title="Collect cash on delivery">💵 Collect on delivery</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">@include('admin.partials.status-badge', ['order' => $order])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $orders->links() }}</div>
        @endif
        </div>
    </div>

    <script>
        function bulkOrders() {
            return {
                selected: [],
                bulkStatus: '',
                allIds: @js($orders->pluck('id')->values()),
                isSelected(id) { return this.selected.includes(id); },
                toggle(id) {
                    const i = this.selected.indexOf(id);
                    if (i === -1) this.selected.push(id);
                    else this.selected.splice(i, 1);
                },
                get allSelected() {
                    return this.allIds.length > 0 && this.allIds.every((id) => this.selected.includes(id));
                },
                toggleAll(checked) {
                    if (checked) {
                        this.allIds.forEach((id) => { if (! this.selected.includes(id)) this.selected.push(id); });
                    } else {
                        this.selected = this.selected.filter((id) => ! this.allIds.includes(id));
                    }
                },
                clear() { this.selected = []; },
            };
        }
    </script>

    @include('admin.partials.live-feed')
@endsection
