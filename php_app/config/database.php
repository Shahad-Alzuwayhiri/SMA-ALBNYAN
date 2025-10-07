<?php

class Database
{
    private static $instance = null;
    private $connection;
    
    // SQLite configuration
    private $dbPath = '../database/contracts.db';

    private function __construct()
    {
        // استخدام SQLite بدلاً من MySQL
        $dsn = "sqlite:" . __DIR__ . "/" . $this->dbPath;
        
        try {
            $this->connection = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // تفعيل foreign keys في SQLite
            $this->connection->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            echo "Database initialization error: " . $e->getMessage() . "\n";
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    // منع استنساخ الكائن
    private function __clone() {}
    
    // منع إلغاء تسلسل الكائن
    public function __wakeup() {}
}

// دالة مساعدة للحصول على الاتصال
function getDB()
{
    return Database::getInstance()->getConnection();
}

// إنشاء قاعدة البيانات إذا لم تكن موجودة
function initializeDatabase()
{
    try {
        // الاتصال بدون تحديد قاعدة بيانات لإنشائها
        $dsn = "mysql:host=localhost;charset=utf8mb4";
        $pdo = new PDO($dsn, 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // إنشاء قاعدة البيانات
        $pdo->exec("CREATE DATABASE IF NOT EXISTS sama_contracts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // تشغيل ملف SQL لإنشاء الجداول (النسخة النظيفة)
        $sqlFile = __DIR__ . '/contracts_clean_schema.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // تنفيذ الاستعلامات
            $statements = explode(';', $sql);
            $pdo->exec("USE sama_contracts");
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            return true;
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Database initialization error: " . $e->getMessage());
        return false;
    }
}

// تشغيل التهيئة تلقائياً
if (!file_exists(__DIR__ . '/.db_initialized')) {
    if (initializeDatabase()) {
        file_put_contents(__DIR__ . '/.db_initialized', date('Y-m-d H:i:s'));
    }
}