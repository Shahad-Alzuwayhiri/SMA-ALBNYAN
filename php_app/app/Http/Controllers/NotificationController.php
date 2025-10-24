<?php

namespace App\Http\Controllers;

class NotificationController
{
    public function index()
    {
        // Sample notifications - replace with real data from database
        $notifications = [
            [
                'id' => 1,
                'title' => 'عقد جديد',
                'message' => 'تم إنشاء عقد جديد يحتاج لموافقتك',
                'read' => false,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            ],
            [
                'id' => 2,
                'title' => 'تحديث العقد',
                'message' => 'تم تعديل عقد #123 بنجاح',
                'read' => true,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            ],
            [
                'id' => 3,
                'title' => 'موافقة مطلوبة',
                'message' => 'عقد #456 بحاجة لموافقة المدير',
                'read' => false,
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours')),
            ],
        ];

        return view('notifications', ['notifications' => $notifications]);
    }

    public function markAsRead($id)
    {
        // Mark notification as read logic here
        return back()->with('success', 'تم وضع الإشعار كمقروء');
    }

    public function markAllAsRead()
    {
        // Mark all notifications as read logic here
        return back()->with('success', 'تم وضع جميع الإشعارات كمقروءة');
    }

    public function clearAll()
    {
        // Clear all notifications logic here
        return back()->with('success', 'تم حذف جميع الإشعارات');
    }
}