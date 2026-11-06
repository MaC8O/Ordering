@extends('layouts.storefront')

@section('title', __('Log in') . ' — ' . $storeStatus->restaurantName())

@section('content')
    <div class="mx-auto max-w-sm">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold">{{ __('Welcome back') }}</h1>
            <p class="mt-1 text-sm text-ink-soft">{{ __('Log in to track your orders and earn free delivery.') }}</p>
        </div>

        <form method="POST" action="{{ route('login.attempt') }}" class="card space-y-4 p-6">
            @csrf
            <div>
                <label for="phone" class="label">{{ __('Phone number') }}</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" inputmode="tel" placeholder="+66..." class="input" autofocus required>
            </div>
            <div>
                <label for="password" class="label">{{ __('Password') }}</label>
                <input id="password" name="password" type="password" class="input" required>
            </div>
            <label class="flex items-center gap-2 text-sm text-ink-soft">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-brand-600"> {{ __('Remember me') }}
            </label>

            @if ($errors->any())
                <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</p>
            @endif

            <button type="submit" class="btn-primary w-full">{{ __('Log in') }}</button>
        </form>

        <p class="mt-4 text-center text-sm text-ink-soft">
            {{ __("Don't have an account?") }}
            <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">{{ __('Register') }}</a>
        </p>
    </div>
@endsection
