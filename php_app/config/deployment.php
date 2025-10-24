<?php
/**
 * Production Deployment Configuration
 * Contract Sama - Security and Environment Settings
 */

return [
    // Environment
    'environment' => 'production',
    'debug' => false,
    'maintenance_mode' => false,
    
    // Security Settings
    'security' => [
        'force_https' => true,
        'session_cookie_secure' => true,
        'session_cookie_httponly' => true,
        'session_cookie_samesite' => 'Strict',
        'csrf_protection' => true,
        'xss_protection' => true,
        'content_type_options' => 'nosniff',
        'frame_options' => 'DENY',
        'referrer_policy' => 'strict-origin-when-cross-origin'
    ],
    
    // Database Settings
    'database' => [
        'connection' => 'sqlite',
        'path' => __DIR__ . '/../database/contracts.db',
        'backup_interval' => 'daily',
        'backup_retention_days' => 30
    ],
    
    // Legacy Heroku Support
    'heroku' => [
        'enabled' => (bool) getenv('DATABASE_URL'),
        'database_url' => getenv('DATABASE_URL')
    ],
    
    // File Upload Security
    'uploads' => [
        'max_size' => '10M',
        'allowed_types' => ['pdf', 'png', 'jpg', 'jpeg'],
        'scan_for_viruses' => false,
        'quarantine_suspicious' => true
    ],
    
    // PDF Generation
    'pdf' => [
        'memory_limit' => '256M',
        'max_execution_time' => 300,
        'font_path' => __DIR__ . '/../public/static/fonts/',
        'temp_path' => __DIR__ . '/../storage/temp/',
        'watermark' => true
    ],
    
    // Performance
    'performance' => [
        'enable_gzip' => true,
        'cache_static_files' => true,
        'minify_html' => true,
        'enable_opcache' => true
    ],
    
    // Rate Limiting
    'rate_limiting' => [
        'enabled' => true,
        'login_attempts' => 5,
        'login_lockout_minutes' => 15,
        'pdf_generation_per_hour' => 100
    ]
];

// Environment Helper Function
function env(string $key, $default = null)
{
    $value = $_ENV[$key] ?? getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    if (in_array(strtolower($value), ['true', 'false'])) {
        return strtolower($value) === 'true';
    }
    
    if (strtolower($value) === 'null') {
        return null;
    }
    
    return $value;
}

// Legacy Database Setup for Heroku
if (getenv('DATABASE_URL')) {
    // Local SQLite database
    $dbPath = __DIR__ . '/../contracts.db';
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Set timezone
date_default_timezone_set('Asia/Riyadh');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>