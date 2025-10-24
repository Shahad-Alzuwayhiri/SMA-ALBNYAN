<?php
/**
 * نظام التوجيه البسيط للمشروع
 * بديل لـ Laravel routes
 */

// تضمين المساعدات
require_once __DIR__ . '/../config/helpers.php';

/**
 * معالج الطلبات الأساسي
 */
function handleRequest() {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    
    // إزالة البادئة إن وجدت
    $uri = trim($uri, '/');
    
    // الصفحة الرئيسية
    if (empty($uri) || $uri === 'index.php') {
        if (isset($_SESSION['user_id'])) {
            header('Location: /dashboard.php');
        } else {
            header('Location: /login.php');
        }
        exit;
    }
    
    // توجيه الطلبات إلى الملفات المناسبة
    $routes = [
        // صفحات المصادقة
        'login' => 'login.php',
        'register' => 'register.php',
        'logout' => 'logout.php',
        
        // لوحات التحكم
        'dashboard' => 'dashboard.php',
        'manager-dashboard' => 'manager_dashboard.php',
        'employee-dashboard' => 'employee_dashboard.php',
        
        // العقود
        'contracts' => 'contracts_list.php',
        'contracts-create' => 'create_contract.php',
        'contracts-view' => 'view_contract.php',
        'contracts-edit' => 'edit_contract.php',
        'export-pdf' => 'export_pdf.php',
        'test-pdf' => 'test_pdf.php',
        
        // أخرى
        'profile' => 'profile.php',
        'notifications' => 'notifications.php',
    ];
    
    // البحث عن المسار المطابق
    if (isset($routes[$uri])) {
        $file = __DIR__ . '/../' . $routes[$uri];
        if (file_exists($file)) {
            include $file;
            return;
        }
    }
    
    // التحقق من ملف مباشر
    $directFile = __DIR__ . '/../' . $uri;
    if (file_exists($directFile) && pathinfo($directFile, PATHINFO_EXTENSION) === 'php') {
        include $directFile;
        return;
    }
    
    // صفحة 404
    http_response_code(404);
    echo "<h1>404 - الصفحة غير موجودة</h1>";
    echo "<p>المسار المطلوب: " . htmlspecialchars($uri) . "</p>";
    echo '<a href="/dashboard.php">العودة للرئيسية</a>';
}

// تشغيل المعالج إذا تم استدعاء الملف مباشرة
if (basename($_SERVER['SCRIPT_NAME']) === 'routes.php') {
    session_start();
    handleRequest();
}