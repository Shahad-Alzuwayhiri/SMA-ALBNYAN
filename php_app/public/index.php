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

// Basic autoloader for our classes
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $file = __DIR__ . '/../' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Helper functions
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

try {
    switch ($path) {
        case '/':
            echo "<h1>مرحباً بك في ContractSama</h1>";
            echo "<p><a href='/test-pdf/123'>اختبار توليد PDF</a></p>";
            break;
            
        case (preg_match('/^\/test-pdf\/(\d+)$/', $path, $matches) ? true : false):
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
            break;
            
        default:
            http_response_code(404);
            echo "<h1>404 - الصفحة غير موجودة</h1>";
            echo "<p><a href='/'>العودة للرئيسية</a></p>";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "<h1>خطأ في الخادم</h1>";
    echo "<p>تفاصيل الخطأ: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='/'>العودة للرئيسية</a></p>";
}