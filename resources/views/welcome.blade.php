<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant SaaS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    @vite(['resources/js/app.js'])
    <style>/* minimal container styling */ body{background:#f3f4f6}</style>
</head>
<body>
    <header>
        <ul>
            <li><a href="{{ route('items.create') }}">Create Item</a></li>
        </ul>
    </header>
    <div class="container mx-auto py-8">
        <div class="max-w-3xl mx-auto">
            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
            @endif
            @yield('content')
            @yield('scripts')
        </div>
    </div>
</body>
</html>