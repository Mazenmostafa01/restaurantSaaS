<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
        
        .checkbox-blue:checked {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-xl mb-4">
                <i class="fas fa-lock text-3xl text-blue-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-black mb-2">Welcome Back</h1>
            <p class="text-black">Sign in to your account</p>
        </div>

        <!-- Login Card -->
        <div class="glass-effect rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-8">
                <form method="POST" action="{{ route('loginPost') }}" id="loginForm">
                    @csrf
                    <!-- Email Field -->
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-blue-500"></i>Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-blue-400"></i>
                            </div>
                            <input type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}"
                                class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none input-focus transition duration-200"
                                placeholder="Enter your email"
                                >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-sm font-semibold text-gray-700">
                                <i class="fas fa-key mr-2 text-blue-500"></i>Password
                            </label>
                            <a href="#" class="text-sm text-blue-600 hover:text-blue-800 transition duration-200">
                                Forgot Password?
                            </a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-blue-400"></i>
                            </div>
                            <input type="password" 
                                id="password" 
                                name="password" 
                                class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none input-focus transition duration-200"
                                placeholder="Enter your password"
                                >
                            <button type="button" 
                                onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye text-blue-400 hover:text-blue-600 transition duration-200" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="mb-8">
                        <label class="flex items-center cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" 
                                    id="remember" 
                                    name="remember"
                                    class="w-5 h-5 border-2 border-gray-300 rounded flex items-center justify-center transition duration-200" id="customCheckbox">
                            </div>
                            <span class="ml-3 text-gray-700">Remember me</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                        id="submitBtn"
                        class="w-full blue-light-gradient text-white font-bold py-3 px-4 rounded-lg hover:shadow-xl transform hover:-translate-y-0.5 transition duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 shadow-lg">
                        <span id="btnText">
                            <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                        </span>
                    </button>
                </form>

                <!-- Divider -->
                <div class="my-8 flex items-center">
                    <div class="flex-grow border-t border-gray-300"></div>
                    <span class="mx-4 text-gray-500 text-sm">Or create account</span>
                    <div class="flex-grow border-t border-gray-300"></div>
                </div>

                <!-- Sign Up Link -->
                <div class="text-center">
                    <p class="text-gray-600">
                        Don't have an account?
                        <a href="#" class="text-blue-600 font-semibold hover:text-blue-800 transition duration-200 ml-1">
                            Create one now
                            <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </p>
                </div>
            </div>

    <script>
        // Password visibility toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
        
        // Custom checkbox functionality
        document.addEventListener('DOMContentLoaded', function() {
            const checkbox = document.getElementById('remember');
            const customCheckbox = document.getElementById('customCheckbox');
            const checkIcon = customCheckbox.querySelector('i');
            
            customCheckbox.addEventListener('click', function() {
                if (checkbox.checked) {
                    customCheckbox.classList.add('bg-blue-600', 'border-blue-600');
                    checkIcon.classList.remove('hidden');
                } else {
                    customCheckbox.classList.remove('bg-blue-600', 'border-blue-600');
                    checkIcon.classList.add('hidden');
                }
            });
            
            // Initialize based on checkbox state
            if (checkbox.checked) {
                customCheckbox.classList.add('bg-blue-600', 'border-blue-600');
                checkIcon.classList.remove('hidden');
            }
        });
        
        // Form submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Basic validation
            if (!email || !password) {
                e.preventDefault();
                
                // Show error animation
                submitBtn.classList.remove('blue-light-gradient');
                submitBtn.classList.add('bg-red-500');
                btnText.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Fill all fields';
                
                setTimeout(() => {
                    submitBtn.classList.remove('bg-red-500');
                    submitBtn.classList.add('blue-light-gradient');
                    btnText.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Sign In';
                }, 2000);
                
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            btnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing in...';
        });
        
        // Input focus effects
        const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
        inputs.forEach(input => {
            const parent = input.parentElement;
            
            input.addEventListener('focus', function() {
                parent.classList.add('ring-2', 'ring-blue-200', 'rounded-lg');
            });
            
            input.addEventListener('blur', function() {
                parent.classList.remove('ring-2', 'ring-blue-200');
            });
        });
        
        // Add floating label effect for filled inputs
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.value) {
                    this.classList.add('bg-blue-50');
                } else {
                    this.classList.remove('bg-blue-50');
                }
            });
            
            // Trigger on page load if there's existing value
            if (input.value) {
                input.classList.add('bg-blue-50');
            }
        });
        
        // Add some interactive effects
        document.querySelectorAll('button, a').forEach(element => {
            element.addEventListener('mouseenter', function() {
                if (!this.classList.contains('submit')) {
                    this.style.transform = 'translateY(-2px)';
                }
            });
            
            element.addEventListener('mouseleave', function() {
                if (!this.classList.contains('submit')) {
                    this.style.transform = 'translateY(0)';
                }
            });
        });
    </script>
</body>
</html>