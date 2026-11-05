@extends('layouts.storefront')

@section('title', __('Checkout') . ' — ' . $storeStatus->restaurantName())

@section('content')
    <h1 class="mb-5 text-2xl font-bold">{{ __('Checkout') }}</h1>

    <form method="POST" action="{{ route('checkout.store') }}" enctype="multipart/form-data"
          x-data="checkoutForm()" x-init="init()" @submit="prepare($event)">
        @csrf
        <input type="hidden" name="cart" x-ref="cartField">

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            {{-- Left: form --}}
            <div class="space-y-6">
                {{-- Contact + delivery --}}
                <section class="card p-5">
                    <h2 class="text-lg font-bold">{{ __('Delivery details') }}</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="customer_name" class="label">{{ __('Full name') }}</label>
                            <input id="customer_name" name="customer_name" x-model="f.name" value="{{ old('customer_name') }}" class="input" required>
                            @error('customer_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="customer_phone" class="label">{{ __('Phone number') }}</label>
                            <input id="customer_phone" name="customer_phone" x-model="f.phone" value="{{ old('customer_phone') }}" inputmode="tel" placeholder="+60123456789" class="input" required>
                            <p class="mt-1 text-xs" :class="phoneValid || f.phone === '' ? 'text-ink-soft' : 'text-red-600'">{{ __('Digits only, 7–15 characters, optional leading +.') }}</p>
                            @error('customer_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="delivery_dorm" class="label">{{ __('Dorm / building / address') }}</label>
                            <input id="delivery_dorm" name="delivery_dorm" x-model="f.dorm" value="{{ old('delivery_dorm') }}"
                                   list="dorm_suggestions" autocomplete="off"
                                   placeholder="{{ __('e.g. Building A, or type your full address') }}" class="input" required>
                            @if (!empty($dorms))
                                <datalist id="dorm_suggestions">
                                    @foreach ($dorms as $dorm)
                                        <option value="{{ $dorm }}"></option>
                                    @endforeach
                                </datalist>
                            @endif
                            <p class="mt-1 text-xs text-ink-soft">{{ __('We drop your order at the building lobby or monitor.') }}</p>
                            @error('delivery_dorm') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="delivery_date" class="label">{{ __('Delivery date') }}</label>
                            <input id="delivery_date" name="delivery_date" type="date" x-model="f.date"
                                   value="{{ old('delivery_date') }}" :min="todayStr" class="input" required>
                            @error('delivery_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="preferred_time" class="label">{{ __('Preferred time') }} <span class="font-normal text-ink-soft">{{ __('(optional)') }}</span></label>
                            <input id="preferred_time" name="preferred_time" type="time" value="{{ old('preferred_time') }}" class="input">
                            @error('preferred_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="delivery_notes" class="label">{{ __('Delivery notes') }} <span class="font-normal text-ink-soft">{{ __('(optional)') }}</span></label>
                            <textarea id="delivery_notes" name="delivery_notes" rows="2" maxlength="500" class="input" placeholder="{{ __('Call when you arrive / leave at reception') }}">{{ old('delivery_notes') }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Payment --}}
                <section class="card p-5">
                    <h2 class="text-lg font-bold">{{ __('Payment') }}</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition"
                               :class="f.payment === 'qr' ? 'border-brand-600 bg-brand-50' : 'border-line'">
                            <input type="radio" name="payment_method" value="qr" x-model="f.payment" class="h-4 w-4 accent-brand-600">
                            <span>
                                <span class="block font-semibold">{{ __('QR bank transfer') }}</span>
                                <span class="block text-xs text-ink-soft">{{ __('Scan, transfer, upload the receipt.') }}</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition"
                               :class="f.payment === 'cash' ? 'border-brand-600 bg-brand-50' : 'border-line'">
                            <input type="radio" name="payment_method" value="cash" x-model="f.payment" class="h-4 w-4 accent-brand-600">
                            <span>
                                <span class="block font-semibold">{{ __('Cash on delivery') }}</span>
                                <span class="block text-xs text-ink-soft">{{ __('Pay the rider when it arrives.') }}</span>
                            </span>
                        </label>
                    </div>
                    @error('payment_method') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                    {{-- QR flow --}}
                    <div x-show="f.payment === 'qr'" x-transition class="mt-5 rounded-xl border border-brand-100 bg-brand-50/60 p-4" x-data="{ zoom: false }">
                        <p class="text-sm font-semibold">{{ __('Scan and transfer, then upload the receipt.') }}</p>
                        <p class="mt-1 text-xs text-ink-soft">{{ __('Only one phone? Tap “Save QR”, then open your bank app → Scan → choose it from your photos.') }}</p>
                        <div class="mt-3 flex flex-col items-start gap-4 sm:flex-row">
                            <div class="shrink-0">
                                @if ($settings->qr_image_path && file_exists(public_path($settings->qr_image_path)))
                                    <button type="button" @click="zoom = true"
                                            class="block h-48 w-48 overflow-hidden rounded-xl border border-line bg-white p-2">
                                        <img src="{{ asset($settings->qr_image_path) }}" alt="{{ __('Payment QR code') }}" class="h-full w-full object-contain">
                                    </button>
                                    <div class="mt-2 flex items-center gap-3 text-xs font-semibold">
                                        <button type="button" @click="zoom = true" class="text-brand-700 underline">{{ __('Tap to enlarge') }}</button>
                                        <a href="{{ asset($settings->qr_image_path) }}" download="promptpay-qr.jpg" class="text-brand-700 underline">{{ __('Save QR') }}</a>
                                    </div>

                                    {{-- Fullscreen QR so it's easy to scan (or save on a single phone). --}}
                                    <div x-show="zoom" x-transition.opacity @click="zoom = false" @keydown.escape.window="zoom = false"
                                         class="fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 bg-black/80 p-6">
                                        <img @click.stop src="{{ asset($settings->qr_image_path) }}" alt="{{ __('Payment QR code') }}"
                                             class="max-h-[70vh] w-auto max-w-[90vw] rounded-2xl bg-white p-4">
                                        <p class="max-w-xs text-center text-sm text-white/90">{{ __('Open your bank app → Scan → choose this QR from your photos, or scan it from another phone.') }}</p>
                                        <div class="flex gap-3">
                                            <a @click.stop href="{{ asset($settings->qr_image_path) }}" download="promptpay-qr.jpg" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Save QR') }}</a>
                                            <button type="button" @click.stop="zoom = false" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold">{{ __('Close') }}</button>
                                        </div>
                                    </div>
                                @else
                                    <div class="grid h-48 w-48 place-items-center rounded-xl border border-line bg-white p-2 text-center text-xs text-ink-soft">{{ __('QR image not set yet — ask the restaurant.') }}</div>
                                @endif
                            </div>
                            <div class="text-sm">
                                @if ($settings->account_name)
                                    <p><span class="text-ink-soft">{{ __('Account name:') }}</span> <span class="font-semibold">{{ $settings->account_name }}</span></p>
                                @endif
                                <p class="mt-1"><span class="text-ink-soft">{{ __('Amount to transfer:') }}</span> <span class="font-bold text-brand-700" x-text="window.CURRENCY + ' ' + window.money(grandTotal)"></span></p>

                                <div class="mt-3">
                                    <label for="payment_screenshot" class="label">{{ __('Transfer screenshot') }}</label>
                                    <input id="payment_screenshot" name="payment_screenshot" type="file" accept="image/png,image/jpeg,image/webp"
                                           @change="onFile($event)"
                                           class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:font-semibold file:text-white">
                                    <p class="mt-1 text-xs text-ink-soft">{{ __('Image only, max 5 MB.') }}</p>
                                    <p x-show="fileError" x-text="fileError" class="mt-1 text-sm text-red-600"></p>
                                    @error('payment_screenshot') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Cash flow --}}
                    <div x-show="f.payment === 'cash'" x-transition class="mt-5 rounded-xl border border-brand-100 bg-brand-50/60 p-4 text-sm">
                        <p x-html="@js(__('Please have :amount ready in cash when your order arrives.')).replace(':amount', '<span class=\'font-bold text-brand-700\'>' + window.CURRENCY + ' ' + window.money(grandTotal) + '</span>')"></p>
                    </div>
                </section>

                {{-- Order notes --}}
                <section class="card p-5">
                    <label for="notes" class="label">{{ __('Order notes') }} <span class="font-normal text-ink-soft">{{ __('(optional)') }}</span></label>
                    <textarea id="notes" name="notes" rows="2" maxlength="500" class="input" placeholder="{{ __('Anything else we should know?') }}">{{ old('notes') }}</textarea>
                </section>
            </div>

            {{-- Right: summary --}}
            <div class="h-max lg:sticky lg:top-24">
                <div class="card p-5">
                    <h2 class="text-lg font-bold">{{ __('Your order') }}</h2>

                    <template x-if="$store.cart.items.length === 0">
                        <p class="mt-4 text-sm text-ink-soft">{{ __('Your cart is empty.') }} <a href="{{ route('menu.index') }}" class="font-semibold text-brand-700 underline">{{ __('Add items') }}</a>.</p>
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
                            <span class="text-ink-soft">{{ __('Subtotal') }}</span>
                            <span class="font-semibold" x-text="window.CURRENCY + ' ' + window.money($store.cart.subtotal)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-soft">{{ __('Delivery fee') }}</span>
                            <span class="font-semibold" x-text="window.CURRENCY + ' ' + window.money(deliveryFee)"></span>
                        </div>
                        <div class="flex justify-between text-base font-bold">
                            <span>{{ __('Total') }}</span>
                            <span x-text="window.CURRENCY + ' ' + window.money(grandTotal)"></span>
                        </div>
                    </div>

                    <button type="submit" :disabled="!canSubmit"
                            class="btn-primary mt-5 w-full disabled:bg-black/20 disabled:text-white/70">
                        {{ __('Place order') }}
                    </button>
                    <p x-show="!canSubmit && $store.cart.items.length > 0" class="mt-2 text-center text-xs text-ink-soft">{{ __('Fill in all required fields to continue.') }}</p>
                    <a href="{{ route('cart.index') }}" class="btn-ghost mt-2 w-full">{{ __('Back to cart') }}</a>
                </div>
            </div>
        </div>
    </form>

    <script>
        function checkoutForm() {
            return {
                deliveryFee: {{ (int) $settings->default_delivery_fee }},
                todayStr: new Date().toLocaleDateString('en-CA'),
                f: {
                    name: @js(old('customer_name', '')),
                    phone: @js(old('customer_phone', '')),
                    dorm: @js(old('delivery_dorm', '')),
                    date: @js(old('delivery_date', '')),
                    payment: @js(old('payment_method', 'qr')),
                },
                fileChosen: false,
                fileError: '',

                init() {
                    // Default the delivery date to today if not pre-filled.
                    if (!this.f.date) this.f.date = this.todayStr;

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
                    if (!this.f.dorm || this.f.dorm.trim().length < 2) return false;
                    if (!this.f.date) return false;
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
