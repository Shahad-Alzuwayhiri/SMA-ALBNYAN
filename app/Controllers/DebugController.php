<?php

namespace App\Controllers;

use Exception;

/**
 * Debug Controller
 * تحكم في أدوات التطوير والتشخيص
 */
class DebugController extends BaseController
{
    public function index()
    {
        // Only allow debug access in development mode
        if (!$this->isDebugMode()) {
            http_response_code(403);
            return $this->view('errors/403', ['message' => 'Debug mode is disabled']);
        }
        
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_connection' => $this->testDatabaseConnection(),
            'session_status' => session_status(),
            'memory_usage' => memory_get_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'loaded_extensions' => get_loaded_extensions(),
            'current_user' => $this->user,
            'environment_vars' => [
                'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
                'REQUEST_URI' => $_SERVER['REQUEST_URI'],
                'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'localhost',
                'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? '',
            ]
        ];
        
        return $this->view('debug/system_info', compact('systemInfo'));
    }
    
    public function status()
    {
        if (!$this->isDebugMode()) {
            http_response_code(403);
            echo json_encode(['error' => 'Debug mode disabled']);
            return;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => $this->testDatabaseConnection(),
            'session_active' => session_status() === PHP_SESSION_ACTIVE,
            'user_authenticated' => !is_null($this->user),
            'memory_usage' => number_format(memory_get_usage(true) / 1024 / 1024, 2) . ' MB'
        ]);
    }
    
    public function phpinfo()
    {
        if (!$this->isDebugMode()) {
            http_response_code(403);
            echo 'Debug mode is disabled';
            return;
        }
        
        phpinfo();
    }
    
    private function isDebugMode()
    {
        // Check if debug is enabled in config or environment
        return defined('DEBUG_MODE') && DEBUG_MODE === true;
    }
    
    private function testDatabaseConnection()
    {
        try {
            if ($this->pdo) {
                $stmt = $this->pdo->query('SELECT 1');
                return $stmt ? 'Connected' : 'Connection Error';
            }
            return 'PDO not initialized';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}