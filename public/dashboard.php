<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . asset('login.php'));
    exit;
}
$userRole = $_SESSION['role'] ?? 'employee';
$title = 'لوحة التحكم';
$is_auth_page = false;
$show_sidebar = true;

ob_start();
?>
<div class="container py-5">
    <div class="text-center">
        <h1 class="mb-4">مرحباً بك في لوحة التحكم</h1>
        <p class="lead mb-4">اختر وجهتك حسب دورك في النظام:</p>
        <?php if ($userRole === 'manager'): ?>
            <a href="<?= asset('manager_dashboard.php') ?>" class="btn btn-primary btn-lg mx-2">لوحة المدير</a>
        <?php endif; ?>
        <a href="<?= asset('employee_dashboard.php') ?>" class="btn btn-outline-primary btn-lg mx-2">لوحة الموظف</a>
    </div>
</div>
<?php
$content = ob_get_clean();
$additional_scripts = '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
include __DIR__ . '/../templates/master_layout.php';