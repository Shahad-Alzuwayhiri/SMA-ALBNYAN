<?php
// تحديث قاعدة البيانات - إضافة الجداول المفقودة
require_once '../includes/auth.php';

try {
    echo "بدء تحديث قاعدة البيانات...\n";
    
    // إنشاء جدول contract_attachments
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contract_attachments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_id INTEGER NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path TEXT NOT NULL,
            file_size INTEGER,
            mime_type VARCHAR(100),
            uploaded_by INTEGER NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        )
    ");
    echo "✅ تم إنشاء جدول contract_attachments\n";
    
    // إنشاء جدول contract_history
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contract_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_id INTEGER NOT NULL,
            action VARCHAR(100) NOT NULL,
            previous_status VARCHAR(50),
            new_status VARCHAR(50),
            comment TEXT,
            action_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
            FOREIGN KEY (action_by) REFERENCES users(id)
        )
    ");
    echo "✅ تم إنشاء جدول contract_history\n";
    
    // إضافة بعض السجلات التجريبية للتاريخ
    $pdo->exec("
        INSERT OR IGNORE INTO contract_history (id, contract_id, action, previous_status, new_status, comment, action_by)
        VALUES 
        (1, 1, 'created', null, 'draft', 'تم إنشاء العقد', 1),
        (2, 2, 'created', null, 'draft', 'تم إنشاء العقد', 2)
    ");
    echo "✅ تم إضافة بيانات تجريبية لتاريخ العقود\n";
    
    // إضافة فهارس لتحسين الأداء
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_contract_attachments_contract_id ON contract_attachments(contract_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_contract_history_contract_id ON contract_history(contract_id)");
    echo "✅ تم إنشاء الفهارس\n";
    
    echo "\n🎉 تم تحديث قاعدة البيانات بنجاح!\n";
    echo "الجداول المضافة:\n";
    echo "- contract_attachments (لإدارة المرفقات)\n";
    echo "- contract_history (لتتبع تاريخ العقود)\n";
    
} catch (PDOException $e) {
    echo "❌ خطأ في تحديث قاعدة البيانات: " . $e->getMessage() . "\n";
}
?>