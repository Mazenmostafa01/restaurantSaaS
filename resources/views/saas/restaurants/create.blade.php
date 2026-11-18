@extends('saas.layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto">
        <!-- Breadcrumb & Header -->
        <header class="mb-8">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('saas.restaurants.index') }}" class="hover:text-indigo-600 transition-colors">Restaurants Dashboard</a>
                <span>/</span>
                <span class="text-gray-900 font-medium">Add New Restaurant</span>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Add New Restaurant</h1>
            <p class="mt-1 text-sm text-gray-500">Create a new tenant restaurant profile and set up its primary administrator account in one step.</p>
        </header>

        <form method="POST" action="{{ route('saas.restaurants.store') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf

            <!-- Section 1: Restaurant Profile -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 pb-6 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                        1
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Restaurant Profile</h2>
                        <p class="text-xs text-gray-500">Basic business information and customer storefront identity.</p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Restaurant Name -->
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Restaurant Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            placeholder="e.g. Bella Italia Bistro"
                            class="block w-full rounded-xl border-0 py-3 px-4 text-sm text-gray-900 ring-1 ring-inset {{ $errors->has('name') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-indigo-600' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Slug (Storefront URL) -->
                    <div class="sm:col-span-2">
                        <label for="slug" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Storefront URL Slug
                            <span class="text-xs font-normal text-gray-500 ml-1">(Optional — auto-generated from name)</span>
                        </label>
                        <div class="flex items-stretch rounded-xl overflow-hidden border {{ $errors->has('slug') ? 'border-red-400 focus-within:ring-2 focus-within:ring-red-500' : 'border-gray-200 focus-within:ring-2 focus-within:ring-indigo-600' }} bg-gray-50/50">
                            <span class="inline-flex items-center px-4 bg-gray-100 text-gray-500 font-mono text-sm border-r border-gray-200 select-none whitespace-nowrap">
                                {{ url('/') }}/
                            </span>
                            <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                                placeholder="bella-italia"
                                class="block flex-1 min-w-0 border-0 py-3 px-4 text-sm font-mono text-gray-900 bg-transparent focus:ring-0 focus:outline-none" />
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400">Determines the public link used for online ordering and customer QR codes.</p>
                        @error('slug')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Contact Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Contact Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                            placeholder="info@bellaitalia.com"
                            class="block w-full rounded-xl border-0 py-3 px-4 text-sm text-gray-900 ring-1 ring-inset {{ $errors->has('email') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-indigo-600' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Contact Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Phone Number <span class="text-xs font-normal text-gray-500 ml-1">(Optional)</span>
                        </label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                            placeholder="+20 100 123 4567"
                            class="block w-full rounded-xl border-0 py-3 px-4 text-sm text-gray-900 ring-1 ring-inset {{ $errors->has('phone') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-indigo-600' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                        @error('phone')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Address -->
                    <div class="sm:col-span-2">
                        <label for="address" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Address / Location <span class="text-xs font-normal text-gray-500 ml-1">(Optional)</span>
                        </label>
                        <input type="text" id="address" name="address" value="{{ old('address') }}"
                            placeholder="123 Nile Corniche, Zamalek, Cairo"
                            class="block w-full rounded-xl border-0 py-3 px-4 text-sm text-gray-900 ring-1 ring-inset {{ $errors->has('address') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-indigo-600' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                        @error('address')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Logo Upload -->
                    <div>
                        <label for="logo" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Restaurant Logo <span class="text-xs font-normal text-gray-500 ml-1">(Optional)</span>
                        </label>
                        <input type="file" id="logo" name="logo" accept="image/*"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer" />
                        <p class="mt-1.5 text-xs text-gray-400">PNG, JPG, or WEBP up to 2MB.</p>
                        @error('logo')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Initial Status Checkbox -->
                    <div class="flex items-center pt-6">
                        <input type="hidden" name="is_active" value="0" />
                        <label class="relative flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                                class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <div class="ml-3">
                                <span class="block text-sm font-semibold text-gray-900">Activate restaurant immediately</span>
                                <span class="block text-xs text-gray-500">When checked, restaurant storefront is live and taking orders right away.</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Section 2: Initial Restaurant Admin Account -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 pb-6 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                        2
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Initial Restaurant Admin Account</h2>
                        <p class="text-xs text-gray-500">This user will manage menu items, employees, and orders inside this restaurant's admin panel.</p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Admin Full Name -->
                    <div>
                        <label for="admin_name" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Admin Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required
                            placeholder="e.g. Mario Rossi"
                            class="block w-full rounded-xl border-0 py-3 px-4 text-sm text-gray-900 ring-1 ring-inset {{ $errors->has('admin_name') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-indigo-600' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                        @error('admin_name')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Admin Username -->
                    <div>
                        <label for="admin_username" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Admin Username <span class="text-red-500">*</span>
                            <span class="text-xs font-normal text-gray-500 ml-1">(Used to log in)</span>
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 font-mono text-sm">
                                @
                            </div>
                            <input type="text" id="admin_username" name="admin_username" value="{{ old('admin_username') }}" required
                                placeholder="mariorossi"
                                class="block w-full rounded-xl border-0 py-3 pl-9 pr-4 text-sm font-mono text-gray-900 ring-1 ring-inset {{ $errors->has('admin_username') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-indigo-600' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                        </div>
                        @error('admin_username')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Admin Email -->
                    <div class="sm:col-span-2">
                        <label for="admin_email" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Admin Email <span class="text-xs font-normal text-gray-500 ml-1">(Optional)</span>
                        </label>
                        <input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email') }}"
                            placeholder="admin@bellaitalia.com"
                            class="block w-full rounded-xl border-0 py-3 px-4 text-sm text-gray-900 ring-1 ring-inset {{ $errors->has('admin_email') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-indigo-600' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                        @error('admin_email')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Admin Password Block -->
                    <div class="sm:col-span-2 pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-semibold text-gray-900">
                                Password Credentials <span class="text-red-500">*</span>
                            </label>
                            <button type="button" onclick="generateAdminPassword()" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                                ⚡ Generate Strong Password
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <input type="text" id="admin_password" name="admin_password" required
                                    placeholder="Password (min. 8 characters)"
                                    class="block w-full rounded-xl border-0 py-3 px-4 text-sm font-mono text-gray-900 ring-1 ring-inset {{ $errors->has('admin_password') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-indigo-600' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                                @error('admin_password')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <input type="text" id="admin_password_confirmation" name="admin_password_confirmation" required
                                    placeholder="Confirm password"
                                    class="block w-full rounded-xl border-0 py-3 px-4 text-sm font-mono text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-inset focus:ring-indigo-600 bg-gray-50/50" />
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">The new restaurant admin will be prompted to change their password upon first login.</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-4 pt-4">
                <a href="{{ route('saas.restaurants.index') }}" 
                   class="px-6 py-3 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-white transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                    class="inline-flex items-center px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-indigo-500/25 transition-all duration-200">
                    Create Restaurant & Admin
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            let slugTouchedManually = false;

            if (slugInput.value.trim() !== '') {
                slugTouchedManually = true;
            }

            slugInput.addEventListener('input', function() {
                slugTouchedManually = true;
            });

            nameInput.addEventListener('input', function() {
                if (!slugTouchedManually) {
                    const generatedSlug = nameInput.value
                        .toLowerCase()
                        .trim()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_-]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    slugInput.value = generatedSlug;
                }
            });
        });

        function generateAdminPassword() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%&*';
            const randomValues = new Uint32Array(12);
            window.crypto.getRandomValues(randomValues);
            let pass = '';
            for (let i = 0; i < 12; i++) {
                pass += chars.charAt(randomValues[i] % chars.length);
            }
            document.getElementById('admin_password').value = pass;
            document.getElementById('admin_password_confirmation').value = pass;
        }
    </script>
@endsection
