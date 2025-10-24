<?php

namespace App\Controllers;

use App\Helpers\InputValidator;

/**
 * Authentication Controller - وحده التحكم في المصادقة
 * Handles user authentication, login, logout, and registration
 * يتعامل مع مصادقة المستخدم وتسجيل الدخول والخروج والتسجيل
 */
class AuthController extends BaseController
{
    /**
     * Show unified login page
     * عرض صفحة تسجيل الدخول الموحدة
     */
    public function showLogin()
    {
        // Redirect authenticated users to home - توجيه المستخدمين المسجلين إلى الصفحة الرئيسية
        if ($this->user) {
            // Redirect to home page instead of specific dashboards to avoid loops
            // The home controller will handle proper dashboard routing
            $this->redirect('/');
            return;
        }
        
        // Get and clear any error messages - جلب ومحو رسائل الخطأ
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);
        
        return $this->view('auth/unified_login', ['error' => $error]);
    }
    
    /**
     * Show enhanced login page (alternative design)
     * عرض صفحة تسجيل الدخول المحسنة (تصميم بديل)
     */
    public function showEnhancedLogin()
    {
        // Redirect authenticated users - توجيه المستخدمين المسجلين
        if ($this->user) {
            $this->redirect('/');
        }
        
        // Get and clear error messages - جلب ومحو رسائل الخطأ
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);
        
        return $this->view('auth/enhanced_login', ['error' => $error]);
    }
    
    /**
     * Show alternative login page (third design option)
     * عرض صفحة تسجيل الدخول البديلة (خيار التصميم الثالث)
     */
    public function showAlternativeLogin()
    {
        // Redirect authenticated users - توجيه المستخدمين المسجلين
        if ($this->user) {
            $this->redirect('/');
        }
        
        // Get and clear error messages - جلب ومحو رسائل الخطأ
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);
        
        return $this->view('auth/alternative_login', ['error' => $error]);
    }
    
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
        }
        
        // CSRF Protection
        $token = $_POST['csrf_token'] ?? '';
        if (!InputValidator::csrfToken($token)) {
            $_SESSION['error'] = 'Invalid security token';
            $this->redirect('/login');
        }
        
        $email = InputValidator::email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (!$email || empty($password)) {
            $_SESSION['error'] = 'البريد الإلكتروني وكلمة المرور مطلوبان';
            $_SESSION['old'] = ['email' => $_POST['email'] ?? ''];
            $this->redirect('/login');
        }
        
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;
                $_SESSION['success'] = 'تم تسجيل الدخول بنجاح';
                $this->redirect('/');
            } else {
                $_SESSION['error'] = 'بيانات الدخول غير صحيحة';
                $this->redirect('/login');
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في تسجيل الدخول';
            $this->redirect('/login');
        }
    }
    
    public function logout()
    {
        session_destroy();
        $this->redirect('/welcome');
    }
    
    public function showSignup()
    {
        if ($this->user) {
            $this->redirect('/');
        }
        
        return $this->view('auth/signup');
    }
    
    public function signup()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/signup');
        }
        
        // CSRF Protection
        $token = $_POST['csrf_token'] ?? '';
        if (!InputValidator::csrfToken($token)) {
            $_SESSION['error'] = 'Invalid security token';
            $this->redirect('/signup');
        }
        
        $name = InputValidator::string($_POST['name'] ?? '', 100);
        $email = InputValidator::email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        // Validation
        $errors = [];
        if (!$name || empty($name)) $errors[] = 'الاسم مطلوب';
        if (!$email) $errors[] = 'البريد الإلكتروني غير صحيح';
        if (!InputValidator::password($password)) $errors[] = 'كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل وتشمل أرقام وحروف';
        if ($password !== $password_confirm) $errors[] = 'كلمة المرور غير متطابقة';
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $this->redirect('/signup');
        }
        
        try {
            // Check if email exists
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'البريد الإلكتروني مستخدم بالفعل';
                $this->redirect('/signup');
            }
            
            // Create user
            $stmt = $this->pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), 'employee']);
            
            $_SESSION['success'] = 'تم إنشاء الحساب بنجاح. يمكنك تسجيل الدخول الآن';
            $this->redirect('/login');
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في إنشاء الحساب';
            $this->redirect('/signup');
        }
    }
}