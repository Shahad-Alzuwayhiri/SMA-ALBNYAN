<?php

namespace App\Helpers;

/**
 * Consolidated Helper Functions
 * دوال مساعدة موحدة لتطبيق سما البنيان لإدارة العقود
 * 
 * This file consolidates all duplicate functions found across the application
 * to eliminate code duplication and provide a single source of truth.
 */
class Functions
{
    // ===========================================
    // STATUS HELPER FUNCTIONS
    // دوال مساعدة لحالات العقود
    // ===========================================

    /**
     * Get status text in Arabic
     * @param string $status The status key
     * @return string Arabic status text
     */
    public static function getStatusText($status)
    {
        $statusMap = [
            'draft' => 'مسودة',
            'pending' => 'قيد المراجعة',
            'pending_review' => 'قيد المراجعة',
            'active' => 'معتمد',
            'approved' => 'موافق عليه',
            'rejected' => 'مرفوض',
            'signed' => 'موقع',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'expired' => 'منتهي الصلاحية'
        ];

        return $statusMap[$status] ?? $status;
    }

    /**
     * Get CSS class for status badge
     * @param string $status The status key
     * @return string Bootstrap CSS class
     */
    public static function getStatusClass($status)
    {
        $statusClasses = [
            'draft' => 'warning',
            'pending' => 'warning',
            'pending_review' => 'warning',
            'active' => 'success',
            'approved' => 'success', 
            'rejected' => 'danger',
            'signed' => 'primary',
            'completed' => 'primary',
            'cancelled' => 'danger',
            'expired' => 'secondary'
        ];

        return $statusClasses[$status] ?? 'secondary';
    }

    /**
     * Get status info with both text and class
     * @param string $status The status key
     * @return array With 'class' and 'text' keys
     */
    public static function getStatusInfo($status)
    {
        return [
            'class' => 'bg-' . self::getStatusClass($status),
            'text' => self::getStatusText($status)
        ];
    }

    /**
     * Get status badge HTML
     * @param string $status The status key
     * @return string Complete HTML badge
     */
    public static function getStatusBadge($status)
    {
        $info = self::getStatusInfo($status);
        return '<span class="badge ' . $info['class'] . '">' . $info['text'] . '</span>';
    }

    /**
     * Legacy compatibility - same as getStatusText
     * @param string $status The status key
     * @return string Arabic status text
     */
    public static function getStatusLabel($status)
    {
        return self::getStatusText($status);
    }

    // ===========================================
    // AUTHENTICATION HELPER FUNCTIONS
    // دوال مساعدة للمصادقة والتفويض
    // ===========================================

    /**
     * Check if user is authenticated
     * @return bool
     */
    public static function isAuthenticated()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Check if user has specific role
     * @param string $role The required role
     * @return bool
     */
    public static function hasRole($role)
    {
        return self::isAuthenticated() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }

    /**
     * Get current authenticated user data
     * @return object|null User object or null if not authenticated
     */
    public static function getCurrentUser()
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return (object)[
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'مستخدم',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'employee'
        ];
    }

    /**
     * Require authentication - redirect if not authenticated
     * @param string $redirectUrl Where to redirect if not authenticated
     */
    public static function requireAuth($redirectUrl = '/login')
    {
        if (!self::isAuthenticated()) {
            $_SESSION['error'] = 'يجب تسجيل الدخول للوصول لهذه الصفحة';
            header("Location: $redirectUrl");
            exit;
        }
    }

    /**
     * Require specific role - redirect if user doesn't have role
     * @param string $role Required role
     * @param string $redirectUrl Where to redirect if unauthorized
     */
    public static function requireRole($role, $redirectUrl = '/login')
    {
        self::requireAuth();
        
        if (!self::hasRole($role)) {
            $_SESSION['error'] = 'ليس لديك صلاحية للوصول لهذه الصفحة';
            
            // Smart redirect based on current user role
            $currentRole = $_SESSION['user_role'] ?? 'employee';
            if ($currentRole === 'manager') {
                header('Location: /manager-dashboard');
            } elseif ($currentRole === 'employee') {
                header('Location: /employee-dashboard');
            } else {
                header("Location: $redirectUrl");
            }
            exit;
        }
    }

    // ===========================================
    // CONFIGURATION FUNCTIONS
    // دوال الإعدادات
    // ===========================================

    /**
     * Get configuration value
     * @param string $key Configuration key (dot notation supported)
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function config($key, $default = null)
    {
        static $config = null;
        
        // Load configuration first time
        if ($config === null) {
            $configFile = __DIR__ . '/../../config/app.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
            } else {
                $config = [];
            }
        }
        
        // Split key into parts for nested access
        $keys = explode('.', $key);
        $value = $config;
        
        // Navigate through configuration
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }

    /**
     * Get environment variable
     * @param string $key Environment variable name
     * @param mixed $default Default value
     * @return mixed Environment value
     */
    public static function env($key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert string booleans to actual booleans
        if (in_array(strtolower($value), ['true', 'false'])) {
            return strtolower($value) === 'true';
        }
        
        // Convert null string to null
        if (strtolower($value) === 'null') {
            return null;
        }
        
        return $value;
    }

    /**
     * Get application name
     * @return string Application name
     */
    public static function appName()
    {
        return self::config('app.name', 'نظام إدارة العقود');
    }

    /**
     * Get application version
     * @return string Application version
     */
    public static function appVersion()
    {
        return self::config('app.version', '1.0.0');
    }

    /**
     * Check if debug mode is enabled
     * @return bool True if debug mode is on
     */
    public static function isDebugMode()
    {
        return self::config('app.debug', false);
    }

    // ===========================================
    // DATABASE HELPER FUNCTIONS
    // دوال مساعدة قاعدة البيانات
    // ===========================================

    /**
     * Get database connection
     * @return \PDO Database connection instance
     * @throws \PDOException If connection fails
     */
    public static function getDatabaseConnection()
    {
        static $pdo = null;
        
        if ($pdo === null) {
            // Load database configuration
            $dbConfig = require __DIR__ . '/../../config/database.php';
            $connectionName = $dbConfig['default'];
            
            // Auto-detect Hostinger environment
            if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'hostinger') !== false) {
                $connectionName = 'mysql';
            }
            
            $config = $dbConfig['connections'][$connectionName];
            
            try {
                if ($config['driver'] === 'sqlite') {
                    $dsn = "sqlite:" . $config['database'];
                    $pdo = new \PDO($dsn, null, null, $config['options']);
                    $pdo->exec('PRAGMA foreign_keys = ON');
                } else {
                    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
                    $pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);
                }
            } catch (\PDOException $e) {
                throw new \PDOException("Database connection failed: " . $e->getMessage());
            }
        }
        
        return $pdo;
    }

    // ===========================================
    // UTILITY FUNCTIONS
    // دوال الأدوات المساعدة
    // ===========================================

    /**
     * Format date in Arabic locale
     * @param string|int $date Date string or timestamp
     * @return string Formatted Arabic date
     */
    public static function formatArabicDate($date)
    {
        if (!$date) return '';
        
        $timestamp = is_string($date) ? strtotime($date) : $date;
        return date('Y/m/d H:i', $timestamp);
    }

    /**
     * Format file size in human readable format
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    public static function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return number_format($bytes / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
}