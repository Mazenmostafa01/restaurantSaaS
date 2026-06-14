<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Order from {{ $restaurant->name }} — browse menu, customize your order, and enjoy!">
    <title>{{ $restaurant->name }}</title>

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
