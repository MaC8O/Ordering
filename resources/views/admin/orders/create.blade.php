@extends('layouts.admin')

@section('title', 'New manual order')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">New manual order</h1>
        <a href="{{ route('admin.orders.index') }}" class="btn-ghost !py-2 text-sm">← Back to orders</a>
    </div>

    <p class="mb-5 max-w-2xl text-sm text-ink-soft">
        Log an order by hand — e.g. a customer who transferred by QR but didn't finish checkout.
        Item prices are calculated automatically; set the payment status to match what you've received.
    </p>

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.orders.store') }}" x-data="manualOrder()" class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        @csrf

        <div class="space-y-6">
            {{-- Customer & delivery --}}
            <section class="card p-5">
                <h2 class="text-lg font-bold">Customer & delivery</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Full name</label>
                        <input name="customer_name" value="{{ old('customer_name') }}" class="input" required>
                    </div>
                    <div>
                        <label class="label">Phone number</label>
                        <input name="customer_phone" value="{{ old('customer_phone') }}" inputmode="tel" placeholder="+60123456789" class="input" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Dorm / building / address</label>
                        <input name="delivery_dorm" value="{{ old('delivery_dorm') }}" list="dorm_suggestions" autocomplete="off" class="input" required>
                        @if (!empty($dorms))
                            <datalist id="dorm_suggestions">
                                @foreach ($dorms as $dorm)<option value="{{ $dorm }}"></option>@endforeach
                            </datalist>
                        @endif
                    </div>
                    <div>
                        <label class="label">Delivery date</label>
                        <input name="delivery_date" type="date" value="{{ old('delivery_date', now()->toDateString()) }}" class="input" required>
                    </div>
                    <div>
                        <label class="label">Preferred time <span class="font-normal text-ink-soft">(optional)</span></label>
                        <input name="preferred_time" type="time" value="{{ old('preferred_time') }}" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Delivery notes <span class="font-normal text-ink-soft">(optional)</span></label>
                        <textarea name="delivery_notes" rows="2" maxlength="500" class="input">{{ old('delivery_notes') }}</textarea>
                    </div>
                </div>
            </section>

            {{-- Items --}}
            <section class="card p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold">Items</h2>
                    <button type="button" @click="addRow()" class="btn-ghost !px-3 !py-1.5 text-sm">+ Add item</button>
                </div>

                <div class="mt-4 space-y-4">
                    <template x-for="(row, i) in rows" :key="row.uid">
                        <div class="rounded-xl border border-line bg-surface-2 p-3">
                            <div class="grid gap-3 sm:grid-cols-[1fr_5rem_auto]">
                                <div>
                                    <label class="label !text-xs">Dish</label>
                                    <select :name="`items[${i}][id]`" x-model.number="row.id" @change="onItemChange(row)" class="input !py-2" required>
                                        <option value="">— choose —</option>
                                        <template x-for="it in items" :key="it.id">
                                            <option :value="it.id" x-text="it.category + ' · ' + it.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="label !text-xs">Qty</label>
                                    <input :name="`items[${i}][qty]`" x-model.number="row.qty" type="number" min="1" max="20" class="input !py-2" required>
                                </div>
                                <div class="flex items-end">
                                    <button type="button" @click="removeRow(i)" class="grid h-10 w-10 place-items-center rounded-lg text-lg text-red-600 hover:bg-red-50" title="Remove">×</button>
                                </div>
                            </div>

                            {{-- Options for the chosen dish --}}
                            <div class="mt-3 grid gap-3 sm:grid-cols-3" x-show="meta(row)">
                                <div x-show="meta(row) && meta(row).proteins.length">
                                    <label class="label !text-xs">Protein</label>
                                    <select :name="`items[${i}][protein]`" x-model="row.protein" class="input !py-2">
                                        <template x-for="p in (meta(row) ? meta(row).proteins : [])" :key="p">
                                            <option :value="p" x-text="p"></option>
                                        </template>
                                    </select>
                                </div>
                                <div x-show="meta(row) && meta(row).hasSizes">
                                    <label class="label !text-xs">Size</label>
                                    <select :name="`items[${i}][size]`" x-model="row.size" class="input !py-2">
                                        <option value="normal">Normal</option>
                                        <option value="large">Large</option>
                                    </select>
                                </div>
                                <div x-show="meta(row) && meta(row).hasEgg">
                                    <label class="label !text-xs">Egg</label>
                                    <select :name="`items[${i}][egg]`" x-model="row.egg" class="input !py-2">
                                        <option value="none">No egg</option>
                                        <option value="fried">Fried egg</option>
                                        <option value="half-boiled">Half-boiled</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="label !text-xs">Item note <span class="font-normal text-ink-soft">(optional)</span></label>
                                <input :name="`items[${i}][note]`" x-model="row.note" type="text" maxlength="200" placeholder="e.g. extra spicy" class="input !py-2">
                            </div>

                            <p class="mt-2 text-right text-sm text-ink-soft" x-show="meta(row)">
                                <span x-text="window.CURRENCY + ' ' + window.money(unitPrice(row))"></span>
                                × <span x-text="row.qty || 0"></span>
                                = <span class="font-semibold text-ink" x-text="window.CURRENCY + ' ' + window.money(lineTotal(row))"></span>
                            </p>
                        </div>
                    </template>
                </div>
            </section>

            {{-- Payment --}}
            <section class="card p-5">
                <h2 class="text-lg font-bold">Payment</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="label">Method</label>
                        <select name="payment_method" class="input">
                            <option value="qr" @selected(old('payment_method','qr')==='qr')>QR transfer</option>
                            <option value="cash" @selected(old('payment_method')==='cash')>Cash</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Payment status</label>
                        <select name="payment_status" class="input">
                            @foreach ($paymentStatuses as $key => $label)
                                <option value="{{ $key }}" @selected(old('payment_status','verified')===$key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Order status</label>
                        <select name="order_status" class="input">
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}" @selected(old('order_status','confirmed')===$key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-4 sm:w-1/2">
                    <label class="label">Delivery fee ({{ config('ordering.currency') }}) <span class="font-normal text-ink-soft">— leave blank to auto-calculate</span></label>
                    <input name="delivery_fee" type="number" step="0.01" min="0" x-model="feeOverride" value="{{ old('delivery_fee') }}" class="input" placeholder="auto">
                </div>
            </section>

            <section class="card p-5">
                <label class="label">Order notes <span class="font-normal text-ink-soft">(optional)</span></label>
                <textarea name="notes" rows="2" maxlength="500" class="input">{{ old('notes') }}</textarea>
            </section>
        </div>

        {{-- Summary --}}
        <div class="h-max lg:sticky lg:top-24">
            <div class="card p-5">
                <h2 class="text-lg font-bold">Summary</h2>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-ink-soft">Subtotal</span>
                        <span class="font-semibold" x-text="window.CURRENCY + ' ' + window.money(subtotal)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-ink-soft">Delivery fee</span>
                        <span class="font-semibold" x-text="window.CURRENCY + ' ' + window.money(deliveryFee)"></span>
                    </div>
                    <hr class="border-line">
                    <div class="flex justify-between text-base font-bold">
                        <span>Total</span>
                        <span x-text="window.CURRENCY + ' ' + window.money(total)"></span>
                    </div>
                </div>
                <button type="submit" class="btn-primary mt-5 w-full">Create order</button>
            </div>
        </div>

        <script>
            function manualOrder() {
                return {
                    items: @js($menuItems),
                    eggFee: {{ $eggFee }},
                    deliveryBase: {{ (int) $settings->default_delivery_fee }},
                    deliveryCap: {{ (int) $settings->delivery_fee_cap }},
                    feeOverride: @js(old('delivery_fee', '')),
                    rows: [],
                    _uid: 0,

                    init() { this.addRow(); },

                    addRow() {
                        this.rows.push({ uid: this._uid++, id: '', qty: 1, protein: '', size: 'normal', egg: 'none', note: '' });
                    },
                    removeRow(i) {
                        this.rows.splice(i, 1);
                        if (this.rows.length === 0) this.addRow();
                    },
                    meta(row) {
                        return this.items.find((it) => it.id === Number(row.id)) || null;
                    },
                    onItemChange(row) {
                        const it = this.meta(row);
                        row.protein = it && it.proteins.length ? it.proteins[0] : '';
                        row.size = 'normal';
                        row.egg = 'none';
                    },
                    unitPrice(row) {
                        const it = this.meta(row);
                        if (!it) return 0;
                        let normal = it.price, large = it.largePrice;
                        if (it.proteins.length && row.protein && it.proteinPrices[row.protein]) {
                            normal = it.proteinPrices[row.protein].normal;
                            large = (it.proteinPrices[row.protein].large ?? normal);
                        }
                        let p = (it.hasSizes && row.size === 'large' && large) ? large : normal;
                        if (it.hasEgg && row.egg && row.egg !== 'none') p += this.eggFee;
                        return p;
                    },
                    lineTotal(row) {
                        return this.unitPrice(row) * (parseInt(row.qty) || 0);
                    },
                    get subtotal() {
                        return this.rows.reduce((s, r) => s + this.lineTotal(r), 0);
                    },
                    get foodQty() {
                        return this.rows.reduce((s, r) => { const it = this.meta(r); return s + (it && !it.isDrink ? (parseInt(r.qty) || 0) : 0); }, 0);
                    },
                    get drinkQty() {
                        return this.rows.reduce((s, r) => { const it = this.meta(r); return s + (it && it.isDrink ? (parseInt(r.qty) || 0) : 0); }, 0);
                    },
                    get computedDeliveryFee() {
                        let fee;
                        if (this.foodQty > 0) fee = this.deliveryBase * this.foodQty;
                        else if (this.drinkQty > 0) fee = this.deliveryBase;
                        else return 0;
                        return this.deliveryCap > 0 ? Math.min(fee, this.deliveryCap) : fee;
                    },
                    get deliveryFee() {
                        return this.feeOverride !== '' && this.feeOverride !== null
                            ? Math.round(parseFloat(this.feeOverride) * 100) || 0
                            : this.computedDeliveryFee;
                    },
                    get total() {
                        return this.subtotal + this.deliveryFee;
                    },
                };
            }
        </script>
    </form>
@endsection
