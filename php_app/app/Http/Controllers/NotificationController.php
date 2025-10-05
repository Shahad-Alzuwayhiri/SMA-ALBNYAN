<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
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
                'created_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'title' => 'تحديث العقد',
                'message' => 'تم تعديل عقد #123 بنجاح',
                'read' => true,
                'created_at' => now()->subDays(1)->format('Y-m-d H:i:s'),
            ],
        ];

        return view('notifications', compact('notifications'));
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