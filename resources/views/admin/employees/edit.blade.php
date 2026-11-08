@extends('welcome')

@section('content')
    <div class="max-w-3xl mx-auto">
        <header class="mb-8">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('employees.index') }}" class="hover:text-gray-900 transition-colors">Staff & Employees</a>
                <span>/</span>
                <span class="text-gray-900 font-medium">Edit Employee</span>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Edit Staff Member</h1>
            <p class="mt-1 text-sm text-gray-500">Update account details, role permissions, or reset password for {{ $employee->name }}.</p>
        </header>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-sm">
            <form method="POST" action="{{ route('employees.update', $employee) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Name Field -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-900 mb-1.5">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name', $employee->name) }}" required
                        class="block w-full rounded-xl border-0 py-3 px-4 text-sm text-gray-900 ring-1 ring-inset {{ $errors->has('name') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-gray-900' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Username Field -->
                <div>
                    <label for="username" class="block text-sm font-semibold text-gray-900 mb-1.5">
                        Username <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 font-mono text-sm">
                            @
                        </div>
                        <input type="text" id="username" name="username" value="{{ old('username', $employee->username) }}" required
                            class="block w-full rounded-xl border-0 py-3 pl-9 pr-4 text-sm font-mono text-gray-900 ring-1 ring-inset {{ $errors->has('username') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-gray-900' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                    </div>
                    @error('username')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-900 mb-1.5">
                        Email Address <span class="text-xs font-normal text-gray-500 ml-1">(Optional)</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email', $employee->email) }}"
                        class="block w-full rounded-xl border-0 py-3 px-4 text-sm text-gray-900 ring-1 ring-inset {{ $errors->has('email') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-gray-900' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role Selection -->
                @php
                    $roleDescriptions = [
                        'Admin' => 'Full access to dashboard, menu items catalog, orders, and staff management.',
                        'Employee' => 'Can take orders, view items, and perform everyday restaurant operations.',
                        'Manager' => 'Can manage staff, oversee orders, and view restaurant reports.',
                        'Cashier' => 'Can process customer orders, accept payments, and issue receipts.',
                        'Chef' => 'Can view and update kitchen order preparation status.',
                    ];
                    $currentRole = old('role', $employee->roles->first()?->name ?? 'Employee');
                    $isSelf = $employee->id === auth()->id();
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-900">
                            Role & Access Level <span class="text-red-500">*</span>
                        </label>
                        @if ($isSelf)
                            <span class="text-xs text-amber-600 font-medium">
                                🔒 You cannot change your own role.
                            </span>
                        @endif
                    </div>

                    @if ($isSelf)
                        <input type="hidden" name="role" value="{{ $currentRole }}" />
                        <div class="rounded-xl border border-purple-200 bg-purple-50/50 p-4">
                            <span class="block text-sm font-semibold text-purple-900">{{ $currentRole }}</span>
                            <span class="block text-xs text-purple-700 mt-0.5">You are currently logged in as this administrator.</span>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($roles as $role)
                                @php
                                    $roleName = $role;
                                    $isChecked = ($currentRole === $roleName);
                                    $description = $roleDescriptions[$roleName] ?? "Assigned as {$roleName} with corresponding system permissions.";
                                    $accentColor = $roleName === 'Admin' ? 'text-purple-600 focus:ring-purple-500' : 'text-blue-600 focus:ring-blue-500';
                                @endphp
                                <label class="relative flex cursor-pointer rounded-xl border border-gray-200 p-4 hover:border-gray-300 focus:outline-none transition-colors">
                                    <input type="radio" name="role" value="{{ $roleName }}" class="mt-0.5 h-4 w-4 {{ $accentColor }} border-gray-300" {{ $isChecked ? 'checked' : '' }} />
                                    <div class="ml-3 flex flex-col">
                                        <span class="block text-sm font-semibold text-gray-900">{{ $roleName }}</span>
                                        <span class="block text-xs text-gray-500 mt-1">{{ $description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    @error('role')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Block (Optional on Edit) -->
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-1">
                        <label for="password" class="block text-sm font-semibold text-gray-900">
                            Reset Password <span class="text-xs font-normal text-gray-500 ml-1">(Leave blank to keep existing password)</span>
                        </label>
                        <button type="button" onclick="generatePassword()" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors">
                            ⚡ Generate Strong Password
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                        <div>
                            <input type="text" id="password" name="password"
                                placeholder="New password (optional)"
                                class="block w-full rounded-xl border-0 py-3 px-4 text-sm font-mono text-gray-900 ring-1 ring-inset {{ $errors->has('password') ? 'ring-red-400 focus:ring-red-500' : 'ring-gray-200 focus:ring-gray-900' }} focus:ring-2 focus:ring-inset bg-gray-50/50" />
                            @error('password')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <input type="text" id="password_confirmation" name="password_confirmation"
                                placeholder="Confirm new password"
                                class="block w-full rounded-xl border-0 py-3 px-4 text-sm font-mono text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-inset focus:ring-gray-900 bg-gray-50/50" />
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="pt-6 border-t border-gray-100 flex items-center justify-end gap-3">
                    <a href="{{ route('employees.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-gray-900 to-gray-800 hover:from-gray-800 hover:to-gray-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        Update Employee
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function generatePassword() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%&*';
            const randomValues = new Uint32Array(10);
            window.crypto.getRandomValues(randomValues);
            let pass = '';
            for (let i = 0; i < 10; i++) {
                pass += chars.charAt(randomValues[i] % chars.length);
            }
            document.getElementById('password').value = pass;
            document.getElementById('password_confirmation').value = pass;
        }
    </script>
@endsection
