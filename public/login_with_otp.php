<?php
require_once '../includes/auth.php';
require_once '../services/OTPService.php';

// إعادة توجيه المستخدمين المسجلين للدخول
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    $dashboardUrl = ($user['role'] === 'employee') ? '/employee_dashboard.php' : '/manager_dashboard.php';
    header("Location: $dashboardUrl");
    exit;
}

$otpService = new OTPService($pdo);
$error = '';
$message = '';
$showOTPForm = false;
$email = '';

// معالجة تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $useOTP = isset($_POST['use_otp']) && $_POST['use_otp'] === '1';
        
        if (empty($email) || empty($password)) {
            $error = 'البريد الإلكتروني وكلمة المرور مطلوبان';
        } else {
            // التحقق من بيانات المستخدم
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    if ($useOTP) {
                        // توليد وإرسال كود OTP
                        $otpCode = $otpService->generateOTP($email, 'login');
                        $showOTPForm = true;
                        $message = 'تم إرسال كود التحقق إلى بريدك الإلكتروني. يرجى إدخال الكود للمتابعة.';
                        
                        // حفظ بيانات المستخدم مؤقتاً في الجلسة
                        $_SESSION['temp_user_data'] = $user;
                        $_SESSION['temp_login_email'] = $email;
                    } else {
                        // تسجيل دخول مباشر بدون OTP
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        
                        // تسجيل النشاط
                        $logStmt = $pdo->prepare("
                            INSERT INTO activity_log (user_id, action, description, created_at) 
                            VALUES (?, 'login', ?, CURRENT_TIMESTAMP)
                        ");
                        $logStmt->execute([$user['id'], 'تسجيل دخول من: ' . $_SERVER['REMOTE_ADDR']]);
                        
                        $dashboardUrl = ($user['role'] === 'employee') ? '/employee_dashboard.php' : '/manager_dashboard.php';
                        header("Location: $dashboardUrl");
                        exit;
                    }
                } else {
                    $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
                }
            } catch (PDOException $e) {
                $error = 'خطأ في الاتصال بقاعدة البيانات';
            }
        }
    } elseif ($action === 'verify_otp') {
        $email = $_SESSION['temp_login_email'] ?? '';
        $otpCode = $_POST['otp_code'] ?? '';
        
        if (empty($otpCode)) {
            $error = 'كود التحقق مطلوب';
            $showOTPForm = true;
        } elseif ($otpService->verifyOTP($email, $otpCode, 'login')) {
            // OTP صحيح - إكمال تسجيل الدخول
            $userData = $_SESSION['temp_user_data'];
            
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_name'] = $userData['name'];
            $_SESSION['user_email'] = $userData['email'];
            $_SESSION['user_role'] = $userData['role'];
            
            // مسح البيانات المؤقتة
            unset($_SESSION['temp_user_data']);
            unset($_SESSION['temp_login_email']);
            
            // تسجيل النشاط
            $logStmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, description, created_at) 
                VALUES (?, 'login', ?, CURRENT_TIMESTAMP)
            ");
            $logStmt->execute([$userData['id'], 'تسجيل دخول مع OTP من: ' . $_SERVER['REMOTE_ADDR']]);
            
            $dashboardUrl = ($userData['role'] === 'employee') ? '/employee_dashboard.php' : '/manager_dashboard.php';
            header("Location: $dashboardUrl");
            exit;
        } else {
            $error = 'كود التحقق غير صحيح أو منتهي الصلاحية';
            $showOTPForm = true;
        }
    } elseif ($action === 'resend_otp') {
        $email = $_SESSION['temp_login_email'] ?? '';
        if ($email) {
            $otpCode = $otpService->generateOTP($email, 'login');
            $showOTPForm = true;
            $message = 'تم إعادة إرسال كود التحقق';
        } else {
            $error = 'حدث خطأ، يرجى المحاولة مرة أخرى';
        }
    }
}

// التحقق من وجود بيانات مؤقتة لإظهار نموذج OTP
if (isset($_SESSION['temp_login_email']) && !$showOTPForm && empty($_POST)) {
    $showOTPForm = true;
    $email = $_SESSION['temp_login_email'];
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام إدارة العقود</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/modern-theme.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            margin: 2rem;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .login-form {
            padding: 3rem 2rem;
        }
        
        .form-control {
            border: 2px solid #f0f0f0;
            border-radius: 15px;
            padding: 1rem 1.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(119, 188, 195, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: 15px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .otp-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: center;
        }
        
        .otp-input {
            font-size: 2rem;
            text-align: center;
            letter-spacing: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .security-features {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .feature-item:last-child {
            margin-bottom: 0;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            background: var(--secondary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 1rem;
        }
        
        .toggle-otp {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .otp-timer {
            color: #f57c00;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .demo-note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="row g-0">
            <div class="col-md-6">
                <div class="login-header h-100 d-flex flex-column justify-content-center">
                    <h1 class="mb-3">
                        <i class="fas fa-file-contract me-3"></i>
                        نظام إدارة العقود
                    </h1>
                    <p class="lead mb-4">منصة متكاملة لإدارة العقود والوثائق</p>
                    
                    <div class="security-features">
                        <h5 class="mb-3 text-white">
                            <i class="fas fa-shield-alt me-2"></i>
                            مميزات الأمان
                        </h5>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div class="text-white">
                                <strong>تشفير متقدم</strong>
                                <p class="mb-0 small">حماية قصوى للبيانات</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="text-white">
                                <strong>التحقق الثنائي (OTP)</strong>
                                <p class="mb-0 small">طبقة أمان إضافية</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <div class="text-white">
                                <strong>سجل النشاطات</strong>
                                <p class="mb-0 small">مراقبة شاملة للعمليات</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="login-form">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$showOTPForm): ?>
                        <!-- نموذج تسجيل الدخول العادي -->
                        <h3 class="mb-4 text-center">تسجيل الدخول</h3>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="login">
                            
                            <div class="mb-3">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control" required 
                                       value="<?= htmlspecialchars($email) ?>"
                                       placeholder="example@company.com">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">كلمة المرور</label>
                                <input type="password" name="password" class="form-control" required 
                                       placeholder="••••••••">
                            </div>
                            
                            <div class="toggle-otp">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="use_otp" value="1" id="useOTP">
                                    <label class="form-check-label" for="useOTP">
                                        <i class="fas fa-mobile-alt me-2"></i>
                                        <strong>استخدام التحقق الثنائي (OTP)</strong>
                                        <br>
                                        <small class="text-muted">للحصول على طبقة أمان إضافية</small>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>تسجيل الدخول
                            </button>
                        </form>
                        
                        <!-- معلومات تجريبية -->
                        <div class="demo-note">
                            <strong><i class="fas fa-info-circle me-2"></i>للاختبار:</strong>
                            <br>
                            <small>
                                المدير: manager@example.com / password123<br>
                                الموظف: employee@example.com / password123
                            </small>
                        </div>
                    <?php else: ?>
                        <!-- نموذج التحقق من OTP -->
                        <h3 class="mb-4 text-center">التحقق الثنائي</h3>
                        
                        <div class="otp-container">
                            <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                            <h5>أدخل كود التحقق</h5>
                            <p class="text-muted">تم إرسال كود التحقق إلى البريد الإلكتروني:<br>
                                <strong><?= htmlspecialchars($email) ?></strong>
                            </p>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="verify_otp">
                                
                                <div class="mb-3">
                                    <input type="text" name="otp_code" class="form-control otp-input" 
                                           maxlength="6" pattern="[0-9]{6}" required 
                                           placeholder="000000" autocomplete="off">
                                </div>
                                
                                <button type="submit" class="btn btn-login mb-3">
                                    <i class="fas fa-check me-2"></i>تحقق من الكود
                                </button>
                            </form>
                            
                            <div class="otp-timer" id="otpTimer">
                                <i class="fas fa-clock me-2"></i>
                                <span id="timerText">الكود صالح لمدة 10 دقائق</span>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="resend_otp">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo me-2"></i>إعادة إرسال الكود
                                </button>
                            </form>
                            
                            <a href="<?= asset('login.php') ?>" class="btn btn-outline-primary ms-2">
                                <i class="fas fa-arrow-right me-2"></i>العودة
                            </a>
                        </div>
                        
                        <?php if (isset($_SESSION['temp_login_email'])): ?>
                            <div class="demo-note">
                                <strong><i class="fas fa-info-circle me-2"></i>للاختبار:</strong>
                                <br>
                                <small>
                                    آخر كود تم إرساله: <strong><?= $otpService->getLastOTP($_SESSION['temp_login_email'], 'login') ?></strong>
                                </small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تحسين تجربة المستخدم لحقل OTP
        const otpInput = document.querySelector('.otp-input');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                // السماح بالأرقام فقط
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // إرسال النموذج تلقائياً عند إدخال 6 أرقام
                if (this.value.length === 6) {
                    setTimeout(() => {
                        this.form.submit();
                    }, 500);
                }
            });
            
            // التركيز على الحقل تلقائياً
            otpInput.focus();
        }
        
        // مؤقت للكود
        function startOTPTimer() {
            const timerElement = document.getElementById('timerText');
            if (!timerElement) return;
            
            let timeLeft = 10 * 60; // 10 دقائق بالثواني
            
            const timer = setInterval(() => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                timerElement.textContent = `الكود صالح لمدة ${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    timerElement.innerHTML = '<span class="text-danger">انتهت صلاحية الكود</span>';
                    
                    // تعطيل حقل الإدخال
                    if (otpInput) {
                        otpInput.disabled = true;
                        otpInput.placeholder = 'انتهت الصلاحية';
                    }
                }
                
                timeLeft--;
            }, 1000);
        }
        
        // بدء المؤقت إذا كان نموذج OTP معروضاً
        if (document.querySelector('.otp-container')) {
            startOTPTimer();
        }
        
        // تحسين تجربة تسجيل الدخول
        const loginForm = document.querySelector('form');
        if (loginForm && !otpInput) {
            loginForm.addEventListener('submit', function(e) {
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري تسجيل الدخول...';
                submitButton.disabled = true;
            });
        }
        
        // تأثيرات بصرية للنموذج
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>