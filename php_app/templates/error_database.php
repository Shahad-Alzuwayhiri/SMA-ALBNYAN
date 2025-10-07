<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطأ في النظام - سما البنيان التجارية</title>
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
        }
        
        .error-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            margin: 2rem;
        }
        
        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .error-title {
            color: #e74c3c;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .error-message {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .error-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #e74c3c;
            text-align: right;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .solutions {
            margin-top: 2rem;
            text-align: right;
        }
        
        .solutions h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .solutions ul {
            list-style: none;
            padding: 0;
        }
        
        .solutions li {
            background: #e8f4fd;
            margin: 0.5rem 0;
            padding: 0.75rem;
            border-radius: 5px;
            border-right: 3px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1 class="error-title">خطأ في النظام</h1>
        <p class="error-message">عذراً، حدث خطأ أثناء محاولة الاتصال بقاعدة البيانات</p>
        
        <?php if (isset($error_details)): ?>
        <div class="error-details">
            <strong>تفاصيل الخطأ:</strong><br>
            <?= htmlspecialchars($error_details) ?>
        </div>
        <?php endif; ?>
        
        <div class="error-actions">
            <a href="/" class="btn btn-primary">العودة للرئيسية</a>
            <button onclick="location.reload()" class="btn btn-secondary">إعادة المحاولة</button>
        </div>
        
        <div class="solutions">
            <h3>حلول مقترحة:</h3>
            <ul>
                <li>تأكد من تشغيل خدمة MySQL في XAMPP</li>
                <li>تحقق من إعدادات قاعدة البيانات</li>
                <li>أعد تشغيل خادم XAMPP</li>
                <li>تواصل مع مدير النظام إذا استمر الخطأ</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Auto retry after 10 seconds
        setTimeout(() => {
            console.log('Auto retrying...');
            location.reload();
        }, 10000);
    </script>
</body>
</html>