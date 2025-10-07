<?php

class SimpleDashboardController
{
    public function index()
    {
        // Load models for dynamic data
        require_once __DIR__ . '/../../Models/Contract.php';
        require_once __DIR__ . '/../../Models/User.php';
        
        // Get current user ID from session
        $userId = $_SESSION['user_id'] ?? null;
        
        try {
            // Get user-specific contract data
            $userContracts = $this->getUserContractStats($userId);
            $recentContracts = $this->getUserRecentContracts($userId, 5);
            
            $data = [
                'total_contracts' => $userContracts['total'] ?? 0,
                'pending_contracts' => $userContracts['pending'] ?? 0,
                'approved_contracts' => $userContracts['approved'] ?? 0,
                'completed_contracts' => $userContracts['completed'] ?? 0,
                'total_revenue' => $userContracts['total_revenue'] ?? 0,
                'monthly_revenue' => $userContracts['monthly_revenue'] ?? 0,
                'yearly_revenue' => $userContracts['yearly_revenue'] ?? 0,
                'notifications_count' => 0, // يمكن إضافة نظام الإشعارات لاحقاً
                'recent_contracts' => $recentContracts,
                'recent_notifications' => [] // يمكن إضافة نظام الإشعارات لاحقاً
            ];
        } catch (Exception $e) {
            // Fallback to empty data if database fails
            $data = [
                'total_contracts' => 0,
                'pending_contracts' => 0,
                'approved_contracts' => 0,
                'completed_contracts' => 0,
                'total_revenue' => 0,
                'monthly_revenue' => 0,
                'yearly_revenue' => 0,
                'notifications_count' => 0,
                'recent_contracts' => [],
                'recent_notifications' => [],
                'error' => 'لا يمكن الوصول لقاعدة البيانات'
            ];
        }
        
        // Include and output the dashboard template
        ob_start();
        include dirname(__DIR__, 3) . '/templates/dashboard.php';
        return ob_get_clean();
    }

    public function managerDashboard()
    {
        // Include layout helpers
        require_once dirname(__DIR__, 3) . '/templates/layout_helpers.php';
        
        // Require authentication and manager role
        requireRole('manager');
        
        // Load models for dynamic data
        require_once __DIR__ . '/../../Models/Contract.php';
        require_once __DIR__ . '/../../Models/User.php';
        
        try {
            // Get dynamic data from database
            $contractStats = \App\Models\Contract::getStats();
            $userStats = \App\Models\User::getStats();
            $recentContracts = \App\Models\Contract::getRecent(5);
            $recentActivities = \App\Models\Contract::getRecentActivities(10);
            
            $data = [
                'total_contracts' => $contractStats['total'] ?? 0,
                'pending_approvals' => $contractStats['pending'] ?? 0,
                'approved_contracts' => $contractStats['approved'] ?? 0,
                'completed_contracts' => $contractStats['completed'] ?? 0,
                'total_employees' => $userStats['total_employees'] ?? 0,
                'active_employees' => $userStats['active_employees'] ?? 0,
                'recent_contracts' => $recentContracts,
                'recent_activities' => $recentActivities,
                'monthly_target' => 100000, // يمكن أن يكون هذا قابل للتخصيص
                'monthly_achievement' => $contractStats['monthly_total'] ?? 0,
                'team_performance' => $this->calculateTeamPerformance($contractStats)
            ];
        } catch (Exception $e) {
            // Fallback to static data if database fails
            $data = [
                'total_contracts' => 0,
                'pending_approvals' => 0,
                'approved_contracts' => 0,
                'completed_contracts' => 0,
                'total_employees' => 0,
                'active_employees' => 0,
                'recent_contracts' => [],
                'recent_activities' => [],
                'monthly_target' => 100000,
                'monthly_achievement' => 0,
                'team_performance' => 0,
                'error' => 'لا يمكن الوصول لقاعدة البيانات'
            ];
        }
        
        // Load content template
        ob_start();
        include dirname(__DIR__, 3) . '/templates/manager_dashboard_content.php';
        $content = ob_get_clean();
        
        // Return using master layout
        return renderMasterLayout(
            $content,
            $data,
            'لوحة تحكم المدير - سما البنيان التجارية',
            false, // is_auth_page
            true, // show_sidebar
            '', // additional_head
            '' // additional_scripts
        );
    }

    // Helper function to calculate team performance
    private function calculateTeamPerformance($contractStats)
    {
        $total = $contractStats['total'] ?? 0;
        $completed = $contractStats['completed'] ?? 0;
        $approved = $contractStats['approved'] ?? 0;
        
        if ($total == 0) return 0;
        
        // Calculate performance as percentage of completed + approved contracts
        $successful = $completed + $approved;
        return round(($successful / $total) * 100);
    }

    // Get contract stats for specific user
    private function getUserContractStats($userId)
    {
        if (!$userId) return [];
        
        try {
            $db = \App\Models\Contract::getDb();
            
            // Total contracts for user
            $totalStmt = $db->prepare("SELECT COUNT(*) as total FROM contracts WHERE user_id = ?");
            $totalStmt->execute([$userId]);
            $total = $totalStmt->fetch()['total'] ?? 0;
            
            // Contracts by status for user
            $statusStmt = $db->prepare("
                SELECT status, COUNT(*) as count, SUM(COALESCE(amount, 0)) as total_amount
                FROM contracts 
                WHERE user_id = ?
                GROUP BY status
            ");
            $statusStmt->execute([$userId]);
            
            $statusCounts = [];
            $totalRevenue = 0;
            while ($row = $statusStmt->fetch()) {
                $statusCounts[$row['status']] = $row['count'];
                $totalRevenue += $row['total_amount'] ?? 0;
            }
            
            // Monthly revenue for user
            $monthlyStmt = $db->prepare("
                SELECT SUM(COALESCE(amount, 0)) as monthly_revenue
                FROM contracts 
                WHERE user_id = ? 
                AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())
            ");
            $monthlyStmt->execute([$userId]);
            $monthlyRevenue = $monthlyStmt->fetch()['monthly_revenue'] ?? 0;
            
            // Yearly revenue for user
            $yearlyStmt = $db->prepare("
                SELECT SUM(COALESCE(amount, 0)) as yearly_revenue
                FROM contracts 
                WHERE user_id = ? 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())
            ");
            $yearlyStmt->execute([$userId]);
            $yearlyRevenue = $yearlyStmt->fetch()['yearly_revenue'] ?? 0;
            
            return [
                'total' => $total,
                'pending' => $statusCounts['pending'] ?? 0,
                'approved' => $statusCounts['approved'] ?? 0,
                'completed' => $statusCounts['completed'] ?? 0,
                'draft' => $statusCounts['draft'] ?? 0,
                'rejected' => $statusCounts['rejected'] ?? 0,
                'total_revenue' => $totalRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'yearly_revenue' => $yearlyRevenue
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    // Get recent contracts for specific user
    private function getUserRecentContracts($userId, $limit = 10)
    {
        if (!$userId) return [];
        
        try {
            $db = \App\Models\Contract::getDb();
            $stmt = $db->prepare("
                SELECT * FROM contracts 
                WHERE user_id = ?
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            
            $contracts = [];
            while ($data = $stmt->fetch()) {
                $contracts[] = [
                    'id' => $data['id'],
                    'serial' => 'CT-' . str_pad($data['id'], 4, '0', STR_PAD_LEFT),
                    'title' => $data['title'],
                    'client_name' => $data['client_name'],
                    'status' => $data['status'],
                    'amount' => $data['amount'],
                    'created_at' => $data['created_at']
                ];
            }
            
            return $contracts;
        } catch (\Exception $e) {
            return [];
        }
    }
}