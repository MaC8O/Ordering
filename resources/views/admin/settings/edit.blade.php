@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    <h1 class="mb-5 text-2xl font-bold">Settings</h1>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Restaurant info --}}
        <div class="card p-5">
            <h2 class="text-lg font-bold">Restaurant info</h2>
            <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-4 space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="label">Restaurant name</label>
                    <input name="restaurant_name" value="{{ old('restaurant_name', $settings->restaurant_name) }}" class="input" required>
                    @error('restaurant_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Phone</label>
                        <input name="phone" value="{{ old('phone', $settings->phone) }}" class="input">
                    </div>
                    <div>
                        <label class="label">Bank account name</label>
                        <input name="account_name" value="{{ old('account_name', $settings->account_name) }}" class="input">
                    </div>
                </div>
                <div>
                    <label class="label">Address</label>
                    <input name="address" value="{{ old('address', $settings->address) }}" class="input">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Delivery fee per item ({{ config('ordering.currency') }})</label>
                        <input name="default_delivery_fee" type="number" step="0.01" min="0" value="{{ old('default_delivery_fee', number_format($settings->default_delivery_fee / 100, 2, '.', '')) }}" class="input" required>
                        <p class="mt-1 text-xs text-ink-soft">Charged per food item. Drinks ride free with a food order; a drinks-only order pays this once.</p>
                    </div>
                    <div>
                        <label class="label">Delivery fee cap ({{ config('ordering.currency') }})</label>
                        <input name="delivery_fee_cap" type="number" step="0.01" min="0" value="{{ old('delivery_fee_cap', number_format($settings->delivery_fee_cap / 100, 2, '.', '')) }}" class="input" required>
                        @error('delivery_fee_cap') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-ink-soft">Maximum total delivery fee, however many items. Set to 0 for no cap.</p>
                    </div>
                    <div>
                        <label class="label">Last-order cutoff (minutes before close)</label>
                        <input name="cutoff_minutes_before_close" type="number" min="0" max="240" value="{{ old('cutoff_minutes_before_close', $settings->cutoff_minutes_before_close) }}" class="input" required>
                    </div>
                </div>
                <div>
                    <label class="label">Dorms / buildings <span class="font-normal text-ink-soft">(one per line)</span></label>
                    <textarea name="dorms" rows="6" class="input">{{ old('dorms', collect($settings->dorms ?? [])->implode("\n")) }}</textarea>
                    <p class="mt-1 text-xs text-ink-soft">These appear in the checkout dropdown. Customers can only pick from this list.</p>
                </div>
                <button class="btn-primary">Save settings</button>
            </form>
        </div>

        {{-- QR image --}}
        <div class="space-y-6">
            <div class="card p-5">
                <h2 class="text-lg font-bold">Payment QR image</h2>
                <div class="mt-4 flex flex-col items-start gap-4 sm:flex-row">
                    <div class="h-36 w-36 shrink-0 overflow-hidden rounded-xl border border-line bg-white p-2">
                        @if ($settings->qr_image_path && file_exists(public_path($settings->qr_image_path)))
                            <img src="{{ asset($settings->qr_image_path) }}?v={{ $settings->updated_at?->timestamp }}" alt="QR code" class="h-full w-full object-contain">
                        @else
                            <div class="grid h-full w-full place-items-center text-center text-xs text-ink-soft">No QR uploaded</div>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.settings.qr') }}" enctype="multipart/form-data" class="flex-1">
                        @csrf
                        <label class="label">Upload / replace</label>
                        <input name="qr_image" type="file" accept="image/png,image/jpeg" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:font-semibold file:text-white" required>
                        <p class="mt-1 text-xs text-ink-soft">PNG or JPG, square, at least 600×600.</p>
                        @error('qr_image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        <button class="btn-primary mt-3 !py-2 text-sm">Upload QR</button>
                    </form>
                </div>
            </div>

            {{-- Weekly schedule --}}
            <div class="card p-5">
                <h2 class="text-lg font-bold">Weekly schedule</h2>
                <p class="mt-1 text-sm text-ink-soft">Pick which days are open and set the hours.</p>
                <form method="POST" action="{{ route('admin.settings.schedule') }}" class="mt-4 space-y-2">
                    @csrf @method('PATCH')
                    @foreach ($weekdays as $num => $name)
                        @php $row = $schedule->get($num); @endphp
                        <div class="flex flex-wrap items-center gap-2 rounded-lg border border-line p-2 text-sm">
                            <label class="flex w-full items-center gap-2 font-semibold sm:w-auto sm:flex-1">
                                <input type="hidden" name="days[{{ $num }}][is_open]" value="0">
                                <input type="checkbox" name="days[{{ $num }}][is_open]" value="1" @checked($row?->is_open ?? false) class="h-4 w-4 accent-brand-600">
                                {{ $name }}
                            </label>
                            <span class="hidden text-ink-soft sm:inline">open</span>
                            <input type="time" name="days[{{ $num }}][open_time]" value="{{ \Illuminate\Support\Str::of($row?->open_time ?? '10:00')->substr(0,5) }}" class="input !py-1.5 min-w-0 flex-1" required>
                            <input type="time" name="days[{{ $num }}][close_time]" value="{{ \Illuminate\Support\Str::of($row?->close_time ?? '21:00')->substr(0,5) }}" class="input !py-1.5 min-w-0 flex-1" required>
                        </div>
                    @endforeach
                    <button class="btn-primary mt-2">Save schedule</button>
                </form>
            </div>

            {{-- Preorder batch --}}
            <div class="card p-5">
                <h2 class="text-lg font-bold">Preorder batch</h2>
                <p class="mt-1 text-sm text-ink-soft">Collect orders in a window, then cook &amp; deliver them all in one run.</p>
                <form method="POST" action="{{ route('admin.settings.preorder') }}" class="mt-4 space-y-4">
                    @csrf @method('PATCH')

                    @php $preorderStatus = new \App\Services\PreorderStatus($settings); @endphp
                    <div class="rounded-xl border border-line bg-surface-2 p-3 text-sm">
                        Status now:
                        @if ($preorderStatus->isOpen())
                            <span class="font-semibold text-green-700">OPEN{{ $preorderStatus->forcedOpen() ? ' (forced)' : '' }}</span> — customers can preorder.
                        @elseif (! $settings->preorder_enabled)
                            <span class="font-semibold text-ink-soft">DISABLED</span> — tick the box below to enable.
                        @else
                            <span class="font-semibold text-amber-700">CLOSED</span> — waiting for the schedule window.
                        @endif
                    </div>

                    <label class="flex items-center gap-2 text-sm font-semibold">
                        <input type="hidden" name="preorder_enabled" value="0">
                        <input type="checkbox" name="preorder_enabled" value="1" @checked(old('preorder_enabled', $settings->preorder_enabled)) class="h-4 w-4 accent-brand-600">
                        Enable weekly preorders
                    </label>

                    <label class="flex items-start gap-2 rounded-xl border border-brand-200 bg-brand-50 p-3 text-sm font-semibold">
                        <input type="hidden" name="preorder_force_open" value="0">
                        <input type="checkbox" name="preorder_force_open" value="1" @checked(old('preorder_force_open', $settings->preorder_force_open)) class="mt-0.5 h-4 w-4 accent-brand-600">
                        <span>
                            Open preorders right now <span class="font-normal text-ink-soft">(ignore the schedule)</span>
                            <span class="mt-0.5 block font-normal text-ink-soft">Keeps preorders open until you untick this. Requires "Enable weekly preorders" above.</span>
                        </span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Opens — day</label>
                            <select name="preorder_open_day" class="input">
                                @foreach ($weekdays as $num => $name)
                                    <option value="{{ $num }}" @selected((int) old('preorder_open_day', $settings->preorder_open_day) === $num)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Opens — time</label>
                            <input type="time" name="preorder_open_time" value="{{ \Illuminate\Support\Str::of(old('preorder_open_time', $settings->preorder_open_time))->substr(0,5) }}" class="input" required>
                        </div>
                        <div>
                            <label class="label">Cutoff — day</label>
                            <select name="preorder_cutoff_day" class="input">
                                @foreach ($weekdays as $num => $name)
                                    <option value="{{ $num }}" @selected((int) old('preorder_cutoff_day', $settings->preorder_cutoff_day) === $num)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Cutoff — time</label>
                            <input type="time" name="preorder_cutoff_time" value="{{ \Illuminate\Support\Str::of(old('preorder_cutoff_time', $settings->preorder_cutoff_time))->substr(0,5) }}" class="input" required>
                        </div>
                    </div>

                    <div>
                        <label class="label">Specific delivery date <span class="font-normal text-ink-soft">(optional)</span></label>
                        <input type="date" name="preorder_fulfillment_date" min="{{ now()->toDateString() }}"
                               value="{{ old('preorder_fulfillment_date', optional($settings->preorder_fulfillment_date)->toDateString()) }}" class="input">
                        @error('preorder_fulfillment_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-ink-soft">Pin an exact delivery date for this batch — it overrides the weekday below. Leave blank to use the recurring weekday. A past date is ignored automatically.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="label">Delivery day <span class="font-normal text-ink-soft">(recurring)</span></label>
                            <select name="preorder_fulfillment_day" class="input">
                                @foreach ($weekdays as $num => $name)
                                    <option value="{{ $num }}" @selected((int) old('preorder_fulfillment_day', $settings->preorder_fulfillment_day) === $num)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Window from</label>
                            <input type="time" name="preorder_window_start" value="{{ \Illuminate\Support\Str::of(old('preorder_window_start', $settings->preorder_window_start))->substr(0,5) }}" class="input" required>
                        </div>
                        <div>
                            <label class="label">Window to</label>
                            <input type="time" name="preorder_window_end" value="{{ \Illuminate\Support\Str::of(old('preorder_window_end', $settings->preorder_window_end))->substr(0,5) }}" class="input" required>
                        </div>
                    </div>

                    <div>
                        <label class="label">Batch delivery fee ({{ config('ordering.currency') }}) <span class="font-normal text-ink-soft">— flat, shared; 0 = free</span></label>
                        <input name="preorder_delivery_fee" type="number" step="0.01" min="0" value="{{ old('preorder_delivery_fee', number_format($settings->preorder_delivery_fee / 100, 2, '.', '')) }}" class="input" required>
                    </div>

                    <button class="btn-primary">Save preorder settings</button>
                </form>
            </div>
        </div>
    </div>
@endsection
