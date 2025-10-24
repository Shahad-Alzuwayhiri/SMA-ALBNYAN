<?php

namespace App\Controllers;

/**
 * Employee Controller
 * التحكم في الموظفين
 */
class EmployeeController extends BaseController
{
    public function index()
    {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!$this->user) {
            $this->redirect('/login');
        }
        
        if (!in_array($this->user['role'], ['admin', 'manager'])) {
            $_SESSION['error'] = 'ليس لديك صلاحية للوصول إلى هذه الصفحة';
            $this->redirect('/dashboard');
        }
        
        try {
            // جلب جميع الموظفين
            $stmt = $this->pdo->prepare('
                SELECT u.* FROM users u LIMIT 100
                       COUNT(c.id) as contract_count,
                       (SELECT COUNT(*) FROM activity_log WHERE user_id = u.id) as activity_count
                FROM users u 
                LEFT JOIN contracts c ON u.id = c.created_by 
                WHERE u.role IN (?, ?)
                GROUP BY u.id 
                ORDER BY u.created_at DESC
            ');
            $stmt->execute(['employee', 'manager']);
            $employees = $stmt->fetchAll();
            
            // إحصائيات
            $totalEmployees = count($employees);
            $activeEmployees = 0;
            $inactiveEmployees = 0;
            
            foreach ($employees as $employee) {
                if ($employee['status'] === 'active') {
                    $activeEmployees++;
                } else {
                    $inactiveEmployees++;
                }
            }
            
            $data = [
                'user' => $this->user,
                'employees' => $employees,
                'stats' => [
                    'total' => $totalEmployees,
                    'active' => $activeEmployees,
                    'inactive' => $inactiveEmployees
                ]
            ];
            
            return $this->view('employees/index', $data);
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في جلب بيانات الموظفين: ' . $e->getMessage();
            $this->redirect('/dashboard');
        }
    }
    
    public function create()
    {
        if (!$this->user || !in_array($this->user['role'], ['admin', 'manager'])) {
            $this->redirect('/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        return $this->view('employees/create', ['user' => $this->user]);
    }
    
    public function store()
    {
        if (!$this->user || !in_array($this->user['role'], ['admin', 'manager'])) {
            $this->redirect('/login');
        }
        
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $role = $_POST['role'] ?? 'employee';
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['error'] = 'جميع الحقول مطلوبة';
            $this->redirect('/manage_employees');
        }
        
        try {
            // التحقق من عدم وجود البريد الإلكتروني
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'البريد الإلكتروني موجود مسبقاً';
                $this->redirect('/manage_employees');
            }
            
            // إنشاء الموظف الجديد
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare('
                INSERT INTO users (name, email, password, phone, role, status, created_by, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $name, $email, $hashedPassword, $phone, $role, 'active', 
                $this->user['id'], date('Y-m-d H:i:s'), date('Y-m-d H:i:s')
            ]);
            
            $_SESSION['success'] = 'تم إنشاء الموظف بنجاح';
            $this->redirect('/manage_employees');
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في إنشاء الموظف: ' . $e->getMessage();
            $this->redirect('/manage_employees');
        }
    }
    
    public function edit($id)
    {
        if (!$this->user || !in_array($this->user['role'], ['admin', 'manager'])) {
            $this->redirect('/login');
        }
        
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $employee = $stmt->fetch();
            
            if (!$employee) {
                $_SESSION['error'] = 'الموظف غير موجود';
                $this->redirect('/manage_employees');
            }
            
            $data = [
                'user' => $this->user,
                'employee' => $employee
            ];
            
            return $this->view('employees/edit', $data);
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في جلب بيانات الموظف: ' . $e->getMessage();
            $this->redirect('/manage_employees');
        }
    }
    
    public function update($id)
    {
        if (!$this->user || !in_array($this->user['role'], ['admin', 'manager'])) {
            $this->redirect('/login');
        }
        
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $role = $_POST['role'] ?? 'employee';
        $status = $_POST['status'] ?? 'active';
        
        if (empty($name) || empty($email)) {
            $_SESSION['error'] = 'الاسم والبريد الإلكتروني مطلوبان';
            $this->redirect('/manage_employees');
        }
        
        try {
            $stmt = $this->pdo->prepare('
                UPDATE users 
                SET name = ?, email = ?, phone = ?, role = ?, status = ?, updated_at = ? 
                WHERE id = ?
            ');
            $stmt->execute([$name, $email, $phone, $role, $status, date('Y-m-d H:i:s'), $id]);
            
            $_SESSION['success'] = 'تم تحديث بيانات الموظف بنجاح';
            $this->redirect('/manage_employees');
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في تحديث الموظف: ' . $e->getMessage();
            $this->redirect('/manage_employees');
        }
    }
    
    public function delete($id)
    {
        if (!$this->user || $this->user['role'] !== 'admin') {
            $_SESSION['error'] = 'ليس لديك صلاحية لحذف الموظفين';
            $this->redirect('/manage_employees');
        }
        
        try {
            // تحديث الحالة إلى inactive بدلاً من الحذف
            $stmt = $this->pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
            $stmt->execute(['inactive', $id]);
            
            $_SESSION['success'] = 'تم إلغاء تفعيل الموظف بنجاح';
            $this->redirect('/manage_employees');
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في حذف الموظف: ' . $e->getMessage();
            $this->redirect('/manage_employees');
        }
    }
}