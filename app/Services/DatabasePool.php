<?php

namespace App\Services;

use PDO;
use PDOException;

/**
 * Database Connection Pool
 * مجموعة اتصالات قاعدة البيانات
 */
class DatabasePool
{
    private static $connections = [];
    private static $config = null;
    private static $instance = null;
    
    private function __construct()
    {
        self::$config = require __DIR__ . '/../../config/database.php';
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection with connection pooling
     */
    public static function getConnection($connectionName = null)
    {
        if ($connectionName === null) {
            $connectionName = self::getDefaultConnection();
        }
        
        // Return existing connection if available
        if (isset(self::$connections[$connectionName]) && self::$connections[$connectionName] !== null) {
            // Test connection is still alive
            try {
                self::$connections[$connectionName]->query('SELECT 1');
                return self::$connections[$connectionName];
            } catch (PDOException $e) {
                // Connection is dead, remove it
                unset(self::$connections[$connectionName]);
            }
        }
        
        // Create new connection
        self::$connections[$connectionName] = self::createConnection($connectionName);
        return self::$connections[$connectionName];
    }
    
    private static function getDefaultConnection()
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/database.php';
        }
        
        $defaultConnection = self::$config['default'];
        
        // Auto-detect environment for Hostinger
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'hostinger') !== false) {
            $defaultConnection = 'mysql';
        }
        
        return $defaultConnection;
    }
    
    private static function createConnection($connectionName)
    {
        $config = self::$config['connections'][$connectionName] ?? null;
        
        if ($config === null) {
            throw new PDOException("Database connection '{$connectionName}' not found");
        }
        
        try {
            if ($config['driver'] === 'sqlite') {
                $dsn = "sqlite:" . $config['database'];
                $pdo = new PDO($dsn, null, null, $config['options']);
                
                // SQLite optimizations
                $pdo->exec('PRAGMA foreign_keys = ON');
                $pdo->exec('PRAGMA journal_mode = WAL');
                $pdo->exec('PRAGMA synchronous = NORMAL');
                $pdo->exec('PRAGMA cache_size = 1000');
                $pdo->exec('PRAGMA temp_store = MEMORY');
                
            } elseif ($config['driver'] === 'mysql') {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset']
                );
                
                // MySQL performance options
                $options = array_merge($config['options'], [
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']} COLLATE {$config['collation']}",
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    PDO::ATTR_PERSISTENT => false, // Disable persistent connections for shared hosting
                ]);
                
                $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
            } else {
                throw new PDOException("Unsupported database driver: {$config['driver']}");
            }
            
            return $pdo;
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Close all connections
     */
    public static function closeAll()
    {
        self::$connections = [];
    }
    
    /**
     * Get connection statistics
     */
    public static function getStats()
    {
        return [
            'active_connections' => count(self::$connections),
            'connection_names' => array_keys(self::$connections)
        ];
    }
}