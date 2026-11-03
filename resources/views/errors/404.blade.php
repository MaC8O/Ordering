<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found</title>
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-cream px-4 text-center">
    <div>
        <p class="text-7xl">🍽️</p>
        <h1 class="mt-4 text-2xl font-bold">404 — Page not found</h1>
        <p class="mt-1 text-ink-soft">We couldn’t find what you were looking for.</p>
        <a href="{{ url('/') }}" class="btn-primary mt-6">Back to the menu</a>
    </div>
</body>
</html>
