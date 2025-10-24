<?php
/**
 * Test File - Sama Al-Bunyan Contracts Platform
 * ملف اختبار للتأكد من عمل النظام
 */

echo "<h1>🎉 مرحباً بك في نظام سما البنيان للعقود</h1>";
echo "<p>النظام يعمل بنجاح!</p>";

// Test database connection
try {
    require_once 'config/database.php';
    
    // Get PDO connection
    if (function_exists('getDatabaseConnection')) {
        $pdo = getDatabaseConnection();
    } else {
        // Fallback: create PDO directly
        $config = $dbConfig['connections'][$dbConfig['default']];
        $pdo = new PDO("sqlite:" . $config['database'], null, null, $config['options']);
    }
    
    echo "<div style='color: green;'>✅ قاعدة البيانات متصلة بنجاح</div>";
    
    // Count contracts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
    $result = $stmt->fetch();
    echo "<div>📊 عدد العقود: " . $result['count'] . "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في قاعدة البيانات: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h3>الروابط المتاحة:</h3>";
echo "<ul>";
echo "<li><a href='public/index.php'>الصفحة الرئيسية</a></li>";
echo "<li><a href='public/create_contract.php'>إنشاء عقد جديد</a></li>";
echo "<li><a href='public/contracts_list.php'>قائمة العقود</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>معلومات الخادم:</strong></p>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li>Current Directory: " . __DIR__ . "</li>";
echo "</ul>";
?>