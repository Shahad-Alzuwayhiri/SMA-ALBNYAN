<?php
// Navigation helper functions

function renderNavigation($userRole = null) {
    $currentPath = $_SERVER['REQUEST_URI'];
    
    $nav = '<nav class="main-navigation">';
    $nav .= '<div class="nav-container">';
    
    // Logo/Brand
    $nav .= '<div class="nav-brand">';
    $nav .= '<a href="/">';
    $nav .= '<h2>🏢 سما البنيان التجارية</h2>';
    $nav .= '</a>';
    $nav .= '</div>';
    
    // Navigation items
    $nav .= '<div class="nav-items">';
    
    if (!$userRole) {
        // Guest navigation
        $nav .= '<a href="/" class="nav-item ' . ($currentPath === '/' ? 'active' : '') . '">الرئيسية</a>';
        $nav .= '<a href="/login" class="nav-item ' . ($currentPath === '/login' ? 'active' : '') . '">تسجيل الدخول</a>';
        $nav .= '<a href="/register" class="nav-item ' . ($currentPath === '/register' ? 'active' : '') . '">إنشاء حساب</a>';
    } else {
        // Authenticated user navigation
        if ($userRole === 'manager') {
            $nav .= '<a href="/manager-dashboard" class="nav-item ' . ($currentPath === '/manager-dashboard' ? 'active' : '') . '">لوحة المدير</a>';
            $nav .= '<a href="/contracts" class="nav-item">العقود</a>';
            $nav .= '<a href="/employees" class="nav-item">الموظفين</a>';
            $nav .= '<a href="/reports" class="nav-item">التقارير</a>';
        } else {
            $nav .= '<a href="/employee-dashboard" class="nav-item ' . ($currentPath === '/employee-dashboard' ? 'active' : '') . '">لوحة الموظف</a>';
            $nav .= '<a href="/my-contracts" class="nav-item">عقودي</a>';
            $nav .= '<a href="/create-contract" class="nav-item">عقد جديد</a>';
        }
        
        // Common authenticated items
        $nav .= '<div class="nav-user">';
        $nav .= '<span class="user-name">مرحباً، ' . ($_SESSION['user_name'] ?? 'مستخدم') . '</span>';
        $nav .= '<a href="/profile" class="nav-item">الملف الشخصي</a>';
        $nav .= '<a href="/logout" class="nav-item">تسجيل الخروج</a>';
        $nav .= '</div>';
    }
    
    $nav .= '</div>';
    $nav .= '</div>';
    $nav .= '</nav>';
    
    return $nav;
}

function renderBreadcrumb($items) {
    $breadcrumb = '<nav class="breadcrumb">';
    $breadcrumb .= '<div class="breadcrumb-container">';
    
    foreach ($items as $index => $item) {
        if ($index > 0) {
            $breadcrumb .= '<span class="breadcrumb-separator">←</span>';
        }
        
        if (isset($item['url']) && $index < count($items) - 1) {
            $breadcrumb .= '<a href="' . $item['url'] . '" class="breadcrumb-item">' . $item['name'] . '</a>';
        } else {
            $breadcrumb .= '<span class="breadcrumb-item active">' . $item['name'] . '</span>';
        }
    }
    
    $breadcrumb .= '</div>';
    $breadcrumb .= '</nav>';
    
    return $breadcrumb;
}
?>

<style>
.main-navigation {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1rem 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 2rem;
}

.nav-brand a {
    text-decoration: none;
    color: white;
}

.nav-brand h2 {
    margin: 0;
    font-size: 1.5rem;
}

.nav-items {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.nav-item {
    color: white;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.nav-item:hover,
.nav-item.active {
    background-color: rgba(255,255,255,0.2);
}

.nav-user {
    display: flex;
    align-items: center;
    gap: 1rem;
    border-left: 1px solid rgba(255,255,255,0.3);
    padding-left: 2rem;
}

.user-name {
    color: white;
    font-weight: bold;
}

.breadcrumb {
    background-color: #f8f9fa;
    padding: 1rem 0;
    margin-bottom: 2rem;
}

.breadcrumb-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.breadcrumb-item {
    color: #6c757d;
    text-decoration: none;
}

.breadcrumb-item.active {
    color: #495057;
    font-weight: bold;
}

.breadcrumb-separator {
    color: #6c757d;
    margin: 0 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .nav-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .nav-items {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .nav-user {
        border-left: none;
        border-top: 1px solid rgba(255,255,255,0.3);
        padding-left: 0;
        padding-top: 1rem;
    }
}
</style>