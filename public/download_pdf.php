<?php
require_once '../includes/auth.php';

$auth->requireAuth();
$user = $auth->getCurrentUser();

$file_id = $_GET['id'] ?? null;
if (!$file_id) {
    die('معرف الملف مطلوب');
}

try {
    // جلب بيانات الملف
    $stmt = $pdo->prepare("
        SELECT f.*, c.contract_number, c.client_name, u.name as uploaded_by_name
        FROM files f
        LEFT JOIN contracts c ON f.contract_id = c.id
        LEFT JOIN users u ON f.uploaded_by = u.id
        WHERE f.id = ?
    ");
    $stmt->execute([$file_id]);
    $file = $stmt->fetch();

    if (!$file) {
        die('الملف غير موجود');
    }

    // التحقق من الصلاحية
    if ($user['role'] === 'employee') {
        // الموظف يرى ملفات عقوده فقط
        $stmt = $pdo->prepare("
            SELECT c.created_by 
            FROM contracts c 
            JOIN files f ON c.id = f.contract_id 
            WHERE f.id = ?
        ");
        $stmt->execute([$file_id]);
        $contract = $stmt->fetch();
        
        if (!$contract || $contract['created_by'] != $user['id']) {
            die('غير مسموح لك بتحميل هذا الملف');
        }
    }

    // إعداد الـ headers للتحميل
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
    header('Content-Length: ' . strlen(base64_decode($file['file_data'])));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // فك تشفير base64 وإرسال الملف للتحميل
    echo base64_decode($file['file_data']);
    exit;

} catch (PDOException $e) {
    die('خطأ في تحميل الملف: ' . $e->getMessage());
}
?>