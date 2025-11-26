@extends('welcome')

@section('content')
<div class="bg-white p-6 rounded shadow">
    <div class="flex items-start justify-between">
        <h1 class="text-2xl font-bold">{{ $item->name }}</h1>
        <div class="space-x-2">
            <form action="{{ route('items.delete', $item->id) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600" onclick="return confirm('Delete this item?')">Delete</button>
            </form>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-3 gap-4">
        <div class="col-span-1">
            @if($item->attachments && $item->attachments->count())
                <img src="{{ Storage::disk($item->attachments->first()->disk)->url($item->attachments->first()->path) }}" alt="{{ $item->name }}" class="w-full h-48 object-cover rounded">
            @else
                <div class="w-full h-48 bg-gray-100 rounded flex items-center justify-center text-gray-500">No image</div>
            @endif
        </div>

        <div class="col-span-2">
            <p class="text-lg font-semibold">Price: <span class="text-gray-700">{{ number_format($item->price, 2) }}</span></p>
            <p class="mt-2">Category: <strong>{{ $item->category }}</strong></p>
            <p class="mt-4 text-gray-700">{{ $item->description }}</p>
            <p class="mt-4 text-sm text-gray-500">Created: {{ $item->created_at->diffForHumans() }}</p>
        </div>
    </div>

    @if($item->attachments && $item->attachments->count() > 1)
    <div class="mt-6">
        <h3 class="font-semibold mb-2">Gallery</h3>
        <div class="grid grid-cols-4 gap-3">
            @foreach($item->attachments as $attachment)
                <div class="border rounded overflow-hidden">
                    <img src="{{ Storage::disk($attachment->disk)->url($attachment->path) }}" alt="{{ $attachment->filename ?? $item->name }}" class="w-full h-32 object-cover">
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@endsection
