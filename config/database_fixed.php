<?php
/**
 * Database Configuration Enhanced - XAMPP Compatible
 * إعدادات قاعدة البيانات المحسنة - متوافقة مع XAMPP
 */

// Database configuration array
$dbConfig = [
    'default' => 'sqlite',
    
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../database/contracts.db',
            'prefix' => '',
            'foreign_key_constraints' => true,
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ],
        
        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_DATABASE') ?: 'contractsama', // XAMPP default
            'username' => getenv('DB_USERNAME') ?: 'root', // XAMPP default
            'password' => getenv('DB_PASSWORD') ?: '', // XAMPP default (no password)
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ]
    ]
];

/**
 * Database Connection Helper
 * مساعد الاتصال بقاعدة البيانات
 */
if (!function_exists('getDatabaseConnection')) {
    function getDatabaseConnection() {
        global $dbConfig;
        $connectionName = $dbConfig['default'];
    
        // Auto-detect environment for Hostinger
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'hostinger') !== false) {
            $connectionName = 'mysql';
        }
        
        $dbConnectionConfig = $dbConfig['connections'][$connectionName];
        
        try {
            if ($dbConnectionConfig['driver'] === 'sqlite') {
                $dsn = "sqlite:" . $dbConnectionConfig['database'];
                $pdo = new PDO($dsn, null, null, $dbConnectionConfig['options']);
                $pdo->exec('PRAGMA foreign_keys = ON');
            } else {
                $dsn = "mysql:host={$dbConnectionConfig['host']};port={$dbConnectionConfig['port']};dbname={$dbConnectionConfig['database']};charset={$dbConnectionConfig['charset']}";
                $pdo = new PDO($dsn, $dbConnectionConfig['username'], $dbConnectionConfig['password'], $dbConnectionConfig['options']);
            }
            
            return $pdo;
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }
}

// Create PDO connection and make it available globally
try {
    $pdo = getDatabaseConnection();
    $GLOBALS['pdo'] = $pdo;
} catch (PDOException $e) {
    // Log error details for debugging
    error_log("Database connection failed: " . $e->getMessage());
    
    // Create a simple error page for production
    if (!defined('DEBUG_MODE')) {
        define('DEBUG_MODE', false);
    }
    
    if (DEBUG_MODE) {
        die("Database Connection Error: " . $e->getMessage());
    } else {
        // For production, set $pdo to null and let the application handle it gracefully
        $pdo = null;
        $GLOBALS['pdo'] = null;
    }
}

// Make $dbConfig available globally for backward compatibility
$GLOBALS['dbConfig'] = $dbConfig;

// Helper functions for database operations
if (!function_exists('getDB')) {
    function getDB() {
        return $GLOBALS['pdo'] ?? getDatabaseConnection();
    }
}

if (!function_exists('executeQuery')) {
    function executeQuery($sql, $params = []) {
        $pdo = getDB();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

if (!function_exists('fetchOne')) {
    function fetchOne($sql, $params = []) {
        $stmt = executeQuery($sql, $params);
        return $stmt->fetch();
    }
}

if (!function_exists('fetchAll')) {
    function fetchAll($sql, $params = []) {
        $stmt = executeQuery($sql, $params);
        return $stmt->fetchAll();
    }
}

if (!function_exists('getLastInsertId')) {
    function getLastInsertId() {
        return getDB()->lastInsertId();
    }
}

// Return the configuration for direct use
return $dbConfig;
?>