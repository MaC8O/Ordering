@extends('layouts.storefront')

@section('title', __('Register') . ' — ' . $storeStatus->restaurantName())

@section('content')
    <div class="mx-auto max-w-sm">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold">{{ __('Create your account') }}</h1>
            <p class="mt-1 text-sm text-ink-soft">{{ __('Order :count times and your next delivery is free.', ['count' => \App\Models\Customer::freeDeliveryThreshold()]) }}</p>
        </div>

        <form method="POST" action="{{ route('register.attempt') }}" class="card space-y-4 p-6">
            @csrf
            <div>
                <label for="name" class="label">{{ __('Full name') }}</label>
                <input id="name" name="name" value="{{ old('name') }}" class="input" autofocus required>
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="label">{{ __('Phone number') }}</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" inputmode="tel" placeholder="+66..." class="input" required>
                <p class="mt-1 text-xs text-ink-soft">{{ __('Use the same number you order with — this is how we match your orders.') }}</p>
                @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="label">{{ __('Password') }}</label>
                <input id="password" name="password" type="password" class="input" required>
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">{{ __('Confirm password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" required>
            </div>

            <button type="submit" class="btn-primary w-full">{{ __('Create account') }}</button>
        </form>

        <p class="mt-4 text-center text-sm text-ink-soft">
            {{ __('Already have an account?') }}
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">{{ __('Log in') }}</a>
        </p>
    </div>
@endsection
