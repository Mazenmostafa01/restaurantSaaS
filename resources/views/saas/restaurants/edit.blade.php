@extends('saas.layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto space-y-8">
        <!-- Breadcrumb & Header -->
        <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                    <a href="{{ route('saas.restaurants.index') }}" class="hover:text-indigo-600 transition-colors">Restaurants Dashboard</a>
                    <span>/</span>
                    <span class="text-gray-900 font-medium">Edit Restaurant</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $restaurant->name }}</h1>
                    @if ($restaurant->trashed())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                            Archived
                        </span>
                    @elseif ($restaurant->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            Inactive
                        </span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ url('/' . $restaurant->slug) }}" target="_blank" 
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    View Storefront
                </a>
            </div>
        </header>

        <!-- Section 1: Edit Restaurant Profile -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 sm:p-8 shadow-sm">
            <div class="pb-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Restaurant Profile & Details</h2>
                <p class="text-xs text-gray-500">Update restaurant branding, customer contact information, and public storefront slug.</p>
            </div>

            <form method="POST" action="{{ route('saas.restaurants.update', $restaurant) }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Name Field -->
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Restaurant Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $restaurant->name) }}" required
                            class="block w-full rounded-xl border-0 py-3 px-4 text-sm text-gray-900 ring-1 ring-inset {{ $errors->has('name') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-indigo-600' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Slug Field with Lock/Unlock -->
                    <div class="sm:col-span-2">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="slug" class="block text-sm font-semibold text-gray-900">
                                Storefront URL Slug <span class="text-red-500">*</span>
                            </label>
                            <button type="button" id="unlockSlugBtn" onclick="toggleSlugLock()" 
                                class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                                <svg class="w-3.5 h-3.5 mr-1" id="slugLockIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span id="slugLockText">Unlock to edit slug</span>
                            </button>
                        </div>
                        <div class="flex items-stretch rounded-xl overflow-hidden border {{ $errors->has('slug') ? 'border-red-400 focus-within:ring-2 focus-within:ring-red-500' : 'border-gray-200 focus-within:ring-2 focus-within:ring-indigo-600' }} bg-gray-50/50">
                            <span class="inline-flex items-center px-4 bg-gray-100 text-gray-500 font-mono text-sm border-r border-gray-200 select-none whitespace-nowrap">
                                {{ url('/') }}/
                            </span>
                            <input type="text" id="slug" name="slug" value="{{ old('slug', $restaurant->slug) }}" required readonly
                                class="block flex-1 min-w-0 border-0 py-3 px-4 text-sm font-mono text-gray-900 bg-gray-100 cursor-not-allowed focus:ring-0 focus:outline-none transition-colors" />
                        </div>
                        <div id="slugWarning" class="hidden mt-2 p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800">
                            <strong>Warning:</strong> Changing this slug immediately alters the restaurant's public storefront URL. Existing links, bookmarks, and printed QR codes will stop working.
                        </div>
                        @error('slug')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Contact Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email', $restaurant->email) }}" required
                            class="block w-full rounded-xl border-0 py-3 px-4 text-sm text-gray-900 ring-1 ring-inset {{ $errors->has('email') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-indigo-600' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone Field -->
                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Phone Number <span class="text-xs font-normal text-gray-500 ml-1">(Optional)</span>
                        </label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $restaurant->phone) }}"
                            class="block w-full rounded-xl border-0 py-3 px-4 text-sm text-gray-900 ring-1 ring-inset {{ $errors->has('phone') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-indigo-600' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                        @error('phone')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Address Field -->
                    <div class="sm:col-span-2">
                        <label for="address" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Address / Location <span class="text-xs font-normal text-gray-500 ml-1">(Optional)</span>
                        </label>
                        <input type="text" id="address" name="address" value="{{ old('address', $restaurant->address) }}"
                            class="block w-full rounded-xl border-0 py-3 px-4 text-sm text-gray-900 ring-1 ring-inset {{ $errors->has('address') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-indigo-600' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                        @error('address')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Logo Field -->
                    <div>
                        <label for="logo" class="block text-sm font-semibold text-gray-900 mb-1.5">
                            Restaurant Logo <span class="text-xs font-normal text-gray-500 ml-1">(Optional)</span>
                        </label>
                        @if ($restaurant->logo_path)
                            <div class="mb-3 flex items-center gap-3 p-3 rounded-xl bg-gray-50 border border-gray-200">
                                <img src="{{ asset('storage/' . $restaurant->logo_path) }}" alt="Current Logo" class="w-12 h-12 rounded-lg object-cover border border-gray-200" />
                                <div class="text-xs text-gray-600">
                                    <span class="font-semibold text-gray-900">Current Logo</span>
                                    <p class="text-gray-400">Upload a new file to replace it.</p>
                                </div>
                            </div>
                        @endif
                        <input type="file" id="logo" name="logo" accept="image/*"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer" />
                        @error('logo')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Active Status Checkbox -->
                    <div class="flex items-center pt-6">
                        <input type="hidden" name="is_active" value="0" />
                        <label class="relative flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $restaurant->is_active) ? 'checked' : '' }}
                                class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <div class="ml-3">
                                <span class="block text-sm font-semibold text-gray-900">Active Restaurant</span>
                                <span class="block text-xs text-gray-500">Unchecking disables the storefront and pauses staff portal access.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end">
                    <button type="submit" 
                        class="inline-flex items-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                        Save Profile Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Section 2: Restaurant Admin Accounts -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-100">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Restaurant Administrators</h2>
                    <p class="text-xs text-gray-500">Manage administrator user accounts belonging to this restaurant tenant.</p>
                </div>
                <button type="button" onclick="toggleAddAdminCard()" 
                    class="inline-flex items-center px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-xl transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New Admin
                </button>
            </div>

            <!-- Add New Admin Collapsible Form -->
            <div id="addAdminCard" class="hidden mt-6 p-6 bg-indigo-50/50 rounded-2xl border border-indigo-100">
                <h3 class="text-sm font-bold text-gray-900 mb-1">Create New Administrator</h3>
                <p class="text-xs text-gray-500 mb-4">Adds a new Admin user with full management rights for this restaurant.</p>

                <form method="POST" action="{{ route('saas.restaurants.admins.store', $restaurant) }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Sarah Smith"
                                class="block w-full rounded-xl border-0 py-2.5 px-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-600 bg-white" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" value="{{ old('username') }}" required placeholder="sarah_admin"
                                class="block w-full rounded-xl border-0 py-2.5 px-3 text-sm font-mono text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-600 bg-white" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Email <span class="text-xs font-normal text-gray-400">(Optional)</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="sarah@bellaitalia.com"
                                class="block w-full rounded-xl border-0 py-2.5 px-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-600 bg-white" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                            <input type="text" id="new_admin_password" name="password" required placeholder="Min. 8 characters"
                                class="block w-full rounded-xl border-0 py-2.5 px-3 text-sm font-mono text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-600 bg-white" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                            <input type="text" id="new_admin_password_confirmation" name="password_confirmation" required placeholder="Confirm password"
                                class="block w-full rounded-xl border-0 py-2.5 px-3 text-sm font-mono text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-600 bg-white" />
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" onclick="generateNewAdminPass()" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            ⚡ Generate Password
                        </button>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="toggleAddAdminCard()" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm">
                                Create Admin
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Current Admins List -->
            <div class="mt-6 overflow-hidden rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50/80">
                        <tr>
                            <th class="px-6 py-3 font-semibold text-gray-700">Administrator</th>
                            <th class="px-6 py-3 font-semibold text-gray-700">Username</th>
                            <th class="px-6 py-3 font-semibold text-gray-700">Email</th>
                            <th class="px-6 py-3 font-semibold text-gray-700">Created</th>
                            <th class="px-6 py-3 font-semibold text-gray-700 text-right">Reset Password</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($admins as $admin)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900">{{ $admin->name }}</div>
                                    @if ($admin->need_password_change)
                                        <span class="inline-flex items-center text-xs text-amber-600 font-medium">
                                            Password change required on login
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-700">
                                    {{ '@' . $admin->username }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $admin->email ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    {{ $admin->created_at?->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button" onclick="toggleResetPass('{{ $admin->id }}')" 
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                                        Reset Password
                                    </button>
                                </td>
                            </tr>
                            <!-- Reset Password Collapsible Row -->
                            <tr id="reset-pass-row-{{ $admin->id }}" class="hidden bg-gray-50/80 border-t border-b border-gray-200">
                                <td colspan="5" class="px-6 py-4">
                                    <form method="POST" action="{{ route('saas.restaurants.admins.reset-password', [$restaurant, $admin]) }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="text-xs font-semibold text-gray-700 whitespace-nowrap">
                                            New Password for {{ $admin->name }}:
                                        </div>
                                        <input type="text" id="reset-pass-{{ $admin->id }}" name="password" required placeholder="New password" 
                                            class="rounded-xl border-0 py-2 px-3 text-xs font-mono ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 bg-white" />
                                        <input type="text" id="reset-pass-conf-{{ $admin->id }}" name="password_confirmation" required placeholder="Confirm" 
                                            class="rounded-xl border-0 py-2 px-3 text-xs font-mono ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 bg-white" />
                                        <button type="button" onclick="generateRowPass('{{ $admin->id }}')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                                            ⚡ Auto
                                        </button>
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl whitespace-nowrap">
                                            Save Password
                                        </button>
                                        <button type="button" onclick="toggleResetPass('{{ $admin->id }}')" class="px-3 py-2 border border-gray-200 text-xs font-semibold text-gray-600 rounded-xl hover:bg-white whitespace-nowrap">
                                            Cancel
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-xs">
                                    No administrators found for this restaurant.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 3: Danger Zone -->
        <div class="bg-white rounded-2xl border border-red-200 p-6 sm:p-8 shadow-sm">
            <div class="pb-4 border-b border-red-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-red-100 text-red-700 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-red-900">Danger Zone</h2>
                    <p class="text-xs text-red-600">Administrative actions with platform-level impact.</p>
                </div>
            </div>

            <div class="mt-6 space-y-6">
                <!-- Action 1: Toggle Active -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-100">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">
                            {{ $restaurant->is_active ? 'Deactivate Restaurant' : 'Activate Restaurant' }}
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $restaurant->is_active ? 'Temporarily pause public storefront orders and prevent staff login.' : 'Make storefront public and re-enable staff login.' }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('saas.restaurants.toggle-active', $restaurant) }}">
                        @csrf
                        @method('PATCH')
                        @if ($restaurant->is_active)
                            <button type="submit" class="px-4 py-2 rounded-xl border border-amber-300 bg-amber-50 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition-colors">
                                Deactivate Restaurant
                            </button>
                        @else
                            <button type="submit" class="px-4 py-2 rounded-xl border border-green-300 bg-green-50 text-xs font-semibold text-green-800 hover:bg-green-100 transition-colors">
                                Activate Restaurant
                            </button>
                        @endif
                    </form>
                </div>

                <!-- Action 2: Archive / Restore -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-100">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">
                            {{ $restaurant->trashed() ? 'Restore Restaurant' : 'Archive Restaurant (Soft Delete)' }}
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $restaurant->trashed() ? 'Restore this restaurant back to Inactive status.' : 'Soft-deletes this restaurant, hides it from storefronts, and marks it archived.' }}
                        </p>
                    </div>
                    @if (! $restaurant->trashed())
                        <form method="POST" action="{{ route('saas.restaurants.destroy', $restaurant) }}" 
                              onsubmit="return confirm('Archive this restaurant? It will be deactivated and marked archived.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-red-50 hover:text-red-700 hover:border-red-200 transition-colors">
                                Archive Restaurant
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('saas.restaurants.restore', $restaurant) }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-xl border border-green-300 bg-green-50 text-xs font-semibold text-green-800 hover:bg-green-100 transition-colors">
                                Restore Restaurant
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Action 3: Permanent Delete -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-red-900">Permanently Delete Restaurant</div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Cascades permanent deletion to all associated orders, items, staff, customers, and attachments. 
                            <span class="text-red-600 font-medium">Cannot be undone.</span>
                        </p>
                        @if ($restaurant->is_active)
                            <p class="text-xs text-amber-600 font-semibold mt-1">
                                Notice: You must deactivate this restaurant before permanent deletion is allowed.
                            </p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('saas.restaurants.force-destroy', $restaurant) }}" 
                          onsubmit="return confirm('Permanently delete this restaurant and ALL its orders, items, staff, and customers? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        @if (! $restaurant->is_active)
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-semibold shadow-sm transition-colors">
                                Permanently Delete
                            </button>
                        @else
                            <button type="button" disabled 
                                class="px-5 py-2.5 rounded-xl bg-gray-200 text-gray-400 text-xs font-semibold cursor-not-allowed"
                                title="Deactivate restaurant first before permanent deletion">
                                Permanently Delete
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function toggleSlugLock() {
            const slugInput = document.getElementById('slug');
            const warning = document.getElementById('slugWarning');
            const text = document.getElementById('slugLockText');

            if (slugInput.hasAttribute('readonly')) {
                slugInput.removeAttribute('readonly');
                slugInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                slugInput.classList.add('bg-white');
                warning.classList.remove('hidden');
                text.textContent = 'Lock slug';
                slugInput.focus();
            } else {
                slugInput.setAttribute('readonly', 'readonly');
                slugInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                slugInput.classList.remove('bg-white');
                warning.classList.add('hidden');
                text.textContent = 'Unlock to edit slug';
            }
        }

        function toggleAddAdminCard() {
            const card = document.getElementById('addAdminCard');
            card.classList.toggle('hidden');
        }

        function toggleResetPass(adminId) {
            const row = document.getElementById('reset-pass-row-' + adminId);
            row.classList.toggle('hidden');
        }

        function generateRandomPassString() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%&*';
            const randomValues = new Uint32Array(12);
            window.crypto.getRandomValues(randomValues);
            let pass = '';
            for (let i = 0; i < 12; i++) {
                pass += chars.charAt(randomValues[i] % chars.length);
            }
            return pass;
        }

        function generateNewAdminPass() {
            const pass = generateRandomPassString();
            document.getElementById('new_admin_password').value = pass;
            document.getElementById('new_admin_password_confirmation').value = pass;
        }

        function generateRowPass(adminId) {
            const pass = generateRandomPassString();
            document.getElementById('reset-pass-' + adminId).value = pass;
            document.getElementById('reset-pass-conf-' + adminId).value = pass;
        }
    </script>
@endsection
