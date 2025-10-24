<?php
/**
 * تحديث قاعدة البيانات - إضافة جدول الملفات
 * سكريبت لإضافة جدول files لحفظ ملفات PDF
 */

try {
    // إنشاء اتصال قاعدة البيانات
    $pdo = new PDO(
        "sqlite:" . __DIR__ . "/contracts.db",
        null,
        null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "🔧 بدء تحديث قاعدة البيانات...\n";
    
    // التحقق من وجود الجدول
    $checkTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='files'");
    
    if ($checkTable->fetch()) {
        echo "✅ جدول files موجود مسبقاً\n";
    } else {
        // إنشاء جدول الملفات
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_id INTEGER,
            file_name VARCHAR(255) NOT NULL,
            file_type VARCHAR(50) DEFAULT 'pdf',
            file_size INTEGER,
            encoded_string TEXT NOT NULL,
            mime_type VARCHAR(100) DEFAULT 'application/pdf',
            uploaded_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
        )";
        
        $pdo->exec($createTableSQL);
        echo "✅ تم إنشاء جدول files بنجاح\n";
        
        // إنشاء الفهارس
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_files_contract_id ON files(contract_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_files_created_at ON files(created_at)");
        echo "✅ تم إنشاء الفهارس بنجاح\n";
    }
    
    echo "🎉 تم تحديث قاعدة البيانات بنجاح!\n";
    
} catch (PDOException $e) {
    echo "❌ خطأ في تحديث قاعدة البيانات: " . $e->getMessage() . "\n";
    exit(1);
}
?>