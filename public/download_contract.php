<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/SimplePdfService.php';

$auth->requireAuth();
$user = $auth->getCurrentUser();

// التحقق من وجود معرف العقد
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    die('معرف العقد غير صحيح');
}

$contract_id = (int)$_GET['id'];

try {
    // جلب بيانات العقد
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as creator_name 
        FROM contracts c 
        LEFT JOIN users u ON c.created_by = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$contract_id]);
    $contract = $stmt->fetch();
    
    if (!$contract) {
        header('HTTP/1.0 404 Not Found');
        die('العقد غير موجود');
    }
    
    // التحقق من صلاحية الوصول
    $canAccess = false;
    if ($user['role'] === 'admin') {
        $canAccess = true;
    } elseif ($user['role'] === 'manager') {
        $canAccess = true;
    } elseif ($user['role'] === 'employee' && $contract['created_by'] == $user['id']) {
        $canAccess = true;
    }
    
    if (!$canAccess) {
        header('HTTP/1.0 403 Forbidden');
        die('غير مسموح لك بتحميل هذا العقد');
    }
    
} catch (PDOException $e) {
    header('HTTP/1.0 500 Internal Server Error');
    die('خطأ في قاعدة البيانات: ' . $e->getMessage());
}

// Generate HTML content for PDF
$pdfService = new SimplePdfService();
$htmlContent = $pdfService->generateContractHtml($contract);

// Set up filename
$filename = "contract_" . $contract['id'] . "_" . date('Y-m-d') . ".html";

// Instead of forcing PDF download, we'll serve an HTML page that can be printed to PDF
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $htmlContent;

// The generateContractPDF function has been replaced by SimplePdfService

// تسجيل عملية التحميل في السجل
try {
    $logStmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, details, created_at) 
        VALUES (?, 'contract_download', ?, datetime('now'))
    ");
    $logStmt->execute([
        $user['id'], 
        "تم تحميل العقد رقم: {$contract['id']} - {$contract['title']}"
    ]);
} catch (PDOException $e) {
    // تجاهل أخطاء السجل
}
?>