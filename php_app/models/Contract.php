<?php

require_once __DIR__ . '/../config/database.php';

class Contract
{
    private $db;
    
    public function __construct()
    {
        $this->db = getDB();
    }
    
    // البحث عن عقد بالمعرف
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM contracts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // إنشاء عقد جديد مع الحقول المحدثة
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO contracts (
                contract_number, client_name, client_id, client_phone, 
                amount, contract_date, signature_method, contract_duration,
                profit_interval, notes, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['contract_number'],
            $data['client_name'],
            $data['client_id'] ?? null,
            $data['client_phone'] ?? null,
            $data['amount'],
            $data['contract_date'] ?? date('Y-m-d'),
            $data['signature_method'] ?? null,
            $data['contract_duration'] ?? 12,
            $data['profit_interval'] ?? 6,
            $data['notes'] ?? null,
            $data['status'] ?? 'active',
            $data['created_by']
        ]);
        
        if ($result) {
            $contractId = $this->db->lastInsertId();
            
            // إنشاء سجل في جدول العقود المفصلة
            $this->createDetailedContract($contractId, $data);
            
            return $contractId;
        }
        
        return false;
    }
    
    // إنشاء سجل في جدول العقود المفصلة
    private function createDetailedContract($contractId, $data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO detailed_contracts (
                contract_id, partner_name, partner_id, partner_phone,
                investment_amount, profit_percent, profit_interval_months,
                contract_date, signature_method, contract_duration, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $contractId,
            $data['client_name'], // استخدام اسم العميل كشريك
            $data['client_id'] ?? null,
            $data['client_phone'] ?? null,
            $data['amount'],
            30.0, // النسبة الافتراضية
            $data['profit_interval'] ?? 6,
            $data['contract_date'] ?? date('Y-m-d'),
            $data['signature_method'] ?? null,
            $data['contract_duration'] ?? 12,
            $data['notes'] ?? null
        ]);
    }
    
    // الحصول على عقد بالمعرف
    public function findById($id)
    {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   creator.name as creator_name,
                   reviewer.name as reviewer_name,
                   signer.name as signer_name
            FROM contracts c
            LEFT JOIN users creator ON c.created_by = creator.id
            LEFT JOIN users reviewer ON c.reviewed_by = reviewer.id
            LEFT JOIN users signer ON c.signed_by = signer.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // الحصول على جميع العقود للمدير
    public function getAllForManager($filters = [])
    {
        $whereClause = "1=1";
        $params = [];
        
        if (isset($filters['status'])) {
            $whereClause .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['created_by'])) {
            $whereClause .= " AND c.created_by = ?";
            $params[] = $filters['created_by'];
        }
        
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   u.name as creator_name,
                   COUNT(n.id) as unread_notifications
            FROM contracts c
            LEFT JOIN users u ON c.created_by = u.id
            LEFT JOIN notifications n ON c.id = n.contract_id AND n.is_read = 0
            WHERE {$whereClause}
            GROUP BY c.id
            ORDER BY c.updated_at DESC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    // الحصول على عقود الموظف
    public function getForEmployee($userId, $filters = [])
    {
        $whereClause = "c.created_by = ?";
        $params = [$userId];
        
        if (isset($filters['status'])) {
            $whereClause .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   COUNT(ca.id) as attachments_count
            FROM contracts c
            LEFT JOIN contract_attachments ca ON c.id = ca.contract_id
            WHERE {$whereClause}
            GROUP BY c.id
            ORDER BY c.updated_at DESC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    // تحديث العقد
    public function update($id, $data, $userId = null)
    {
        $fields = [];
        $params = [];
        
        $allowedFields = [
            'title', 'second_party_name', 'second_party_phone', 'second_party_email',
            'contract_amount', 'profit_percentage', 'start_date', 'end_date',
            'description', 'terms_conditions', 'status', 'reviewed_by', 'signed_by',
            'pdf_path', 'signed_pdf_path'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        $stmt = $this->db->prepare("
            UPDATE contracts 
            SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        $result = $stmt->execute($params);
        
        if ($result && isset($data['status']) && $userId) {
            $contract = $this->findById($id);
            $this->addToHistory($id, 'edited', $contract['status'], $data['status'], null, $userId);
        }
        
        return $result;
    }
    
    // تغيير حالة العقد
    public function changeStatus($id, $newStatus, $userId, $comment = null)
    {
        $contract = $this->findById($id);
        if (!$contract) {
            return false;
        }
        
        $oldStatus = $contract['status'];
        
        $stmt = $this->db->prepare("UPDATE contracts SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $result = $stmt->execute([$newStatus, $id]);
        
        if ($result) {
            $action = $this->getActionFromStatus($newStatus);
            $this->addToHistory($id, $action, $oldStatus, $newStatus, $comment, $userId);
            
            // إضافة إشعار للمستخدم المناسب
            $this->createNotification($id, $newStatus, $userId);
        }
        
        return $result;
    }
    
    // حذف العقد
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM contracts WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // إضافة إلى السجل التاريخي
    public function addToHistory($contractId, $action, $previousStatus, $newStatus, $comment, $actionBy)
    {
        $stmt = $this->db->prepare("
            INSERT INTO contract_history (contract_id, action, previous_status, new_status, comment, action_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$contractId, $action, $previousStatus, $newStatus, $comment, $actionBy]);
    }
    
    // الحصول على تاريخ العقد
    public function getHistory($contractId)
    {
        $stmt = $this->db->prepare("
            SELECT ch.*, u.name as user_name
            FROM contract_history ch
            LEFT JOIN users u ON ch.action_by = u.id
            WHERE ch.contract_id = ?
            ORDER BY ch.created_at DESC
        ");
        $stmt->execute([$contractId]);
        return $stmt->fetchAll();
    }
    
    // توليد رقم العقد - دالة عامة محسّنة
    public function generateContractNumber()
    {
        // استخدام الدوال المساعدة للحصول على رقم عقد فريد
        require_once __DIR__ . '/../helpers/contract_helpers.php';
        return generateUniqueContractNumber($this->db);
    }
    
    // تحديد الإجراء من الحالة
    private function getActionFromStatus($status)
    {
        $actions = [
            'draft' => 'created',
            'pending_review' => 'submitted',
            'approved' => 'approved',
            'signed' => 'signed',
            'rejected' => 'rejected'
        ];
        
        return $actions[$status] ?? 'edited';
    }
    
    // إنشاء إشعار
    private function createNotification($contractId, $status, $userId)
    {
        require_once __DIR__ . '/Notification.php';
        $notification = new Notification();
        
        $contract = $this->findById($contractId);
        
        $notifications = [
            'pending_review' => [
                'user_id' => 1, // المدير
                'type' => 'contract_pending',
                'title' => 'عقد جديد بانتظار المراجعة',
                'message' => "العقد رقم {$contract['contract_number']} بانتظار موافقتك وتوقيعك"
            ],
            'signed' => [
                'user_id' => $contract['created_by'],
                'type' => 'contract_signed',
                'title' => 'تم توقيع العقد',
                'message' => "تم توقيع العقد رقم {$contract['contract_number']} بنجاح"
            ],
            'rejected' => [
                'user_id' => $contract['created_by'],
                'type' => 'contract_rejected',
                'title' => 'تم رفض العقد',
                'message' => "تم رفض العقد رقم {$contract['contract_number']} ويحتاج إلى تعديل"
            ]
        ];
        
        if (isset($notifications[$status])) {
            $notificationData = $notifications[$status];
            $notificationData['contract_id'] = $contractId;
            $notification->create($notificationData);
        }
    }
    
    // إحصائيات العقود
    public function getStats()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_contracts,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
                COUNT(CASE WHEN status = 'pending_review' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'signed' THEN 1 END) as signed_count,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
                SUM(contract_amount) as total_amount,
                SUM(CASE WHEN status = 'signed' THEN contract_amount ELSE 0 END) as signed_amount,
                AVG(profit_percentage) as avg_profit_percentage
            FROM contracts
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
}