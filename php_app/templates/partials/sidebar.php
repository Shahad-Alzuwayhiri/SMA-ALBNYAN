<?php
$userRole = $_SESSION['user_role'] ?? 'guest';
$currentPath = $_SERVER['REQUEST_URI'];
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h3>🏢 سما البنيان</h3>
        <p class="user-info">
            مرحباً، <?= htmlspecialchars($_SESSION['user_name'] ?? 'مستخدم') ?>
            <br><small><?= $userRole === 'manager' ? 'مدير النظام' : 'موظف' ?></small>
        </p>
    </div>
    
    <nav class="sidebar-nav">
        <?php if ($userRole === 'manager'): ?>
            <!-- Manager Navigation -->
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
            
            <a href="/signatures" class="nav-link <?= strpos($currentPath, '/signatures') === 0 ? 'active' : '' ?>">
                <span class="nav-icon">✍️</span>
                <span class="nav-text">التوقيعات</span>
            </a>
            
            <a href="/reports" class="nav-link <?= strpos($currentPath, '/reports') === 0 ? 'active' : '' ?>">
                <span class="nav-icon">📈</span>
                <span class="nav-text">التقارير</span>
            </a>
            
        <?php else: ?>
            <!-- Employee Navigation -->
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
            
            <a href="/my-documents" class="nav-link <?= strpos($currentPath, '/my-documents') === 0 ? 'active' : '' ?>">
                <span class="nav-icon">📁</span>
                <span class="nav-text">مستنداتي</span>
            </a>
        <?php endif; ?>
        
        <!-- Common links -->
        <div class="nav-divider"></div>
        
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
        
        <a href="/logout" class="nav-link logout-link">
            <span class="nav-icon">🚪</span>
            <span class="nav-text">تسجيل الخروج</span>
        </a>
    </nav>
</div>

<style>
.sidebar {
    width: 280px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 0 15px 15px 0;
    box-shadow: 2px 0 15px rgba(0,0,0,0.1);
    height: 100vh;
    position: sticky;
    top: 0;
    overflow-y: auto;
}

.sidebar-header {
    padding: 2rem;
    text-align: center;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0 15px 0 0;
}

.sidebar-header h3 {
    margin: 0 0 1rem 0;
    font-size: 1.5rem;
}

.user-info {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

.sidebar-nav {
    padding: 1rem 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 1rem 2rem;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
    border-right: 3px solid transparent;
    position: relative;
}

.nav-link:hover {
    background-color: rgba(102, 126, 234, 0.1);
    border-right-color: #667eea;
    transform: translateX(-5px);
}

.nav-link.active {
    background-color: rgba(102, 126, 234, 0.15);
    border-right-color: #667eea;
    color: #667eea;
    font-weight: 600;
}

.nav-icon {
    font-size: 1.2rem;
    margin-left: 1rem;
    width: 24px;
    text-align: center;
}

.nav-text {
    flex: 1;
}

.nav-divider {
    height: 1px;
    background: rgba(0,0,0,0.1);
    margin: 1rem 2rem;
}

.logout-link {
    color: #e74c3c;
}

.logout-link:hover {
    background-color: rgba(231, 76, 60, 0.1);
    border-right-color: #e74c3c;
}

.notification-badge {
    background: #e74c3c;
    color: white;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
    margin-right: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-radius: 0;
    }
    
    .main-container {
        flex-direction: column;
    }
    
    .sidebar-header {
        border-radius: 0;
    }
    
    .nav-link {
        padding: 0.75rem 1rem;
    }
}
</style>