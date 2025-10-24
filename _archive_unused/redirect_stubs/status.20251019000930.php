<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار النظام - سما البنيان</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 40px;
            background: #f8f9fa;
            color: #2c3e50;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #2563eb; margin-bottom: 20px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d5f4e6; border-left: 4px solid #27ae60; }
        .error { background: #fdeaea; border-left: 4px solid #e74c3c; }
        .links { margin-top: 30px; }
        .links a {
            display: inline-block;
            margin: 10px;
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .links a:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏢 نظام سما البنيان للعقود</h1>
        
        <div class="status success">
            <strong>✅ النظام يعمل بنجاح!</strong><br>
            تم تشغيل الموقع على XAMPP بنجاح.
        </div>

        <?php
        // Test database connection
        try {
            require_once 'config/database.php';
            $pdo = getDatabaseConnection();
            echo '<div class="status success"><strong>✅ قاعدة البيانات متصلة</strong><br>قاعدة البيانات تعمل بشكل صحيح.</div>';
            
            // Count contracts
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
            $result = $stmt->fetch();
            echo '<div class="status success"><strong>📊 عدد العقود الحالية: ' . $result['count'] . '</strong></div>';
            
        } catch (Exception $e) {
            echo '<div class="status error"><strong>❌ خطأ في قاعدة البيانات:</strong><br>' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>

        <div class="links">
            <h3>الصفحات المتاحة:</h3>
            <a href="<?php echo asset('index.php'); ?>">🏠 الصفحة الرئيسية</a>
            <a href="<?php echo asset('create_contract.php'); ?>">📝 إنشاء عقد جديد</a>
            <a href="<?php echo asset('contracts_list.php'); ?>">📋 قائمة العقود</a>
            <a href="diagnostic.php">🔧 التشخيص الشامل</a>
            <a href="test.php">🧪 اختبار النظام</a>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;">
            <strong>معلومات الخادم:</strong><br>
            PHP Version: <?php echo phpversion(); ?><br>
            Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'غير معروف'; ?><br>
            Document Root: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'غير معروف'; ?><br>
            Current Time: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html>