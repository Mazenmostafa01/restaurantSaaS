@extends('welcome')

@section('content')
    <header class="mb-7"><a class="rounded bg-gray-800 p-2 text-white" href="{{ route('items.create') }}">Add new item</a>
    </header>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($items as $item)
            <div
                class="flex h-full flex-col overflow-hidden rounded-lg bg-white shadow-md transition-shadow duration-300 hover:shadow-lg">
                <!-- Photo Slider - Fixed height container -->
                <div class="relative h-48 w-full bg-gray-100">
                    <div class="item-slider-{{ $item->id }} swiper-container h-full w-full">
                        <div class="swiper-wrapper">
                            @if ($item->attachments->count())
                                @foreach ($item->attachments as $attachment)
                                    <div class="swiper-slide">
                                        <div class="flex h-full w-full items-center justify-center bg-gray-100">
                                            <img src="{{ $attachment->url() }}" alt="{{ $item->name }}"
                                                class="h-full w-full object-cover">
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="swiper-slide">
                                    <div class="flex h-full w-full items-center justify-center bg-gray-100">
                                        <img src="{{ asset('storage/images/burger-1396639_1280.png') }}"
                                            {{-- add you alternative if there is no photo --}} alt="Default Image" class="h-full w-full object-cover">
                                    </div>
                                </div>
                            @endif
                        </div>
                        <!-- Navigation buttons -->
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                        <!-- Pagination -->
                        <div class="swiper-pagination"></div>
                    </div>
                </div>

                <!-- Item Details -->
                <div class="flex flex-grow flex-col p-4">
                    <div class="mb-2 flex items-start justify-between">
                        <h3 class="truncate text-lg font-semibold text-gray-800">{{ $item->name }}</h3>
                        <span
                            class="ml-2 whitespace-nowrap rounded bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">
                            EGP{{ number_format($item->price, 2) }}
                        </span>
                    </div>

                    <p class="mb-3 line-clamp-2 flex-grow text-sm text-gray-600">{{ $item->description }}</p>

                    <div class="mb-4 flex items-center justify-between text-sm text-gray-500">
                        <span class="flex items-center truncate">
                            <svg class="mr-1 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <span class="truncate">{{ $item->category ?? 'Uncategorized' }}</span>
                        </span>
                        @if (!$item->deleted_at)
                            <span
                                class="flex-shrink-0 whitespace-nowrap rounded-full bg-green-100 px-2 py-1 text-xs text-green-800">Available</span>
                        @else
                            <span
                                class="flex-shrink-0 whitespace-nowrap rounded-full bg-red-100 px-2 py-1 text-xs text-red-800">Unavailable</span>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-auto flex space-x-2">
                        @if (!$item->deleted_at)
                            <a href="{{ route('items.show', $item->id) }}"
                                class="flex flex-1 items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-white transition duration-300 hover:bg-blue-900">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                Show
                            </a>
                            <button onclick="openDeleteModal('{{ $item->id }}', '{{ $item->name }}')"
                                class="flex flex-1 items-center justify-center rounded-md bg-red-500 px-4 py-2 text-white transition duration-300 hover:bg-red-600">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete
                            </button>
                        @else
                            <button onclick="openRestoreModal('{{ $item->id }}', '{{ $item->name }}')"
                                class="flex flex-1 items-center justify-center rounded-md bg-green-500 px-4 py-2 text-white transition duration-300 hover:bg-green-600">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Restore
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
        <div class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg">
            <div class="mt-3 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.288 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium leading-6 text-gray-900">Delete Item</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500" id="deleteModalText">
                        Are you sure you want to delete this item?
                    </p>
                </div>
                <div class="items-center px-4 py-3">
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="flex space-x-3">
                            <button type="button" onclick="closeDeleteModal()"
                                class="flex-1 rounded-md bg-gray-300 px-4 py-2 text-base font-medium text-gray-800 shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                                Cancel
                            </button>
                            <button type="submit"
                                class="flex-1 rounded-md bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                Delete
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id="restoreModal" class="fixed inset-0 z-50 hidden h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
        <div class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg">
            <div class="mt-3 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.288 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium leading-6 text-gray-900">Restore Item</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500" id="restoreModalText">
                        Are you sure you want to restore this item?
                    </p>
                </div>
                <div class="items-center px-4 py-3">
                    <form id="restoreForm" method="POST">
                        @csrf
                        <div class="flex space-x-3">
                            <button type="button" onclick="closeRestoreModal()"
                                class="flex-1 rounded-md bg-gray-300 px-4 py-2 text-base font-medium text-gray-800 shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                                Cancel
                            </button>
                            <button type="submit"
                                class="flex-1 rounded-md bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                Restore
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No items</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new item.</p>
            <div class="mt-6">
                <a href="{{ route('items.create') }}"
                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Item
                </a>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <!-- Swiper JS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>

    <script>
        // Initialize all Swiper sliders
        document.addEventListener('DOMContentLoaded', function() {
            @foreach ($items as $item)
                new Swiper('.item-slider-{{ $item->id }}', {
                    loop: true,
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                    autoplay: {
                        delay: 5000,
                        disableOnInteraction: false,
                    },
                });
            @endforeach
        });

        // Delete Modal Functions
        function openDeleteModal(id, name) {
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModalText').innerHTML =
                `Are you sure you want to remove <strong>"${name}"</strong>?.`;
            document.getElementById('deleteForm').action = `{{ route('items.delete', ':id') }}`.replace(':id', id);
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
                closeRestoreModal();
            }
        });

        function openRestoreModal(id, name) {
            document.getElementById('restoreModal').classList.remove('hidden');
            document.getElementById('restoreModalText').innerHTML =
                `Are you sure you want to restore <strong>"${name}"</strong>?.`;
            document.getElementById('restoreForm').action = `{{ route('items.restore', ':id') }}`.replace(':id', id);
        }

        function closeRestoreModal() {
            document.getElementById('restoreModal').classList.add('hidden');
        }
    </script>

    <style>
        /* Minimal custom CSS - only for Swiper */
        .swiper-container {
            width: 100%;
            height: 100%;
        }

        .swiper-slide {
            text-align: center;
            font-size: 18px;
            background: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .swiper-button-next,
        .swiper-button-prev {
            color: white;
            background: rgba(0, 0, 0, 0.3);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            transform: scale(0.8);
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 20px;
        }

        /* Only this custom CSS for line clamping since Tailwind doesn't have it by default */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
@endsection
