<?php

require_once __DIR__ . '/../../Models/User.php';
use App\Models\User;

class SimpleAuthController
{
    public function showLoginForm()
    {
        // Include layout helpers
        require_once dirname(__DIR__, 3) . '/templates/layout_helpers.php';
        
        // Check if user is already logged in
        if (isAuthenticated()) {
            $user = getCurrentUser();
            if ($user->role === 'manager') {
                header('Location: /manager-dashboard');
            } else {
                header('Location: /employee-dashboard');
            }
            exit;
        }
        
        // Use new master layout system
        ob_start();
        include dirname(__DIR__, 3) . '/templates/login.php';
        $loginContent = ob_get_clean();
        
        return renderMasterLayout(
            $loginContent,
            [],
            'تسجيل الدخول - سما البنيان التجارية',
            true, // is_auth_page
            false, // show_sidebar
            '', // additional_head
            '' // additional_scripts
        );
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // التحقق من بيانات المستخدم في قاعدة البيانات
        $user = User::findByEmail($email);

        if ($user && $user->checkPassword($password)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['user_role'] = $user->role;
            $_SESSION['success'] = 'تم تسجيل الدخول بنجاح!';
            
            // توجيه المستخدم حسب دوره
            if ($user->role === 'manager') {
                header('Location: /manager-dashboard');
            } else {
                header('Location: /employee-dashboard');
            }
            exit;
        }

        $_SESSION['error'] = 'بيانات تسجيل الدخول غير صحيحة';
        $_SESSION['old'] = ['email' => $email];
        header('Location: /login');
        exit;
    }

    public function showRegisterForm()
    {
        // Include layout helpers
        require_once dirname(__DIR__, 3) . '/templates/layout_helpers.php';
        
        // Check if user is already logged in
        if (isAuthenticated()) {
            $user = getCurrentUser();
            if ($user->role === 'manager') {
                header('Location: /manager-dashboard');
            } else {
                header('Location: /employee-dashboard');
            }
            exit;
        }
        
        // Use new master layout system
        ob_start();
        include dirname(__DIR__, 3) . '/templates/signup.php';
        $signupContent = ob_get_clean();
        
        return renderMasterLayout(
            $signupContent,
            [],
            'إنشاء حساب جديد - سما البنيان التجارية',
            true, // is_auth_page
            false, // show_sidebar
            '', // additional_head
            '' // additional_scripts
        );
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /register');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';
        $terms = isset($_POST['terms']);

        // Validation
        $errors = [];
        
        if (empty($name)) $errors[] = 'الاسم الكامل مطلوب';
        if (empty($email)) $errors[] = 'البريد الإلكتروني مطلوب';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
        if (empty($phone)) $errors[] = 'رقم الهاتف مطلوب';
        if (empty($password)) $errors[] = 'كلمة المرور مطلوبة';
        if (strlen($password) < 6) $errors[] = 'كلمة المرور يجب أن تحتوي على 6 أحرف على الأقل';
        if ($password !== $passwordConfirmation) $errors[] = 'كلمتا المرور غير متطابقتين';
        if (!$terms) $errors[] = 'يجب الموافقة على الشروط والأحكام';

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = compact('name', 'email', 'phone');
            header('Location: /register');
            exit;
        }

        // التحقق من وجود البريد الإلكتروني
        $existingUser = User::findByEmail($email);
        if ($existingUser) {
            $_SESSION['errors'] = ['البريد الإلكتروني مستخدم بالفعل'];
            $_SESSION['old'] = compact('name', 'email', 'phone');
            header('Location: /register');
            exit;
        }

        // إنشاء المستخدم الجديد
        try {
            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->phone = $phone;
            $user->role = 'employee'; // الدور الافتراضي
            $user->setPassword($password);
            
            if ($user->save()) {
                $_SESSION['success'] = 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول';
                header('Location: /login');
            } else {
                $_SESSION['errors'] = ['حدث خطأ أثناء إنشاء الحساب، يرجى المحاولة مرة أخرى'];
                $_SESSION['old'] = compact('name', 'email', 'phone');
                header('Location: /register');
            }
        } catch (Exception $e) {
            $_SESSION['errors'] = ['حدث خطأ في النظام: ' . $e->getMessage()];
            $_SESSION['old'] = compact('name', 'email', 'phone');
            header('Location: /register');
        }
        exit;
    }

    public function showForgotPasswordForm()
    {
        // Include and output the forgot password template
        ob_start();
        include dirname(__DIR__, 3) . '/templates/forgot_password.php';
        return ob_get_clean();
    }

    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /forgot-password');
            exit;
        }

        $email = $_POST['email'] ?? '';

        if (empty($email)) {
            $_SESSION['error'] = 'البريد الإلكتروني مطلوب';
            header('Location: /forgot-password');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'البريد الإلكتروني غير صحيح';
            $_SESSION['old'] = ['email' => $email];
            header('Location: /forgot-password');
            exit;
        }

        // In a real app, send reset email
        $_SESSION['success'] = 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني';
        $_SESSION['info'] = 'في النسخة التجريبية، يمكنك الوصول لرابط إعادة التعيين مباشرة: <a href="/reset-password?email=' . urlencode($email) . '&token=demo-token-123">إعادة تعيين كلمة المرور</a>';
        header('Location: /forgot-password');
        exit;
    }

    public function showResetPasswordForm()
    {
        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';

        if (empty($token) || empty($email)) {
            $_SESSION['error'] = 'رابط إعادة تعيين كلمة المرور غير صحيح أو منتهي الصلاحية';
            header('Location: /forgot-password');
            exit;
        }

        // Include and output the reset password template
        ob_start();
        include dirname(__DIR__, 3) . '/templates/reset_password.php';
        return ob_get_clean();
    }

    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /forgot-password');
            exit;
        }

        $token = $_POST['token'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        // Validation
        $errors = [];
        
        if (empty($token)) $errors[] = 'رمز إعادة التعيين مطلوب';
        if (empty($email)) $errors[] = 'البريد الإلكتروني مطلوب';
        if (empty($password)) $errors[] = 'كلمة المرور الجديدة مطلوبة';
        if (strlen($password) < 8) $errors[] = 'كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'كلمة المرور يجب أن تحتوي على حرف كبير واحد على الأقل';
        if (!preg_match('/[a-z]/', $password)) $errors[] = 'كلمة المرور يجب أن تحتوي على حرف صغير واحد على الأقل';
        if (!preg_match('/\d/', $password)) $errors[] = 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل';
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) $errors[] = 'كلمة المرور يجب أن تحتوي على رمز خاص واحد على الأقل';
        if ($password !== $passwordConfirmation) $errors[] = 'كلمتا المرور غير متطابقتين';

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['email' => $email];
            header('Location: /reset-password?token=' . urlencode($token) . '&email=' . urlencode($email));
            exit;
        }

        // In a real app, update password in database
        $_SESSION['success'] = 'تم تحديث كلمة المرور بنجاح! يمكنك الآن تسجيل الدخول بكلمة المرور الجديدة';
        header('Location: /login');
        exit;
    }

    public function profile()
    {
        return "صفحة الملف الشخصي قيد الإنشاء";
    }
    
    public function logout()
    {
        // Clear all session data
        $_SESSION = array();
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Redirect to home page
        header('Location: /');
        exit;
    }
    
    public function dashboard()
    {
        // توجيه المستخدم حسب دوره
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager') {
            require_once __DIR__ . '/ContractController.php';
            $contractController = new ContractController();
            return $contractController->showManagerDashboard();
        } else {
            require_once __DIR__ . '/ContractController.php';
            $contractController = new ContractController();
            return $contractController->showEmployeeDashboard();
        }
    }
}