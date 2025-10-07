<?php
// إنشاء جدول العقود المفصلة
require_once 'config/database.php';

try {
    $db = getDB();
    
    // إنشاء جدول العقود المفصلة
    $sql = "CREATE TABLE IF NOT EXISTS detailed_contracts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        contract_id INTEGER NOT NULL,
        contract_type VARCHAR(100) DEFAULT 'general',
        hijri_date VARCHAR(50),
        location VARCHAR(255),
        first_party_name VARCHAR(255),
        first_party_commercial_reg VARCHAR(100),
        first_party_city VARCHAR(100),
        first_party_district VARCHAR(100),
        first_party_representative VARCHAR(255),
        second_party_name VARCHAR(255),
        second_party_id VARCHAR(50),
        second_party_mobile VARCHAR(50),
        capital_amount DECIMAL(15,2),
        profit_percentage DECIMAL(5,2),
        profit_period_months INTEGER DEFAULT 6,
        withdrawal_notice_days INTEGER DEFAULT 60,
        penalty_amount DECIMAL(10,2) DEFAULT 3000,
        penalty_period_days INTEGER DEFAULT 30,
        commission_percentage DECIMAL(5,2) DEFAULT 2.5,
        force_majeure_days INTEGER DEFAULT 90,
        full_contract_text TEXT,
        contract_clauses TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
    )";
    
    $db->exec($sql);
    echo "جدول العقود المفصلة تم إنشاؤه بنجاح\n";
    
    // إضافة الأعمدة الجديدة لجدول العقود الأساسي إذا لم تكن موجودة
    $alterQueries = [
        "ALTER TABLE contracts ADD COLUMN contract_type VARCHAR(100) DEFAULT 'simple'",
        "ALTER TABLE contracts ADD COLUMN hijri_date VARCHAR(50)",
        "ALTER TABLE contracts ADD COLUMN location VARCHAR(255)",
        "ALTER TABLE contracts ADD COLUMN is_detailed BOOLEAN DEFAULT 0"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $db->exec($query);
            echo "تم تحديث جدول العقود: " . substr($query, 0, 50) . "...\n";
        } catch (PDOException $e) {
            // العمود موجود بالفعل - تجاهل الخطأ
            if (strpos($e->getMessage(), 'duplicate column name') === false) {
                echo "تحذير: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "تم إعداد قاعدة البيانات للعقود المفصلة بنجاح!\n";
    
} catch (PDOException $e) {
    echo "خطأ في إعداد قاعدة البيانات: " . $e->getMessage() . "\n";
}
?>