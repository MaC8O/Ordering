@extends('layouts.storefront')

@php $isPreorder = $preorderMode ?? false; @endphp

@section('title', ($isPreorder ? __('Preorder') : __('Checkout')) . ' — ' . $storeStatus->restaurantName())

@section('content')
    <h1 class="mb-5 text-2xl font-bold">{{ $isPreorder ? __('Preorder for :day', ['day' => $preorder->fulfillmentAt()->locale(app()->getLocale())->isoFormat('dddd')]) : __('Checkout') }}</h1>

    @if ($isPreorder)
        <div class="mb-5 overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 p-5 text-white shadow-lg">
            <div class="flex items-start gap-3">
                <span class="text-2xl">🗓️</span>
                <div>
                    <p class="font-bold">{{ __('Preorder — cooked & delivered in one batch') }}</p>
                    <p class="mt-1 text-base font-semibold">🗓️ {{ $preorder->fulfillmentDateLabel() }}</p>
                    <p class="mt-0.5 text-sm text-indigo-50/90">{{ __('Delivered :time. Pay now by QR transfer to reserve your meal.', ['time' => $preorder->timeWindow()]) }}</p>
                </div>
            </div>
        </div>
    @endif

    @if ($freeDelivery)
        <div class="mb-5 flex items-center gap-3 rounded-2xl bg-gradient-to-br from-green-600 to-emerald-600 p-4 text-white shadow">
            <span class="text-2xl">🎉</span>
            <p class="text-sm font-semibold">{{ __('Loyalty reward: delivery on this order is free!') }}</p>
        </div>
    @elseif (! $customer)
        <div class="mb-5 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-brand-100 bg-brand-50/60 p-4 text-sm">
            <p class="text-ink-soft">{{ __('Have an account? Log in to track orders and earn free delivery.') }}</p>
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">{{ __('Log in') }} →</a>
        </div>
    @endif

    <form method="POST" action="{{ $isPreorder ? route('preorder.store') : route('checkout.store') }}" enctype="multipart/form-data"
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

                        @if ($isPreorder)
                            <div class="sm:col-span-2">
                                <label class="label">{{ __('Delivery') }}</label>
                                <div class="flex items-center gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-3 text-sm">
                                    <span class="text-lg">🛵</span>
                                    <div>
                                        <p class="font-semibold text-ink">{{ $preorder->fulfillmentDateLabel() }}</p>
                                        <p class="text-xs text-ink-soft">{{ __('Delivery window') }}: {{ $preorder->timeWindow() }}</p>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-ink-soft">{{ __('All preorders are delivered together on this date.') }}</p>
                            </div>
                        @else
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
                        @endif

                        <div class="sm:col-span-2">
                            <label for="delivery_notes" class="label">{{ __('Delivery notes') }} <span class="font-normal text-ink-soft">{{ __('(optional)') }}</span></label>
                            <textarea id="delivery_notes" name="delivery_notes" rows="2" maxlength="500" class="input" placeholder="{{ __('Call when you arrive / leave at reception') }}">{{ old('delivery_notes') }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Payment --}}
                <section class="card p-5">
                    <h2 class="text-lg font-bold">{{ __('Payment') }}</h2>
                    @if ($isPreorder)
                        {{-- Preorders are paid upfront by QR to reserve the batch. --}}
                        <input type="hidden" name="payment_method" value="qr">
                        <p class="mt-2 text-sm text-ink-soft">{{ __('Preorders are paid upfront by QR transfer to reserve your meal.') }}</p>
                    @else
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
                    @endif
                    @error('payment_method') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                    {{-- QR flow --}}
                    @php
                        $hasQr = $settings->qr_image_path && file_exists(public_path($settings->qr_image_path));
                        $promptpayId = config('ordering.promptpay_id');
                        $staticQrUrl = $hasQr ? asset($settings->qr_image_path) : '';
                        $showQr = $hasQr || filled($promptpayId);
                    @endphp
                    <div x-show="f.payment === 'qr'" x-transition
                         class="mt-5 overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm"
                         x-data="{ zoom: false }">

                        {{-- Must-read: transferring alone does not place the order --}}
                        <div class="border-b border-amber-300 bg-amber-50 p-4 sm:p-5">
                            <p class="flex items-start gap-2 text-sm text-amber-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                                <span><span class="font-bold">{{ __('Important:') }}</span> {{ __('After transferring, you MUST upload your transfer screenshot and click “:button” to complete your order. Transferring alone does not confirm your order.', ['button' => $isPreorder ? __('Place preorder') : __('Place order')]) }}</span>
                            </p>
                        </div>

                        {{-- Step 1 — scan & pay --}}
                        <div class="border-b border-brand-100 bg-gradient-to-br from-brand-50 to-brand-50/40 p-4 sm:p-5">
                            <div class="flex items-center gap-2">
                                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">1</span>
                                <h3 class="font-semibold">{{ __('Scan & pay') }}</h3>
                            </div>

                            <div class="mt-4 flex flex-col items-center gap-5 sm:flex-row sm:items-start">
                                @if ($showQr)
                                    {{-- QR tile (scales down on small screens, fixed beside details on sm+) --}}
                                    <div class="w-full max-w-[14rem] shrink-0 sm:w-52">
                                        <button type="button" @click="zoom = true"
                                                class="group relative block aspect-square w-full overflow-hidden rounded-2xl border border-line bg-white p-3 shadow-sm ring-1 ring-black/[0.03] transition hover:-translate-y-0.5 hover:shadow-md">
                                            <img :src="qrSrc" x-show="qrSrc" alt="{{ __('Payment QR code') }}" class="h-full w-full object-contain">
                                            <div x-show="!qrSrc" class="grid h-full w-full place-items-center text-xs text-ink-soft">{{ __('Generating QR…') }}</div>
                                            <span class="pointer-events-none absolute bottom-2 right-2 grid h-7 w-7 place-items-center rounded-lg bg-black/55 text-white opacity-0 transition group-hover:opacity-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9m11.25-5.25v4.5m0-4.5h-4.5m4.5 0L15 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15m11.25 5.25v-4.5m0 4.5h-4.5m4.5 0L15 15"/></svg>
                                            </span>
                                        </button>
                                        <p x-show="qrIsDynamic" x-cloak class="mt-1.5 text-center text-xs font-medium text-brand-700">{{ __('Amount is already set in this QR.') }}</p>
                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                            <button type="button" @click="zoom = true"
                                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-line bg-white px-3 py-2 text-xs font-semibold text-ink transition hover:border-brand-300 hover:text-brand-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9m11.25-5.25v4.5m0-4.5h-4.5m4.5 0L15 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15m11.25 5.25v-4.5m0 4.5h-4.5m4.5 0L15 15"/></svg>
                                                {{ __('Enlarge') }}
                                            </button>
                                            <a :href="qrSrc" :download="qrIsDynamic ? 'promptpay-qr.png' : 'promptpay-qr.jpg'"
                                               class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-brand-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-brand-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                                {{ __('Save QR') }}
                                            </a>
                                        </div>
                                    </div>

                                    {{-- Pay-to details --}}
                                    <div class="w-full space-y-3">
                                        @if ($settings->account_name)
                                            <div>
                                                <p class="text-xs font-medium uppercase tracking-wide text-ink-soft">{{ __('Account name') }}</p>
                                                <p class="font-semibold">{{ $settings->account_name }}</p>
                                            </div>
                                        @endif
                                        <div class="rounded-xl border border-brand-100 bg-brand-50/70 px-3 py-2.5">
                                            <p class="text-xs font-medium uppercase tracking-wide text-ink-soft">{{ __('Amount to transfer') }}</p>
                                            <p class="text-xl font-bold text-brand-700" x-text="window.CURRENCY + ' ' + window.money(grandTotal)"></p>
                                        </div>
                                        <p class="flex items-start gap-1.5 text-xs text-ink-soft">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                                            <span>{{ __('Only one phone? Tap “Save QR”, then open your bank app → Scan → choose it from your photos.') }}</span>
                                        </p>
                                    </div>
                                @else
                                    <div class="grid aspect-square w-full max-w-[14rem] place-items-center rounded-2xl border border-dashed border-line bg-white p-4 text-center text-xs text-ink-soft sm:w-52">
                                        {{ __('QR image not set yet — ask the restaurant.') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Step 2 — upload receipt --}}
                        <div class="p-4 sm:p-5">
                            <div class="flex items-center gap-2">
                                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">2</span>
                                <h3 class="font-semibold">{{ __('Upload your receipt') }}</h3>
                            </div>
                            <p class="mt-1 text-xs text-ink-soft">{{ __('Send us a photo of your transfer confirmation so we can verify it.') }}</p>

                            <label for="payment_screenshot"
                                   class="mt-3 flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-line bg-surface-2 px-4 py-6 text-center transition hover:border-brand-300 hover:bg-brand-50/40"
                                   :class="fileChosen && 'border-brand-400 bg-brand-50/60'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                <span class="text-sm font-semibold text-brand-700" x-show="!fileChosen">{{ __('Tap to upload receipt') }}</span>
                                <span class="text-sm font-semibold text-brand-700" x-show="fileChosen" x-cloak>{{ __('Receipt added — tap to change') }}</span>
                                <span class="text-xs text-ink-soft">{{ __('Image only, max 5 MB.') }}</span>
                                <input id="payment_screenshot" name="payment_screenshot" type="file" accept="image/png,image/jpeg,image/webp"
                                       @change="onFile($event)" class="sr-only">
                            </label>
                            <p x-show="fileError" x-text="fileError" class="mt-1 text-sm text-red-600"></p>
                            @error('payment_screenshot') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Fullscreen QR — easy to scan, or save on a single phone --}}
                        @if ($showQr)
                            <div x-show="zoom" x-transition.opacity x-cloak @click="zoom = false" @keydown.escape.window="zoom = false"
                                 class="fixed inset-0 z-50 flex flex-col items-center justify-center gap-5 bg-black/80 p-6 backdrop-blur-sm">
                                <img @click.stop :src="qrSrc" x-show="qrSrc" alt="{{ __('Payment QR code') }}"
                                     class="max-h-[65vh] w-auto max-w-[88vw] rounded-2xl bg-white p-4 shadow-2xl">
                                <p class="max-w-xs text-center text-sm text-white/90">{{ __('Open your bank app → Scan → choose this QR from your photos, or scan it from another phone.') }}</p>
                                <div class="flex gap-3">
                                    <a @click.stop :href="qrSrc" :download="qrIsDynamic ? 'promptpay-qr.png' : 'promptpay-qr.jpg'"
                                       class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                        {{ __('Save QR') }}
                                    </a>
                                    <button type="button" @click.stop="zoom = false" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold transition hover:bg-white/90">{{ __('Close') }}</button>
                                </div>
                            </div>
                        @endif
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
                        <template x-for="line in $store.cart.items" :key="line.key">
                            <div class="flex items-start justify-between gap-3 text-sm">
                                <span>
                                    <span class="font-semibold" x-text="line.qty + '×'"></span> <span x-text="line.name"></span>
                                    <span x-show="line.protein || line.size === 'large' || (line.egg && line.egg !== 'none')" x-cloak class="mt-0.5 flex flex-wrap gap-1">
                                        <template x-if="line.protein"><span class="rounded bg-brand-50 px-1.5 text-[11px] font-semibold text-brand-700" x-text="line.protein"></span></template>
                                        <span x-show="line.size === 'large'" class="rounded bg-surface-2 px-1.5 text-[11px] text-ink-soft">{{ __('Large') }}</span>
                                        <span x-show="line.egg === 'fried'" class="rounded bg-surface-2 px-1.5 text-[11px] text-ink-soft">{{ __('Fried egg') }}</span>
                                        <span x-show="line.egg === 'half-boiled'" class="rounded bg-surface-2 px-1.5 text-[11px] text-ink-soft">{{ __('Half-boiled egg') }}</span>
                                    </span>
                                </span>
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
                            <span x-show="!freeDelivery" class="font-semibold" x-text="window.CURRENCY + ' ' + window.money(deliveryFee)"></span>
                            <span x-show="freeDelivery" x-cloak class="font-bold uppercase text-green-600">{{ __('Free') }}</span>
                        </div>
                        <p x-show="hasDrink && !drinksOnly" x-cloak class="flex items-center gap-1.5 rounded-lg bg-green-50 px-2.5 py-1.5 text-xs font-medium text-green-700">🥤 {{ __('Your drinks are delivered free with your food order.') }}</p>
                        <p x-show="drinksOnly" x-cloak class="flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-800">🥤 {{ __('A delivery fee applies to drinks-only orders.') }}</p>
                        <div class="flex justify-between text-base font-bold">
                            <span>{{ __('Total') }}</span>
                            <span x-text="window.CURRENCY + ' ' + window.money(grandTotal)"></span>
                        </div>
                    </div>

                    <button type="submit" :disabled="!canSubmit"
                            class="btn-primary mt-5 w-full disabled:bg-black/20 disabled:text-white/70">
                        {{ $isPreorder ? __('Place preorder') : __('Place order') }}
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
                deliveryFeeBase: {{ $deliveryFeeMinor ?? (int) $settings->default_delivery_fee }},
                deliveryFeeCap: {{ (int) $settings->delivery_fee_cap }},
                perItemDelivery: {{ ($isPreorder ?? false) ? 'false' : 'true' }},
                freeDelivery: {{ $freeDelivery ? 'true' : 'false' }},
                isPreorder: {{ ($isPreorder ?? false) ? 'true' : 'false' }},
                todayStr: new Date().toLocaleDateString('en-CA'),
                f: {
                    name: @js(old('customer_name', $customer?->name ?? '')),
                    phone: @js(old('customer_phone', $customer?->phone ?? '')),
                    dorm: @js(old('delivery_dorm', '')),
                    date: @js(($isPreorder ?? false) ? $preorder->fulfillmentDate() : old('delivery_date', '')),
                    payment: @js(($isPreorder ?? false) ? 'qr' : old('payment_method', 'qr')),
                },
                fileChosen: false,
                fileError: '',

                // QR: a dynamic PromptPay QR (amount embedded) when an id is set,
                // otherwise the restaurant's uploaded static image.
                promptpayId: @js($promptpayId),
                staticQr: @js($staticQrUrl),
                qrDataUrl: '',

                init() {
                    // Default the delivery date to today if not pre-filled.
                    if (!this.f.date) this.f.date = this.todayStr;

                    // Build the PromptPay QR now and whenever the total changes.
                    this.renderQr();
                    this.$watch('$store.cart.items', () => this.renderQr());
                },
                // Mirrors App\Services\DeliveryFee on the server: base × food units,
                // drinks free with food, flat base for a drinks-only order. Preorders
                // stay on their own flat fee.
                get deliveryFee() {
                    if (this.freeDelivery) return 0;
                    if (!this.perItemDelivery) return this.deliveryFeeBase;
                    const items = this.$store.cart.items;
                    const foodQty = items.reduce((s, i) => s + (i.isDrink ? 0 : i.qty), 0);
                    const drinkQty = items.reduce((s, i) => s + (i.isDrink ? i.qty : 0), 0);
                    let fee;
                    if (foodQty > 0) fee = this.deliveryFeeBase * foodQty;
                    else if (drinkQty > 0) fee = this.deliveryFeeBase;
                    else return 0;
                    return this.deliveryFeeCap > 0 ? Math.min(fee, this.deliveryFeeCap) : fee;
                },
                get grandTotal() {
                    return this.$store.cart.subtotal + this.deliveryFee;
                },
                get hasDrink() {
                    return this.$store.cart.items.some((i) => i.isDrink);
                },
                get drinksOnly() {
                    const items = this.$store.cart.items;
                    return items.length > 0 && items.every((i) => i.isDrink);
                },
                get qrIsDynamic() {
                    return !!this.qrDataUrl;
                },
                get qrSrc() {
                    // Prefer the dynamic amount-embedded QR; fall back to the static image.
                    return this.qrDataUrl || this.staticQr;
                },
                renderQr() {
                    if (!this.promptpayId || typeof window.promptpayQrDataUrl !== 'function') return;
                    const baht = this.grandTotal / 100;
                    Promise.resolve(window.promptpayQrDataUrl(this.promptpayId, baht))
                        .then((url) => { this.qrDataUrl = url || ''; })
                        .catch(() => { this.qrDataUrl = ''; });
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
