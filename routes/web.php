<?php
/**
 * Simple Switch-Based Router
 * موجه بسيط باستخدام Switch
 * 
 * SMA ALBNYAN Contract Management System
 * نظام إدارة العقود - شركة سما البنيان التجارية
 */

use App\Controllers\AuthController;
use App\Controllers\ContractController;
use App\Controllers\DashboardController;
use App\Controllers\EmployeeController;
use App\Controllers\HomeController;
use App\Controllers\NotificationController;
use App\Controllers\DebugController;
use App\Controllers\LinkCheckerController;

// Get the request URI and method
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove trailing slash except for root
if ($requestUri !== '/' && substr($requestUri, -1) === '/') {
    $requestUri = rtrim($requestUri, '/');
}

// Extract path segments for parameterized routes
$segments = explode('/', trim($requestUri, '/'));

// Route matching with switch statement
switch ($requestMethod . ' ' . $requestUri) {
    
    // ===== HOME ROUTES =====
    case 'GET /':
        $controller = new HomeController();
        $controller->index();
        break;
        
    case 'GET /welcome':
        $controller = new HomeController();
        $controller->welcome();
        break;
        
    case 'GET /login-pages':
        $controller = new HomeController();
        $controller->loginPages();
        break;
    
    // ===== AUTHENTICATION ROUTES =====
    case 'GET /login':
        $controller = new AuthController();
        $controller->showLogin();
        break;
        
    case 'POST /login':
        $controller = new AuthController();
        $controller->login();
        break;
        
    case 'GET /signup':
        $controller = new AuthController();
        $controller->showSignup();
        break;
        
    case 'POST /signup':
        $controller = new AuthController();
        $controller->signup();
        break;
        
    case 'GET /logout':
        $controller = new AuthController();
        $controller->logout();
        break;
    
    // ===== DASHBOARD ROUTES =====
    case 'GET /manager-dashboard':
        $controller = new DashboardController();
        $controller->manager();
        break;
        
    case 'GET /employee-dashboard':
        $controller = new DashboardController();
        $controller->employee();
        break;
        
    // Legacy dashboard redirect
    case 'GET /dashboard':
        header('Location: /');
        exit;
    
    // ===== CONTRACT ROUTES =====
    case 'GET /contracts':
    case 'GET /contracts_list':
    case 'GET /contracts_list.php':
        $controller = new ContractController();
        $controller->index();
        break;
        
    case 'GET /contracts/create':
        $controller = new ContractController();
        $controller->create();
        break;
        
    case 'POST /contracts/create':
        $controller = new ContractController();
        $controller->create();
        break;
        
    // Investment Contract Routes (using existing methods)
    case 'GET /investment-contracts':
        $controller = new ContractController();
        $controller->index();
        break;
        
    case 'GET /investment-contracts/create':
    case 'GET /property-investment/create':
        $controller = new ContractController();
        $controller->create();
        break;
        
    case 'POST /investment-contracts/create':
    case 'POST /property-investment/create':
        $controller = new ContractController();
        $controller->create();
        break;
    
    // ===== EMPLOYEE MANAGEMENT ROUTES =====
    case 'GET /manage_employees':
    case 'GET /manage_employees.php':
        $controller = new EmployeeController();
        $controller->index();
        break;
    
    // ===== NOTIFICATION ROUTES =====
    case 'GET /notifications':
    case 'GET /notifications.php':
        $controller = new NotificationController();
        $controller->index();
        break;
    
    // ===== DEBUG & SYSTEM ROUTES =====
    case 'GET /debug':
    case 'GET /system-info':
        $controller = new DebugController();
        $controller->index();
        break;
        
    case 'GET /debug-status':
        // Show debug configuration status
        require_once __DIR__ . '/../debug_status.php';
        break;
        
    case 'GET /status':
        $controller = new DebugController();
        $controller->status();
        break;
        
    case 'GET /phpinfo':
        $controller = new DebugController();
        $controller->phpinfo();
        break;
        
    case 'GET /check-links':
        $controller = new LinkCheckerController();
        $controller->index();
        break;
    
    // ===== PARAMETERIZED ROUTES =====
    default:
        // Handle parameterized routes
        if (count($segments) >= 2 && $segments[0] === 'contracts') {
            $contractId = $segments[1];
            $controller = new ContractController();
            
            if (count($segments) === 2) {
                // GET /contracts/{id}
                if ($requestMethod === 'GET') {
                    $controller->show($contractId);
                    break;
                }
            } elseif (count($segments) === 3) {
                $action = $segments[2];
                
                switch ($requestMethod . ' ' . $action) {
                    case 'GET edit':
                        // GET /contracts/{id}/edit
                        $controller->edit($contractId);
                        break;
                        
                    case 'POST edit':
                        // POST /contracts/{id}/edit
                        $controller->edit($contractId);
                        break;
                        
                    case 'GET pdf':
                        // GET /contracts/{id}/pdf
                        $controller->exportPdf($contractId);
                        break;
                        
                    default:
                        // 404 Not Found
                        http_response_code(404);
                        echo "<h1>404 - الصفحة غير موجودة</h1>";
                        echo "<p>الصفحة المطلوبة غير موجودة.</p>";
                        echo "<a href='/'>العودة للصفحة الرئيسية</a>";
                        break;
                }
                break;
            }
        }
        
        // If no route matches, show 404
        http_response_code(404);
        echo "<h1>404 - الصفحة غير موجودة</h1>";
        echo "<p>الصفحة المطلوبة غير موجودة.</p>";
        echo "<a href='/'>العودة للصفحة الرئيسية</a>";
        break;
}