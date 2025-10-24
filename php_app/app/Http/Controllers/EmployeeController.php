<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Contract.php';
require_once __DIR__ . '/../models/Notification.php';

class EmployeeController
{
    private $userModel;
    private $contractModel;
    private $notificationModel;
    
    public function __construct()
    {
        $this->userModel = new User();
        $this->contractModel = new Contract();
        $this->notificationModel = new Notification();
    }
    
    // الحصول على جميع الموظفين (للمدير فقط)
    public function getAllEmployees()
    {
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك بعرض بيانات الموظفين']);
            return;
        }
        
        $employees = $this->userModel->getAllEmployees();
        echo json_encode($employees);
    }
    
    // إضافة موظف جديد (للمدير فقط)
    public function addEmployee()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'طريقة غير مسموحة']);
            return;
        }
        
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك بإضافة موظفين']);
            return;
        }
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'password' => $_POST['password'] ?? '',
            'role' => 'employee'
        ];
        
        // التحقق من البيانات المطلوبة
        $errors = [];
        if (empty($data['name'])) $errors[] = 'الاسم الكامل مطلوب';
        if (empty($data['email'])) $errors[] = 'البريد الإلكتروني مطلوب';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
        if (empty($data['password'])) $errors[] = 'كلمة المرور مطلوبة';
        if (strlen($data['password']) < 6) $errors[] = 'كلمة المرور يجب أن تحتوي على 6 أحرف على الأقل';
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['error' => implode(', ', $errors)]);
            return;
        }
        
        // التحقق من عدم وجود البريد الإلكتروني مسبقاً
        $existingUser = $this->userModel->findByEmail($data['email']);
        if ($existingUser) {
            http_response_code(400);
            echo json_encode(['error' => 'البريد الإلكتروني مستخدم بالفعل']);
            return;
        }
        
        try {
            $result = $this->userModel->create($data);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'تم إضافة الموظف بنجاح']);
            } else {
                throw new Exception('فشل في إضافة الموظف');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    // تحديث صلاحيات الموظف
    public function updatePermissions($userId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'طريقة غير مسموحة']);
            return;
        }
        
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح লক بتحديث الصلاحيات']);
            return;
        }
        
        $user = $this->userModel->findById($userId);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'الموظف غير موجود']);
            return;
        }
        
        if ($user['role'] !== 'employee') {
            http_response_code(400);
            echo json_encode(['error' => 'يمكن تحديث صلاحيات الموظفين فقط']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = $_POST;
        }
        
        $permissions = $input['permissions'] ?? [];
        
        // التحقق من صحة الصلاحيات
        $allowedPermissions = [
            'create_contracts',
            'edit_own_contracts',
            'upload_files',
            'view_own_contracts',
            'submit_for_review',
            'view_all_contracts', // صلاحية خاصة
            'edit_any_contract'   // صلاحية خاصة
        ];
        
        $validPermissions = [];
        foreach ($permissions as $permission => $value) {
            if (in_array($permission, $allowedPermissions)) {
                $validPermissions[$permission] = (bool)$value;
            }
        }
        
        try {
            $result = $this->userModel->updatePermissions($userId, $validPermissions);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'تم تحديث الصلاحيات بنجاح']);
            } else {
                throw new Exception('فشل في تحديث الصلاحيات');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    // إلغاء تفعيل الموظف
    public function deactivateEmployee($userId)
    {
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك بإلغاء تفعيل الموظفين']);
            return;
        }
        
        $user = $this->userModel->findById($userId);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'الموظف غير موجود']);
            return;
        }
        
        if ($user['role'] !== 'employee') {
            http_response_code(400);
            echo json_encode(['error' => 'يمكن إلغاء تفعيل الموظفين فقط']);
            return;
        }
        
        try {
            $result = $this->userModel->deactivate($userId);
            if ($result) {
                // إشعار الموظف بإلغاء التفعيل
                $this->notificationModel->create([
                    'user_id' => $userId,
                    'type' => 'account_deactivated',
                    'title' => 'تم إلغاء تفعيل حسابك',
                    'message' => 'تم إلغاء تفعيل حسابك من قبل المدير. تواصل مع الإدارة للمزيد من المعلومات.'
                ]);
                
                echo json_encode(['success' => true, 'message' => 'تم إلغاء تفعيل الموظف بنجاح']);
            } else {
                throw new Exception('فشل في إلغاء تفعيل الموظف');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    // تفعيل الموظف
    public function activateEmployee($userId)
    {
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك بتفعيل الموظفين']);
            return;
        }
        
        try {
            $result = $this->userModel->activate($userId);
            if ($result) {
                // إشعار الموظف بإعادة التفعيل
                $this->notificationModel->create([
                    'user_id' => $userId,
                    'type' => 'account_activated',
                    'title' => 'تم تفعيل حسابك',
                    'message' => 'تم إعادة تفعيل حسابك. يمكنك الآن الوصول إلى النظام.'
                ]);
                
                echo json_encode(['success' => true, 'message' => 'تم تفعيل الموظف بنجاح']);
            } else {
                throw new Exception('فشل في تفعيل الموظف');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    // الحصول على تفاصيل الموظف
    public function getEmployeeDetails($userId)
    {
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك بعرض تفاصيل الموظفين']);
            return;
        }
        
        $user = $this->userModel->findById($userId);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'الموظف غير موجود']);
            return;
        }
        
        // الحصول على إحصائيات الموظف
        $stats = $this->userModel->getUserStats($userId);
        $user['stats'] = $stats;
        
        // الحصول على آخر 10 عقود للموظف
        $contracts = $this->contractModel->getForEmployee($userId);
        $user['recent_contracts'] = array_slice($contracts, 0, 10);
        
        // إزالة كلمة المرور من الاستجابة
        unset($user['password']);
        
        echo json_encode($user);
    }
    
    // الحصول على أداء الموظفين
    public function getEmployeesPerformance()
    {
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك بعرض أداء الموظفين']);
            return;
        }
        
        $employees = $this->userModel->getAllEmployees();
        
        foreach ($employees as &$employee) {
            $stats = $this->userModel->getUserStats($employee['id']);
            $employee['performance'] = $stats;
            
            // حساب معدل النجاح
            if ($stats['total_contracts'] > 0) {
                $employee['success_rate'] = ($stats['signed_contracts'] / $stats['total_contracts']) * 100;
            } else {
                $employee['success_rate'] = 0;
            }
        }
        
        echo json_encode($employees);
    }
    
    // تصدير بيانات الموظفين (CSV)
    public function exportEmployees()
    {
        if (!$this->isManager()) {
            http_response_code(403);
            echo "غير مصرح لك بتصدير بيانات الموظفين";
            return;
        }
        
        $employees = $this->userModel->getAllEmployees();
        
        // إعداد headers للتحميل
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="employees_' . date('Y-m-d') . '.csv"');
        
        // إنشاء CSV
        $output = fopen('php://output', 'w');
        
        // إضافة BOM لدعم UTF-8 في Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // العناوين
        fputcsv($output, [
            'الاسم',
            'البريد الإلكتروني',
            'الهاتف',
            'عدد العقود',
            'آخر نشاط',
            'الحالة',
            'تاريخ الإنشاء'
        ]);
        
        // البيانات
        foreach ($employees as $employee) {
            fputcsv($output, [
                $employee['name'],
                $employee['email'],
                $employee['phone'],
                $employee['total_contracts'],
                $employee['last_activity'] ? date('Y-m-d', strtotime($employee['last_activity'])) : 'لا يوجد',
                $employee['is_active'] ? 'نشط' : 'غير نشط',
                date('Y-m-d', strtotime($employee['created_at']))
            ]);
        }
        
        fclose($output);
    }
    
    // إرسال إشعار جماعي للموظفين
    public function sendBulkNotification()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'طريقة غير مسموحة']);
            return;
        }
        
        if (!$this->isManager()) {
            http_response_code(403);
            echo json_encode(['error' => 'غير مصرح لك بإرسال الإشعارات']);
            return;
        }
        
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $employeeIds = $_POST['employee_ids'] ?? [];
        
        if (empty($title) || empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'العنوان والرسالة مطلوبان']);
            return;
        }
        
        try {
            $count = 0;
            
            if (empty($employeeIds)) {
                // إرسال لجميع الموظفين
                $employees = $this->userModel->getAllEmployees();
                foreach ($employees as $employee) {
                    $this->notificationModel->create([
                        'user_id' => $employee['id'],
                        'type' => 'general_notification',
                        'title' => $title,
                        'message' => $message
                    ]);
                    $count++;
                }
            } else {
                // إرسال لموظفين محددين
                foreach ($employeeIds as $employeeId) {
                    $this->notificationModel->create([
                        'user_id' => $employeeId,
                        'type' => 'general_notification',
                        'title' => $title,
                        'message' => $message
                    ]);
                    $count++;
                }
            }
            
            echo json_encode(['success' => true, 'message' => "تم إرسال الإشعار إلى {$count} موظف"]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    // عرض واجهة إدارة الموظفين
    public function showEmployeesManagement()
    {
        if (!$this->isManager()) {
            http_response_code(403);
            return "غير مصرح لك بإدارة الموظفين";
        }
        
        $employees = $this->userModel->getAllEmployees();
        
        ob_start();
        include __DIR__ . '/../templates/employees_management.php';
        return ob_get_clean();
    }
    
    // التحقق من كون المستخدم مدير
    private function isManager()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager';
    }
}