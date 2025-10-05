<?php

namespace App\Http\Controllers;

class DashboardController
{
    public function index()
    {
        $user = auth()->user();
        
        // جمع الإحصائيات (بيانات وهمية للعرض)
        $metrics = [
            'total_count' => 15,
            'pending_count' => 3,
            'in_progress' => 8,
            'closed_count' => 4,
            'users_count' => 10,
        ];

        // مهام وهمية للعرض
        $tasks = [
            ['id' => 1, 'title' => 'مراجعة عقد الشركة الجديدة', 'status' => 'pending', 'due_date' => '2025-10-10'],
            ['id' => 2, 'title' => 'إعداد تقرير شهري', 'status' => 'pending', 'due_date' => '2025-10-15'],
        ];

        // إشعارات وهمية
        $notifications = [
            ['message' => 'تم إنشاء عقد جديد', 'created_at' => date('Y-m-d H:i')],
            ['message' => 'يتطلب عقد #123 موافقة', 'created_at' => date('Y-m-d H:i')],
        ];

        return view('dashboard', compact('metrics', 'tasks', 'notifications'));
    }

    public function managerDashboard()
    {
        // التحقق من صلاحيات المدير (تبسيط للعرض)
        $user = auth()->user();
        if (!$user || $user->role !== 'manager') {
            header('Location: /');
            exit;
        }

        $metrics = [
            'total_count' => 15,
            'pending_count' => 3,
            'closed_count' => 4,
            'employees_count' => 8,
            'users_count' => 10,
        ];

        // بيانات المخطط البياني
        $chart_data = [
            'type' => 'bar',
            'data' => [
                'labels' => ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو'],
                'datasets' => [[
                    'label' => 'العقود الشهرية',
                    'data' => [12, 19, 3, 5, 2],
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                ]]
            ]
        ];

        $tasks = [
            ['title' => 'مراجعة العقود المعلقة', 'assigned_by' => 'النظام'],
            ['title' => 'إعداد التقرير الشهري', 'assigned_by' => 'الإدارة'],
        ];

        $notifications = [
            ['message' => 'عقد جديد يحتاج موافقة', 'created_at' => date('H:i')],
            ['message' => 'تم إنجاز عقد #456', 'created_at' => date('H:i', strtotime('-3 hours'))],
        ];

        $recent_activities = [
            ['type' => 'contract', 'title' => 'إنشاء عقد #789', 'actor' => 'أحمد محمد', 'created_at' => date('H:i', strtotime('-30 minutes'))],
            ['type' => 'file', 'title' => 'عقد_شركة_ABC.pdf', 'name' => 'عقد_شركة_ABC.pdf', 'created_at' => date('H:i', strtotime('-1 hour'))],
        ];

        // عقود وهمية للعرض
        $all_contracts = [
            [
                'id' => 1,
                'serial' => '#001',
                'employee_name' => 'أحمد محمد',
                'client_name' => 'شركة الرياض',
                'status' => 'pending',
                'status_display' => '<span class="badge badge-warning">بانتظار الموافقة</span>',
                'created_at' => date('Y-m-d'),
            ],
            [
                'id' => 2,
                'serial' => '#002',
                'employee_name' => 'فاطمة علي',
                'client_name' => 'مؤسسة جدة',
                'status' => 'approved',
                'status_display' => '<span class="badge badge-success">معتمد</span>',
                'created_at' => date('Y-m-d', strtotime('-1 day')),
            ],
        ];

        return view('manager_dashboard', compact(
            'metrics', 'chart_data', 'tasks', 'notifications', 
            'recent_activities', 'all_contracts'
        ));
    }

    public function updateTask($id)
    {
        // وضع المهمة كمكتملة
        $_SESSION['task_' . $id . '_completed'] = true;
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    public function deleteTask($id)
    {
        // حذف المهمة
        unset($_SESSION['task_' . $id]);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}