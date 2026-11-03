@extends('layouts.storefront')

@section('title', 'Checkout — ' . $storeStatus->restaurantName())

@section('content')
    <h1 class="mb-5 text-2xl font-bold">Checkout</h1>

    <form method="POST" action="{{ route('checkout.store') }}" enctype="multipart/form-data"
          x-data="checkoutForm()" x-init="init()" @submit="prepare($event)">
        @csrf
        <input type="hidden" name="cart" x-ref="cartField">

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            {{-- Left: form --}}
            <div class="space-y-6">
                {{-- Contact + delivery --}}
                <section class="card p-5">
                    <h2 class="text-lg font-bold">Delivery details</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="customer_name" class="label">Full name</label>
                            <input id="customer_name" name="customer_name" x-model="f.name" value="{{ old('customer_name') }}" class="input" required>
                            @error('customer_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="customer_phone" class="label">Phone number</label>
                            <input id="customer_phone" name="customer_phone" x-model="f.phone" value="{{ old('customer_phone') }}" inputmode="tel" placeholder="+60123456789" class="input" required>
                            <p class="mt-1 text-xs" :class="phoneValid || f.phone === '' ? 'text-ink-soft' : 'text-red-600'">Digits only, 7–15 characters, optional leading +.</p>
                            @error('customer_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="delivery_dorm" class="label">Dorm / building</label>
                            <select id="delivery_dorm" name="delivery_dorm" x-model="f.dorm" class="input" required>
                                <option value="">Select…</option>
                                @foreach ($dorms as $dorm)
                                    <option value="{{ $dorm }}" @selected(old('delivery_dorm') === $dorm)>{{ $dorm }}</option>
                                @endforeach
                            </select>
                            @error('delivery_dorm') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="delivery_room" class="label">Room number</label>
                            <input id="delivery_room" name="delivery_room" x-model="f.room" value="{{ old('delivery_room') }}" placeholder="e.g. 204B" class="input" required>
                            @error('delivery_room') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="preferred_time" class="label">Preferred time <span class="font-normal text-ink-soft">(optional)</span></label>
                            <input id="preferred_time" name="preferred_time" type="time" value="{{ old('preferred_time') }}" class="input">
                            @error('preferred_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="delivery_notes" class="label">Delivery notes <span class="font-normal text-ink-soft">(optional)</span></label>
                            <textarea id="delivery_notes" name="delivery_notes" rows="2" maxlength="500" class="input" placeholder="Call when you arrive / leave at reception">{{ old('delivery_notes') }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Payment --}}
                <section class="card p-5">
                    <h2 class="text-lg font-bold">Payment</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition"
                               :class="f.payment === 'qr' ? 'border-brand-600 bg-brand-50' : 'border-line'">
                            <input type="radio" name="payment_method" value="qr" x-model="f.payment" class="h-4 w-4 accent-brand-600">
                            <span>
                                <span class="block font-semibold">QR bank transfer</span>
                                <span class="block text-xs text-ink-soft">Scan, transfer, upload the receipt.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition"
                               :class="f.payment === 'cash' ? 'border-brand-600 bg-brand-50' : 'border-line'">
                            <input type="radio" name="payment_method" value="cash" x-model="f.payment" class="h-4 w-4 accent-brand-600">
                            <span>
                                <span class="block font-semibold">Cash on delivery</span>
                                <span class="block text-xs text-ink-soft">Pay the rider when it arrives.</span>
                            </span>
                        </label>
                    </div>
                    @error('payment_method') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                    {{-- QR flow --}}
                    <div x-show="f.payment === 'qr'" x-transition class="mt-5 rounded-xl border border-brand-100 bg-brand-50/60 p-4">
                        <p class="text-sm font-semibold">Scan and transfer, then upload the screenshot.</p>
                        <div class="mt-3 flex flex-col items-start gap-4 sm:flex-row">
                            <div class="h-44 w-44 shrink-0 overflow-hidden rounded-xl border border-line bg-white p-2">
                                @if ($settings->qr_image_path && file_exists(public_path($settings->qr_image_path)))
                                    <img src="{{ asset($settings->qr_image_path) }}" alt="Payment QR code" class="h-full w-full object-contain">
                                @else
                                    <div class="grid h-full w-full place-items-center text-center text-xs text-ink-soft">QR image not set yet — ask the restaurant.</div>
                                @endif
                            </div>
                            <div class="text-sm">
                                @if ($settings->account_name)
                                    <p><span class="text-ink-soft">Account name:</span> <span class="font-semibold">{{ $settings->account_name }}</span></p>
                                @endif
                                <p class="mt-1"><span class="text-ink-soft">Amount to transfer:</span> <span class="font-bold text-brand-700" x-text="window.CURRENCY + ' ' + window.money(grandTotal)"></span></p>

                                <div class="mt-3">
                                    <label for="payment_screenshot" class="label">Transfer screenshot</label>
                                    <input id="payment_screenshot" name="payment_screenshot" type="file" accept="image/png,image/jpeg,image/webp"
                                           @change="onFile($event)"
                                           class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:font-semibold file:text-white">
                                    <p class="mt-1 text-xs text-ink-soft">Image only, max 5 MB.</p>
                                    <p x-show="fileError" x-text="fileError" class="mt-1 text-sm text-red-600"></p>
                                    @error('payment_screenshot') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Cash flow --}}
                    <div x-show="f.payment === 'cash'" x-transition class="mt-5 rounded-xl border border-brand-100 bg-brand-50/60 p-4 text-sm">
                        <p>Please have <span class="font-bold text-brand-700" x-text="window.CURRENCY + ' ' + window.money(grandTotal)"></span> ready in cash when your order arrives.</p>
                    </div>
                </section>

                {{-- Order notes --}}
                <section class="card p-5">
                    <label for="notes" class="label">Order notes <span class="font-normal text-ink-soft">(optional)</span></label>
                    <textarea id="notes" name="notes" rows="2" maxlength="500" class="input" placeholder="Anything else we should know?">{{ old('notes') }}</textarea>
                </section>
            </div>

            {{-- Right: summary --}}
            <div class="h-max lg:sticky lg:top-24">
                <div class="card p-5">
                    <h2 class="text-lg font-bold">Your order</h2>

                    <template x-if="$store.cart.items.length === 0">
                        <p class="mt-4 text-sm text-ink-soft">Your cart is empty. <a href="{{ route('menu.index') }}" class="font-semibold text-brand-700 underline">Add items</a>.</p>
                    </template>

                    <div x-show="$store.cart.items.length > 0" class="mt-4 space-y-3">
                        <template x-for="line in $store.cart.items" :key="line.id">
                            <div class="flex items-start justify-between gap-3 text-sm">
                                <span><span class="font-semibold" x-text="line.qty + '×'"></span> <span x-text="line.name"></span></span>
                                <span class="font-semibold whitespace-nowrap" x-text="window.CURRENCY + ' ' + window.money(line.price * line.qty)"></span>
                            </div>
                        </template>

                        <hr class="border-line">
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-soft">Subtotal</span>
                            <span class="font-semibold" x-text="window.CURRENCY + ' ' + window.money($store.cart.subtotal)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-soft">Delivery fee</span>
                            <span class="font-semibold" x-text="window.CURRENCY + ' ' + window.money(deliveryFee)"></span>
                        </div>
                        <div class="flex justify-between text-base font-bold">
                            <span>Total</span>
                            <span x-text="window.CURRENCY + ' ' + window.money(grandTotal)"></span>
                        </div>
                    </div>

                    <button type="submit" :disabled="!canSubmit"
                            class="btn-primary mt-5 w-full disabled:bg-black/20 disabled:text-white/70">
                        Place order
                    </button>
                    <p x-show="!canSubmit && $store.cart.items.length > 0" class="mt-2 text-center text-xs text-ink-soft">Fill in all required fields to continue.</p>
                    <a href="{{ route('cart.index') }}" class="btn-ghost mt-2 w-full">Back to cart</a>
                </div>
            </div>
        </div>
    </form>

    <script>
        function checkoutForm() {
            return {
                deliveryFee: {{ (int) $settings->default_delivery_fee }},
                f: {
                    name: @js(old('customer_name', '')),
                    phone: @js(old('customer_phone', '')),
                    dorm: @js(old('delivery_dorm', '')),
                    room: @js(old('delivery_room', '')),
                    payment: @js(old('payment_method', 'qr')),
                },
                fileChosen: false,
                fileError: '',

                init() {
                    // If the cart is empty on arrival, nudge back to the menu.
                    if (this.$store.cart.items.length === 0) {
                        // leave them on the page but summary shows empty + submit disabled
                    }
                },
                get grandTotal() {
                    return this.$store.cart.subtotal + this.deliveryFee;
                },
                get phoneValid() {
                    return /^\+?[0-9]{7,15}$/.test(this.f.phone.trim());
                },
                get canSubmit() {
                    if (this.$store.cart.items.length === 0) return false;
                    if (!this.f.name || this.f.name.trim().length < 2) return false;
                    if (!this.phoneValid) return false;
                    if (!this.f.dorm) return false;
                    if (!this.f.room.trim()) return false;
                    if (this.f.payment === 'qr' && !this.fileChosen) return false;
                    return true;
                },
                onFile(e) {
                    this.fileError = '';
                    const file = e.target.files[0];
                    if (!file) { this.fileChosen = false; return; }
                    if (!file.type.startsWith('image/')) {
                        this.fileError = 'Please choose an image file.';
                        this.fileChosen = false;
                        e.target.value = '';
                        return;
                    }
                    if (file.size > 5 * 1024 * 1024) {
                        this.fileError = 'The screenshot must be 5 MB or smaller.';
                        this.fileChosen = false;
                        e.target.value = '';
                        return;
                    }
                    this.fileChosen = true;
                },
                prepare(e) {
                    if (!this.canSubmit) { e.preventDefault(); return; }
                    this.$refs.cartField.value = this.$store.cart.toPayload();
                },
            };
        }
    </script>
@endsection
