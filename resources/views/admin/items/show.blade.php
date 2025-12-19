@extends('welcome')

@section('content')
    <div class="rounded bg-white p-6 shadow">
        <div class="flex items-start justify-between">
            <h1 class="text-2xl font-bold">{{ $item->name }}</h1>
            <div class="space-x-2">
                <a href="{{ route('items.edit', $item->id) }}">Edit</a>
                <form action="{{ route('items.delete', $item->id) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600" onclick="return confirm('Delete this item?')">Delete</button>
                </form>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-3 gap-4">
            <div class="col-span-1">
                @if ($attachments && $attachments->count())
                    <img src="{{ $attachments->first() }}" alt="{{ $item->name }}"
                        class="h-48 w-full rounded object-cover">
                @else
                    <div class="flex h-48 w-full items-center justify-center rounded bg-gray-100 text-gray-500">No image
                    </div>
                @endif
            </div>

            <div class="col-span-2">
                <p class="text-lg font-semibold">Price: <span class="text-gray-700">{{ number_format($item->price, 2) }}
                        EGP</span></p>
                <p class="mt-2">Category: <strong>{{ $item->category }}</strong></p>
                <p class="mt-4 text-gray-700">{{ $item->description }}</p>
                <p class="mt-4 text-sm text-gray-500">Created: {{ $item->created_at->diffForHumans() }}</p>
            </div>
        </div>

        @if ($attachments && $attachments->count() > 1)
            <div class="mt-6">
                <h3 class="mb-2 font-semibold">Gallery</h3>
                <div class="grid grid-cols-4 gap-3">
                    @foreach ($attachments as $attachment)
                        <div class="overflow-hidden rounded border">
                            <img src="{{ $attachment }}" alt="{{ $item->name }}" class="h-32 w-full object-cover">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
