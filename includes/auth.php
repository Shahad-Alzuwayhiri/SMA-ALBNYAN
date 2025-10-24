<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إعدادات قاعدة البيانات
$database_path = __DIR__ . '/../database/contracts.db';

try {
    $pdo = new PDO("sqlite:$database_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_phone'] = $user['phone'] ?? '';
            
            // تسجيل النشاط
            $this->logActivity($user['id'], 'login', 'تسجيل دخول المستخدم');
            
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'تسجيل خروج المستخدم');
        }
        
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
            'phone' => $_SESSION['user_phone']
        ];
    }
    
    public function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $role = $_SESSION['user_role'];
        
        // صلاحيات الأدمن
        if ($role === 'admin') {
            return true;
        }
        
        // صلاحيات المدير
        if ($role === 'manager') {
            $managerPermissions = [
                'view_all_contracts',
                'approve_contracts', 
                'reject_contracts',
                'sign_contracts',
                'view_reports',
                'manage_employees'
            ];
            return in_array($permission, $managerPermissions);
        }
        
        // صلاحيات الموظف
        if ($role === 'employee') {
            $employeePermissions = [
                'create_contracts',
                'view_own_contracts',
                'edit_own_contracts',
                'submit_for_review'
            ];
            return in_array($permission, $employeePermissions);
        }
        
        return false;
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }
    
    public function requirePermission($permission) {
        $this->requireAuth();
        
        if (!$this->hasPermission($permission)) {
            header('HTTP/1.0 403 Forbidden');
            die('غير مسموح لك بالوصول لهذه الصفحة');
        }
    }
    
    public function logActivity($userId, $action, $description, $contractId = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO activity_log (user_id, action, description, related_contract_id) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $description,
            $contractId
        ]);
    }
    
    public function createNotification($userId, $title, $message, $type, $contractId = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_contract_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$userId, $title, $message, $type, $contractId]);
    }
    
    public function getNotifications($userId, $limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function markNotificationAsRead($notificationId, $userId) {
        $stmt = $this->pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?
        ");
        
        return $stmt->execute([$notificationId, $userId]);
    }
    
    // طريقة للتحقق من وجود المستخدم
    public function userExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    // طريقة لإنشاء مستخدم جديد
    public function createUser($name, $email, $phone, $password, $role = 'employee') {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, phone, password, role, created_at) 
            VALUES (?, ?, ?, ?, ?, datetime('now'))
        ");
        
        if ($stmt->execute([$name, $email, $phone, $hashed_password, $role])) {
            return $this->pdo->lastInsertId();
        }
        
        return false;
    }
    
    // طريقة للحصول على مستخدم بدور معين
    public function getUserByRole($role) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE role = ? LIMIT 1");
        $stmt->execute([$role]);
        return $stmt->fetch();
    }
}

// إنشاء مثيل من كلاس المصادقة
$auth = new Auth($pdo);
?>