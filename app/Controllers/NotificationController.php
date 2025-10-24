<?php

namespace App\Controllers;

/**
 * Notification Controller
 * التحكم في الإشعارات
 */
class NotificationController extends BaseController
{
    public function index()
    {
        // التحقق من تسجيل الدخول
        if (!$this->user) {
            $this->redirect('/login');
        }
        
        try {
            // جلب الإشعارات الخاصة بالمستخدم
            $stmt = $this->pdo->prepare('
                SELECT n.*, c.contract_number FROM notifications n LEFT JOIN contracts c ON n.contract_id = c.id LIMIT 100
                FROM notifications n 
                LEFT JOIN contracts c ON n.related_contract_id = c.id 
                WHERE n.user_id = ? 
                ORDER BY n.created_at DESC
            ');
            $stmt->execute([$this->user['id']]);
            $notifications = $stmt->fetchAll();
            
            // إحصائيات الإشعارات
            $unreadCount = 0;
            foreach ($notifications as $notification) {
                if (!$notification['is_read']) {
                    $unreadCount++;
                }
            }
            
            $data = [
                'user' => $this->user,
                'notifications' => $notifications,
                'unreadCount' => $unreadCount,
                'totalCount' => count($notifications)
            ];
            
            return $this->view('notifications/index', $data);
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في جلب الإشعارات: ' . $e->getMessage();
            $this->redirect('/dashboard');
        }
    }
    
    public function markAsRead($id)
    {
        if (!$this->user) {
            $this->redirect('/login');
        }
        
        try {
            $stmt = $this->pdo->prepare('
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ');
            $stmt->execute([$id, $this->user['id']]);
            
            $_SESSION['success'] = 'تم تحديث حالة الإشعار';
            $this->redirect('/notifications');
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في تحديث الإشعار: ' . $e->getMessage();
            $this->redirect('/notifications');
        }
    }
    
    public function markAllAsRead()
    {
        if (!$this->user) {
            $this->redirect('/login');
        }
        
        try {
            $stmt = $this->pdo->prepare('
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = ?
            ');
            $stmt->execute([$this->user['id']]);
            
            $_SESSION['success'] = 'تم تحديث جميع الإشعارات';
            $this->redirect('/notifications');
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'خطأ في تحديث الإشعارات: ' . $e->getMessage();
            $this->redirect('/notifications');
        }
    }
}