<?php
require_once '../includes/auth.php';

header('Content-Type: application/json');

// التحقق من صلاحية المدير
$auth->requireAuth();
$user = $auth->getCurrentUser();

if (!in_array($user['role'], ['manager', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مسموح لك بهذا الإجراء']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$contractId = $input['contract_id'] ?? null;
$action = $input['action'] ?? null;
$notes = $input['notes'] ?? '';

if (!$contractId || !$action) {
    echo json_encode(['success' => false, 'message' => 'معرف العقد والإجراء مطلوبان']);
    exit;
}

if (!in_array($action, ['approve', 'reject', 'sign'])) {
    echo json_encode(['success' => false, 'message' => 'إجراء غير صحيح']);
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
    
    // تحديد الحالة الجديدة والشروط
    $newStatus = '';
    $requiredStatus = '';
    $actionMessage = '';
    $notificationTitle = '';
    $notificationMessage = '';
    
    switch ($action) {
        case 'approve':
            if ($contract['status'] !== 'pending_review') {
                echo json_encode(['success' => false, 'message' => 'يمكن الموافقة على العقود قيد المراجعة فقط']);
                exit;
            }
            $newStatus = 'approved';
            $actionMessage = 'تمت الموافقة على العقد';
            $notificationTitle = 'تمت الموافقة على العقد';
            $notificationMessage = "تمت الموافقة على العقد رقم {$contract['contract_number']} من قبل {$user['name']}";
            break;
            
        case 'reject':
            if ($contract['status'] !== 'pending_review') {
                echo json_encode(['success' => false, 'message' => 'يمكن رفض العقود قيد المراجعة فقط']);
                exit;
            }
            $newStatus = 'rejected';
            $actionMessage = 'تم رفض العقد';
            $notificationTitle = 'تم رفض العقد';
            $notificationMessage = "تم رفض العقد رقم {$contract['contract_number']} من قبل {$user['name']}";
            if ($notes) {
                $notificationMessage .= "\nالأسباب: " . $notes;
            }
            break;
            
        case 'sign':
            if ($contract['status'] !== 'approved') {
                echo json_encode(['success' => false, 'message' => 'يمكن توقيع العقود الموافق عليها فقط']);
                exit;
            }
            $newStatus = 'signed';
            $actionMessage = 'تم توقيع العقد';
            $notificationTitle = 'تم توقيع العقد';
            $notificationMessage = "تم توقيع العقد رقم {$contract['contract_number']} من قبل {$user['name']}";
            break;
    }
    
    // تحديث العقد
    $updateQuery = "
        UPDATE contracts 
        SET status = ?, 
            approved_by = ?, 
            manager_notes = ?, 
            approval_date = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP";
    
    $updateParams = [$newStatus, $user['id'], $notes];
    
    // إضافة تاريخ التوقيع إذا كان الإجراء توقيع
    if ($action === 'sign') {
        $updateQuery .= ", signed_date = CURRENT_TIMESTAMP";
    }
    
    $updateQuery .= " WHERE id = ?";
    $updateParams[] = $contractId;
    
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute($updateParams);
    
    // إنشاء إشعار للموظف الذي أنشأ العقد
    if ($contract['created_by']) {
        $auth->createNotification(
            $contract['created_by'],
            $notificationTitle,
            $notificationMessage,
            'contract_' . $action,
            $contractId
        );
    }
    
    // تسجيل النشاط
    $activityDescription = "{$actionMessage} رقم {$contract['contract_number']}";
    if ($notes) {
        $activityDescription .= " - الملاحظات: " . $notes;
    }
    
    $logStmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, description, related_contract_id) 
        VALUES (?, ?, ?, ?)
    ");
    $logStmt->execute([
        $user['id'], 
        'contract_' . $action,
        $activityDescription, 
        $contractId
    ]);
    
    echo json_encode(['success' => true, 'message' => $actionMessage . ' بنجاح']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>