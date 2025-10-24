<?php
/**
 * Simple Test Page - No Authentication Required
 * صفحة اختبار بسيطة بدون مصادقة
 */
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار الاتصال - سما البنيان</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 40px;
            background: #f8f9fa;
            color: #2c3e50;
            line-height: 1.6;
            direction: rtl;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #2563eb; margin-bottom: 20px; }
        .success { background: #d5f4e6; padding: 15px; border-left: 4px solid #27ae60; margin: 10px 0; }
        .link { display: inline-block; margin: 10px; padding: 10px 15px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏢 اختبار الاتصال - سما البنيان</h1>
        
        <div class="success">
            <strong>✅ الصفحة تعمل بنجاح!</strong><br>
            هذه صفحة اختبار للتأكد من عمل النظام.
        </div>

        <p><strong>الوقت الحالي:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>مسار الملف:</strong> <?php echo __FILE__; ?></p>
        <p><strong>مجلد العمل:</strong> <?php echo __DIR__; ?></p>

        <h3>روابط الاختبار:</h3>
        <a href="../status.php" class="link">🔍 فحص الحالة</a>
        <a href="../sitemap.php" class="link">🗺️ خريطة الموقع</a>
        <a href="index.php" class="link">🏠 الصفحة الرئيسية</a>
        
        <?php
        // Test database connection
        try {
            require_once '../config/database.php';
            $pdo = getDatabaseConnection();
            echo '<div class="success"><strong>✅ قاعدة البيانات متصلة</strong></div>';
            
            // Count contracts
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
            $result = $stmt->fetch();
            echo '<p><strong>عدد العقود في قاعدة البيانات:</strong> ' . $result['count'] . '</p>';
            
        } catch (Exception $e) {
            echo '<div style="background: #fdeaea; padding: 15px; border-left: 4px solid #e74c3c; margin: 10px 0;"><strong>❌ خطأ في قاعدة البيانات:</strong><br>' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</body>
</html>