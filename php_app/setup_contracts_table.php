<?php
// إعدادات قاعدة البيانات
$database_path = __DIR__ . '/../contracts.db';

try {
    $pdo = new PDO("sqlite:$database_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

echo "🔄 إنشاء جدول العقود...\n";

try {
    // إنشاء جدول العقود الأساسي
    $createContractsTable = "
    CREATE TABLE IF NOT EXISTS contracts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        contract_number TEXT UNIQUE NOT NULL,
        client_name TEXT NOT NULL,
        client_id TEXT NOT NULL,
        client_phone TEXT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        profit_percentage DECIMAL(5,2) DEFAULT 30.00,
        contract_duration INTEGER DEFAULT 6,
        profit_interval TEXT DEFAULT 'monthly',
        signature_method TEXT DEFAULT 'electronic',
        contract_date DATE NOT NULL,
        notes TEXT,
        status TEXT NOT NULL DEFAULT 'draft',
        created_by INTEGER,
        approved_by INTEGER,
        manager_notes TEXT,
        approval_date DATETIME,
        signed_date DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id),
        FOREIGN KEY (approved_by) REFERENCES users(id)
    )";
    
    $pdo->exec($createContractsTable);
    echo "✅ تم إنشاء جدول العقود بنجاح\n";
    
    // إضافة عقد تجريبي
    $insertSampleContract = "
    INSERT OR IGNORE INTO contracts (
        contract_number, client_name, client_id, client_phone, amount, 
        contract_date, status, created_by
    ) VALUES (
        'CON-2025-001', 'أحمد محمد السعد', '1234567890', '0555123456', 100000.00,
        '2025-10-06', 'draft', 1
    )";
    
    $pdo->exec($insertSampleContract);
    echo "✅ تم إضافة عقد تجريبي\n";
    
    echo "\n🎉 تم إعداد جدول العقود بنجاح!\n";
    
} catch (PDOException $e) {
    echo "❌ خطأ في إنشاء جدول العقود: " . $e->getMessage() . "\n";
    exit(1);
}
?>