<?php
require_once '../includes/auth.php';

// تسجيل الخروج
$auth->logout();

// إعادة التوجيه لصفحة تسجيل الدخول
header('Location: /login.php');
exit;
?>