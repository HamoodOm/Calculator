<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Check if user is active
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'تم تعطيل حسابك. يرجى التواصل مع المسؤول.',
                ])->onlyInput('email');
            }

            // Check if user's institution is active (skip for super users)
            if (!$user->isSuperUser() && $user->institution && !$user->institution->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'تم تعطيل المؤسسة التابع لها حسابك. يرجى التواصل مع المسؤول.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            // Log successful login
            ActivityLogService::logLogin($user);

            return redirect()->intended(route('dashboard'));
        }

        // Log failed login attempt
        ActivityLogService::logLoginFailed($request->email);

        return back()->withErrors([
            'email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
        ])->onlyInput('email');
    }

    /**
     * Show registration form
     */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Handle registration request
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // New users are created without any role - admin must assign permissions
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => null, // No role assigned - admin must grant access
            'is_active' => true,
        ]);

        // Log registration
        ActivityLogService::logCreate($user, 'تسجيل مستخدم جديد');

        Auth::login($user);

        // Log login after registration
        ActivityLogService::logLogin($user);

        return redirect()->route('dashboard')->with('status', 'تم إنشاء حسابك بنجاح! يرجى انتظار المسؤول لمنحك الصلاحيات اللازمة.');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        // Log logout before actually logging out
        if ($user) {
            ActivityLogService::logLogout($user);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Show dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();

        return view('auth.dashboard', [
            'user' => $user,
        ]);
    }
}
