<?php
require_once '../includes/auth.php';

// Handle login if form submitted
if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($email, $password)) {
        $user = $auth->getCurrentUser();
        echo "<div class='alert alert-success'>✅ تم تسجيل الدخول بنجاح! مرحباً " . htmlspecialchars($user['name']) . "</div>";
        
        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: /manager_dashboard.php");
        } elseif ($user['role'] === 'manager') {
            header("Location: /manager_dashboard.php");
        } else {
            header("Location: /employee_dashboard.php");
        }
        exit;
    } else {
        echo "<div class='alert alert-danger'>❌ خطأ في البريد الإلكتروني أو كلمة المرور</div>";
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار تسجيل الدخول - نظام إدارة العقود</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .login-container { max-width: 500px; margin: 50px auto; }
        .card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .test-users { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header bg-primary text-white text-center">
                    <h3>🔐 اختبار تسجيل الدخول</h3>
                </div>
                <div class="card-body">
                    
                    <!-- Test Users Info -->
                    <div class="test-users">
                        <h5>👥 مستخدمين للاختبار:</h5>
                        <p><strong>مدير:</strong> admin@sama.com / 123456</p>
                        <p><strong>مدير:</strong> manager@sama.com / 123456</p>
                        <p><strong>موظف:</strong> employee@sama.com / 123456</p>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" value="employee@sama.com" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور</label>
                            <input type="password" name="password" class="form-control" value="123456" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">تسجيل الدخول</button>
                    </form>
                    
                    <hr>
                    <div class="text-center">
                        <h6>🔗 روابط سريعة:</h6>
                        <a href="/employee_dashboard.php" class="btn btn-outline-success btn-sm">لوحة الموظف</a>
                        <a href="/manager_dashboard.php" class="btn btn-outline-info btn-sm">لوحة المدير</a>
                        <a href="/contracts_list.php" class="btn btn-outline-warning btn-sm">قائمة العقود</a>
                        <a href="/contracts_create.php" class="btn btn-outline-primary btn-sm">إنشاء عقد</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>