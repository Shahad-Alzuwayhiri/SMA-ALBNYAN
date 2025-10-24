<?php
// Login page using master layout (auth pattern)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$title = 'تسجيل الدخول';
$is_auth_page = true;
$show_sidebar = false;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    try {
        $user = $auth->login($email, $password);
        header('Location: ' . asset('employee_dashboard.php'));
        exit;
    } catch (Exception $e) {
        $error = 'بيانات الدخول غير صحيحة.';
    }
}

ob_start();
?>
<div class="auth-card glass-card p-4 mx-auto" style="max-width:400px;">
    <h2 class="mb-4 text-center">تسجيل الدخول</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger text-center"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="mb-3">
            <label for="email" class="form-label">البريد الإلكتروني</label>
            <input type="email" class="form-control" id="email" name="email" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">كلمة المرور</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">دخول</button>
    </form>
    <div class="mt-3 text-center">
        <a href="<?= asset('forgot_password.php') ?>">نسيت كلمة المرور؟</a>
    </div>
    <div class="login-link text-center mt-4">
        <p>ليس لديك حساب؟ <a href="<?= asset('signup.php') ?>">إنشاء حساب جديد</a></p>
    </div>
</div>
<?php
$content = ob_get_clean();
$additional_scripts = '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
include __DIR__ . '/../templates/master_layout.php';
