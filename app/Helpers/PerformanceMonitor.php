<?php

namespace App\Helpers;

/**
 * Performance Monitor Helper
 * مساعد مراقبة الأداء
 */
class PerformanceMonitor
{
    private static $startTime = null;
    private static $markers = [];
    private static $queries = [];
    
    /**
     * Start performance monitoring
     */
    public static function start()
    {
        self::$startTime = microtime(true);
        self::$markers = [];
        self::$queries = [];
    }
    
    /**
     * Add a performance marker
     */
    public static function mark($label)
    {
        if (self::$startTime === null) {
            self::start();
        }
        
        self::$markers[$label] = microtime(true) - self::$startTime;
    }
    
    /**
     * Log a database query for monitoring
     */
    public static function logQuery($sql, $params = [], $executionTime = null)
    {
        if ($executionTime === null) {
            $executionTime = 0;
        }
        
        self::$queries[] = [
            'sql' => $sql,
            'params' => $params,
            'time' => $executionTime,
            'timestamp' => microtime(true)
        ];
    }
    
    /**
     * Get performance report
     */
    public static function getReport()
    {
        if (self::$startTime === null) {
            return ['error' => 'Performance monitoring not started'];
        }
        
        $totalTime = microtime(true) - self::$startTime;
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        return [
            'total_time' => round($totalTime * 1000, 2) . 'ms',
            'memory_usage' => self::formatBytes($memoryUsage),
            'peak_memory' => self::formatBytes($peakMemory),
            'markers' => array_map(function($time) {
                return round($time * 1000, 2) . 'ms';
            }, self::$markers),
            'query_count' => count(self::$queries),
            'queries' => self::$queries,
            'slow_queries' => self::getSlowQueries()
        ];
    }
    
    /**
     * Get slow queries (over 100ms)
     */
    private static function getSlowQueries()
    {
        return array_filter(self::$queries, function($query) {
            return $query['time'] > 0.1; // 100ms
        });
    }
    
    /**
     * Format bytes to human readable format
     */
    private static function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $power = floor(($bytes ? log($bytes) : 0) / log(1024));
        $power = min($power, count($units) - 1);
        
        $bytes /= pow(1024, $power);
        
        return round($bytes, 2) . ' ' . $units[$power];
    }
    
    /**
     * Output performance report as HTML
     */
    public static function displayReport()
    {
        if (!self::isDebugMode()) {
            return;
        }
        
        $report = self::getReport();
        
        echo "<div style='position: fixed; bottom: 0; right: 0; width: 300px; background: rgba(0,0,0,0.9); color: white; padding: 10px; font-size: 12px; z-index: 9999; max-height: 200px; overflow-y: auto;'>";
        echo "<h4 style='margin: 0 0 10px 0; color: #4CAF50;'>Performance Report</h4>";
        echo "<div><strong>Total Time:</strong> {$report['total_time']}</div>";
        echo "<div><strong>Memory:</strong> {$report['memory_usage']} (Peak: {$report['peak_memory']})</div>";
        echo "<div><strong>DB Queries:</strong> {$report['query_count']}</div>";
        
        if (!empty($report['slow_queries'])) {
            echo "<div style='color: #FF5722;'><strong>Slow Queries:</strong> " . count($report['slow_queries']) . "</div>";
        }
        
        if (!empty($report['markers'])) {
            echo "<div><strong>Markers:</strong></div>";
            foreach ($report['markers'] as $label => $time) {
                echo "<div style='margin-left: 10px;'>• {$label}: {$time}</div>";
            }
        }
        
        echo "</div>";
    }
    
    /**
     * Check if debug mode is enabled
     */
    private static function isDebugMode()
    {
        return defined('DEBUG_MODE') && DEBUG_MODE === true;
    }
    
    /**
     * Log performance data to file
     */
    public static function logToFile($filename = null)
    {
        if ($filename === null) {
            $filename = __DIR__ . '/../../storage/logs/performance_' . date('Y-m-d') . '.log';
        }
        
        $report = self::getReport();
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'report' => $report
        ];
        
        // Ensure log directory exists
        $logDir = dirname($filename);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($filename, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
}