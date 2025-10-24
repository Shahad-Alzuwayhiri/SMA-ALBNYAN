<?php

namespace App\Http\Controllers;

class AuthController
{
    public function showLoginForm()
    {
        // Clear previous errors
        unset($_SESSION['errors']);
        
        // Include and output the login template
        ob_start();
        include dirname(__DIR__, 3) . '/templates/login.php';
        return ob_get_clean();
    }

    public function login()
    {
        $identity = $_POST['identity'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Simple demo authentication - replace with real DB check
        if ($identity && $password) {
            // Demo users
            $users = [
                'manager@sama.com' => ['name' => 'مدير النظام', 'role' => 'manager', 'password' => '123456'],
                'employee@sama.com' => ['name' => 'موظف', 'role' => 'employee', 'password' => '123456'],
                'ahmed@sama.com' => ['name' => 'أحمد محمد', 'role' => 'employee', 'password' => '123456'],
            ];
            
            if (isset($users[$identity]) && $users[$identity]['password'] === $password) {
                // Set session
                $_SESSION['user_id'] = 1;
                $_SESSION['user_name'] = $users[$identity]['name'];
                $_SESSION['user_email'] = $identity;
                $_SESSION['user_role'] = $users[$identity]['role'];
                
                // Clear errors
                unset($_SESSION['errors']);
                
                // Redirect based on role
                if ($_SESSION['user_role'] === 'manager') {
                    header('Location: /manager-dashboard');
                } else {
                    header('Location: /dashboard');
                }
                exit;
            }
        }
        
        $_SESSION['errors'] = ['البيانات المدخلة غير صحيحة. جرب: manager@sama.com / 123456'];
        header('Location: /login');
        exit;
    }

    public function showRegisterForm()
    {
        unset($_SESSION['errors']);
        return view('auth.register');
    }

    public function register()
    {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirmation = $_POST['password_confirmation'] ?? '';
        $invite_code = $_POST['invite_code'] ?? '';
        
        // Simple validation
        $errors = [];
        if (!$name) $errors[] = 'الاسم مطلوب';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
        if (!$password || strlen($password) < 6) $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        if ($password !== $password_confirmation) $errors[] = 'كلمة المرور غير متطابقة';
        
        if ($errors) {
            $_SESSION['errors'] = $errors;
            header('Location: /register');
            exit;
        }
        
        // Determine role
        $role = ($invite_code === 'MANAGER2025') ? 'manager' : 'employee';
        
        // Auto login after registration
        $_SESSION['user_id'] = rand(2, 1000);
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        
        // Redirect based on role
        if ($role === 'manager') {
            header('Location: /manager-dashboard');
        } else {
            header('Location: /dashboard');
        }
        exit;
    }

    public function logout()
    {
        // Clear all session data
        session_destroy();
        session_start(); // Restart for flash messages
        
        $_SESSION['success'] = 'تم تسجيل الخروج بنجاح';
        header('Location: /login');
        exit;
    }

    public function profile()
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        unset($_SESSION['errors']);
        return view('auth.profile');
    }

    public function updateProfile()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirmation = $_POST['password_confirmation'] ?? '';
        
        $errors = [];
        if (!$name) $errors[] = 'الاسم مطلوب';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
        if ($password && strlen($password) < 6) $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        if ($password && $password !== $password_confirmation) $errors[] = 'كلمة المرور غير متطابقة';
        
        if ($errors) {
            $_SESSION['errors'] = $errors;
            header('Location: /profile');
            exit;
        }
        
        // Update session
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
        $_SESSION['success'] = 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني';
        header('Location: /forgot-password');
        exit;
    }

    public function showResetForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword()
    {
        $_SESSION['success'] = 'تم تغيير كلمة المرور بنجاح';
        header('Location: /login');
        exit;
    }
}