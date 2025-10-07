<?php
// إنشاء جداول قاعدة البيانات المطلوبة

require_once 'config/database.php';

try {
    $db = getDB();
    
    echo "📦 إنشاء جداول قاعدة البيانات...\n";
    echo "===================================\n\n";
    
    // إنشاء جدول المستخدمين
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'employee',
        status VARCHAR(50) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ تم إنشاء جدول users\n";
    
    // إنشاء جدول العقود
    $db->exec("CREATE TABLE IF NOT EXISTS contracts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        contract_number VARCHAR(100) UNIQUE NOT NULL,
        title VARCHAR(255) NOT NULL,
        second_party_name VARCHAR(255) NOT NULL,
        second_party_phone VARCHAR(50),
        second_party_email VARCHAR(255),
        contract_amount DECIMAL(15,2) DEFAULT 0,
        profit_percentage DECIMAL(5,2) DEFAULT 0,
        start_date DATE,
        end_date DATE,
        description TEXT,
        terms_conditions TEXT,
        status VARCHAR(50) DEFAULT 'draft',
        created_by INTEGER NOT NULL,
        reviewed_by INTEGER,
        signed_by INTEGER,
        pdf_path VARCHAR(500),
        signed_pdf_path VARCHAR(500),
        contract_type VARCHAR(100) DEFAULT 'simple',
        hijri_date VARCHAR(50),
        location VARCHAR(255),
        is_detailed BOOLEAN DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id),
        FOREIGN KEY (reviewed_by) REFERENCES users(id),
        FOREIGN KEY (signed_by) REFERENCES users(id)
    )");
    echo "✅ تم إنشاء جدول contracts\n";
    
    // إنشاء جدول العقود المفصلة
    $db->exec("CREATE TABLE IF NOT EXISTS detailed_contracts (
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
    )");
    echo "✅ تم إنشاء جدول detailed_contracts\n";
    
    // إنشاء جدول تاريخ العقود
    $db->exec("CREATE TABLE IF NOT EXISTS contract_history (
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
    )");
    echo "✅ تم إنشاء جدول contract_history\n";
    
    // إنشاء جدول الإشعارات
    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        contract_id INTEGER,
        user_id INTEGER NOT NULL,
        type VARCHAR(100) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✅ تم إنشاء جدول notifications\n";
    
    // إدراج بيانات المستخدمين التجريبية
    $stmt = $db->prepare("INSERT OR IGNORE INTO users (id, name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    
    $users = [
        [1, 'المدير العام', 'admin@sama.com', password_hash('123456', PASSWORD_DEFAULT), 'manager'],
        [2, 'مدير العقود', 'manager@sama.com', password_hash('123456', PASSWORD_DEFAULT), 'manager'],
        [3, 'موظف العقود', 'employee@sama.com', password_hash('123456', PASSWORD_DEFAULT), 'employee']
    ];
    
    foreach ($users as $user) {
        $stmt->execute($user);
    }
    echo "✅ تم إدراج بيانات المستخدمين التجريبية\n";
    
    echo "\n🎉 تم إعداد قاعدة البيانات بنجاح!\n";
    
} catch (Exception $e) {
    echo "❌ خطأ في إعداد قاعدة البيانات: " . $e->getMessage() . "\n";
}
?>