<?php

require_once __DIR__ . '/../config/database.php';

class Notification
{
    private $db;
    
    public function __construct()
    {
        $this->db = getDB();
    }
    
    // إنشاء إشعار جديد
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, contract_id, type, title, message)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['user_id'],
            $data['contract_id'] ?? null,
            $data['type'],
            $data['title'],
            $data['message']
        ]);
    }
    
    // الحصول على إشعارات المستخدم
    public function getForUser($userId, $limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT n.*, c.contract_number, c.title as contract_title
            FROM notifications n
            LEFT JOIN contracts c ON n.contract_id = c.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    // الحصول على الإشعارات غير المقروءة
    public function getUnreadForUser($userId)
    {
        $stmt = $this->db->prepare("
            SELECT n.*, c.contract_number, c.title as contract_title
            FROM notifications n
            LEFT JOIN contracts c ON n.contract_id = c.id
            WHERE n.user_id = ? AND n.is_read = 0
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    // عدد الإشعارات غير المقروءة
    public function getUnreadCount($userId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    // تعيين الإشعار كمقروء
    public function markAsRead($notificationId)
    {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$notificationId]);
    }
    
    // تعيين جميع إشعارات المستخدم كمقروءة
    public function markAllAsRead($userId)
    {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
    
    // حذف الإشعارات القديمة (أكثر من 30 يوم)
    public function deleteOld($days = 30)
    {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        return $stmt->execute([$days]);
    }
    
    // حذف إشعار محدد
    public function delete($notificationId)
    {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ?");
        return $stmt->execute([$notificationId]);
    }
    
    // إشعارات خاصة بعقد معين
    public function getForContract($contractId)
    {
        $stmt = $this->db->prepare("
            SELECT n.*, u.name as user_name
            FROM notifications n
            LEFT JOIN users u ON n.user_id = u.id
            WHERE n.contract_id = ?
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$contractId]);
        return $stmt->fetchAll();
    }
    
    // إرسال إشعار لجميع المديرين
    public function notifyAllManagers($data)
    {
        // الحصول على جميع المديرين
        $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'manager' AND is_active = 1");
        $stmt->execute();
        $managers = $stmt->fetchAll();
        
        foreach ($managers as $manager) {
            $notificationData = $data;
            $notificationData['user_id'] = $manager['id'];
            $this->create($notificationData);
        }
        
        return true;
    }
    
    // إشعار سريع للمدير عن عقد جديد
    public function notifyManagerNewContract($contractId, $creatorName)
    {
        require_once __DIR__ . '/Contract.php';
        $contractModel = new Contract();
        $contract = $contractModel->findById($contractId);
        
        $this->notifyAllManagers([
            'contract_id' => $contractId,
            'type' => 'contract_pending',
            'title' => 'عقد جديد بانتظار المراجعة',
            'message' => "العقد رقم {$contract['contract_number']} تم إنشاؤه من قبل {$creatorName} وبانتظار المراجعة"
        ]);
    }
    
    // إشعار الموظف بتوقيع العقد
    public function notifyEmployeeContractSigned($contractId, $employeeId)
    {
        require_once __DIR__ . '/Contract.php';
        $contractModel = new Contract();
        $contract = $contractModel->findById($contractId);
        
        $contractNumber = $contract ? $contract['contract_number'] : "رقم $contractId";
        
        $this->create([
            'user_id' => $employeeId,
            'contract_id' => $contractId,
            'type' => 'contract_signed',
            'title' => 'تم توقيع عقدك',
            'message' => "تم توقيع العقد رقم {$contractNumber} بنجاح من قبل المدير"
        ]);
    }
}