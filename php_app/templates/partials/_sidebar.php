<?php
/**
 * Sidebar Component
 * الشريط الجانبي للتنقل
 */

$userRole = $_SESSION['user_role'] ?? 'guest';
$currentPath = $_SERVER['REQUEST_URI'];
$userName = $_SESSION['user_name'] ?? 'مستخدم';
?>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <h2>🏢 سما البنيان</h2>
        </div>
        <div class="user-info">
            <div class="user-avatar">
                <span><?= mb_substr($userName, 0, 1) ?></span>
            </div>
            <div class="user-details">
                <h4><?= htmlspecialchars($userName) ?></h4>
                <p><?= $userRole === 'manager' ? 'مدير النظام' : 'موظف' ?></p>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <?php if ($userRole === 'manager'): ?>
            <!-- Manager Navigation -->
            <div class="nav-section">
                <h5 class="nav-section-title">الإدارة</h5>
                
                <a href="/manager-dashboard" class="nav-link <?= $currentPath === '/manager-dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">لوحة المدير</span>
                </a>
                
                <a href="/contracts" class="nav-link <?= strpos($currentPath, '/contracts') === 0 ? 'active' : '' ?>">
                    <span class="nav-icon">📄</span>
                    <span class="nav-text">إدارة العقود</span>
                </a>
                
                <a href="/employees" class="nav-link <?= strpos($currentPath, '/employees') === 0 ? 'active' : '' ?>">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">إدارة الموظفين</span>
                </a>
                
                <a href="/reports" class="nav-link <?= strpos($currentPath, '/reports') === 0 ? 'active' : '' ?>">
                    <span class="nav-icon">📈</span>
                    <span class="nav-text">التقارير</span>
                </a>
            </div>
            
        <?php else: ?>
            <!-- Employee Navigation -->
            <div class="nav-section">
                <h5 class="nav-section-title">العمل</h5>
                
                <a href="/employee-dashboard" class="nav-link <?= $currentPath === '/employee-dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">لوحة الموظف</span>
                </a>
                
                <a href="/my-contracts" class="nav-link <?= strpos($currentPath, '/my-contracts') === 0 ? 'active' : '' ?>">
                    <span class="nav-icon">📄</span>
                    <span class="nav-text">عقودي</span>
                </a>
                
                <a href="/create-contract" class="nav-link <?= $currentPath === '/create-contract' ? 'active' : '' ?>">
                    <span class="nav-icon">➕</span>
                    <span class="nav-text">عقد جديد</span>
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Common Links -->
        <div class="nav-section">
            <h5 class="nav-section-title">الحساب</h5>
            
            <a href="/profile" class="nav-link <?= $currentPath === '/profile' ? 'active' : '' ?>">
                <span class="nav-icon">👤</span>
                <span class="nav-text">الملف الشخصي</span>
            </a>
            
            <a href="/notifications" class="nav-link <?= $currentPath === '/notifications' ? 'active' : '' ?>">
                <span class="nav-icon">🔔</span>
                <span class="nav-text">الإشعارات</span>
                <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                    <span class="notification-badge"><?= $_SESSION['unread_notifications'] ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div class="nav-section">
            <a href="/logout" class="nav-link logout-link">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">تسجيل الخروج</span>
            </a>
        </div>
    </nav>
</div>

<style>
.sidebar {
    width: var(--sidebar-width);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    box-shadow: 2px 0 20px rgba(0,0,0,0.1);
    height: 100vh;
    position: sticky;
    top: 0;
    overflow-y: auto;
    border-radius: 0 15px 15px 0;
}

.sidebar-header {
    padding: 2rem 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
}

.logo h2 {
    color: #333;
    margin-bottom: 1rem;
    font-size: 1.5rem;
    text-align: center;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
}

.user-details h4 {
    margin: 0;
    font-size: 1rem;
    color: #333;
}

.user-details p {
    margin: 0;
    font-size: 0.875rem;
    color: #666;
}

.sidebar-nav {
    padding: 1rem 0;
}

.nav-section {
    margin-bottom: 2rem;
}

.nav-section-title {
    padding: 0.5rem 1.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    margin-bottom: 0.5rem;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    color: #555;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.nav-link:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #333;
    transform: translateX(-5px);
}

.nav-link.active {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
    color: #333;
    font-weight: 600;
}

.nav-link.active::before {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 2px 0 0 2px;
}

.nav-icon {
    font-size: 1.25rem;
    width: 24px;
    text-align: center;
}

.nav-text {
    font-size: 0.95rem;
}

.logout-link {
    color: #dc2626 !important;
    border-top: 1px solid rgba(0,0,0,0.1);
    margin-top: 1rem;
    padding-top: 1rem;
}

.logout-link:hover {
    background: rgba(220, 38, 38, 0.1) !important;
}

.notification-badge {
    background: #ef4444;
    color: white;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
    margin-right: auto;
    min-width: 20px;
    text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-radius: 0;
    }
    
    .sidebar-header {
        padding: 1rem;
    }
    
    .user-info {
        justify-content: center;
        text-align: center;
    }
    
    .nav-link {
        justify-content: center;
        text-align: center;
    }
    
    .nav-text {
        display: none;
    }
    
    .nav-section-title {
        text-align: center;
    }
}
</style>