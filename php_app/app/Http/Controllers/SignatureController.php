<?php

require_once __DIR__ . '/../models/DigitalSignature.php';
require_once __DIR__ . '/../models/Contract.php';
require_once __DIR__ . '/../models/User.php';

class SignatureController
{
    private $signatureModel;
    private $contractModel;
    private $userModel;
    
    public function __construct()
    {
        $this->signatureModel = new DigitalSignature();
        $this->contractModel = new Contract();
        $this->userModel = new User();
    }
    
    // رفع توقيع أو ختم جديد
    public function uploadSignature()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'طريقة غير مسموحة']);
            return;
        }
        
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك برفع التوقيعات']);
            return;
        }
        
        if (!isset($_FILES['signature']) || $_FILES['signature']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'لم يتم رفع ملف صحيح']);
            return;
        }
        
        $signatureType = $_POST['signature_type'] ?? 'electronic_signature';
        $allowedTypes = ['manager_signature', 'company_seal', 'electronic_signature'];
        
        if (!in_array($signatureType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'نوع التوقيع غير صحيح']);
            return;
        }
        
        try {
            $result = $this->signatureModel->uploadSignature($_SESSION['user_id'], $signatureType, $_FILES['signature']);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'تم رفع التوقيع بنجاح']);
            } else {
                throw new Exception('فشل في حفظ التوقيع');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    // الحصول على التوقيعات المحفوظة
    public function getSavedSignatures()
    {
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح']);
            return;
        }
        
        $signatures = $this->signatureModel->getSavedSignatures($_SESSION['user_id']);
        echo json_encode($signatures);
    }
    
    // توقيع العقد
    public function signContract($contractId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'طريقة غير مسموحة']);
            return;
        }
        
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك بتوقيع العقود']);
            return;
        }
        
        $contract = $this->contractModel->findById($contractId);
        if (!$contract) {
            http_response_code(404);
            echo json_encode(['error' => 'العقد غير موجود']);
            return;
        }
        
        if ($contract['status'] !== 'pending_review') {
            http_response_code(400);
            echo json_encode(['error' => 'يمكن توقيع العقود المرفوعة للمراجعة فقط']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $signatureData = $input['signature_data'] ?? '';
        $signatureType = $input['signature_type'] ?? 'electronic_signature';
        
        if (empty($signatureData)) {
            http_response_code(400);
            echo json_encode(['error' => 'بيانات التوقيع مطلوبة']);
            return;
        }
        
        try {
            $result = $this->signatureModel->signContract($contractId, $_SESSION['user_id'], $signatureData, $signatureType);
            
            if ($result) {
                // إنشاء PDF موقع
                $signedPdfPath = $this->signatureModel->generateSignedPDF($contractId);
                
                // إشعار الموظف
                require_once __DIR__ . '/../models/Notification.php';
                $notificationModel = new Notification();
                $notificationModel->notifyEmployeeContractSigned($contractId, $contract['created_by']);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'تم توقيع العقد بنجاح',
                    'signed_pdf' => $signedPdfPath
                ]);
            } else {
                throw new Exception('فشل في توقيع العقد');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    // الحصول على توقيعات العقد
    public function getContractSignatures($contractId)
    {
        $contract = $this->contractModel->findById($contractId);
        if (!$contract) {
            http_response_code(404);
            echo json_encode(['error' => 'العقد غير موجود']);
            return;
        }
        
        // التحقق من الصلاحيات
        if (!$this->isManager() && $contract['created_by'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك بعرض توقيعات هذا العقد']);
            return;
        }
        
        $signatures = $this->signatureModel->getByContract($contractId);
        http_response_code(200);
        echo json_encode($signatures);
        // id, contract_id, user_id, signature_type, signature_data, created_at, verified
        echo json_encode($signatures);
    }
    
    // التحقق من صحة التوقيع
    public function verifySignature($signatureId)
    {
        $verification = $this->signatureModel->verifySignature($signatureId);
        echo json_encode($verification);
    }
    
    // الحصول على العقود بانتظار التوقيع
    public function getPendingSignatures()
    {
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح']);
            return;
        }
        
        $contracts = $this->contractModel->getAllForManager(['status' => 'pending_review']);
        echo json_encode($contracts);
    }
    
    // تحميل الملف الموقع
    public function downloadSignedContract($contractId)
    {
        $contract = $this->contractModel->findById($contractId);
        if (!$contract) {
            http_response_code(404);
            echo "العقد غير موجود";
            return;
        }
        
        // التحقق من الصلاحيات
        if (!$this->isManager() && $contract['created_by'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo "غير مصرح لك بتحميل هذا العقد";
            return;
        }
        
        if ($contract['status'] !== 'signed' || empty($contract['signed_pdf_path'])) {
            http_response_code(400);
            echo "العقد غير موقع أو الملف غير متوفر";
            return;
        }
        
        $filePath = __DIR__ . '/../../storage/signed_contracts/' . $contract['signed_pdf_path'];
        
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "الملف غير موجود";
            return;
        }
        
        // إرسال الملف للتحميل
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $contract['contract_number'] . '_signed.pdf"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
    }
    
    // عرض واجهة التوقيع الإلكتروني
    public function showSignatureInterface($contractId)
    {
        if (!$this->isManager()) {
            http_response_code(403);
            return "غير مصرح لك بتوقيع العقود";
        }
        
        $contract = $this->contractModel->findById($contractId);
        if (!$contract) {
            http_response_code(404);
            return "العقد غير موجود";
        }
        
        if ($contract['status'] !== 'pending_review') {
            return "هذا العقد لا يحتاج إلى توقيع أو تم توقيعه بالفعل";
        }
        
        $savedSignatures = $this->signatureModel->getSavedSignatures($_SESSION['user_id']);
        
        ob_start();
        include __DIR__ . '/../templates/signature_interface.php';
        return ob_get_clean();
    }
    
    // إحصائيات التوقيعات
    public function getSignatureStats()
    {
        $userId = $this->isManager() ? null : $_SESSION['user_id'];
        $stats = $this->signatureModel->getSignatureStats($userId);
        echo json_encode($stats);
    }
    
    // واجهة إدارة التوقيعات (للمدير)
    public function showSignatureManagement()
    {
        if (!$this->isManager()) {
            http_response_code(403);
            return "غير مصرح لك بإدارة التوقيعات";
        }
        
        $savedSignatures = $this->signatureModel->getSavedSignatures($_SESSION['user_id']);
        $pendingContracts = $this->contractModel->getAllForManager(['status' => 'pending_review']);
        
        ob_start();
        include __DIR__ . '/../templates/signature_management.php';
        return ob_get_clean();
    }
    
    // رفض العقد مع تعليق
    public function rejectContract($contractId)
    {
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك برفض العقود']);
            return;
        }
        
        $contract = $this->contractModel->findById($contractId);
        if (!$contract) {
            http_response_code(404);
            echo json_encode(['error' => 'العقد غير موجود']);
            return;
        }
        
        if ($contract['status'] !== 'pending_review') {
            http_response_code(400);
            echo json_encode(['error' => 'يمكن رفض العقود المرفوعة للمراجعة فقط']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $comment = $input['comment'] ?? '';
        
        try {
            $result = $this->contractModel->changeStatus($contractId, 'rejected', $_SESSION['user_id'], $comment);
            
            if ($result) {
                // إشعار الموظف برفض العقد
                require_once __DIR__ . '/../models/Notification.php';
                $notificationModel = new Notification();
                $contractData = $this->contractModel->findById($contractId);
                
                $notificationModel->create([
                    'user_id' => $contract['created_by'],
                    'contract_id' => $contractId,
                    'type' => 'contract_rejected',
                    'title' => 'تم رفض العقد',
                    'message' => "تم رفض العقد رقم {$contractData['contract_number']}. التعليق: {$comment}"
                ]);
                
                echo json_encode(['success' => true, 'message' => 'تم رفض العقد']);
            } else {
                throw new Exception('فشل في رفض العقد');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    // التحقق من كون المستخدم مدير
    private function isManager()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager';
    }
}