<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة العقود - سما</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            direction: rtl;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 500px;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }
        .logo {
            font-size: 2.5em;
            color: #667eea;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .status {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #c3e6cb;
        }
        .links {
            display: grid;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        .btn.secondary {
            background: #6c757d;
        }
        .btn.secondary:hover {
            background: #545b62;
        }
        .server-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🏢 نظام إدارة العقود</div>
        <h1>مرحباً بك في سما</h1>
        
        <div class="status">
            ✅ الخادم يعمل بنجاح!<br>
            🔧 PHP 8.2.12 جاهز<br>
            🚀 النظام مُحدث ومُحسن
        </div>
        
        <div class="links">
            <a href="/login" class="btn">🔐 تسجيل الدخول</a>
            <a href="/dashboard" class="btn">📊 لوحة التحكم</a>
            <a href="/contracts" class="btn">📄 العقود</a>
            <a href="/notifications" class="btn">🔔 الإشعارات</a>
            <a href="/test.php" class="btn secondary">⚙️ معلومات PHP</a>
        </div>
        
        <div class="server-info">
            الخادم: localhost:8000<br>
            حالة النظام: محدث وجاهز للاستخدام<br>
            التاريخ: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html>