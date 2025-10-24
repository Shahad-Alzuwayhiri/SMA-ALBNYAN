<?php
/**
 * Database Configuration
 * إعدادات قاعدة البيانات
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
            'database' => getenv('DB_DATABASE') ?: 'u123456789_contractsama', // Hostinger format
            'username' => getenv('DB_USERNAME') ?: 'u123456789_contracts', // Hostinger format
            'password' => getenv('DB_PASSWORD') ?: 'YourSecurePassword123!', // Change in production
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
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

// Make $dbConfig available globally for backward compatibility
$GLOBALS['dbConfig'] = $dbConfig;

// Return the configuration for direct use
return $dbConfig;