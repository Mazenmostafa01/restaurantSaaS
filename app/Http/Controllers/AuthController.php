<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email_or_username' => ['required', 'string'],
            'password' => ['required'],
        ]);
        $remember = $request->has('remember');

        $loginValue = $credentials['email_or_username'];
        $field = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $authCredentials = [
            $field => $loginValue,
            'password' => $credentials['password'],
        ];

        if (Auth::attempt($authCredentials, $remember)) {
            $request->session()->regenerate();

            if ($request->user()?->need_password_change) {
                return redirect()->route('password.change');
            }

            return redirect()->route('home');
        }

        return back()->withErrors([
            'error' => 'wrong username or password',
        ])->onlyInput('email_or_username');
    }

    public function showChangePassword(): View|RedirectResponse
    {
        if (! Auth::user()?->need_password_change) {
            return redirect()->route('home');
        }

        return view('auth.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($request->input('password')),
            'need_password_change' => false,
        ]);

        return redirect()->route('home')->with('success', 'Your password has been changed successfully.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
