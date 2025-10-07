<?php
session_start();

echo "<h1>System Comprehensive Test</h1>";

// Test different user roles
$roles = [
    'manager' => ['id' => 2, 'name' => 'مدير العقود', 'email' => 'manager@sama.com'],
    'employee' => ['id' => 3, 'name' => 'موظف العقود', 'email' => 'employee@sama.com'],
    'admin' => ['id' => 1, 'name' => 'المدير العام', 'email' => 'admin@sama.com']
];

$currentRole = $_GET['role'] ?? 'manager';

if (isset($roles[$currentRole])) {
    $_SESSION['user_id'] = $roles[$currentRole]['id'];
    $_SESSION['user_name'] = $roles[$currentRole]['name'];
    $_SESSION['user_email'] = $roles[$currentRole]['email'];
    $_SESSION['user_role'] = $currentRole;
    
    echo "<div class='alert alert-success'>Logged in as: {$roles[$currentRole]['name']} ($currentRole)</div>";
} else {
    echo "<div class='alert alert-danger'>Invalid role selected</div>";
}

echo "<h2>Test Different Roles:</h2>";
echo "<p>";
foreach ($roles as $role => $data) {
    $active = ($currentRole === $role) ? 'btn-primary' : 'btn-outline-primary';
    echo "<a href='?role=$role' class='btn $active me-2'>$role</a>";
}
echo "</p>";

echo "<h2>Available Pages:</h2>";

$pages = [
    'Manager Dashboard' => '/manager_dashboard.php',
    'Employee Dashboard' => '/employee_dashboard.php',
    'Contracts List' => '/contracts_list.php',
    'Create Contract' => '/create_contract.php',
    'Notifications' => '/notifications.php',
    'Profile' => '/profile.php',
    'Manage Employees' => '/manage_employees.php',
];

echo "<div class='row'>";
foreach ($pages as $title => $url) {
    echo "<div class='col-md-4 mb-3'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>$title</h5>";
    echo "<a href='$url' class='btn btn-primary' target='_blank'>Open Page</a>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";

echo "<h2>Quick Tests:</h2>";

try {
    require_once '../includes/auth.php';
    
    echo "<div class='row'>";
    
    // Test 1: Database Connection
    echo "<div class='col-md-6 mb-3'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>Database Test</h5>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
    $contractCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $notificationCount = $stmt->fetch()['count'];
    
    echo "<p>✅ Users: $userCount</p>";
    echo "<p>✅ Contracts: $contractCount</p>";
    echo "<p>✅ Notifications: $notificationCount</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // Test 2: Auth System
    echo "<div class='col-md-6 mb-3'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>Auth System Test</h5>";
    
    if ($auth->isLoggedIn()) {
        $user = $auth->getCurrentUser();
        echo "<p>✅ User logged in: {$user['name']}</p>";
        echo "<p>✅ Role: {$user['role']}</p>";
        echo "<p>✅ Email: {$user['email']}</p>";
    } else {
        echo "<p>❌ User not logged in</p>";
    }
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
.alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }
.alert-danger { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
.btn { display: inline-block; padding: 6px 12px; margin-bottom: 0; font-size: 14px; font-weight: normal; line-height: 1.42857143; text-align: center; white-space: nowrap; vertical-align: middle; cursor: pointer; border: 1px solid transparent; border-radius: 4px; text-decoration: none; }
.btn-primary { color: #fff; background-color: #337ab7; border-color: #2e6da4; }
.btn-outline-primary { color: #337ab7; background-color: transparent; border-color: #337ab7; }
.row { display: flex; flex-wrap: wrap; margin: -15px; }
.col-md-4, .col-md-6 { flex: 0 0 33.333333%; max-width: 33.333333%; padding: 15px; }
.col-md-6 { flex: 0 0 50%; max-width: 50%; }
.card { border: 1px solid #ddd; border-radius: 4px; }
.card-body { padding: 15px; }
.card-title { margin-top: 0; margin-bottom: 10px; }
.me-2 { margin-right: 8px; }
.mb-3 { margin-bottom: 15px; }
</style>";
?>