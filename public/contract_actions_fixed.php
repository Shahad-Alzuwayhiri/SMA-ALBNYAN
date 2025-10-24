<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// التحقق من تسجيل الدخول والصلاحية
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

$user = $auth->getCurrentUser();

if (!in_array($user['role'], ['manager', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مسموح لك بهذا الإجراء']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مسموحة']);
    exit;
}

$action = $_POST['action'] ?? '';
$contractId = $_POST['contract_id'] ?? null;

if (!$contractId || !$action) {
    echo json_encode(['success' => false, 'message' => 'معرف العقد والإجراء مطلوبان']);
    exit;
}

try {
    // التحقق من وجود العقد
    $stmt = $pdo->prepare("SELECT * FROM contracts WHERE id = ?");
    $stmt->execute([$contractId]);
    $contract = $stmt->fetch();
    
    if (!$contract) {
        echo json_encode(['success' => false, 'message' => 'العقد غير موجود']);
        exit;
    }
    
    $newStatus = '';
    $message = '';
    $notificationTitle = '';
    $notificationMessage = '';
    
    switch ($action) {
        case 'approve':
            if ($contract['status'] !== 'draft') {
                echo json_encode(['success' => false, 'message' => 'لا يمكن الموافقة على عقد غير مسودة']);
                exit;
            }
            $newStatus = 'active';
            $message = 'تم اعتماد العقد بنجاح';
            $notificationTitle = 'تم اعتماد العقد';
            $notificationMessage = "تم اعتماد العقد رقم {$contract['contract_number']} من قبل المدير";
            break;
            
        case 'reject':
            if ($contract['status'] !== 'draft') {
                echo json_encode(['success' => false, 'message' => 'لا يمكن رفض عقد غير مسودة']);
                exit;
            }
            $newStatus = 'cancelled';
            $message = 'تم رفض العقد';
            $notificationTitle = 'تم رفض العقد';
            $notificationMessage = "تم رفض العقد رقم {$contract['contract_number']} من قبل المدير";
            break;
            
        case 'sign':
            if ($contract['status'] !== 'active') {
                echo json_encode(['success' => false, 'message' => 'لا يمكن توقيع عقد غير معتمد']);
                exit;
            }
            $newStatus = 'completed';
            $message = 'تم توقيع العقد بنجاح';
            $notificationTitle = 'تم توقيع العقد';
            $notificationMessage = "تم توقيع العقد رقم {$contract['contract_number']} وأصبح ساري المفعول";
            break;
            
        case 'cancel':
            if (!in_array($contract['status'], ['draft', 'active'])) {
                echo json_encode(['success' => false, 'message' => 'لا يمكن إلغاء هذا العقد']);
                exit;
            }
            $newStatus = 'cancelled';
            $message = 'تم إلغاء العقد';
            $notificationTitle = 'تم إلغاء العقد';
            $notificationMessage = "تم إلغاء العقد رقم {$contract['contract_number']}";
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'إجراء غير صحيح']);
            exit;
    }
    
    // تحديث حالة العقد
    $stmt = $pdo->prepare("
        UPDATE contracts 
        SET status = ?, reviewed_by = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $updateResult = $stmt->execute([$newStatus, $user['id'], $contractId]);
    
    if (!$updateResult) {
        echo json_encode(['success' => false, 'message' => 'فشل في تحديث العقد']);
        exit;
    }
    
    // تسجيل النشاط
    $auth->logActivity($user['id'], $action . '_contract', $message . " - العقد رقم: {$contract['contract_number']}", $contractId);
    
    // إضافة إشعار للموظف المنشئ
    if ($contract['created_by'] && $contract['created_by'] != $user['id']) {
        $auth->createNotification(
            $contract['created_by'],
            $notificationTitle,
            $notificationMessage,
            'info',
            $contractId
        );
    }
    
    // إضافة سجل في تاريخ العقد
    $stmt = $pdo->prepare("
        INSERT INTO contract_history (contract_id, action, performed_by, notes, created_at) 
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$contractId, $action, $user['id'], $message]);
    
    // Note: Status functions now available via autoloaded App\Helpers\Functions class
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'new_status' => $newStatus,
        'status_text' => getStatusText($newStatus),
        'status_class' => getStatusClass($newStatus)
    ]);
    
} catch (Exception $e) {
    error_log("Contract action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام: ' . $e->getMessage()]);
}
?>