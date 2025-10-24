<?php
/**
 * تنزيل ملف PDF - نظام سما البنيان
 * صفحة لتنزيل ملفات PDF
 */

require_once '../includes/auth.php';
require_once '../models/FileManager.php';

// التحقق من المصادقة
$auth->requireAuth();

// التحقق من معرف الملف
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    die('معرف الملف غير صحيح');
}

$fileId = (int)$_GET['id'];
$fileManager = new FileManager($pdo);

// تنزيل الملف
$fileManager->downloadFile($fileId);
?>