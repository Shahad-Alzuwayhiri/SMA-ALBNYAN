<?php
require_once '../includes/auth.php';

echo "<h1>Database Test</h1>";

try {
    // Test users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "<p>Users in database: $userCount</p>";
    
    if ($userCount > 0) {
        $stmt = $pdo->query("SELECT email, role FROM users LIMIT 5");
        $users = $stmt->fetchAll();
        echo "<h3>Sample Users:</h3><ul>";
        foreach ($users as $user) {
            echo "<li>{$user['email']} - {$user['role']}</li>";
        }
        echo "</ul>";
    }
    
    // Test contracts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
    $contractCount = $stmt->fetch()['count'];
    echo "<p>Contracts in database: $contractCount</p>";
    
    if ($contractCount > 0) {
        $stmt = $pdo->query("SELECT client_name, status, amount FROM contracts LIMIT 5");
        $contracts = $stmt->fetchAll();
        echo "<h3>Sample Contracts:</h3><ul>";
        foreach ($contracts as $contract) {
            echo "<li>{$contract['client_name']} - {$contract['status']} - {$contract['amount']}</li>";
        }
        echo "</ul>";
    }
    
    // Test notifications
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $notificationCount = $stmt->fetch()['count'];
    echo "<p>Notifications in database: $notificationCount</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>