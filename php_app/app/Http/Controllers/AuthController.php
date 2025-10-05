<?php

namespace App\Http\Controllers;

class AuthController
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'identity' => 'required|string',
            'password' => 'required|string',
        ]);

        // Try to login with email or phone
        $login_field = filter_var($credentials['identity'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        
        if (Auth::attempt([$login_field => $credentials['identity'], 'password' => $credentials['password']], $request->filled('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            if ($user->isManager()) {
                return redirect()->route('manager.dashboard');
            }
            
            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'identity' => 'البيانات المدخلة غير صحيحة.',
        ])->onlyInput('identity');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'invite_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect('register')
                        ->withErrors($validator)
                        ->withInput();
        }

        $role = 'employee'; // Default role
        
        // Check invite code for manager role
        if ($request->filled('invite_code') && $request->invite_code === 'MANAGER2025') {
            $role = 'manager';
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
        ]);

        Auth::login($user);

        if ($user->isManager()) {
            return redirect()->route('manager.dashboard');
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function profile()
    {
        return view('auth.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->route('profile')
                        ->withErrors($validator)
                        ->withInput();
        }

        $user->name = $request->name;
        $user->email = $request->email;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();

        return redirect()->route('profile')->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetEmail(Request $request)
    {
        // Placeholder - implement password reset logic
        return back()->with('status', 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني');
    }

    public function showResetForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        // Placeholder - implement password reset logic
        return redirect()->route('login')->with('status', 'تم تغيير كلمة المرور بنجاح');
    }
}