<?php
// تحديث جدول العقود لإضافة الحقول الجديدة

// إعدادات قاعدة البيانات
$dsn = 'sqlite:contracts.db';
$user = null;
$pass = null;
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$sql = "
-- إنشاء جدول المستخدمين
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- إنشاء جدول العقود الأساسية مع جميع الحقول المطلوبة
CREATE TABLE IF NOT EXISTS contracts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contract_number TEXT UNIQUE NOT NULL,
    client_name TEXT NOT NULL,
    client_id TEXT,
    client_phone TEXT,
    amount DECIMAL(15,2) NOT NULL,
    contract_date DATE,
    signature_method TEXT,
    contract_duration INTEGER DEFAULT 12,
    profit_interval INTEGER DEFAULT 6,
    notes TEXT,
    status TEXT DEFAULT 'active',
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- إنشاء جدول العقود المفصلة
CREATE TABLE IF NOT EXISTS detailed_contracts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contract_id INTEGER NOT NULL,
    partner_name TEXT NOT NULL,
    partner_id TEXT,
    partner_phone TEXT,
    investment_amount DECIMAL(15,2) NOT NULL,
    profit_percent DECIMAL(5,2) DEFAULT 30.00,
    profit_interval_months INTEGER DEFAULT 6,
    contract_date DATE,
    signature_method TEXT,
    contract_duration INTEGER DEFAULT 12,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
);

-- إدراج مستخدم افتراضي
INSERT OR IGNORE INTO users (name, email, password, role) VALUES 
('المدير العام', 'admin@sama.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('مدير العقود', 'manager@sama.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager');
";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec($sql);
    echo "تم تحديث جدول العقود بنجاح مع الحقول الجديدة\n";
} catch (PDOException $e) {
    echo "خطأ في تحديث قاعدة البيانات: " . $e->getMessage() . "\n";
}
?>