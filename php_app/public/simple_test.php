<?php
session_start();

echo "<!DOCTYPE html>";
echo "<html dir='rtl' lang='ar'>";
echo "<head><meta charset='UTF-8'><title>اختبار الخادم</title></head>";
echo "<body>";
echo "<h1>✅ الخادم يعمل بشكل صحيح!</h1>";
echo "<p>الوقت الحالي: " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='/login'>اذهب لصفحة تسجيل الدخول</a></p>";
echo "<p><a href='/dashboard'>اذهب للداشبورد</a></p>";
echo "<p><a href='/contracts'>اذهب للعقود</a></p>";
echo "</body></html>";
?>