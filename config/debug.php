<?php
/**
 * Debug Configuration
 * إعدادات التصحيح
 * 
 * SMA ALBNYAN Contract Management System
 * نظام إدارة العقود - شركة سما البنيان التجارية
 */

class DebugConfig
{
    private static $isProduction = null;
    private static $logFile = null;
    
    /**
     * Initialize debug configuration
     * تهيئة إعدادات التصحيح
     */
    public static function init()
    {
        self::detectEnvironment();
        self::setupErrorHandling();
        self::ensureLogDirectory();
    }
    
    /**
     * Detect if running in production or development environment
     * تحديد بيئة التشغيل (إنتاج أو تطوير)
     */
    private static function detectEnvironment()
    {
        // Check for explicit environment variable first
        if (isset($_ENV['APP_ENV'])) {
            self::$isProduction = $_ENV['APP_ENV'] === 'production';
            return;
        }
        
        // Check for common localhost indicators
        $localhostHosts = [
            'localhost',
            '127.0.0.1',
            '::1',
            'localhost:8000',
            'localhost:8080',
            '127.0.0.1:8000',
            '127.0.0.1:8080'
        ];
        
        // Check HTTP_HOST
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            self::$isProduction = !in_array($host, $localhostHosts) && 
                                  !preg_match('/^192\.168\./', $host) && 
                                  !preg_match('/^10\./', $host) && 
                                  !preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $host);
        } else {
            // CLI environment - assume development
            self::$isProduction = false;
        }
        
        // Additional check for common development indicators
        if (isset($_SERVER['SERVER_NAME'])) {
            $serverName = $_SERVER['SERVER_NAME'];
            if (in_array($serverName, $localhostHosts) || 
                strpos($serverName, '.local') !== false ||
                strpos($serverName, '.dev') !== false ||
                strpos($serverName, '.test') !== false) {
                self::$isProduction = false;
            }
        }
    }
    
    /**
     * Setup error handling based on environment
     * إعداد معالجة الأخطاء حسب البيئة
     */
    private static function setupErrorHandling()
    {
        // Set error log file path
        self::$logFile = __DIR__ . '/../storage/logs/php-error.log';
        
        if (self::$isProduction) {
            // Production Environment Settings
            error_reporting(E_ALL);
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            ini_set('log_errors', '1');
            ini_set('error_log', self::$logFile);
            
            // Additional production settings
            ini_set('log_errors_max_len', '0');
            ini_set('ignore_repeated_errors', '1');
            ini_set('ignore_repeated_source', '1');
            
            // Set custom error handler for production
            set_error_handler([self::class, 'productionErrorHandler']);
            set_exception_handler([self::class, 'productionExceptionHandler']);
            
        } else {
            // Development Environment Settings
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            ini_set('log_errors', '1');
            ini_set('error_log', self::$logFile);
            
            // Additional development settings for better debugging
            ini_set('html_errors', '1');
            ini_set('docref_root', 'http://php.net/manual/en/');
            ini_set('docref_ext', '.php');
        }
    }
    
    /**
     * Ensure log directory exists and is writable
     * التأكد من وجود مجلد السجلات وإمكانية الكتابة فيه
     */
    private static function ensureLogDirectory()
    {
        $logDir = dirname(self::$logFile);
        
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                error_log("Failed to create log directory: {$logDir}");
                return false;
            }
        }
        
        if (!is_writable($logDir)) {
            error_log("Log directory is not writable: {$logDir}");
            return false;
        }
        
        // Create .htaccess to protect log files
        $htaccessPath = $logDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "Order deny,allow\nDeny from all\n";
            file_put_contents($htaccessPath, $htaccessContent);
        }
        
        return true;
    }
    
    /**
     * Custom error handler for production
     * معالج أخطاء مخصص للإنتاج
     */
    public static function productionErrorHandler($errno, $errstr, $errfile, $errline)
    {
        $errorTypes = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Standards',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];
        
        $errorType = $errorTypes[$errno] ?? 'Unknown Error';
        
        $logMessage = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $errorType,
            $errstr,
            $errfile,
            $errline
        );
        
        error_log($logMessage, 3, self::$logFile);
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Custom exception handler for production
     * معالج استثناءات مخصص للإنتاج
     */
    public static function productionExceptionHandler($exception)
    {
        $logMessage = sprintf(
            "[%s] Uncaught Exception: %s in %s on line %d\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        error_log($logMessage, 3, self::$logFile);
        
        // Show generic error page to user
        http_response_code(500);
        if (headers_sent() === false) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        echo '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطأ في النظام</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-container { max-width: 600px; margin: 0 auto; }
        h1 { color: #d32f2f; }
        p { color: #666; }
        .btn { background: #1976d2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>حدث خطأ في النظام</h1>
        <p>نعتذر، حدث خطأ غير متوقع. تم تسجيل الخطأ وسيتم إصلاحه قريباً.</p>
        <a href="/" class="btn">العودة للصفحة الرئيسية</a>
    </div>
</body>
</html>';
        
        exit();
    }
    
    /**
     * Check if running in production environment
     * التحقق من بيئة الإنتاج
     */
    public static function isProduction()
    {
        return self::$isProduction ?? false;
    }
    
    /**
     * Check if running in development environment
     * التحقق من بيئة التطوير
     */
    public static function isDevelopment()
    {
        return !self::isProduction();
    }
    
    /**
     * Get log file path
     * الحصول على مسار ملف السجل
     */
    public static function getLogFile()
    {
        return self::$logFile;
    }
    
    /**
     * Log a custom message
     * تسجيل رسالة مخصصة
     */
    public static function log($message, $level = 'INFO')
    {
        $logMessage = sprintf(
            "[%s] %s: %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message
        );
        
        error_log($logMessage, 3, self::$logFile);
    }
}