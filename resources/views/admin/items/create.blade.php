@extends('welcome')

@section('content')
    <div class="mx-auto mt-10 max-w-lg rounded bg-white p-8 shadow">
        <h2 class="mb-6 text-2xl font-bold">Create New Item</h2>
        <form method="POST" action="{{ route('items.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label for="name" class="mb-2 block font-semibold text-gray-700">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                    class="w-full rounded border px-3 py-2">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="price" class="mb-2 block font-semibold text-gray-700">Price</label>
                <input type="number" name="price" id="price" value="{{ old('price') }}"
                    class="w-full rounded border px-3 py-2" step="0.01">
                @error('price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="category" class="mb-2 block font-semibold text-gray-700">Category</label>
                <select name="category" id="category" class="w-full rounded border px-3 py-2">
                    <option value="">Select category</option>
                    @if (!empty($categories))
                        @foreach ($categories as $category)
                            <option value="{{ $category->value }}"
                                {{ old('category') == $category->value ? 'selected' : '' }}>
                                {{ ucfirst($category->value) }}</option>
                        @endforeach
                    @endif
                </select>
                @error('category')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="description" class="mb-2 block font-semibold text-gray-700">Description</label>
                <textarea name="description" id="description" class="w-full rounded border px-3 py-2" rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="image" class="mb-2 block font-semibold text-gray-700">Upload images</label>
                <input type="file" name="images[]" multiple>
            </div>
            <button type="submit" class="mt-2 rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Create
                Item</button>
        </form>
    </div>
@endsection
