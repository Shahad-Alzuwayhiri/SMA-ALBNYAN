<?php
/**
 * Diagnostic Tool - Contract System
 * أداة تشخيص النظام
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تشخيص النظام - سما البنيان</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 20px; background: #f8f9fa; color: #2c3e50; 
        }
        .container { 
            max-width: 1000px; margin: 0 auto; 
            background: white; padding: 30px; 
            border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header { 
            text-align: center; margin-bottom: 30px; 
            color: #d4af37; border-bottom: 2px solid #d4af37; 
            padding-bottom: 15px; 
        }
        .section { 
            margin: 20px 0; padding: 15px; 
            border: 1px solid #ddd; border-radius: 8px; 
        }
        .success { background: #d5f4e6; border-color: #27ae60; }
        .error { background: #fdeaea; border-color: #e74c3c; }
        .warning { background: #fef9e7; border-color: #f39c12; }
        .info { background: #ebf3fd; border-color: #3498db; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: right; }
        th { background: #f8f9fa; }
        .status-ok { color: #27ae60; font-weight: bold; }
        .status-error { color: #e74c3c; font-weight: bold; }
        .status-warning { color: #f39c12; font-weight: bold; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .quick-links { 
            display: flex; gap: 10px; margin: 20px 0; 
            flex-wrap: wrap; justify-content: center; 
        }
        .quick-links a { 
            padding: 10px 15px; background: #3498db; 
            color: white; border-radius: 5px; 
        }
        .quick-links a:hover { background: #2980b9; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 تشخيص نظام سما البنيان للعقود</h1>
            <p>فحص شامل لحالة النظام والملفات</p>
        </div>

        <?php
        $errors = [];
        $warnings = [];
        $success = [];
        
        // Test 1: PHP Version
        $phpVersion = phpversion();
        if (version_compare($phpVersion, '8.0', '>=')) {
            $success[] = "إصدار PHP: {$phpVersion} ✅";
        } else {
            $errors[] = "إصدار PHP قديم: {$phpVersion} - يتطلب 8.0+";
        }
        
        // Test 2: Required Extensions
        $extensions = ['pdo', 'pdo_sqlite', 'mbstring', 'openssl'];
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $success[] = "الإضافة {$ext} مفعلة ✅";
            } else {
                $errors[] = "الإضافة {$ext} غير مفعلة ❌";
            }
        }
        
        // Test 3: File System
        $basePath = __DIR__;
        $requiredDirs = [
            'public' => 'مجلد الصفحات العامة',
            'config' => 'مجلد الإعدادات',
            'database' => 'مجلد قاعدة البيانات',
            'includes' => 'مجلد الملفات المشتركة'
        ];
        
        foreach ($requiredDirs as $dir => $desc) {
            $path = $basePath . '/' . $dir;
            if (is_dir($path)) {
                $success[] = "{$desc}: موجود ✅";
            } else {
                $errors[] = "{$desc}: غير موجود ❌";
            }
        }
        
        // Test 4: Key Files
        $requiredFiles = [
            'public/index.php' => 'الصفحة الرئيسية',
            'public/create_contract.php' => 'صفحة إنشاء العقود',
            'config/database.php' => 'إعدادات قاعدة البيانات',
            'database/contracts.db' => 'قاعدة البيانات'
        ];
        
        foreach ($requiredFiles as $file => $desc) {
            $path = $basePath . '/' . $file;
            if (file_exists($path)) {
                $size = filesize($path);
                $success[] = "{$desc}: موجود ({$size} بايت) ✅";
            } else {
                $errors[] = "{$desc}: غير موجود ❌";
            }
        }
        
        // Test 5: Database Connection
        try {
            require_once 'config/database.php';
            if (function_exists('getDatabaseConnection')) {
                $pdo = getDatabaseConnection();
            } else {
                // Fallback: create PDO directly
                $config = $dbConfig['connections'][$dbConfig['default']];
                $pdo = new PDO("sqlite:" . $config['database'], null, null, $config['options']);
            }
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
            $result = $stmt->fetch();
            $success[] = "قاعدة البيانات: متصلة ({$result['count']} عقد) ✅";
        } catch (Exception $e) {
            $errors[] = "قاعدة البيانات: خطأ في الاتصال - " . $e->getMessage();
        }
        
        // Test 6: Permissions
        $writableDirs = ['database', 'storage/logs', 'public/uploads'];
        foreach ($writableDirs as $dir) {
            $path = $basePath . '/' . $dir;
            if (is_dir($path) && is_writable($path)) {
                $success[] = "صلاحيات الكتابة في {$dir}: متاحة ✅";
            } else {
                $warnings[] = "صلاحيات الكتابة في {$dir}: غير متاحة ⚠️";
            }
        }
        ?>

        <!-- Results Display -->
        <?php if (!empty($success)): ?>
            <div class="section success">
                <h3>✅ العناصر السليمة</h3>
                <ul>
                    <?php foreach ($success as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($warnings)): ?>
            <div class="section warning">
                <h3>⚠️ تحذيرات</h3>
                <ul>
                    <?php foreach ($warnings as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="section error">
                <h3>❌ مشاكل تحتاج حل</h3>
                <ul>
                    <?php foreach ($errors as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- System Information -->
        <div class="section info">
            <h3>💻 معلومات النظام</h3>
            <table>
                <tr><th>المعلومة</th><th>القيمة</th></tr>
                <tr><td>إصدار PHP</td><td><?= phpversion() ?></td></tr>
                <tr><td>خادم الويب</td><td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'غير محدد' ?></td></tr>
                <tr><td>نظام التشغيل</td><td><?= php_uname() ?></td></tr>
                <tr><td>المسار الأساسي</td><td><?= __DIR__ ?></td></tr>
                <tr><td>مجلد الجذر</td><td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'غير محدد' ?></td></tr>
                <tr><td>الرابط الحالي</td><td><?= 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI'] ?></td></tr>
            </table>
        </div>

        <!-- Quick Navigation -->
        <div class="section info">
            <h3>🔗 روابط سريعة</h3>
            <div class="quick-links">
                <a href="index.php">الصفحة الرئيسية</a>
                <a href="<?php echo asset(''); ?>">مجلد Public</a>
                <a href="<?php echo asset('index.php'); ?>">النظام مباشرة</a>
                <a href="test.php">اختبار بسيط</a>
            </div>
        </div>

        <!-- Overall Status -->
        <div class="section <?= empty($errors) ? 'success' : 'error' ?>">
            <h3>📊 الحالة العامة</h3>
            <?php if (empty($errors)): ?>
                <p class="status-ok">✅ النظام جاهز للعمل!</p>
                <p>يمكنك الآن الوصول إلى النظام عبر: 
                    <a href="<?php echo asset('index.php'); ?>">النقر هنا</a>
                </p>
            <?php else: ?>
                <p class="status-error">❌ يوجد <?= count($errors) ?> مشكلة تحتاج حل</p>
                <p>يرجى حل المشاكل المذكورة أعلاه أولاً.</p>
            <?php endif; ?>
        </div>

        <hr style="margin: 30px 0;">
        <p style="text-align: center; color: #7f8c8d; font-size: 14px;">
            © 2025 شركة سما البنيان للتطوير العقاري - أداة التشخيص
        </p>
    </div>
</body>
</html>