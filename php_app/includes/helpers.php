<?php

/**
 * Render a template with layout
 */
if (!function_exists('renderWithLayout')) {
    function renderWithLayout($templateName, $data = [], $layoutOptions = []) {
    // Set default layout options
    $defaultOptions = [
        'title' => 'نظام إدارة العقود - سما البنيان التجارية',
        'is_auth_page' => false,
        'show_sidebar' => false,
        'additional_head' => '',
        'additional_scripts' => ''
    ];
    
    $options = array_merge($defaultOptions, $layoutOptions);
    
    // Extract data variables
    extract($data);
    
    // Extract layout options as variables
    extract($options);
    
    // Capture the template content
    ob_start();
    $templatePath = __DIR__ . '/../templates/' . $templateName . '.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    } else {
        echo "<div class='error'>Template '$templateName' not found</div>";
    }
    $content = ob_get_clean();
    
    // Render with layout
    ob_start();
    include __DIR__ . '/../templates/layout.php';
    return ob_get_clean();
    }
}

/**
 * Render auth pages (login, register, etc.)
 */
if (!function_exists('renderAuthPage')) {
    function renderAuthPage($templateName, $data = []) {
    return renderWithLayout($templateName, $data, [
        'is_auth_page' => true,
        'show_sidebar' => false
    ]);
    }
}

/**
 * Render dashboard pages with sidebar
 */
if (!function_exists('renderDashboardPage')) {
    function renderDashboardPage($templateName, $data = [], $title = null) {
    return renderWithLayout($templateName, $data, [
        'show_sidebar' => true,
        'title' => $title ?: 'لوحة التحكم - سما البنيان التجارية'
    ]);
    }
}

/**
 * Render regular pages
 */
if (!function_exists('renderPage')) {
    function renderPage($templateName, $data = [], $title = null) {
    return renderWithLayout($templateName, $data, [
        'show_sidebar' => false,
        'title' => $title
    ]);
    }
}

/**
 * Check if user is authenticated
 */
if (!function_exists('isAuthenticated')) {
    function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

/**
 * Check if user has role
 */
if (!function_exists('hasRole')) {
    function hasRole($role) {
    return isAuthenticated() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
}

/**
 * Require authentication
 */
if (!function_exists('requireAuth')) {
    function requireAuth() {
    if (!isAuthenticated()) {
        $_SESSION['error'] = 'يجب تسجيل الدخول للوصول لهذه الصفحة';
        header('Location: /login');
        exit;
    }
    }
}

/**
 * Require specific role
 */
if (!function_exists('requireRole')) {
    function requireRole($role) {
    requireAuth();
    if (!hasRole($role)) {
        $_SESSION['error'] = 'ليس لديك صلاحية للوصول لهذه الصفحة';
        header('Location: /login');
        exit;
    }
    }
}

/**
 * Redirect with message
 */
if (!function_exists('redirectWithMessage')) {
    function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION[$type] = $message;
    header("Location: $url");
    exit;
    }
}

/**
 * Get current user
 */
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return (object)[
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'مستخدم',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'employee'
    ];
    }
}

/**
 * Get old input value
 */
if (!function_exists('old')) {
    function old($key, $default = '') {
        return $_SESSION['old'][$key] ?? $default;
    }
}

/**
 * Format date in Arabic
 */
if (!function_exists('formatArabicDate')) {
    function formatArabicDate($date) {
    if (!$date) return '';
    
    $timestamp = is_string($date) ? strtotime($date) : $date;
    return date('Y/m/d H:i', $timestamp);
    }
}

/**
 * Get status label in Arabic
 */
if (!function_exists('getStatusLabel')) {
    function getStatusLabel($status) {
    $statusLabels = [
        'draft' => 'مسودة',
        'pending' => 'قيد المراجعة',
        'approved' => 'موافق عليه',
        'rejected' => 'مرفوض',
        'signed' => 'موقع',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي'
    ];
    
    return $statusLabels[$status] ?? $status;
    }
}

/**
 * Get status class for CSS
 */
if (!function_exists('getStatusClass')) {
    function getStatusClass($status) {
    $statusClasses = [
        'draft' => 'status-draft',
        'pending' => 'status-pending',
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        'signed' => 'status-signed',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled'
    ];
    
    return $statusClasses[$status] ?? 'status-default';
    }
}
?>