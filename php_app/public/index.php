<?php
// Simple PHP application bootstrap
// This will work without full Laravel framework

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('Asia/Riyadh');

// Start session
session_start();

// Include helper functions (Laravel-like functions)
require_once __DIR__ . '/../app/helpers.php';

// Include new template helpers
if (file_exists(__DIR__ . '/../includes/helpers.php')) {
    require_once __DIR__ . '/../includes/helpers.php';
}

// Basic autoloader for our classes
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $file = __DIR__ . '/../' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Helper functions to simulate Laravel
if (!function_exists('response')) {
    function response($content, $status = 200, $headers = []) {
        http_response_code($status);
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        return $content;
    }
}

if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return __DIR__ . '/../storage/' . ltrim($path, '/');
    }
}

if (!function_exists('public_path')) {
    function public_path($path = '') {
        return __DIR__ . '/' . ltrim($path, '/');
    }
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

// Laravel-like helper functions
if (!function_exists('view')) {
    function view($template, $data = []) {
        // First try to load from templates directory (new system)
        $templateFile = __DIR__ . '/../templates/' . str_replace('.', '/', $template) . '.php';
        if (file_exists($templateFile)) {
            extract($data);
            ob_start();
            include $templateFile;
            return ob_get_clean();
        }
        
        // Fallback: Return error message for missing views
        return "View [$template] not found in either templates or resources/views directories.";
    }
}

if (!function_exists('redirect')) {
    function redirect() {
        return new class {
            public function route($name, $params = []) {
                $routes = [
                    'dashboard' => '/',
                    'login' => '/login',
                    'register' => '/register',
                    'contracts.index' => '/contracts',
                    'contracts.create' => '/contracts/create',
                ];
                $url = $routes[$name] ?? '/';
                header("Location: $url");
                exit;
            }
        };
    }
}

if (!function_exists('back')) {
    function back() {
        return new class {
            public function with($key, $message) {
                $_SESSION['flash'][$key] = $message;
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }
            public function withErrors($errors) {
                $_SESSION['errors'] = $errors;
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }
        };
    }
}

if (!function_exists('route')) {
    function route($name, $params = []) {
        $routes = [
            'dashboard' => '/',
            'manager.dashboard' => '/manager-dashboard',
            'login' => '/login',
            'register' => '/register',
            'forgot-password' => '/forgot-password',
            'password.request' => '/forgot-password',
            'reset-password' => '/reset-password',
            'logout' => '/logout',
            'profile' => '/profile',
            'notifications' => '/notifications',
            'contracts.index' => '/contracts',
            'contracts.create' => '/contracts/create',
            'contracts.show' => '/contracts/' . ($params[0] ?? ''),
            'contracts.pdf' => '/contracts/' . ($params[0] ?? '') . '/pdf',
            'contracts.in-progress' => '/contracts-in-progress',
            'contracts.closed' => '/contracts-closed',
        ];
        return $routes[$name] ?? '/';
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('session')) {
    function session($key = null) {
        if ($key === null) {
            return $_SESSION;
        }
        return $_SESSION[$key] ?? null;
    }
}

if (!function_exists('old')) {
    function old($key, $default = '') {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('auth')) {
    function auth() {
        return new class {
            public function user() {
                if (isset($_SESSION['user_id'])) {
                    return (object)[
                        'id' => $_SESSION['user_id'],
                        'name' => $_SESSION['user_name'] ?? 'مستخدم',
                        'email' => $_SESSION['user_email'] ?? '',
                        'role' => $_SESSION['user_role'] ?? 'employee',
                    ];
                }
                return null;
            }
            public function check() {
                return isset($_SESSION['user_id']);
            }
        };
    }
}

if (!function_exists('now')) {
    function now() {
        return new class {
            public function subDays($days) {
                return (object)['created_at' => date('Y-m-d H:i:s', strtotime("-$days days"))];
            }
            public function subHours($hours) {
                return (object)['created_at' => date('Y-m-d H:i:s', strtotime("-$hours hours"))];
            }
            public function subMonths($months) {
                return (object)['created_at' => date('Y-m-d H:i:s', strtotime("-$months months"))];
            }
            public function subMinutes($minutes) {
                return (object)['created_at' => date('Y-m-d H:i:s', strtotime("-$minutes minutes"))];
            }
            public function format($format) {
                return date($format);
            }
        };
    }
}

if (!function_exists('compact')) {
    function compact() {
        $args = func_get_args();
        $result = [];
        foreach ($args as $arg) {
            if (isset($GLOBALS[$arg])) {
                $result[$arg] = $GLOBALS[$arg];
            }
        }
        return $result;
    }
}

// Load environment variables if .env exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Simple routing
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Clean up path - remove leading/trailing slashes and normalize
$path = trim($path, '/');

// Handle VS Code browser requests and other parameters gracefully
if (empty($path) || $path === 'index.php') {
    $path = '';
}

// Debug information
error_log("Request URI: " . $request_uri);
error_log("Parsed Path: " . $path);
error_log("Method: " . $method);

// Global error handler for database issues
function handleDatabaseError($error) {
    $error_details = $error;
    ob_start();
    include __DIR__ . '/../templates/error_database.php';
    echo ob_get_clean();
    exit;
}

try {
    // Route handling
    if ($path === '' || $path === 'index') {
        // صفحة الترحيب التي تشرح النظام
        ob_start();
        include __DIR__ . '/../templates/simple_welcome.php';
        echo ob_get_clean();
        exit;
        
    } elseif ($path === 'dashboard') {
        require_once __DIR__ . '/../app/Http/Controllers/SimpleDashboardController.php';
        $controller = new SimpleDashboardController();
        echo $controller->index();
        
    } elseif ($path === 'manager-dashboard') {
        require_once __DIR__ . '/../app/Http/Controllers/SimpleDashboardController.php';
        $controller = new SimpleDashboardController();
        echo $controller->managerDashboard();
        
    } elseif ($path === 'login') {
        require_once __DIR__ . '/../app/Http/Controllers/SimpleAuthController.php';
        $controller = new SimpleAuthController();
        if ($method === 'GET') {
            echo $controller->showLoginForm();
        } elseif ($method === 'POST') {
            $controller->login();
        }
        
    } elseif ($path === 'register') {
        require_once __DIR__ . '/../app/Http/Controllers/SimpleAuthController.php';
        $controller = new SimpleAuthController();
        if ($method === 'GET') {
            echo $controller->showRegisterForm();
        } elseif ($method === 'POST') {
            $controller->register();
        }
        
    } elseif ($path === 'forgot-password') {
        require_once __DIR__ . '/../app/Http/Controllers/SimpleAuthController.php';
        $controller = new SimpleAuthController();
        if ($method === 'GET') {
            echo $controller->showForgotPasswordForm();
        } elseif ($method === 'POST') {
            $controller->forgotPassword();
        }
        
    } elseif ($path === 'reset-password') {
        require_once __DIR__ . '/../app/Http/Controllers/SimpleAuthController.php';
        $controller = new SimpleAuthController();
        if ($method === 'GET') {
            echo $controller->showResetPasswordForm();
        } elseif ($method === 'POST') {
            $controller->resetPassword();
        }
        
    } elseif ($path === 'logout') {
        require_once __DIR__ . '/../app/Http/Controllers/SimpleAuthController.php';
        $controller = new SimpleAuthController();
        $controller->logout();
        
    } elseif ($path === 'profile') {
        require_once __DIR__ . '/../app/Http/Controllers/SimpleAuthController.php';
        $controller = new SimpleAuthController();
        echo $controller->profile();
        
    } elseif ($path === 'employee-dashboard') {
        // التحقق من صلاحيات الموظف
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employee') {
            $_SESSION['error'] = 'ليس لديك صلاحية للوصول لهذه الصفحة';
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../app/Http/Controllers/ContractController.php';
        $controller = new ContractController();
        echo $controller->showEmployeeDashboard();
        
    } elseif ($path === 'contracts') {
        // صفحة العقود العامة
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // توجيه المستخدم حسب دوره
        if ($_SESSION['user_role'] === 'manager') {
            require_once __DIR__ . '/../app/Http/Controllers/ContractController.php';
            $controller = new ContractController();
            echo $controller->showManagerDashboard();
        } else {
            require_once __DIR__ . '/../app/Http/Controllers/ContractController.php';
            $controller = new ContractController();
            echo $controller->showEmployeeDashboard();
        }
        
    } elseif ($path === 'contracts/create') {
        // صفحة إنشاء عقد جديد
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        require_once __DIR__ . '/../app/Http/Controllers/ContractController.php';
        $controller = new ContractController();
        if ($method === 'GET') {
            include __DIR__ . '/create_contract.php';
        } elseif ($method === 'POST') {
            $controller->createContract();
        }
        
    // API Routes for Contracts
    } elseif ($path === '/api/contracts/manager' && $method === 'GET') {
        require_once __DIR__ . '/../app/Http/Controllers/ContractController.php';
        $controller = new ContractController();
        $controller->getContractsForManager();
        
    } elseif ($path === '/api/contracts/employee' && $method === 'GET') {
        require_once __DIR__ . '/../app/Http/Controllers/ContractController.php';
        $controller = new ContractController();
        $controller->getContractsForEmployee();
        
    } elseif ($path === '/api/contracts' && $method === 'POST') {
        require_once __DIR__ . '/../app/Http/Controllers/ContractController.php';
        $controller = new ContractController();
        $controller->createContract();
        
    } elseif (preg_match('/^\/api\/contracts\/(\d+)$/', $path, $matches) && $method === 'PUT') {
        $contractId = $matches[1];
        require_once __DIR__ . '/../app/Http/Controllers/ContractController.php';
        $controller = new ContractController();
        $controller->updateContract($contractId);
        
    } elseif (preg_match('/^\/api\/contracts\/(\d+)\/submit-review$/', $path, $matches) && $method === 'POST') {
        $contractId = $matches[1];
        require_once __DIR__ . '/../app/Http/Controllers/ContractController.php';
        $controller = new ContractController();
        $controller->submitForReview($contractId);
        
    } elseif (preg_match('/^\/api\/contracts\/(\d+)\/sign$/', $path, $matches) && $method === 'POST') {
        $contractId = $matches[1];
        require_once __DIR__ . '/../app/Http/Controllers/SignatureController.php';
        $controller = new SignatureController();
        $controller->signContract($contractId);
        
    } elseif (preg_match('/^\/api\/contracts\/(\d+)\/reject$/', $path, $matches) && $method === 'POST') {
        $contractId = $matches[1];
        require_once __DIR__ . '/../app/Http/Controllers/SignatureController.php';
        $controller = new SignatureController();
        $controller->rejectContract($contractId);
        
    // API Routes for Employees
    } elseif ($path === '/api/employees' && $method === 'GET') {
        require_once __DIR__ . '/../app/Http/Controllers/EmployeeController.php';
        $controller = new EmployeeController();
        $controller->getAllEmployees();
        
    } elseif ($path === '/api/employees' && $method === 'POST') {
        require_once __DIR__ . '/../app/Http/Controllers/EmployeeController.php';
        $controller = new EmployeeController();
        $controller->addEmployee();
        
    } elseif (preg_match('/^\/api\/employees\/(\d+)\/permissions$/', $path, $matches) && $method === 'PUT') {
        $userId = $matches[1];
        require_once __DIR__ . '/../app/Http/Controllers/EmployeeController.php';
        $controller = new EmployeeController();
        $controller->updatePermissions($userId);
        
    } elseif (preg_match('/^\/api\/employees\/(\d+)\/deactivate$/', $path, $matches) && $method === 'POST') {
        $userId = $matches[1];
        require_once __DIR__ . '/../app/Http/Controllers/EmployeeController.php';
        $controller = new EmployeeController();
        $controller->deactivateEmployee($userId);
        
    } elseif (preg_match('/^\/api\/employees\/(\d+)\/activate$/', $path, $matches) && $method === 'POST') {
        $userId = $matches[1];
        require_once __DIR__ . '/../app/Http/Controllers/EmployeeController.php';
        $controller = new EmployeeController();
        $controller->activateEmployee($userId);
        
    // API Routes for Signatures
    } elseif ($path === '/api/signatures/upload' && $method === 'POST') {
        require_once __DIR__ . '/../app/Http/Controllers/SignatureController.php';
        $controller = new SignatureController();
        $controller->uploadSignature();
        
    } elseif ($path === '/api/signatures/saved' && $method === 'GET') {
        require_once __DIR__ . '/../app/Http/Controllers/SignatureController.php';
        $controller = new SignatureController();
        $controller->getSavedSignatures();
        
    } elseif ($path === '/api/signatures/pending' && $method === 'GET') {
        require_once __DIR__ . '/../app/Http/Controllers/SignatureController.php';
        $controller = new SignatureController();
        $controller->getPendingSignatures();
        
    } elseif ($path === '/notifications') {
        include __DIR__ . '/notifications.php';
        
    } elseif ($path === '/employees') {
        include __DIR__ . '/manage_employees.php';
        
    } elseif ($path === '/contracts') {
        require_once __DIR__ . '/../app/Http/Controllers/SimpleContractController.php';
        $controller = new SimpleContractController();
        echo $controller->index();
        
    } elseif ($path === '/contracts/create') {
        require_once __DIR__ . '/../app/Http/Controllers/SimpleContractController.php';
        $controller = new SimpleContractController();
        if ($method === 'GET') {
            echo $controller->create();
        } elseif ($method === 'POST') {
            $controller->store();
        }
        
    } elseif ($path === 'contracts/create-detailed') {
        require_once __DIR__ . '/../controllers/DetailedContractController.php';
        $controller = new DetailedContractController();
        if ($method === 'GET') {
            echo $controller->create();
        } elseif ($method === 'POST') {
            $controller->store();
        }
        
    } elseif ($path === 'contracts/import-text') {
        require_once __DIR__ . '/../controllers/DetailedContractController.php';
        $controller = new DetailedContractController();
        if ($method === 'GET') {
            echo $controller->importFromText();
        } elseif ($method === 'POST') {
            $controller->importFromText();
        }
        
    } elseif (preg_match('/^contracts\/view-detailed\/(\d+)$/', $path, $matches)) {
        $id = $matches[1];
        require_once __DIR__ . '/../controllers/DetailedContractController.php';
        $controller = new DetailedContractController();
        echo $controller->view($id);
        
    } elseif (preg_match('/^contracts\/update-detailed\/(\d+)$/', $path, $matches)) {
        $id = $matches[1];
        require_once __DIR__ . '/../controllers/DetailedContractController.php';
        $controller = new DetailedContractController();
        if ($method === 'POST') {
            $controller->update($id);
        } else {
            header('Location: /contracts/view-detailed/' . $id);
        }
        
    } elseif (preg_match('/^contracts\/delete-detailed\/(\d+)$/', $path, $matches)) {
        $id = $matches[1];
        require_once __DIR__ . '/../controllers/DetailedContractController.php';
        $controller = new DetailedContractController();
        $controller->delete($id);
        
    } elseif (preg_match('/^contracts\/export-pdf\/(\d+)$/', $path, $matches)) {
        $id = $matches[1];
        require_once __DIR__ . '/../controllers/DetailedContractController.php';
        $controller = new DetailedContractController();
        $controller->exportPdf($id);
        
    } elseif ($path === '/contracts-in-progress') {
        require_once __DIR__ . '/../app/Http/Controllers/SimpleContractController.php';
        $controller = new SimpleContractController();
        echo $controller->inProgress();
        
    } elseif ($path === '/contracts-closed') {
        require_once __DIR__ . '/../app/Http/Controllers/SimpleContractController.php';
        $controller = new SimpleContractController();
        echo $controller->closed();
        
    } elseif (preg_match('/^\/contracts\/(\d+)$/', $path, $matches)) {
        $id = $matches[1];
        require_once __DIR__ . '/../app/Http/Controllers/SimpleContractController.php';
        $controller = new SimpleContractController();
        echo $controller->show($id);
        
    } elseif (preg_match('/^\/contracts\/(\d+)\/pdf$/', $path, $matches)) {
        $id = $matches[1];
        require_once __DIR__ . '/../app/Http/Controllers/SimpleContractController.php';
        $controller = new SimpleContractController();
        echo $controller->pdf($id);
        
    } elseif (preg_match('/^\/test-pdf\/(\d+)$/', $path, $matches)) {
        // Keep the original test PDF route
        $id = $matches[1];
        
        // Include PdfService
        require_once __DIR__ . '/../app/Services/PdfService.php';
        
        // Sample contract data
        $contractData = [
            'contract_number' => 'CT-' . str_pad($id, 4, '0', STR_PAD_LEFT),
            'partner2_name' => 'أحمد محمد العلي',
            'partner_name' => 'أحمد محمد العلي', 
            'partner_id' => '1234567890',
            'partner_phone' => '+966501234567',
            'client_address' => 'الرياض، المملكة العربية السعودية',
            'investment_amount' => 100000,
            'capital_amount' => 90000,
            'profit_percent' => 15,
            'profit_interval_months' => 3,
            'withdrawal_notice_days' => 30,
            'start_date_h' => date('Y-m-d'),
            'end_date_h' => date('Y-m-d', strtotime('+1 year')),
            'commission_percent' => 2,
            'exit_notice_days' => 30,
            'penalty_amount' => 5000,
        ];

        $pdfService = new \App\Services\PdfService();
        
        // Try PDF generation first
        $pdfContent = $pdfService->generateContractPdf($contractData);
        
        if ($pdfContent !== false) {
            // Success - return PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="contract_' . $contractData['contract_number'] . '.pdf"');
            echo $pdfContent;
        } else {
            // Fallback to HTML version
            $htmlContent = $pdfService->generateHtmlContract($contractData);
            header('Content-Type: text/html; charset=utf-8');
            echo $htmlContent;
        }
        
    } else {
        // Serve static files
        $staticFile = __DIR__ . $path;
        if (file_exists($staticFile) && is_file($staticFile)) {
            $ext = pathinfo($staticFile, PATHINFO_EXTENSION);
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'ttf' => 'font/ttf',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
            ];
            
            if (isset($mimeTypes[$ext])) {
                header('Content-Type: ' . $mimeTypes[$ext]);
            }
            readfile($staticFile);
        } else {
            http_response_code(404);
            if (function_exists('renderPage')) {
                echo renderPage('404', [], 'الصفحة غير موجودة - 404');
            } else {
                echo "<h1>404 - الصفحة غير موجودة</h1>";
                echo "<p><a href='/'>العودة للرئيسية</a></p>";
            }
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "<h1>خطأ في الخادم</h1>";
    echo "<p>تفاصيل الخطأ: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='/'>العودة للرئيسية</a></p>";
}