<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin login · {{ config('app.name') }}</title>
    @include('partials.theme-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-cream px-4">
    <div class="w-full max-w-sm">
        <div class="mb-6 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-xl bg-brand-600 text-2xl text-white">🍽️</span>
            <h1 class="mt-3 text-xl font-bold">{{ config('app.name') }} Admin</h1>
            <p class="text-sm text-ink-soft">Sign in to manage orders and the menu.</p>
        </div>

        <form method="POST" action="{{ route('admin.login.attempt') }}" class="card space-y-4 p-6">
            @csrf
            <div>
                <label for="username" class="label">Username</label>
                <input id="username" name="username" value="{{ old('username') }}" class="input" autofocus required>
            </div>
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" name="password" type="password" class="input" required>
            </div>
            <label class="flex items-center gap-2 text-sm text-ink-soft">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-brand-600"> Remember me
            </label>

            @if ($errors->any())
                <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</p>
            @endif

            <button type="submit" class="btn-primary w-full">Log in</button>
        </form>

        <p class="mt-4 text-center text-sm"><a href="{{ route('menu.index') }}" class="text-ink-soft hover:text-brand-700">← Back to store</a></p>
    </div>
</body>
</html>
