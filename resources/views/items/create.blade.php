@extends('welcome')

@section('content')
<div class="max-w-lg mx-auto mt-10 bg-white p-8 rounded shadow">
    <h2 class="text-2xl font-bold mb-6">Create New Item</h2>
    <form method="POST" action="{{ route('items.store') }}">
        @csrf
        <div class="mb-4">
            <label for="name" class="block text-gray-700 font-semibold mb-2">Name</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2">
            @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="mb-4">
            <label for="price" class="block text-gray-700 font-semibold mb-2">Price</label>
            <input type="number" name="price" id="price" value="{{ old('price') }}" class="w-full border rounded px-3 py-2" step="0.01">
            @error('price')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="mb-4">
            <label for="category" class="block text-gray-700 font-semibold mb-2">Category</label>
                <select name="category" id="category" class="w-full border rounded px-3 py-2">
                    <option value="">Select category</option>
                    @if(!empty($categories))
                        @foreach($categories as $category)
                            <option value="{{ $category->value }}" {{ old('category') == $category->value ? 'selected' : '' }}>
                                {{ ucfirst($category->value) }}</option>
                        @endforeach
                    @endif
            </select>
            @error('category')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="mb-4">
            <label for="description" class="block text-gray-700 font-semibold mb-2">Description</label>
            <textarea name="description" id="description" class="w-full border rounded px-3 py-2" rows="3">{{ old('description') }}</textarea>
            @error('description')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Create Item</button>
    </form>
</div>
@endsection