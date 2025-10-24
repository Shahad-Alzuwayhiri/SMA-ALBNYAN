<?php
echo "<h1>Database Consolidation and Fix</h1>";

// Define the primary database path
$primary_db_path = __DIR__ . '/../database/contracts.db';
$main_db_path = __DIR__ . '/../contracts.db';

echo "<h2>Step 1: Database Analysis</h2>";

try {
    // Check which database has more data
    $primary_size = file_exists($primary_db_path) ? filesize($primary_db_path) : 0;
    $main_size = file_exists($main_db_path) ? filesize($main_db_path) : 0;
    
    echo "<p>Database directory database: $primary_size bytes</p>";
    echo "<p>Main directory database: $main_size bytes</p>";
    
    // Use the larger database as the primary
    $source_db = ($primary_size >= $main_size) ? $primary_db_path : $main_db_path;
    $target_db = $primary_db_path;
    
    echo "<p>Using as primary: $source_db</p>";
    
    // Ensure target directory exists
    $target_dir = dirname($target_db);
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
        echo "<p>✅ Created database directory</p>";
    }
    
    // Copy source to target if different
    if ($source_db !== $target_db && file_exists($source_db)) {
        copy($source_db, $target_db);
        echo "<p>✅ Copied database to standard location</p>";
    }
    
    echo "<h2>Step 2: Database Setup and Sample Data</h2>";
    
    // Connect to the database
    $pdo = new PDO("sqlite:$target_db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'employee',
            status TEXT DEFAULT 'active',
            phone TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contracts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_number TEXT UNIQUE,
            client_name TEXT NOT NULL,
            client_email TEXT,
            client_phone TEXT,
            title TEXT NOT NULL,
            description TEXT,
            amount DECIMAL(10,2) DEFAULT 0,
            status TEXT DEFAULT 'draft',
            created_by INTEGER,
            approved_by INTEGER,
            start_date DATE,
            end_date DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id),
            FOREIGN KEY (approved_by) REFERENCES users(id)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            message TEXT,
            type TEXT DEFAULT 'info',
            is_read INTEGER DEFAULT 0,
            related_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            action TEXT NOT NULL,
            description TEXT,
            contract_id INTEGER,
            ip_address TEXT,
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (contract_id) REFERENCES contracts(id)
        )
    ");
    
    echo "<p>✅ Database tables created/verified</p>";
    
    // Add users if they don't exist
    $users = [
        ['المدير العام', 'admin@sama.com', 'admin', '0501234567'],
        ['مدير العقود', 'manager@sama.com', 'manager', '0501234568'],
        ['موظف العقود', 'employee@sama.com', 'employee', '0501234569'],
    ];
    
    $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
    
    foreach ($users as $userData) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$userData[1]]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, phone) VALUES (?, ?, ?, ?, 'active', ?)");
            $stmt->execute([$userData[0], $userData[1], $hashedPassword, $userData[2], $userData[3]]);
            echo "<p>✅ Created user: {$userData[1]}</p>";
        }
    }
    
    // Get user IDs for sample data
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'employee' LIMIT 1");
    $stmt->execute();
    $employee = $stmt->fetch();
    $employeeId = $employee['id'] ?? 3;
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'manager' LIMIT 1");
    $stmt->execute();
    $manager = $stmt->fetch();
    $managerId = $manager['id'] ?? 2;
    
    // Add contracts if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
    $contractCount = $stmt->fetch()['count'];
    
    if ($contractCount == 0) {
        $contracts = [
            ['CT-001', 'شركة الرياض للتطوير', 'info@riyadh-dev.com', '0112345678', 'عقد تطوير موقع إلكتروني', 'تطوير موقع إلكتروني متكامل', 50000.00, 'pending_review', $employeeId, null],
            ['CT-002', 'مؤسسة جدة التجارية', 'contact@jeddah-trade.com', '0126789012', 'عقد تصميم هوية بصرية', 'تصميم هوية بصرية كاملة', 25000.00, 'approved', $employeeId, $managerId],
            ['CT-003', 'شركة الدمام للخدمات', 'services@dammam.com', '0138901234', 'عقد تطوير تطبيق جوال', 'تطوير تطبيق جوال لإدارة الخدمات', 75000.00, 'signed', $employeeId, $managerId],
            ['CT-004', 'مكتب الخبر للاستشارات', 'info@khobar-consulting.sa', '0139876543', 'عقد استشارات تقنية', 'تقديم استشارات تقنية لمدة 6 أشهر', 30000.00, 'draft', $employeeId, null],
        ];
        
        $contractStmt = $pdo->prepare("
            INSERT INTO contracts (contract_number, client_name, client_email, client_phone, title, description, amount, status, created_by, approved_by, start_date, end_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, date('now'), date('now', '+30 days'))
        ");
        
        foreach ($contracts as $contract) {
            $contractStmt->execute($contract);
        }
        
        echo "<p>✅ Created " . count($contracts) . " sample contracts</p>";
    }
    
    // Add notifications if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $notificationCount = $stmt->fetch()['count'];
    
    if ($notificationCount == 0) {
        $notifications = [
            [$managerId, 'عقد جديد بانتظار المراجعة', 'تم إنشاء عقد جديد CT-001 وهو بانتظار مراجعتك', 'contract_review', 0],
            [$employeeId, 'تم الموافقة على العقد', 'تم الموافقة على العقد CT-002 من قبل المدير', 'contract_approved', 0],
            [$employeeId, 'عقد جديد موقع', 'تم توقيع العقد CT-003 بنجاح', 'contract_signed', 1],
        ];
        
        $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($notifications as $notification) {
            $notificationStmt->execute($notification);
        }
        
        echo "<p>✅ Created " . count($notifications) . " sample notifications</p>";
    }
    
    echo "<h2>✅ Database Setup Complete!</h2>";
    echo "<p><strong>Primary Database:</strong> $target_db</p>";
    echo "<p><strong>File Size:</strong> " . filesize($target_db) . " bytes</p>";
    
    // Final counts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
    $contractCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $notificationCount = $stmt->fetch()['count'];
    
    echo "<p><strong>Final Data Count:</strong></p>";
    echo "<ul>";
    echo "<li>Users: $userCount</li>";
    echo "<li>Contracts: $contractCount</li>";
    echo "<li>Notifications: $notificationCount</li>";
    echo "</ul>";
    
    echo "<h3>Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@sama.com / 123456</li>";
    echo "<li><strong>Manager:</strong> manager@sama.com / 123456</li>";
    echo "<li><strong>Employee:</strong> employee@sama.com / 123456</li>";
    echo "</ul>";
    
    echo "<p><a href='/login.php' class='btn btn-primary'>Test Login System</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>