<?php

namespace App\Http\Controllers;

class AuthController
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login()
    {
        // Simple login logic for demo
        $identity = $_POST['identity'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Simple validation (in real app, use proper validation)
        if (empty($identity) || empty($password)) {
            $_SESSION['errors'] = ['البيانات المدخلة غير مكتملة'];
            header('Location: /login');
            exit;
        }
        
        // Simple demo login (replace with real authentication)
        if ($password === 'admin123') {
            $_SESSION['user_id'] = 1;
            $_SESSION['user_name'] = 'أحمد محمد';
            $_SESSION['user_email'] = $identity;
            $_SESSION['user_role'] = 'manager';
            header('Location: /manager-dashboard');
        } else {
            $_SESSION['user_id'] = 2;
            $_SESSION['user_name'] = 'فاطمة علي';
            $_SESSION['user_email'] = $identity;
            $_SESSION['user_role'] = 'employee';
            header('Location: /');
        }
        exit;
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register()
    {
        // Simple registration logic for demo
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirmation = $_POST['password_confirmation'] ?? '';
        $invite_code = $_POST['invite_code'] ?? '';
        
        // Basic validation
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['errors'] = ['جميع الحقول مطلوبة'];
            header('Location: /register');
            exit;
        }
        
        if ($password !== $password_confirmation) {
            $_SESSION['errors'] = ['كلمة المرور غير متطابقة'];
            header('Location: /register');
            exit;
        }
        
        // Determine role based on invite code
        $role = ($invite_code === 'MANAGER2025') ? 'manager' : 'employee';
        
        // Create user session (in real app, save to database)
        $_SESSION['user_id'] = rand(100, 999);
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        
        if ($role === 'manager') {
            header('Location: /manager-dashboard');
        } else {
            header('Location: /');
        }
        exit;
    }

    public function logout()
    {
        session_destroy();
        header('Location: /login');
        exit;
    }

    public function profile()
    {
        return view('auth.profile');
    }

    public function updateProfile()
    {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($email)) {
            $_SESSION['errors'] = ['الاسم والبريد الإلكتروني مطلوبان'];
            header('Location: /profile');
            exit;
        }
        
        // Update session data
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        
        $_SESSION['success'] = 'تم تحديث الملف الشخصي بنجاح';
        header('Location: /profile');
        exit;
    }

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetEmail()
    {
        $_SESSION['status'] = 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني';
        header('Location: /forgot-password');
        exit;
    }

    public function showResetForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword()
    {
        $_SESSION['status'] = 'تم تغيير كلمة المرور بنجاح';
        header('Location: /login');
        exit;
    }
}