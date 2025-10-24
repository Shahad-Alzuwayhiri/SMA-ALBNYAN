<?php

namespace App\Controllers;

/**
 * Dashboard Controller
 * التحكم في لوحات التحكم
 */
class DashboardController extends BaseController
{
    /**
     * Manager Dashboard
     * لوحة تحكم المدير
     */
    public function manager()
    {
        // Check if user is logged in
        if (!$this->user) {
            $this->redirect('/login');
            return;
        }
        
        // Check if user has proper role - if not, redirect to appropriate dashboard
        if (!in_array($this->user['role'], ['admin', 'manager'])) {
            // Instead of redirecting to login, redirect to employee dashboard
            $this->redirect('/employee-dashboard');
            return;
        }
        
        // Include the manager dashboard
        require __DIR__ . '/../../public/manager_dashboard.php';
    }
    
    /**
     * Employee Dashboard
     * لوحة تحكم الموظف
     */
    public function employee()
    {
        // Check if user is logged in
        if (!$this->user) {
            $this->redirect('/login');
            return;
        }
        
        // Include the employee dashboard
        require __DIR__ . '/../../public/employee_dashboard.php';
    }
}