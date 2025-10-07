<?php
require_once '../includes/auth.php';

echo "<h1>Adding Sample Data</h1>";

try {
    // First, let's make sure we have the admin user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@sama.com'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    echo "<h2>Users Setup</h2>";
    if (!$admin) {
        // Create admin user
        $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['المدير العام', 'admin@sama.com', $hashedPassword, 'admin', 'active', '0501234567']);
        echo "<p>✅ Created admin user</p>";
    } else {
        echo "<p>✅ Admin user exists</p>";
    }
    
    // Check for manager
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'manager@sama.com'");
    $stmt->execute();
    $manager = $stmt->fetch();
    
    if (!$manager) {
        $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['مدير العقود', 'manager@sama.com', $hashedPassword, 'manager', 'active', '0501234568']);
        echo "<p>✅ Created manager user</p>";
        $managerId = $pdo->lastInsertId();
    } else {
        echo "<p>✅ Manager user exists</p>";
        $managerId = $manager['id'];
    }
    
    // Check for employee
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'employee@sama.com'");
    $stmt->execute();
    $employee = $stmt->fetch();
    
    if (!$employee) {
        $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['موظف العقود', 'employee@sama.com', $hashedPassword, 'employee', 'active', '0501234569']);
        echo "<p>✅ Created employee user</p>";
        $employeeId = $pdo->lastInsertId();
    } else {
        echo "<p>✅ Employee user exists</p>";
        $employeeId = $employee['id'];
    }
    
    // Now add sample contracts if they don't exist
    echo "<h2>Contracts Setup</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
    $contractCount = $stmt->fetch()['count'];
    
    if ($contractCount == 0) {
        $sampleContracts = [
            [
                'contract_number' => 'CT-001',
                'client_name' => 'شركة الرياض للتطوير',
                'client_email' => 'info@riyadh-dev.com',
                'client_phone' => '0112345678',
                'title' => 'عقد تطوير موقع إلكتروني',
                'description' => 'تطوير موقع إلكتروني متكامل لشركة الرياض للتطوير',
                'amount' => 50000.00,
                'status' => 'pending_review',
                'created_by' => $employeeId,
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+30 days')),
            ],
            [
                'contract_number' => 'CT-002',
                'client_name' => 'مؤسسة جدة التجارية',
                'client_email' => 'contact@jeddah-trade.com',
                'client_phone' => '0126789012',
                'title' => 'عقد تصميم هوية بصرية',
                'description' => 'تصميم هوية بصرية كاملة للمؤسسة',
                'amount' => 25000.00,
                'status' => 'approved',
                'created_by' => $employeeId,
                'approved_by' => $managerId,
                'start_date' => date('Y-m-d', strtotime('-10 days')),
                'end_date' => date('Y-m-d', strtotime('+20 days')),
            ],
            [
                'contract_number' => 'CT-003',
                'client_name' => 'شركة الدمام للخدمات',
                'client_email' => 'services@dammam.com',
                'client_phone' => '0138901234',
                'title' => 'عقد تطوير تطبيق جوال',
                'description' => 'تطوير تطبيق جوال لإدارة الخدمات',
                'amount' => 75000.00,
                'status' => 'signed',
                'created_by' => $employeeId,
                'approved_by' => $managerId,
                'start_date' => date('Y-m-d', strtotime('-20 days')),
                'end_date' => date('Y-m-d', strtotime('+40 days')),
            ],
            [
                'contract_number' => 'CT-004',
                'client_name' => 'مكتب الخبر للاستشارات',
                'client_email' => 'info@khobar-consulting.sa',
                'client_phone' => '0139876543',
                'title' => 'عقد استشارات تقنية',
                'description' => 'تقديم استشارات تقنية لمدة 6 أشهر',
                'amount' => 30000.00,
                'status' => 'draft',
                'created_by' => $employeeId,
                'start_date' => date('Y-m-d', strtotime('+5 days')),
                'end_date' => date('Y-m-d', strtotime('+185 days')),
            ]
        ];
        
        $contractStmt = $pdo->prepare("
            INSERT INTO contracts (
                contract_number, client_name, client_email, client_phone, 
                title, description, amount, status, created_by, approved_by,
                start_date, end_date, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        foreach ($sampleContracts as $contract) {
            $contractStmt->execute([
                $contract['contract_number'],
                $contract['client_name'],
                $contract['client_email'],
                $contract['client_phone'],
                $contract['title'],
                $contract['description'],
                $contract['amount'],
                $contract['status'],
                $contract['created_by'],
                $contract['approved_by'] ?? null,
                $contract['start_date'],
                $contract['end_date']
            ]);
        }
        
        echo "<p>✅ Created " . count($sampleContracts) . " sample contracts</p>";
    } else {
        echo "<p>✅ Contracts exist ($contractCount contracts)</p>";
    }
    
    // Add sample notifications
    echo "<h2>Notifications Setup</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $notificationCount = $stmt->fetch()['count'];
    
    if ($notificationCount == 0) {
        $sampleNotifications = [
            [
                'user_id' => $managerId,
                'title' => 'عقد جديد بانتظار المراجعة',
                'message' => 'تم إنشاء عقد جديد CT-001 وهو بانتظار مراجعتك',
                'type' => 'contract_review',
                'is_read' => 0
            ],
            [
                'user_id' => $employeeId,
                'title' => 'تم الموافقة على العقد',
                'message' => 'تم الموافقة على العقد CT-002 من قبل المدير',
                'type' => 'contract_approved',
                'is_read' => 0
            ],
            [
                'user_id' => $employeeId,
                'title' => 'عقد جديد موقع',
                'message' => 'تم توقيع العقد CT-003 بنجاح',
                'type' => 'contract_signed',
                'is_read' => 1
            ]
        ];
        
        $notificationStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        foreach ($sampleNotifications as $notification) {
            $notificationStmt->execute([
                $notification['user_id'],
                $notification['title'],
                $notification['message'],
                $notification['type'],
                $notification['is_read']
            ]);
        }
        
        echo "<p>✅ Created " . count($sampleNotifications) . " sample notifications</p>";
    } else {
        echo "<p>✅ Notifications exist ($notificationCount notifications)</p>";
    }
    
    echo "<h2>✅ Sample Data Setup Complete!</h2>";
    echo "<h3>Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@sama.com / 123456</li>";
    echo "<li><strong>Manager:</strong> manager@sama.com / 123456</li>";
    echo "<li><strong>Employee:</strong> employee@sama.com / 123456</li>";
    echo "</ul>";
    
    echo "<p><a href='/login.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>