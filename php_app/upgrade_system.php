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

echo "🔄 بدء ترقية النظام لإدارة الأدوار والموافقات...\n";

try {
    // إنشاء جدول المستخدمين إذا لم يكن موجوداً
    $createUsersTable = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'employee', -- employee, manager, admin
        phone TEXT,
        status TEXT NOT NULL DEFAULT 'active', -- active, inactive
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    
    $pdo->exec($createUsersTable);
    echo "✅ تم إنشاء جدول المستخدمين\n";
    
    // إضافة المستخدمين الافتراضيين
    $defaultUsers = [
        [
            'name' => 'مدير النظام',
            'email' => 'admin@sama.com',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'role' => 'admin',
            'phone' => '0555123456',
            'status' => 'active'
        ],
        [
            'name' => 'المدير العام',
            'email' => 'manager@sama.com', 
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'role' => 'manager',
            'phone' => '0555123457',
            'status' => 'active'
        ],
        [
            'name' => 'موظف العقود',
            'email' => 'employee@sama.com',
            'password' => password_hash('123456', PASSWORD_DEFAULT), 
            'role' => 'employee',
            'phone' => '0555123458',
            'status' => 'active'
        ]
    ];
    
    $insertUser = "INSERT OR IGNORE INTO users (name, email, password, role, phone, status) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($insertUser);
    
    foreach ($defaultUsers as $user) {
        $stmt->execute([
            $user['name'],
            $user['email'], 
            $user['password'],
            $user['role'],
            $user['phone'],
            $user['status']
        ]);
    }
    echo "✅ تم إضافة المستخدمين الافتراضيين\n";
    
    // تحديث جدول العقود لإضافة حقول الموافقة
    $updateContractsTable = "
    ALTER TABLE contracts ADD COLUMN status TEXT NOT NULL DEFAULT 'draft';
    ALTER TABLE contracts ADD COLUMN created_by INTEGER;
    ALTER TABLE contracts ADD COLUMN approved_by INTEGER;
    ALTER TABLE contracts ADD COLUMN manager_notes TEXT;
    ALTER TABLE contracts ADD COLUMN approval_date DATETIME;
    ALTER TABLE contracts ADD COLUMN signed_date DATETIME;
    ";
    
    // تنفيذ كل أمر ALTER TABLE بشكل منفصل
    $alterCommands = [
        "ALTER TABLE contracts ADD COLUMN status TEXT NOT NULL DEFAULT 'draft'",
        "ALTER TABLE contracts ADD COLUMN created_by INTEGER", 
        "ALTER TABLE contracts ADD COLUMN approved_by INTEGER",
        "ALTER TABLE contracts ADD COLUMN manager_notes TEXT",
        "ALTER TABLE contracts ADD COLUMN approval_date DATETIME",
        "ALTER TABLE contracts ADD COLUMN signed_date DATETIME"
    ];
    
    foreach ($alterCommands as $command) {
        try {
            $pdo->exec($command);
        } catch (PDOException $e) {
            // تجاهل الأخطاء إذا كان العمود موجود بالفعل
            if (strpos($e->getMessage(), 'duplicate column name') === false) {
                echo "⚠️ تحذير: " . $e->getMessage() . "\n";
            }
        }
    }
    echo "✅ تم تحديث جدول العقود بحقول الموافقة\n";
    
    // إنشاء جدول الإشعارات
    $createNotificationsTable = "
    CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        type TEXT NOT NULL, -- contract_created, contract_approved, contract_rejected, contract_signed
        related_id INTEGER, -- contract_id
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (related_id) REFERENCES contracts(id)
    )";
    
    $pdo->exec($createNotificationsTable);
    echo "✅ تم إنشاء جدول الإشعارات\n";
    
    // إنشاء جدول سجل الأنشطة
    $createActivityLogTable = "
    CREATE TABLE IF NOT EXISTS activity_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        action TEXT NOT NULL,
        description TEXT NOT NULL,
        contract_id INTEGER,
        ip_address TEXT,
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (contract_id) REFERENCES contracts(id)
    )";
    
    $pdo->exec($createActivityLogTable);
    echo "✅ تم إنشاء جدول سجل الأنشطة\n";
    
    echo "\n🎉 تم ترقية النظام بنجاح!\n";
    echo "📧 المستخدمين الافتراضيين:\n";
    echo "   - admin@sama.com (مدير النظام) - كلمة المرور: 123456\n";
    echo "   - manager@sama.com (المدير العام) - كلمة المرور: 123456\n";
    echo "   - employee@sama.com (موظف العقود) - كلمة المرور: 123456\n";
    
} catch (PDOException $e) {
    echo "❌ خطأ في ترقية النظام: " . $e->getMessage() . "\n";
    exit(1);
}
?>