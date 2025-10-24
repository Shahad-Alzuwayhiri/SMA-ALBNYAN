<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سما البنيان التجارية - نظام إدارة العقود</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        
        .status {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            color: #a7f3d0;
            font-size: 1.1rem;
        }
        
        .links {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(79, 172, 254, 0.4);
        }
        
        .info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: right;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .php-info {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏢 سما البنيان التجارية</h1>
        <div class="subtitle">نظام إدارة العقود المتقدم</div>
        
        <div class="status">
            ✅ الخادم يعمل بنجاح!<br>
            🚀 النظام جاهز للاستخدام
        </div>
        
        <div class="info">
            <strong>🎯 مرحباً بك في نظام إدارة العقود</strong><br><br>
            هذا النظام مخصص لإدارة عقود مؤسسة سما البنيان التجارية بطريقة احترافية وحديثة.
            يمكنك تسجيل الدخول للبدء في استخدام النظام.
        </div>
        
        <div class="links">
            <a href="/login" class="btn">🔑 تسجيل الدخول</a>
            <a href="/register" class="btn">👤 إنشاء حساب</a>
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