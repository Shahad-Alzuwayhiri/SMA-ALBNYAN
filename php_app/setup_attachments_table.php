<?php

try {
    // Connect to database
    $dbPath = __DIR__ . '/database/contracts.db';
    $dbDir = dirname($dbPath);
    
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0777, true);
    }
    
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create contract_attachments table
    $sql = "
        CREATE TABLE IF NOT EXISTS contract_attachments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_id INTEGER NOT NULL,
            file_name TEXT NOT NULL,
            file_path TEXT NOT NULL,
            file_size INTEGER,
            mime_type TEXT,
            uploaded_by INTEGER,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        )
    ";
    
    $pdo->exec($sql);
    
    echo "✅ تم إنشاء جدول contract_attachments بنجاح!\n";
    
    // Also ensure other required tables exist
    $tables = [
        'activity_log' => "
            CREATE TABLE IF NOT EXISTS activity_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action TEXT NOT NULL,
                target_type TEXT,
                target_id INTEGER,
                details TEXT,
                ip_address TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ",
        
        'notifications' => "
            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                type TEXT DEFAULT 'info',
                is_read INTEGER DEFAULT 0,
                related_id INTEGER,
                related_type TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ",
        
        'contract_history' => "
            CREATE TABLE IF NOT EXISTS contract_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                contract_id INTEGER NOT NULL,
                status_from TEXT,
                status_to TEXT NOT NULL,
                changed_by INTEGER,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (changed_by) REFERENCES users(id)
            )
        "
    ];
    
    foreach ($tables as $tableName => $createSql) {
        $pdo->exec($createSql);
        echo "✅ تم التأكد من وجود جدول $tableName\n";
    }
    
    echo "\n🎉 تم إعداد جميع الجداول المطلوبة بنجاح!\n";
    
} catch (Exception $e) {
    echo "❌ خطأ في إعداد قاعدة البيانات: " . $e->getMessage() . "\n";
    exit(1);
}