<?php
$pdo = new PDO('sqlite:database/contracts.db');

// التحقق من وجود جدول المستخدمين
try {
    $result = $pdo->query('SELECT COUNT(*) as count FROM users');
    echo 'عدد المستخدمين: ' . $result->fetch()['count'] . "\n";
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
    echo "جدول المستخدمين غير موجود، سأنشئه...\n";
    
    // إنشاء جدول المستخدمين
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'employee',
            phone TEXT,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // إدراج مستخدمين تجريبيين
    $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
    
    $users = [
        ['admin@sama.com', 'مشرف النظام', 'admin'],
        ['manager@sama.com', 'مدير العقود', 'manager'],
        ['employee@sama.com', 'موظف العقود', 'employee']
    ];
    
    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO users (email, name, role, password) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($users as $user) {
        $stmt->execute([$user[0], $user[1], $user[2], $hashedPassword]);
    }
    
    echo "تم إنشاء جدول المستخدمين وإضافة المستخدمين التجريبيين!\n";
}