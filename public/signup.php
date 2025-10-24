<?php
require_once __DIR__ . '/../includes/auth.php';

$error = $error ?? '';
$success = $success ?? '';
$errors = $errors ?? [];

// إذا كان المستخدم مسجل دخوله بالفعل
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    
    // توجيه حسب الدور
    if ($user['role'] === 'admin' || $user['role'] === 'manager') {
        header('Location: ' . asset('manager_dashboard.php'));
    } else {
        header('Location: ' . asset('employee_dashboard.php'));
    }
    exit;
}

// معالجة إنشاء الحساب (keep existing logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = 'employee'; // افتراضياً يتم إنشاء حساب موظف
    
    // التحقق من البيانات
    if (empty($name)) {
        $errors[] = 'يرجى إدخال الاسم الكامل';
    }
    
    if (empty($email)) {
        $errors[] = 'يرجى إدخال البريد الإلكتروني';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }
    
    if (empty($phone)) {
        $errors[] = 'يرجى إدخال رقم الهاتف';
    }
    
    if (empty($password)) {
        $errors[] = 'يرجى إدخال كلمة المرور';
    } elseif (strlen($password) < 6) {
        $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'كلمة المرور وتأكيد كلمة المرور غير متطابقتان';
    }
    
    // التحقق من عدم وجود المستخدم مسبقاً
    if (empty($errors)) {
        if ($auth->userExists($email)) {
            $errors[] = 'البريد الإلكتروني مستخدم من قبل';
        }
    }
    
    // إنشاء الحساب
    if (empty($errors)) {
        $userId = $auth->createUser($name, $email, $phone, $password, $role);
        
        if ($userId) {
            $success = 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول';
            
            // تسجيل النشاط
            $auth->logActivity($userId, 'account_created', 'تم إنشاء حساب جديد للمستخدم: ' . $name);
            
            // إرسال إشعار لمدير النظام
            $admin = $auth->getUserByRole('admin');
            
            if ($admin) {
                $auth->createNotification(
                    $admin['id'],
                    'حساب جديد',
                    'تم إنشاء حساب جديد للمستخدم: ' . $name,
                    'info'
                );
            }
            
            // إعادة تعيين النموذج
            $name = $email = $phone = '';
        } else {
            $error = 'حدث خطأ أثناء إنشاء الحساب';
        }
    }
}

// Page metadata for master layout
$title = "إنشاء حساب جديد - نظام إدارة العقود";
$is_auth_page = true;
$show_sidebar = false;

ob_start();
?>

<div class="auth-card">
    <div class="auth-logo text-center mb-4">
        <h1><i class="fas fa-user-plus me-2"></i>سما البنيان</h1>
        <p>إنشاء حساب جديد</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" id="signupForm">
        <div class="mb-3">
            <label for="name" class="form-label">الاسم الكامل</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" class="form-control" id="name" name="name" 
                       placeholder="أدخل اسمك الكامل" value="<?= htmlspecialchars($name ?? '') ?>" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">البريد الإلكتروني</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="أدخل بريدك الإلكتروني" value="<?= htmlspecialchars($email ?? '') ?>" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">رقم الهاتف</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                <input type="tel" class="form-control" id="phone" name="phone" 
                       placeholder="أدخل رقم هاتفك" value="<?= htmlspecialchars($phone ?? '') ?>" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">كلمة المرور</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="أدخل كلمة المرور (6 أحرف على الأقل)" required>
            </div>
        </div>

        <div class="mb-4">
            <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                       placeholder="أعد إدخال كلمة المرور" required>
            </div>
        </div>

        <button type="submit" class="btn btn-signup w-100">
            <i class="fas fa-user-plus me-2"></i>إنشاء الحساب
        </button>
    </form>

    <div class="login-link text-center mt-4">
        <p>لديك حساب بالفعل؟ <a href="<?= asset('login.php') ?>">تسجيل الدخول</a></p>
    </div>
</div>

<?php
$content = ob_get_clean();

$additional_scripts = '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>' . "\n" .
                      "<script>document.getElementById('confirm_password')?.addEventListener('input', function(){const p=document.getElementById('password').value;this.setCustomValidity(p!==this.value? 'كلمة المرور غير متطابقة':'' );});document.getElementById('signupForm')?.addEventListener('submit', function(){const btn=document.querySelector('.btn-signup'); if(btn){btn.innerHTML='<i class=\\\"fas fa-spinner fa-spin me-2\\\"></i>جاري إنشاء الحساب...'; btn.disabled=true;}});</script>";

require_once __DIR__ . '/../templates/master_layout.php';