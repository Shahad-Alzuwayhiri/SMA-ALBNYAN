<?php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// التحقق من صلاحية الموظف
$auth->requireAuth();
$user = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$contractId = $input['contract_id'] ?? null;

if (!$contractId) {
    echo json_encode(['success' => false, 'message' => 'معرف العقد مطلوب']);
    exit;
}

try {
    // التحقق من أن العقد ملك للمستخدم الحالي وفي حالة مسودة
    $stmt = $pdo->prepare("
        SELECT * FROM contracts 
        WHERE id = ? AND created_by = ? AND status = 'draft'
    ");
    $stmt->execute([$contractId, $user['id']]);
    $contract = $stmt->fetch();
    
    if (!$contract) {
        echo json_encode(['success' => false, 'message' => 'العقد غير موجود أو لا يمكن إرساله للمراجعة']);
        exit;
    }
    
    // تحديث حالة العقد إلى "قيد المراجعة"
    $updateStmt = $pdo->prepare("
        UPDATE contracts 
        SET status = 'pending_review', updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $updateStmt->execute([$contractId]);
    
    // إنشاء إشعار للمدير
    $managerStmt = $pdo->prepare("SELECT id FROM users WHERE role IN ('manager', 'admin') AND status = 'active'");
    $managerStmt->execute();
    $managers = $managerStmt->fetchAll();
    
    foreach ($managers as $manager) {
        $auth->createNotification(
            $manager['id'],
            'عقد جديد للمراجعة',
            "تم إرسال عقد جديد رقم {$contract['contract_number']} من الموظف {$user['name']} للمراجعة",
            'contract_created',
            $contractId
        );
    }
    
    // تسجيل النشاط
    $logStmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, description, related_contract_id) 
        VALUES (?, 'submit_for_review', ?, ?)
    ");
    $logStmt->execute([
        $user['id'], 
        "إرسال العقد رقم {$contract['contract_number']} للمراجعة", 
        $contractId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'تم إرسال العقد للمراجعة بنجاح']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>