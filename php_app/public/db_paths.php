<?php
echo "<h1>Database Path Analysis</h1>";

$databases = [
    'Main Directory' => __DIR__ . '/../contracts.db',
    'Database Directory' => __DIR__ . '/../database/contracts.db',
    'Public Directory' => __DIR__ . '/contracts.db'
];

foreach ($databases as $name => $path) {
    echo "<h2>$name: $path</h2>";
    
    if (file_exists($path)) {
        echo "<p>✅ File exists (Size: " . filesize($path) . " bytes)</p>";
        
        try {
            $pdo = new PDO("sqlite:$path");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check users
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $userCount = $stmt->fetch()['count'] ?? 0;
            echo "<p>Users: $userCount</p>";
            
            // Check contracts
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
            $contractCount = $stmt->fetch()['count'] ?? 0;
            echo "<p>Contracts: $contractCount</p>";
            
            // Check notifications
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
                $notificationCount = $stmt->fetch()['count'] ?? 0;
                echo "<p>Notifications: $notificationCount</p>";
            } catch (Exception $e) {
                echo "<p>Notifications: Table doesn't exist</p>";
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ File does not exist</p>";
    }
    
    echo "<hr>";
}

echo "<h2>auth.php Configuration</h2>";
$authPath = __DIR__ . '/../includes/auth.php';
$authContent = file_get_contents($authPath);
if (preg_match('/\$database_path = (.*?);/', $authContent, $matches)) {
    echo "<p>Auth uses: <code>" . $matches[1] . "</code></p>";
}
?>