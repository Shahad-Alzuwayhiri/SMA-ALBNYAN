<?php
try {
    $pdo = new PDO('sqlite:contracts.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // إنشاء جدول المستخدمين
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            role VARCHAR(50) DEFAULT 'employee',
            status VARCHAR(50) DEFAULT 'active',
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // إنشاء جدول العقود
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contracts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_number VARCHAR(100) UNIQUE NOT NULL,
            client_name VARCHAR(255) NOT NULL,
            client_id VARCHAR(20) NOT NULL,
            client_phone VARCHAR(50),
            client_email VARCHAR(255),
            amount DECIMAL(15,2) NOT NULL,
            profit_percentage DECIMAL(5,2) DEFAULT 30,
            contract_duration INTEGER DEFAULT 12,
            profit_interval VARCHAR(20) DEFAULT 'monthly',
            signature_method VARCHAR(50) DEFAULT 'electronic',
            contract_date DATE NOT NULL,
            notes TEXT,
            status VARCHAR(50) DEFAULT 'draft',
            created_by INTEGER NOT NULL,
            reviewed_by INTEGER,
            signed_by INTEGER,
            pdf_path VARCHAR(500),
            signed_pdf_path VARCHAR(500),
            net_profit DECIMAL(15,2),
            is_amendment BOOLEAN DEFAULT 0,
            parent_contract_id INTEGER,
            amendment_duration_months INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            contract_type VARCHAR(50) DEFAULT 'real_estate',
            property_number VARCHAR(50),
            property_location TEXT,
            profit_frequency INTEGER DEFAULT 1,
            title TEXT,
            signature_path TEXT,
            signature_comments TEXT,
            signed_at DATETIME,
            FOREIGN KEY (created_by) REFERENCES users(id),
            FOREIGN KEY (reviewed_by) REFERENCES users(id),
            FOREIGN KEY (signed_by) REFERENCES users(id)
        )
    ");

    // إنشاء جدول الإشعارات
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) DEFAULT 'info',
            is_read BOOLEAN DEFAULT 0,
            related_contract_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (related_contract_id) REFERENCES contracts(id)
        )
    ");

    // إنشاء جدول سجل النشاطات
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            related_contract_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (related_contract_id) REFERENCES contracts(id)
        )
    ");

    // إدراج المستخدمين الافتراضيين
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $employee_password = password_hash('employee123', PASSWORD_DEFAULT);

    $pdo->exec("
        INSERT OR IGNORE INTO users (id, name, email, password, role, phone) VALUES 
        (1, 'مدير النظام', 'admin@sma-albnyan.com', '$admin_password', 'admin', '+966501234567'),
        (2, 'موظف العقود', 'employee@sma-albnyan.com', '$employee_password', 'employee', '+966501234568')
    ");

    // إدراج عقود تجريبية
    $pdo->exec("
        INSERT OR IGNORE INTO contracts (
            id, contract_number, client_name, client_id, client_phone, client_email,
            amount, profit_percentage, contract_duration, contract_date, 
            status, created_by, notes, contract_type
        ) VALUES 
        (1, 'SMA-2025-001', 'محمد أحمد العلي', '1234567890', '0501234567', 'mohammed@example.com', 
         100000.00, 30.00, 12, '2025-01-15', 'draft', 2, 'عقد استثمار عقاري', 'real_estate'),
        (2, 'SMA-2025-002', 'فاطمة سعد الحربي', '1234567891', '0501234568', 'fatima@example.com', 
         150000.00, 25.00, 18, '2025-02-01', 'active', 2, 'عقد استثمار شركة', 'company'),
        (3, 'SMA-2025-003', 'عبدالله محمد القحطاني', '1234567892', '0501234569', 'abdullah@example.com', 
         200000.00, 35.00, 24, '2025-02-15', 'pending', 2, 'عقد استثمار مختلط', 'real_estate')
    ");

    echo "تم إنشاء قاعدة البيانات بنجاح!\n";
    echo "الحسابات الافتراضية:\n";
    echo "- Admin: admin@sma-albnyan.com / admin123\n";
    echo "- Employee: employee@sma-albnyan.com / employee123\n";

} catch (PDOException $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?>