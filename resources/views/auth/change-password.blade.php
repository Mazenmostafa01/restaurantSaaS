<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Change Password - First Time Setup</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/js/app.js'])
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        .blue-gradient {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #60a5fa 100%);
        }

        .blue-light-gradient {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }

        .wave-bg {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%233b82f6' fill-opacity='0.1' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,149.3C960,160,1056,160,1152,138.7C1248,117,1344,75,1392,53.3L1440,32L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
            background-position: center;
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body class="blue-gradient min-h-screen flex items-center justify-center p-4 wave-bg">
    <!-- Decorative Circles -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-10 left-10 w-64 h-64 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 floating"></div>
        <div class="absolute bottom-10 right-10 w-64 h-64 bg-blue-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 floating" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/3 w-48 h-48 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 floating" style="animation-delay: 2s;"></div>
    </div>

    <div class="relative max-w-md w-full z-10">
        <!-- Logo/Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-xl mb-4">
                <i class="fas fa-shield-alt text-3xl text-blue-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-black mb-1">Set Your New Password</h1>
            <p class="text-gray-700 text-sm">For security, you must update your temporary password before accessing the system.</p>
        </div>

        <!-- Change Password Card -->
        <div class="glass-effect rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-8">
                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                            <span class="text-sm font-semibold text-red-700">Please correct the following:</span>
                        </div>
                        <ul class="list-disc list-inside text-xs text-red-600 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.change.update') }}" id="changePasswordForm">
                    @csrf

                    <!-- New Password Field -->
                    <div class="mb-5">
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-key mr-2 text-blue-500"></i>New Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-blue-400"></i>
                            </div>
                            <input type="password"
                                id="password"
                                name="password"
                                class="pl-10 pr-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none input-focus transition duration-200"
                                placeholder="At least 8 characters"
                                required
                                autofocus
                            >
                            <button type="button"
                                onclick="toggleVisibility('password', 'eyeIcon1')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600">
                                <i class="fas fa-eye text-blue-400" id="eyeIcon1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm New Password Field -->
                    <div class="mb-6">
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-check-circle mr-2 text-blue-500"></i>Confirm New Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-blue-400"></i>
                            </div>
                            <input type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="pl-10 pr-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none input-focus transition duration-200"
                                placeholder="Re-enter new password"
                                required
                            >
                            <button type="button"
                                onclick="toggleVisibility('password_confirmation', 'eyeIcon2')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600">
                                <i class="fas fa-eye text-blue-400" id="eyeIcon2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        id="submitBtn"
                        class="w-full blue-light-gradient text-white font-bold py-3 px-4 rounded-lg hover:shadow-xl transform hover:-translate-y-0.5 transition duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 shadow-lg">
                        <span id="btnText">
                            <i class="fas fa-check mr-2"></i>Update Password & Continue
                        </span>
                    </button>
                </form>

                <!-- Divider & Logout -->
                <div class="my-6 flex items-center">
                    <div class="flex-grow border-t border-gray-300"></div>
                    <span class="mx-3 text-gray-400 text-xs uppercase tracking-wider">or</span>
                    <div class="flex-grow border-t border-gray-300"></div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="text-center">
                    @csrf
                    <button type="submit" class="text-sm text-gray-600 hover:text-red-600 font-medium transition duration-200">
                        <i class="fas fa-sign-out-alt mr-1"></i>Log out of this session
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
