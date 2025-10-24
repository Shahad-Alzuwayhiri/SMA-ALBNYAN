<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سما البنيان للتطوير والاستثمار العقاري</title>
    <?php
    // Defensive bootstrap for asset() helper
    if (!function_exists('asset')) {
        $helpers = __DIR__ . '/../includes/helpers.php';
        if (file_exists($helpers)) {
            require_once $helpers;
        }
        if (!function_exists('asset')) {
            function asset($path) { return $path; }
        }
    }
    ?>
    <link rel="stylesheet" href="<?php echo asset('assets/css/unified-theme.css'); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: center;
            border-left: 5px solid #1e3d59;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        
        .company-name {
            text-align: right;
        }
        
        .company-arabic {
            color: #1e3d59;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .company-english {
            color: #667eea;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .company-description {
            color: #666;
            font-size: 0.9rem;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            border-top: 4px solid #667eea;
        }
        
        .welcome-title {
            color: #1e3d59;
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        .status {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            border-left: 4px solid #667eea;
            transition: transform 0.3s ease;
        }
        
        .feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .feature-title {
            color: #1e3d59;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .feature-desc {
            color: #666;
            font-size: 0.9rem;
        }
        
        .links {
            margin: 30px 0;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            min-width: 150px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.4);
        }
        
        .info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            text-align: right;
            color: #495057;
            border-right: 4px solid #17a2b8;
        }
        
        .php-info {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 20px;
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <div class="logo-icon">SMA</div>
            <div class="company-name">
                <div class="company-arabic">سما البنيان</div>
                <div class="company-english">SMA ALBNYAN</div>
                <div class="company-description">للتطوير والاستثمار العقاري</div>
            </div>
        </div>
    </div>

    <div class="container">
        <h1 class="welcome-title">نظام إدارة العقود الإلكتروني</h1>
        <div class="subtitle">منصة متطورة لإدارة العقود والاستثمارات العقارية</div>
        
        <div class="status">
            ✅ النظام جاهز للاستخدام<br>
            🚀 مرحباً بك في منظومة سما البنيان الرقمية
        </div>

        <div class="features">
            <div class="feature">
                <div class="feature-icon">�</div>
                <div class="feature-title">إدارة العقود</div>
                <div class="feature-desc">إنشاء وتحرير ومتابعة جميع العقود الاستثمارية بكفاءة عالية</div>
            </div>
            <div class="feature">
                <div class="feature-icon">📊</div>
                <div class="feature-title">التقارير المالية</div>
                <div class="feature-desc">تقارير مفصلة عن الأرباح والاستثمارات وحالة العقود</div>
            </div>
            <div class="feature">
                <div class="feature-icon">👥</div>
                <div class="feature-title">إدارة العملاء</div>
                <div class="feature-desc">متابعة بيانات العملاء والمستثمرين بشكل منظم</div>
            </div>
        </div>
        
        <div class="info">
            <strong>🏢 مؤسسة سما البنيان للتطوير والاستثمار العقاري</strong><br><br>
            نظام إدارة العقود المتطور المصمم خصيصاً لتلبية احتياجات مؤسستنا في إدارة العقود الاستثمارية والعقارية.
            يوفر النظام واجهة سهلة الاستخدام ومميزات متقدمة لضمان أفضل تجربة للمستخدمين.
        </div>
        
        <div class="links">
            <a href="<?= asset('login.php') ?>" class="btn">🔑 تسجيل الدخول</a>
            <a href="/dashboard.php" class="btn btn-secondary">� لوحة التحكم</a>
        </div>
        
        <div class="php-info">
            <strong>معلومات النظام:</strong><br>
            PHP Version: <?php echo PHP_VERSION; ?><br>
            Server Time: <?php echo date('Y-m-d H:i:s'); ?><br>
            Status: ✅ Active
        </div>
    </div>
</body>
</html>