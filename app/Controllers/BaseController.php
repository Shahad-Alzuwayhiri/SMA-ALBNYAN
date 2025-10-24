<?php

namespace App\Controllers;

/**
 * Base Controller Class - الكلاس الأساسي للتحكم
 * Provides common functionality for all controllers including database, authentication, and view rendering
 * يوفر الوظائف المشتركة لجميع وحدات التحكم بما في ذلك قاعدة البيانات والمصادقة وعرض الصفحات
 */
abstract class BaseController
{
    protected $config;  // Application configuration - إعدادات التطبيق
    protected $pdo;     // Database connection - اتصال قاعدة البيانات
    protected $user;    // Current authenticated user - المستخدم المسجل حاليا
    
    /**
     * Constructor - initialize common dependencies
     * المنشئ - تهيئة التبعيات المشتركة
     */
    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->initDatabase();  // Setup database connection - إعداد اتصال قاعدة البيانات
        $this->initAuth();      // Initialize authentication - تهيئة المصادقة
    }
    
    /**
     * Initialize database connection
     * تهيئة اتصال قاعدة البيانات
     */
    protected function initDatabase()
    {
        // Use global PDO if available, otherwise create new connection
        // استخدام PDO العام إذا كان متاحا، وإلا إنشاء اتصال جديد
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] !== null) {
            $this->pdo = $GLOBALS['pdo'];
            return;
        }
        
        $dbConfig = require __DIR__ . '/../../config/database.php';
        
        // Safely access configuration with null checks
        if (!isset($dbConfig['default']) || !isset($dbConfig['connections'])) {
            throw new \Exception('Database configuration is invalid');
        }
        
        $defaultConnection = $dbConfig['default'];
        if (!isset($dbConfig['connections'][$defaultConnection])) {
            throw new \Exception("Database connection '{$defaultConnection}' not found");
        }
        
        $connection = $dbConfig['connections'][$defaultConnection];
        
        try {
            if (isset($connection['driver']) && $connection['driver'] === 'sqlite') {
                $dsn = "sqlite:" . ($connection['database'] ?? '');
                $options = [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $this->pdo = new \PDO($dsn, null, null, $options);
                $this->pdo->exec('PRAGMA foreign_keys = ON');
            } elseif (isset($connection['driver']) && $connection['driver'] === 'mysql') {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $connection['host'] ?? 'localhost',
                    $connection['port'] ?? '3306',
                    $connection['database'] ?? '',
                    $connection['charset'] ?? 'utf8mb4'
                );
                $options = [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $this->pdo = new \PDO($dsn, $connection['username'] ?? '', $connection['password'] ?? '', $options);
            }
        } catch (\PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new \Exception('Database connection failed');
        }
    }
    
    protected function initAuth()
    {
        // Start session with secure settings
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'cookie_samesite' => 'Strict',
                'use_strict_mode' => true,
            ]);
        }
        
        // Force clear any phantom session data that might cause redirect loops
        if (empty($_SESSION['user']) && !empty($_SESSION)) {
            // Clear any leftover session data that might confuse the system
            foreach ($_SESSION as $key => $value) {
                if ($key !== 'last_regeneration') {
                    unset($_SESSION[$key]);
                }
            }
        }
        
        $this->user = $_SESSION['user'] ?? null;
        
        // Simplified session regeneration to avoid conflicts
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        }
        // Disabled automatic regeneration to prevent redirect loops
        // elseif (time() - $_SESSION['last_regeneration'] > 300) {
        //     session_regenerate_id(true);
        //     $_SESSION['last_regeneration'] = time();
        // }
    }
    
    protected function requireAuth()
    {
        if (!$this->user) {
            $this->redirect('/login');
        }
    }
    
    protected function requireRole($roles)
    {
        $this->requireAuth();
        if (!in_array($this->user['role'], (array)$roles)) {
            $this->abort(403);
        }
    }
    
    protected function redirect($path, $code = 302)
    {
        header("Location: {$path}", true, $code);
        exit;
    }
    
    protected function abort($code = 404)
    {
        http_response_code($code);
        require __DIR__ . "/../../public/{$code}.php";
        exit;
    }
    
    protected function view($template, $data = [])
    {
        extract($data);
        $config = $this->config;
        $user = $this->user;
        
        // Check if template exists
        $templatePath = __DIR__ . "/../../templates/{$template}.php";
        if (!file_exists($templatePath)) {
            throw new \Exception("Template '{$template}' not found at {$templatePath}");
        }
        
        require $templatePath;
    }
    
    protected function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}