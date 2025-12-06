@extends('welcome')

@section('content')
<div class="max-w-lg mx-auto mt-10 bg-white p-8 rounded shadow">

    <div id="update-warning"
        class="hidden mb-4 p-3 bg-yellow-100 text-yellow-800 rounded">
        ⚠ This item was updated by another user. Please reload the page.
    </div>

    <h2 class="text-2xl font-bold mb-6">Edit Item</h2>

    <form method="POST" action="{{ route('items.update', $item->id) }}" id="edit-form" enctype="multipart/form-data">
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
                    @if(!empty($categories))
                        @foreach($categories as $category)
                            <option value="{{ $category->value }}" {{ old('category', $item->category) == $category->value ? 'selected' : '' }}>
                                {{ ucfirst($category->value) }}</option>
                        @endforeach
                    @endif
            </select>
            @error('category')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="description" class="block text-gray-700 font-semibold mb-2">Description</label>
            <textarea name="description" id="description" class="w-full border rounded px-3 py-2" rows="3">{{ old('description', $item->description) }}</textarea>
            @error('description')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="image" class="block text-gray-700 font-semibold mb-2">Upload images</label>
            <input type="file" name="images[]" multiple>
        </div>
        @if($item->attachments->count())
            <div class="mt-6">
                <h3 class="font-semibold mb-2">Existing Images</h3>
                <div class="grid grid-cols-4 gap-3">

                    @foreach($item->attachments as $attachment)
                    <div class="border rounded overflow-hidden relative">
                        <img src="{{ $attachment->url() }}"
                            alt="Image"
                            class="w-full h-32 object-cover">

                        <label class="absolute bottom-1 left-1 bg-red-600 text-white px-2 py-1 rounded text-xs cursor-pointer">
                            <input type="checkbox"
                                name="delete_images[]"
                                value="{{ $attachment->id }}"
                                class="mr-1">
                            Delete
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex items-center space-x-3 mt-3">
            <button type="submit" id="submit-btn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save Changes</button>
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
