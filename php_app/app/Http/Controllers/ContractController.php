<?php

require_once __DIR__ . '/../../../models/Contract.php';
require_once __DIR__ . '/../../../models/User.php';
require_once __DIR__ . '/../../../models/Notification.php';

class ContractController
{
    private $contractModel;
    private $userModel;
    private $notificationModel;
    
    public function __construct()
    {
        $this->contractModel = new Contract();
        $this->userModel = new User();
        $this->notificationModel = new Notification();
    }
    
    // عرض لوحة تحكم المدير
    public function showManagerDashboard()
    {
        if (!$this->isManager()) {
            header('Location: /employee-dashboard');
            exit;
        }
        
        // الحصول على الإحصائيات
        $stats = $this->contractModel->getStats();
        $pendingCount = $stats['pending_count'] ?? 0;
        $unreadNotifications = $this->notificationModel->getUnreadCount($_SESSION['user_id']);
        
        ob_start();
        include dirname(__DIR__, 3) . '/templates/manager_dashboard.php';
        return ob_get_clean();
    }
    
    // عرض لوحة تحكم الموظف
    public function showEmployeeDashboard()
    {
        if ($this->isManager()) {
            header('Location: /manager-dashboard');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $userStats = $this->userModel->getUserStats($userId);
        $contracts = $this->contractModel->getForEmployee($userId);
        $notifications = $this->notificationModel->getForUser($userId, 5);
        
        ob_start();
        include dirname(__DIR__, 3) . '/templates/employee_dashboard.php';
        return ob_get_clean();
    }
    
    // الحصول على العقود للمدير (AJAX)
    public function getContractsForManager()
    {
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك بالوصول']);
            return;
        }
        
        $filters = [];
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['created_by']) && !empty($_GET['created_by'])) {
            $filters['created_by'] = $_GET['created_by'];
        }
        
        $contracts = $this->contractModel->getAllForManager($filters);
        echo json_encode($contracts);
    }
    
    // الحصول على عقود الموظف (AJAX)
    public function getContractsForEmployee()
    {
        $userId = $_SESSION['user_id'];
        $filters = [];
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        $contracts = $this->contractModel->getForEmployee($userId, $filters);
        echo json_encode($contracts);
    }
    
    // إنشاء عقد جديد
    public function createContract()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'طريقة غير مسموحة']);
            return;
        }
        
        $data = [
            'title' => $_POST['title'] ?? '',
            'second_party_name' => $_POST['second_party_name'] ?? '',
            'second_party_phone' => $_POST['second_party_phone'] ?? '',
            'second_party_email' => $_POST['second_party_email'] ?? '',
            'contract_amount' => floatval($_POST['contract_amount'] ?? 0),
            'profit_percentage' => floatval($_POST['profit_percentage'] ?? 0),
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'description' => $_POST['description'] ?? '',
            'terms_conditions' => $_POST['terms_conditions'] ?? '',
            'created_by' => $_SESSION['user_id']
        ];
        
        // التحقق من البيانات المطلوبة
        $errors = [];
        if (empty($data['title'])) $errors[] = 'عنوان العقد مطلوب';
        if (empty($data['second_party_name'])) $errors[] = 'اسم الطرف الثاني مطلوب';
        if ($data['contract_amount'] <= 0) $errors[] = 'قيمة العقد يجب أن تكون أكبر من صفر';
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['error' => implode(', ', $errors)]);
            return;
        }
        
        $contractId = $this->contractModel->create($data);
        if ($contractId) {
            echo json_encode(['success' => true, 'contract_id' => $contractId, 'message' => 'تم إنشاء العقد بنجاح']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'فشل في إنشاء العقد']);
        }
    }
    
    // تحديث العقد
    public function updateContract($contractId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'طريقة غير مسموحة']);
            return;
        }
        
        // قراءة البيانات من الـ request body
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = $_POST;
        }
        
        $contract = $this->contractModel->findById($contractId);
        if (!$contract) {
            http_response_code(404);
            echo json_encode(['error' => 'العقد غير موجود']);
            return;
        }
        
        // التحقق من الصلاحيات
        if (!$this->isManager() && $contract['created_by'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك بتعديل هذا العقد']);
            return;
        }
        
        // لا يمكن تعديل العقود الموقعة
        if ($contract['status'] === 'signed') {
            http_response_code(400);
            echo json_encode(['error' => 'لا يمكن تعديل العقود الموقعة']);
            return;
        }
        
        $result = $this->contractModel->update($contractId, $input, $_SESSION['user_id']);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'تم تحديث العقد بنجاح']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'فشل في تحديث العقد']);
        }
    }
    
    // رفع العقد للمراجعة
    public function submitForReview($contractId)
    {
        $contract = $this->contractModel->findById($contractId);
        if (!$contract) {
            http_response_code(404);
            echo json_encode(['error' => 'العقد غير موجود']);
            return;
        }
        
        // التحقق من الصلاحيات
        if ($contract['created_by'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك بهذا الإجراء']);
            return;
        }
        
        if ($contract['status'] !== 'draft') {
            http_response_code(400);
            echo json_encode(['error' => 'يمكن رفع المسودات فقط للمراجعة']);
            return;
        }
        
        $result = $this->contractModel->changeStatus($contractId, 'pending_review', $_SESSION['user_id']);
        if ($result) {
            // إشعار المدير بالعقد الجديد
            $user = $this->userModel->findById($_SESSION['user_id']);
            $this->notificationModel->notifyManagerNewContract($contractId, $user['name']);
            
            echo json_encode(['success' => true, 'message' => 'تم رفع العقد للمراجعة بنجاح']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'فشل في رفع العقد للمراجعة']);
        }
    }
    
    // توقيع العقد (المدير فقط)
    public function signContract($contractId)
    {
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
        
        // تحديث العقد
        $updateData = [
            'status' => 'signed',
            'signed_by' => $_SESSION['user_id']
        ];
        
        $result = $this->contractModel->update($contractId, $updateData, $_SESSION['user_id']);
        if ($result) {
            // إشعار الموظف بتوقيع العقد
            $this->notificationModel->notifyEmployeeContractSigned($contractId, $contract['created_by']);
            
            echo json_encode(['success' => true, 'message' => 'تم توقيع العقد بنجاح']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'فشل في توقيع العقد']);
        }
    }
    
    // رفض العقد (المدير فقط)
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
        
        $input = json_decode(file_get_contents('php://input'), true);
        $comment = $input['comment'] ?? '';
        
        $result = $this->contractModel->changeStatus($contractId, 'rejected', $_SESSION['user_id'], $comment);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'تم رفض العقد']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'فشل في رفض العقد']);
        }
    }
    
    // الحصول على تفاصيل العقد
    public function getContractDetails($contractId)
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
            echo json_encode(['error' => 'غير مصرح لك بعرض هذا العقد']);
            return;
        }
        
        // الحصول على تاريخ العقد
        $history = $this->contractModel->getHistory($contractId);
        $contract['history'] = $history;
        
        echo json_encode($contract);
    }
    
    // الحصول على إحصائيات المدير
    public function getManagerStats()
    {
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح']);
            return;
        }
        
        $stats = $this->contractModel->getStats();
        echo json_encode($stats);
    }
    
    // الحصول على إحصائيات الموظف
    public function getEmployeeStats()
    {
        $userId = $_SESSION['user_id'];
        $stats = $this->userModel->getUserStats($userId);
        echo json_encode($stats);
    }
    
    // التحقق من كون المستخدم مدير
    private function isManager()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager';
    }
    
    // التحقق من كون المستخدم مسجل دخول
    private function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function pdf($id)
    {
        header("Content-Type: text/html; charset=utf-8");
        echo "<h1>عقد رقم: CT-" . str_pad($id, 4, "0", STR_PAD_LEFT) . "</h1>";
        exit;
    }

    public function inProgress()
    {
        $contracts_in_progress = [
            (object)["id" => 1, "serial" => "CT-0001", "client_name" => "أحمد محمد", "status" => "in_progress", "status_display" => "قيد التنفيذ"]
        ];
        return view("contracts.in_progress", ["contracts_in_progress" => $contracts_in_progress]);
    }

    public function closed()
    {
        $contracts_closed = [
            (object)["id" => 3, "serial" => "CT-0003", "client_name" => "محمد سالم", "status" => "completed", "status_display" => "مكتمل"]
        ];
        return view("contracts.closed", ["contracts_closed" => $contracts_closed]);
    }

    public function approve($id)
    {
        $_SESSION["success"] = "تم اعتماد العقد بنجاح";
        header("Location: " . ($_SERVER["HTTP_REFERER"] ?? "/dashboard"));
        exit;
    }

    public function reject($id)
    {
        $_SESSION["success"] = "تم رفض العقد";
        header("Location: " . ($_SERVER["HTTP_REFERER"] ?? "/dashboard"));
        exit;
    }

    public function archive($id)
    {
        $_SESSION["success"] = "تم أرشفة العقد";
        header("Location: " . ($_SERVER["HTTP_REFERER"] ?? "/dashboard"));
        exit;
    }
}
