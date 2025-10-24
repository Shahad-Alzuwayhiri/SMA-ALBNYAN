<?php
session_start();

// تسجيل دخول تجريبي سريع للاختبار
$_SESSION['user_id'] = 2; // Manager ID
$_SESSION['user_name'] = 'مدير العقود';
$_SESSION['user_email'] = 'manager@sama.com';
$_SESSION['user_role'] = 'manager';

echo "<p>Logged in as Manager for testing. <a href='/manager_dashboard.php'>Go to Manager Dashboard</a></p>";
echo "<p><a href='/employee_dashboard.php'>Go to Employee Dashboard</a></p>";
echo "<p><a href='/logout.php'>Logout</a></p>";
?>