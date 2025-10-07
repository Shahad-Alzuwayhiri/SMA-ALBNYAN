<?php
require_once '../includes/auth.php';

$error = '';
$success = '';

// إذا كان المستخدم مسجل دخوله بالفعل
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    
    // توجيه حسب الدور
    if ($user['role'] === 'admin' || $user['role'] === 'manager') {
        header('Location: /manager_dashboard.php');
    } else {
        header('Location: /employee_dashboard.php');
    }
    exit;
}

// معالجة تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور';
    } else {
        if ($auth->login($email, $password)) {
            $user = $auth->getCurrentUser();
            
            // توجيه حسب الدور
            if ($user['role'] === 'admin' || $user['role'] === 'manager') {
                header('Location: /manager_dashboard.php');
            } else {
                header('Location: /employee_dashboard.php');
            }
            exit;
        } else {
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        }
    }
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
    <link href="/static/css/modern-theme.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #253355 0%, #77bcc3 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 100%;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-section h1 {
            color: #253355;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .logo-section p {
            color: #77bcc3;
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid #e8eaec;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #77bcc3;
            box-shadow: 0 0 0 0.2rem rgba(119, 188, 195, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #253355 0%, #77bcc3 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 51, 85, 0.3);
            color: white;
        }
        
        .demo-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .demo-account {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #77bcc3;
        }
        
        .demo-account:last-child {
            margin-bottom: 0;
        }
        
        .btn-demo {
            background: #77bcc3;
            border: none;
            border-radius: 6px;
            color: white;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            margin-right: 0.5rem;
        }
        
        .btn-demo:hover {
            background: #253355;
            color: white;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <h1><i class="fas fa-file-contract me-2"></i>سما البنيان</h1>
                <p>نظام إدارة العقود</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="أدخل بريدك الإلكتروني" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="أدخل كلمة المرور" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>تسجيل الدخول
                </button>
            </form>
            
            <div class="demo-section">
                <h6 class="text-center mb-3">
                    <i class="fas fa-info-circle me-2"></i>حسابات تجريبية
                </h6>
                
                <div class="demo-account">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>مدير النظام:</strong> admin@sama.com<br>
                            <small class="text-muted">كلمة المرور: 123456</small>
                        </div>
                        <button type="button" class="btn-demo" onclick="fillDemo('admin@sama.com', '123456')">
                            تجربة
                        </button>
                    </div>
                </div>
                
                <div class="demo-account">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>المدير العام:</strong> manager@sama.com<br>
                            <small class="text-muted">كلمة المرور: 123456</small>
                        </div>
                        <button type="button" class="btn-demo" onclick="fillDemo('manager@sama.com', '123456')">
                            تجربة
                        </button>
                    </div>
                </div>
                
                <div class="demo-account">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>موظف العقود:</strong> employee@sama.com<br>
                            <small class="text-muted">كلمة المرور: 123456</small>
                        </div>
                        <button type="button" class="btn-demo" onclick="fillDemo('employee@sama.com', '123456')">
                            تجربة
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function fillDemo(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>