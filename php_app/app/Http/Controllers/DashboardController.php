<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contract;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // جمع الإحصائيات
        $metrics = [
            'total_count' => Contract::count(),
            'pending_count' => Contract::where('status', 'pending')->count(),
            'in_progress' => Contract::where('status', 'in_progress')->count(),
            'closed_count' => Contract::whereIn('status', ['completed', 'rejected'])->count(),
            'users_count' => User::count(),
        ];

        // مهام وهمية للعرض
        $tasks = [
            ['id' => 1, 'title' => 'مراجعة عقد الشركة الجديدة', 'status' => 'pending', 'due_date' => '2025-10-10'],
            ['id' => 2, 'title' => 'إعداد تقرير شهري', 'status' => 'pending', 'due_date' => '2025-10-15'],
        ];

        // إشعارات وهمية
        $notifications = [
            ['message' => 'تم إنشاء عقد جديد', 'created_at' => now()->subHours(2)->format('Y-m-d H:i')],
            ['message' => 'يتطلب عقد #123 موافقة', 'created_at' => now()->subHours(5)->format('Y-m-d H:i')],
        ];

        return view('dashboard', compact('metrics', 'tasks', 'notifications'));
    }

    public function managerDashboard()
    {
        // التحقق من صلاحيات المدير
        if (!auth()->user()->isManager()) {
            return redirect()->route('dashboard')->with('error', 'ليس لديك صلاحية للوصول لهذه الصفحة');
        }

        $metrics = [
            'total_count' => Contract::count(),
            'pending_count' => Contract::where('status', 'pending')->count(),
            'closed_count' => Contract::whereIn('status', ['completed', 'rejected'])->count(),
            'employees_count' => User::where('role', 'employee')->count(),
            'users_count' => User::count(),
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
            ['message' => 'عقد جديد يحتاج موافقة', 'created_at' => now()->subHours(1)->format('H:i')],
            ['message' => 'تم إنجاز عقد #456', 'created_at' => now()->subHours(3)->format('H:i')],
        ];

        $recent_activities = [
            ['type' => 'contract', 'title' => 'إنشاء عقد #789', 'actor' => 'أحمد محمد', 'created_at' => now()->subMinutes(30)->format('H:i')],
            ['type' => 'file', 'title' => 'عقد_شركة_ABC.pdf', 'name' => 'عقد_شركة_ABC.pdf', 'created_at' => now()->subHours(1)->format('H:i')],
        ];

        $all_contracts = Contract::with('user')->latest()->limit(10)->get()->map(function($contract) {
            return [
                'id' => $contract->id,
                'serial' => $contract->serial ?? '#' . $contract->id,
                'employee_name' => $contract->user->name ?? 'غير محدد',
                'client_name' => $contract->client_name ?? 'غير محدد',
                'status' => $contract->status,
                'status_display' => $this->getStatusDisplay($contract->status),
                'created_at' => $contract->created_at->format('Y-m-d'),
            ];
        })->toArray();

        return view('manager_dashboard', compact(
            'metrics', 'chart_data', 'tasks', 'notifications', 
            'recent_activities', 'all_contracts'
        ));
    }

    private function getStatusDisplay($status)
    {
        $statuses = [
            'pending' => '<span class="badge badge-warning">بانتظار الموافقة</span>',
            'approved' => '<span class="badge badge-success">معتمد</span>',
            'in_progress' => '<span class="badge badge-info">قيد التنفيذ</span>',
            'completed' => '<span class="badge badge-success">مكتمل</span>',
            'rejected' => '<span class="badge badge-danger">مرفوض</span>',
        ];

        return $statuses[$status] ?? '<span class="badge badge-secondary">' . $status . '</span>';
    }
}