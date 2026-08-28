<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Order from {{ $restaurant->name }} — browse menu, customize your order, and enjoy!">
    <title>{{ $restaurant->name }}</title>

    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    {{-- Pass restaurant data to the React app --}}
    @php
        $restaurantData = [
            'id'      => $restaurant->id,
            'name'    => $restaurant->name,
            'slug'    => $restaurant->slug,
            'email'   => $restaurant->email,
            'phone'   => $restaurant->phone,
            'address' => $restaurant->address,
            'logo'    => $restaurant->logo_path ? asset('storage/' . $restaurant->logo_path) : null,
        ];
    @endphp
    <script>
        window.__RESTAURANT__ = @json($restaurantData);
    </script>

    @viteReactRefresh
    @vite(['resources/js/customer-app/main.jsx'])
</head>
<body>
    <div id="customer-app"></div>
</body>
</html>
