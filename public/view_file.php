<?php
/**
 * عرض ملف PDF - نظام سما البنيان
 * صفحة لعرض ملفات PDF في المتصفح
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/FileManager.php';

// التحقق من المصادقة
$auth->requireAuth();

// التحقق من معرف الملف
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    die('معرف الملف غير صحيح');
}

$fileId = (int)$_GET['id'];
$fileManager = new FileManager($pdo);

// عرض الملف
$fileManager->viewFile($fileId);
?>