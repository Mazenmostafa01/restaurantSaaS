@extends('welcome')

@section('content')
<div class="max-w-lg mx-auto mt-10 bg-white p-8 rounded shadow">

    <div id="update-warning"
        class="hidden mb-4 p-3 bg-yellow-100 text-yellow-800 rounded">
        ⚠ This item was updated by another user. Please reload the page.
    </div>

    <h2 class="text-2xl font-bold mb-6">Edit Item</h2>

    <form method="POST" action="{{ route('items.update', $item->id) }}" id="edit-form">
        @csrf
        @method('PUT')

                <div class="mb-4">
            <label for="name" class="block text-gray-700 font-semibold mb-2">Name</label>
            <input type="text" name="name" id="name" class="w-full border rounded px-3 py-2" value="{{ old('name', $item->name) }}">
            @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="price" class="block text-gray-700 font-semibold mb-2">Price</label>
            <input type="number" name="price" id="price" class="w-full border rounded px-3 py-2" step="0.01" value="{{ old('price', $item->price) }}">
            @error('price')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="category" class="block text-gray-700 font-semibold mb-2">Category</label>
            <select name="category" id="category" class="w-full border rounded px-3 py-2">
                <option value="">Select category</option>
                <option value="food" {{ old('category', $item->category) == 'food' ? 'selected' : '' }}>Food</option>
                <option value="beverage" {{ old('category', $item->category) == 'beverage' ? 'selected' : '' }}>Beverage</option>
            </select>
            @error('category')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="description" class="block text-gray-700 font-semibold mb-2">Description</label>
            <textarea name="description" id="description" class="w-full border rounded px-3 py-2" rows="3">{{ old('description', $item->description) }}</textarea>
            @error('description')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center space-x-3">
            <button type="submit" id="submit-btn" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Save Changes</button>
            <a href="{{ route('items.show', $item->id) }}" class="text-gray-600">Cancel</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let updatedByAnotherUser = false;

    Echo.channel('item-update.{{ $item->id }}')
        .listen('.item.updated', (e) => {
            console.log(e);
            updatedByAnotherUser = true;
            document.getElementById('submit-btn').remove();

            setTimeout(() =>
             {
                document.getElementById('update-warning').classList.remove('hidden');
            },1000);
            
        });
});
</script>
@endsection
