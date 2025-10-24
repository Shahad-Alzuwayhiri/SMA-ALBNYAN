<?php

namespace App\Controllers;

use App\Services\EnhancedPdfService;

/**
 * Contract Controller - إدارة العقود
 * Manages all contract operations including creation, viewing, editing, and PDF generation
 * يدير جميع عمليات العقود بما في ذلك الإنشاء والعرض والتحرير وإنتاج ملفات PDF
 */
class ContractController extends BaseController
{
    /**
     * Display contracts list with pagination
     * عرض قائمة العقود مع ترقيم الصفحات
     */
    public function index()
    {
        $this->requireAuth(); // Ensure user is logged in - التأكد من تسجيل دخول المستخدم
        
        // Setup pagination - إعداد ترقيم الصفحات
        $page = (int)($_GET['page'] ?? 1);
        $perPage = $this->config['pagination']['per_page'];
        $offset = ($page - 1) * $perPage;
        
        try {
            // Get contracts with pagination
            $whereClause = '';
            $params = [];
            
            // Filter by user role
            if ($this->user['role'] === 'employee') {
                $whereClause = 'WHERE created_by = ?';
                $params[] = $this->user['id'];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT c.*, u.name as creator_name FROM contracts c LEFT JOIN users u ON c.creator_id = u.id LIMIT 100
                FROM contracts c 
                LEFT JOIN users u ON c.created_by = u.id 
                {$whereClause}
                ORDER BY c.created_at DESC 
                LIMIT {$perPage} OFFSET {$offset}
            ");
            $stmt->execute($params);
            $contracts = $stmt->fetchAll();
            
            // Get total count
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM contracts {$whereClause}");
            $countStmt->execute($params);
            $totalContracts = $countStmt->fetch()['total'];
            
            $totalPages = ceil($totalContracts / $perPage);
            
            return $this->view('contracts/index', [
                'contracts' => $contracts,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalContracts' => $totalContracts
            ]);
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في جلب العقود';
            return $this->view('contracts/index', ['contracts' => []]);
        }
    }
    
    /**
     * Show single contract details
     * عرض تفاصيل عقد واحد
     */
    public function show($id)
    {
        $this->requireAuth(); // Authentication required - مطلوب تسجيل الدخول
        
        try {
            // Get contract with creator and client info - جلب العقد مع معلومات المنشئ والعميل
            $stmt = $this->pdo->prepare('
                SELECT c.*, u.name as creator_name, u2.name as client_name FROM contracts c LEFT JOIN users u ON c.creator_id = u.id LEFT JOIN users u2 ON c.client_id = u2.id LIMIT 100
                FROM contracts c 
                LEFT JOIN users u ON c.created_by = u.id
                LEFT JOIN users u2 ON c.client_id = u2.id
                WHERE c.id = ?
            ');
            $stmt->execute([$id]);
            $contract = $stmt->fetch();
            
            // Check if contract exists - التحقق من وجود العقد
            if (!$contract) {
                $_SESSION['error'] = 'العقد غير موجود';
                $this->redirect('/contracts');
            }
            
            // Check user permissions - فحص صلاحيات المستخدم
            if ($this->user['role'] === 'employee' && $contract['created_by'] != $this->user['id']) {
                $this->abort(403);
            }
            
            return $this->view('contracts/show', ['contract' => $contract]);
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في جلب العقد';
            $this->redirect('/contracts');
        }
    }
    
    /**
     * Show contract creation form or handle POST request
     * عرض نموذج إنشاء عقد أو معالجة طلب POST
     */
    public function create()
    {
        $this->requireAuth(); // Authentication required - مطلوب تسجيل الدخول
        
        // Handle form submission - معالجة إرسال النموذج
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        // Show creation form - عرض نموذج الإنشاء
        return $this->view('contracts/create');
    }
    
    /**
     * Store new contract in database
     * حفظ عقد جديد في قاعدة البيانات
     */
    protected function store()
    {
        $this->requireAuth(); // Authentication required - مطلوب تسجيل الدخول
        
        // Get form data with defaults - جلب بيانات النموذج مع القيم الافتراضية
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $type = $_POST['type'] ?? 'general';
        $client_id = $_POST['client_id'] ?? null;
        $amount = $_POST['amount'] ?? 0;
        $status = 'draft'; // All new contracts start as draft - جميع العقود الجديدة تبدأ كمسودة
        
        try {
            // Insert new contract - إدراج عقد جديد
            $stmt = $this->pdo->prepare('
                INSERT INTO contracts (title, content, type, client_id, amount, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                $title,
                $content,
                $type,
                $client_id,
                $amount,
                $status,
                $this->user['id'], // Current user as creator - المستخدم الحالي كمنشئ
                date('Y-m-d H:i:s')
            ]);
            
            $_SESSION['success'] = 'تم إنشاء العقد بنجاح';
            $this->redirect('/contracts');
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في إنشاء العقد';
            $this->redirect('/contracts/create');
        }
    }
    
    /**
     * Show contract edit form
     * عرض نموذج تحرير العقد
     */
    public function edit($id)
    {
        $this->requireAuth(); // Authentication required - مطلوب تسجيل الدخول
        
        try {
            // Get contract by ID - جلب العقد بالمعرف
            $stmt = $this->pdo->prepare('SELECT * FROM contracts WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $contract = $stmt->fetch();
            
            // Check if contract exists - التحقق من وجود العقد
            if (!$contract) {
                $_SESSION['error'] = 'العقد غير موجود';
                $this->redirect('/contracts');
            }
            
            // Check user permissions - فحص صلاحيات المستخدم
            if ($this->user['role'] === 'employee' && $contract['created_by'] != $this->user['id']) {
                $this->abort(403);
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                return $this->update($id);
            }
            
            return $this->view('contracts/edit', ['contract' => $contract]);
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في جلب العقد';
            $this->redirect('/contracts');
        }
    }
    
    protected function update($id)
    {
        $this->requireAuth();
        
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $type = $_POST['type'] ?? 'general';
        $client_id = $_POST['client_id'] ?? null;
        $amount = $_POST['amount'] ?? 0;
        
        try {
            $stmt = $this->pdo->prepare('
                UPDATE contracts 
                SET title = ?, content = ?, type = ?, client_id = ?, amount = ?, updated_at = ?
                WHERE id = ?
            ');
            
            $stmt->execute([
                $title,
                $content,
                $type,
                $client_id,
                $amount,
                date('Y-m-d H:i:s'),
                $id
            ]);
            
            $_SESSION['success'] = 'تم تحديث العقد بنجاح';
            $this->redirect("/contracts/{$id}");
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في تحديث العقد';
            $this->redirect("/contracts/{$id}/edit");
        }
    }
    
    public function exportPdf($id)
    {
        $this->requireAuth();
        
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM contracts WHERE id = ?');
            $stmt->execute([$id]);
            $contract = $stmt->fetch();
            
            if (!$contract) {
                $_SESSION['error'] = 'العقد غير موجود';
                $this->redirect('/contracts');
            }
            
            // Check permissions
            if ($this->user['role'] === 'employee' && $contract['created_by'] != $this->user['id']) {
                $this->abort(403);
            }
            
            $pdfService = new EnhancedPdfService();
            $contractType = $contract['type'] ?? 'standard';
            $pdfService->outputPdf($contract, $contractType);
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في تصدير PDF: ' . $e->getMessage();
            $this->redirect('/contracts');
        }
    }
}