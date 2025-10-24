<?php
// Reset password page using master layout (auth pattern)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$title = 'إعادة تعيين كلمة المرور';
$is_auth_page = true;
$show_sidebar = false;

$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if ($password && $confirm && $password === $confirm) {
        // TODO: Implement password update logic
        $success = 'تم تعيين كلمة المرور الجديدة بنجاح.';
    } else {
        $error = 'يرجى إدخال كلمة مرور متطابقة.';
    }
}

ob_start();
?>
<div class="auth-card glass-card p-4 mx-auto" style="max-width:400px;">
    <h2 class="mb-4 text-center">إعادة تعيين كلمة المرور</h2>
    <?php if ($success): ?>
        <div class="alert alert-success text-center"> <?= htmlspecialchars($success) ?> </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger text-center"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="mb-3">
            <label for="password" class="form-label">كلمة المرور الجديدة</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="confirm" class="form-label">تأكيد كلمة المرور</label>
            <input type="password" class="form-control" id="confirm" name="confirm" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">تعيين كلمة المرور</button>
    </form>
    <div class="mt-3 text-center">
        <a href="<?= asset('login.php') ?>">العودة لتسجيل الدخول</a>
    </div>
</div>
<?php
$content = ob_get_clean();
$additional_scripts = '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
include __DIR__ . '/../templates/master_layout.php';
