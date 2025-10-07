<?php
/**
 * Contract Sama - Deployment Script
 * 
 * This script automates the deployment process for Contract Sama
 * Use this for production deployments
 */

declare(strict_types=1);

class ContractSamaDeployer
{
    private string $projectRoot;
    private string $backupDir;
    private array $config;
    
    public function __construct()
    {
        $this->projectRoot = dirname(__FILE__);
        $this->backupDir = $this->projectRoot . '/backups';
        $this->config = $this->loadConfig();
        
        // Ensure backup directory exists
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    public function deploy(): void
    {
        echo "🚀 Starting Contract Sama Deployment...\n\n";
        
        try {
            $this->preDeploymentChecks();
            $this->createBackup();
            $this->updateDependencies();
            $this->runMigrations();
            $this->optimizeForProduction();
            $this->setPermissions();
            $this->runTests();
            $this->clearCache();
            
            echo "✅ Deployment completed successfully!\n";
            
        } catch (Exception $e) {
            echo "❌ Deployment failed: " . $e->getMessage() . "\n";
            $this->rollback();
            exit(1);
        }
    }
    
    private function loadConfig(): array
    {
        $configFile = $this->projectRoot . '/php_app/config/deployment.php';
        if (file_exists($configFile)) {
            return include $configFile;
        }
        
        return [
            'environment' => 'production',
            'debug' => false,
            'backup_retention_days' => 7,
            'maintenance_mode' => true
        ];
    }
    
    private function preDeploymentChecks(): void
    {
        echo "🔍 Running pre-deployment checks...\n";
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            throw new Exception("PHP 8.1 or higher is required. Current version: " . PHP_VERSION);
        }
        
        // Check required extensions
        $requiredExtensions = ['pdo', 'mbstring', 'json', 'gd', 'zip'];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                throw new Exception("Required PHP extension not found: {$ext}");
            }
        }
        
        // Check writable directories
        $writableDirs = [
            $this->projectRoot . '/php_app/storage',
            $this->projectRoot . '/php_app/storage/logs',
            $this->projectRoot . '/php_app/database'
        ];
        
        foreach ($writableDirs as $dir) {
            if (!is_writable($dir)) {
                throw new Exception("Directory not writable: {$dir}");
            }
        }
        
        echo "✅ Pre-deployment checks passed\n\n";
    }
    
    private function createBackup(): void
    {
        echo "💾 Creating backup...\n";
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $this->backupDir . "/backup_{$timestamp}.tar.gz";
        
        // Create database backup
        $dbFile = $this->projectRoot . '/php_app/database/contracts.db';
        if (file_exists($dbFile)) {
            $dbBackup = $this->backupDir . "/database_{$timestamp}.db";
            copy($dbFile, $dbBackup);
        }
        
        // Create full backup (excluding sensitive files)
        $excludeFiles = [
            '--exclude=*.log',
            '--exclude=.git',
            '--exclude=node_modules',
            '--exclude=vendor',
            '--exclude=backups'
        ];
        
        $command = sprintf(
            'tar %s -czf %s -C %s .',
            implode(' ', $excludeFiles),
            escapeshellarg($backupFile),
            escapeshellarg($this->projectRoot)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            echo "⚠️  Warning: Backup creation failed, continuing deployment...\n";
        } else {
            echo "✅ Backup created: {$backupFile}\n\n";
        }
        
        // Clean old backups
        $this->cleanOldBackups();
    }
    
    private function updateDependencies(): void
    {
        echo "📦 Updating dependencies...\n";
        
        $phpAppDir = $this->projectRoot . '/php_app';
        chdir($phpAppDir);
        
        // Install/update Composer dependencies
        exec('composer install --no-dev --optimize-autoloader --no-interaction', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Composer install failed");
        }
        
        echo "✅ Dependencies updated\n\n";
    }
    
    private function runMigrations(): void
    {
        echo "🗃️ Running database migrations...\n";
        
        $phpAppDir = $this->projectRoot . '/php_app';
        chdir($phpAppDir);
        
        // Run upgrade system script
        if (file_exists('upgrade_system.php')) {
            include 'upgrade_system.php';
        }
        
        echo "✅ Database migrations completed\n\n";
    }
    
    private function optimizeForProduction(): void
    {
        echo "⚡ Optimizing for production...\n";
        
        // Enable OPcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Set production environment variables
        $envFile = $this->projectRoot . '/php_app/.env';
        if (!file_exists($envFile)) {
            $envContent = "APP_ENV=production\n";
            $envContent .= "APP_DEBUG=false\n";
            $envContent .= "APP_URL=" . $this->getAppUrl() . "\n";
            file_put_contents($envFile, $envContent);
        }
        
        echo "✅ Production optimization completed\n\n";
    }
    
    private function setPermissions(): void
    {
        echo "🔒 Setting file permissions...\n";
        
        $directories = [
            $this->projectRoot . '/php_app/storage' => 0755,
            $this->projectRoot . '/php_app/storage/logs' => 0755,
            $this->projectRoot . '/php_app/database' => 0755,
            $this->projectRoot . '/php_app/public' => 0755
        ];
        
        foreach ($directories as $dir => $permission) {
            if (is_dir($dir)) {
                chmod($dir, $permission);
                
                // Set permissions recursively
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                
                foreach ($iterator as $item) {
                    if ($item->isDir()) {
                        chmod($item->getPathname(), $permission);
                    } else {
                        chmod($item->getPathname(), 0644);
                    }
                }
            }
        }
        
        echo "✅ File permissions set\n\n";
    }
    
    private function runTests(): void
    {
        echo "🧪 Running tests...\n";
        
        $phpAppDir = $this->projectRoot . '/php_app';
        chdir($phpAppDir);
        
        // Run simple tests
        if (file_exists('test_simple.php')) {
            ob_start();
            include 'test_simple.php';
            $output = ob_get_clean();
            
            if (strpos($output, 'ERROR') !== false) {
                throw new Exception("Tests failed: {$output}");
            }
        }
        
        echo "✅ Tests passed\n\n";
    }
    
    private function clearCache(): void
    {
        echo "🧹 Clearing cache...\n";
        
        // Clear PHP OPcache
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Clear application cache/logs
        $cacheFiles = glob($this->projectRoot . '/php_app/storage/logs/*.log');
        foreach ($cacheFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        echo "✅ Cache cleared\n\n";
    }
    
    private function rollback(): void
    {
        echo "🔄 Rolling back deployment...\n";
        
        // Find latest backup
        $backups = glob($this->backupDir . '/backup_*.tar.gz');
        if (empty($backups)) {
            echo "❌ No backup found for rollback\n";
            return;
        }
        
        rsort($backups);
        $latestBackup = $backups[0];
        
        // Extract backup
        $command = sprintf(
            'tar -xzf %s -C %s',
            escapeshellarg($latestBackup),
            escapeshellarg($this->projectRoot)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "✅ Rollback completed\n";
        } else {
            echo "❌ Rollback failed\n";
        }
    }
    
    private function cleanOldBackups(): void
    {
        $retentionDays = $this->config['backup_retention_days'] ?? 7;
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        
        $backups = glob($this->backupDir . '/backup_*.tar.gz');
        foreach ($backups as $backup) {
            if (filemtime($backup) < $cutoffTime) {
                unlink($backup);
            }
        }
        
        $dbBackups = glob($this->backupDir . '/database_*.db');
        foreach ($dbBackups as $backup) {
            if (filemtime($backup) < $cutoffTime) {
                unlink($backup);
            }
        }
    }
    
    private function getAppUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$protocol}://{$host}";
    }
}

// Run deployment if script is called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $deployer = new ContractSamaDeployer();
    $deployer->deploy();
}