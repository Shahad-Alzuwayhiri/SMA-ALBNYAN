<?php
/**
 * Database Configuration - Clean and Simple (No Conflicts)
 * إعدادات قاعدة البيانات البسيطة والنظيفة
 */

// Single PDO instance for performance
if (!isset($GLOBALS['db_connection'])) {
    $GLOBALS['db_connection'] = null;
}

/**
 * Get Database Connection
 * الحصول على اتصال قاعدة البيانات
 */
if (!function_exists('getDatabaseConnection')) {
    function getDatabaseConnection() {
        // Return existing connection if available
        if ($GLOBALS['db_connection'] !== null) {
            return $GLOBALS['db_connection'];
        }
        
        try {
            $dbPath = __DIR__ . '/../database/contracts.db';
            
            // Check if database file exists
            if (!file_exists($dbPath)) {
                throw new Exception("Database file not found: $dbPath");
            }
            
            // Create PDO connection
            $dsn = "sqlite:$dbPath";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, null, null, $options);
            
            // Enable foreign key constraints
            $pdo->exec('PRAGMA foreign_keys = ON');
            
            // Store connection for reuse
            $GLOBALS['db_connection'] = $pdo;
            
            return $pdo;
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
}

/**
 * Test Database Connection
 * اختبار اتصال قاعدة البيانات
 */
if (!function_exists('testDatabaseConnection')) {
    function testDatabaseConnection() {
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
            $result = $stmt->fetch();
            return [
                'status' => 'success',
                'message' => 'Database connected successfully',
                'tables_count' => $result['count']
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}

/**
 * Helper Functions
 */
if (!function_exists('getDB')) {
    function getDB() {
        return getDatabaseConnection();
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
        $results = [];
        $maxRows = 5000;
        $results = [];
        $count = 0;
        foreach (fetchIter($sql, $params) as $row) {
            if (++$count > $maxRows) {
                throw new Exception('fetchAll() exceeded safe max rows cap');
            }
            $results[] = $row;
        }
        return $results;
// Streaming generator for memory-safe row iteration
if (!function_exists('fetchIter')) {
    function fetchIter($sql, $params = []) {
        $stmt = executeQuery($sql, $params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
        $stmt->closeCursor();
    }
}
    }
}

// Remove legacy $GLOBALS['pdo'] and ensure only $GLOBALS['db_connection'] is used
?>