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
        @if ($activeCount === 0 && $completedCount === 0)
            <div class="card p-8 text-center text-sm text-ink-soft">No orders match these filters.</div>
        @else
            {{-- Pending / In Progress — always shown first, in full --}}
            <section class="mb-8">
                <div class="mb-3 flex items-center gap-2">
                    <h2 class="text-lg font-bold">Pending / In Progress</h2>
                    <span class="badge bg-brand-100 text-brand-800">{{ $activeCount }}</span>
                </div>
                @if ($activeOrders->isEmpty())
                    <div class="card p-6 text-center text-sm text-ink-soft">No active orders right now.</div>
                @else
                    @include('admin.orders.partials.orders-group', ['orders' => $activeOrders])
                @endif
            </section>

            {{-- Completed — collapsed by default on mobile, always open on wider screens.
                 Open state lives in the parent component so the live refresh can't reset it. --}}
            <section>
                <button type="button" @click="completedOpen = ! completedOpen" class="mb-3 flex w-full items-center gap-2 text-left">
                    <h2 class="text-lg font-bold">Completed</h2>
                    <span class="badge bg-surface-2 text-ink-soft">{{ $completedCount }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-auto h-5 w-5 text-ink-soft transition sm:hidden" :class="completedOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="completedOpen" x-cloak>
                    @if ($completedOrders->isEmpty())
                        <div class="card p-6 text-center text-sm text-ink-soft">No completed orders{{ ($activeCount > 0) ? ' yet' : '' }}.</div>
                    @else
                        @include('admin.orders.partials.orders-group', ['orders' => $completedOrders])
                        <div class="mt-4">{{ $completedOrders->links() }}</div>
                    @endif
                </div>
            </section>
        @endif
        </div>
    </div>

    <script>
        function bulkOrders() {
            return {
                selected: [],
                bulkStatus: '',
                completedOpen: window.innerWidth >= 640,
                isSelected(id) { return this.selected.includes(id); },
                toggle(id) {
                    const i = this.selected.indexOf(id);
                    if (i === -1) this.selected.push(id);
                    else this.selected.splice(i, 1);
                },
                allSelectedIn(ids) {
                    return ids.length > 0 && ids.every((id) => this.selected.includes(id));
                },
                toggleGroupAll(ids, checked) {
                    if (checked) {
                        ids.forEach((id) => { if (! this.selected.includes(id)) this.selected.push(id); });
                    } else {
                        this.selected = this.selected.filter((id) => ! ids.includes(id));
                    }
                },
                clear() { this.selected = []; },
            };
        }
    </script>

    @include('admin.partials.live-feed')
@endsection
