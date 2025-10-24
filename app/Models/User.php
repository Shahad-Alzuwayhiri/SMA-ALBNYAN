<?php

namespace App\Models;

class User
{
    private $db;
    
    public function __construct($pdo = null)
    {
        if ($pdo) {
            $this->db = $pdo;
        } else {
            global $pdo;
            $this->db = $pdo;
        }
    }
    
    // العثور على مستخدم بالبريد الإلكتروني
    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    // العثور على مستخدم بالمعرف
    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // إنشاء مستخدم جديد
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, phone, password, role, permissions) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $permissions = json_encode($this->getDefaultPermissions($data['role'] ?? 'employee'));
        
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'] ?? 'employee',
            $permissions
        ]);
    }
    
    // الحصول على جميع الموظفين
    public function getAllEmployees()
    {
        $stmt = $this->db->prepare("
            SELECT u.*, 
                   COUNT(c.id) as total_contracts,
                   MAX(c.updated_at) as last_activity
            FROM users u
            LEFT JOIN contracts c ON u.id = c.created_by
            WHERE u.role = 'employee' AND u.is_active = 1
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // تحديث صلاحيات المستخدم
    public function updatePermissions($userId, $permissions)
    {
        $stmt = $this->db->prepare("UPDATE users SET permissions = ? WHERE id = ?");
        return $stmt->execute([json_encode($permissions), $userId]);
    }
    
    // إلغاء تفعيل المستخدم
    public function deactivate($userId)
    {
        $stmt = $this->db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$userId]);
    }
    
    // تفعيل المستخدم
    public function activate($userId)
    {
        $stmt = $this->db->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        return $stmt->execute([$userId]);
    }
    
    // الحصول على الصلاحيات الافتراضية
    private function getDefaultPermissions($role)
    {
        $permissions = [
            'manager' => [
                'view_all_contracts' => true,
                'sign_contracts' => true,
                'manage_employees' => true,
                'view_reports' => true,
                'approve_contracts' => true,
                'reject_contracts' => true,
                'edit_any_contract' => true,
                'delete_contracts' => true
            ],
            'employee' => [
                'create_contracts' => true,
                'edit_own_contracts' => true,
                'upload_files' => true,
                'view_own_contracts' => true,
                'submit_for_review' => true
            ]
        ];
        
        return $permissions[$role] ?? $permissions['employee'];
    }
    
    // التحقق من الصلاحية
    public function hasPermission($userId, $permission)
    {
        $user = $this->findById($userId);
        if (!$user || !$user['permissions']) {
            return false;
        }
        
        $permissions = json_decode($user['permissions'], true);
        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }
    
    // التحقق من كلمة المرور
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
    
    // إحصائيات المستخدم
    public function getUserStats($userId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_contracts,
                COUNT(CASE WHEN status = 'pending_review' THEN 1 END) as pending_contracts,
                COUNT(CASE WHEN status = 'signed' THEN 1 END) as signed_contracts,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_contracts,
                COUNT(*) as total_contracts,
                SUM(CASE WHEN status = 'signed' THEN amount ELSE 0 END) as total_signed_amount
            FROM contracts 
            WHERE created_by = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}