@extends('welcome')

@section('content')
    <div class="mx-auto mt-10 max-w-lg rounded bg-white p-8 shadow">

        <div id="update-warning" class="mb-4 hidden rounded bg-yellow-100 p-3 text-yellow-800">
            ⚠ This item was updated by another user. Please reload the page.
        </div>

        <h2 class="mb-6 text-2xl font-bold">Edit Item</h2>

        <form method="POST" action="{{ route('items.update', $item->id) }}" id="edit-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="name" class="mb-2 block font-semibold text-gray-700">Name</label>
                <input type="text" name="name" id="name" class="w-full rounded border px-3 py-2"
                    value="{{ old('name', $item->name) }}">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="price" class="mb-2 block font-semibold text-gray-700">Price</label>
                <input type="number" name="price" id="price" class="w-full rounded border px-3 py-2" step="0.01"
                    value="{{ old('price', $item->price) }}">
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
                                {{ old('category', $item->category) == $category->value ? 'selected' : '' }}>
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
                <textarea name="description" id="description" class="w-full rounded border px-3 py-2" rows="3">{{ old('description', $item->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="image" class="mb-2 block font-semibold text-gray-700">Upload images</label>
                <input type="file" name="images[]" multiple>
            </div>
            @if ($item->attachments->count())
                <div class="mt-6">
                    <h3 class="mb-2 font-semibold">Existing Images</h3>
                    <div class="grid grid-cols-4 gap-3">

                        @foreach ($item->attachments as $attachment)
                            <div class="relative overflow-hidden rounded border">
                                <img src="{{ $attachment->url() }}" alt="Image" class="h-32 w-full object-cover">

                                <label
                                    class="absolute bottom-1 left-1 cursor-pointer rounded bg-red-600 px-2 py-1 text-xs text-white">
                                    <input type="checkbox" name="delete_images[]" value="{{ $attachment->id }}"
                                        class="mr-1">
                                    Delete
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-3 flex items-center space-x-3">
                <button type="submit" id="submit-btn"
                    class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Save Changes</button>
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

                    setTimeout(() => {
                        document.getElementById('update-warning').classList.remove('hidden');
                    }, 1000);

                });
        });
    </script>
@endsection
