<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة العقود - مؤسسة سما البنيان التجارية</title>
    <link rel="stylesheet" href="/static/css/glassmorphism.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <?php
    require_once __DIR__ . '/../includes/navigation.php';
    echo renderNavigation();
    ?>
    
    <div class="welcome-container">
        <div class="welcome-header">
            <div class="company-logo">
                <h1>🏢 مؤسسة سما البنيان التجارية</h1>
                <p class="company-subtitle">نظام إدارة العقود المتقدم</p>
            </div>
        </div>

        <div class="welcome-content">
            <div class="glass-container">
                <div class="welcome-section">
                    <div class="section-icon">🎯</div>
                    <h2>نظام إدارة العقود المخصص</h2>
                    <p class="section-description">
                        هذا النظام مصمم خصيصاً لإدارة العقود الخاصة بمؤسسة سما البنيان التجارية. 
                        النظام يعتمد بالكامل على البيانات التي يدخلها المستخدمون وليس على بيانات جاهزة مسبقاً.
                    </p>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">👥</div>
                        <h3>إدارة ديناميكية</h3>
                        <p>جميع العقود والبيانات يتم إنشاؤها وإدارتها من قبل الموظفين والمدير داخل النظام</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">✍️</div>
                        <h3>التوقيع الإلكتروني</h3>
                        <p>نظام توقيع إلكتروني متقدم للمصادقة على العقود وتبادلها بين المدير والموظفين</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🔄</div>
                        <h3>تتبع شامل</h3>
                        <p>متابعة دورة حياة العقد من الإنشاء إلى التوقيع مع الإشعارات الفورية</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h3>تقارير متطورة</h3>
                        <p>إحصائيات وتقارير تفصيلية تتحدث تلقائياً مع كل عقد جديد</p>
                    </div>
                </div>

                <div class="getting-started">
                    <h3>🚀 البدء في استخدام النظام</h3>
                    <div class="steps-container">
                        <div class="step">
                            <span class="step-number">1</span>
                            <div class="step-content">
                                <h4>تسجيل الدخول</h4>
                                <p>ادخل بحساب المدير أو الموظف</p>
                            </div>
                        </div>
                        
                        <div class="step">
                            <span class="step-number">2</span>
                            <div class="step-content">
                                <h4>إضافة الموظفين</h4>
                                <p>المدير يضيف حسابات الموظفين</p>
                            </div>
                        </div>
                        
                        <div class="step">
                            <span class="step-number">3</span>
                            <div class="step-content">
                                <h4>إنشاء العقود</h4>
                                <p>الموظفون ينشئون العقود ويرفعونها للمراجعة</p>
                            </div>
                        </div>
                        
                        <div class="step">
                            <span class="step-number">4</span>
                            <div class="step-content">
                                <h4>التوقيع والاعتماد</h4>
                                <p>المدير يراجع ويوقع العقود إلكترونياً</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="login-section">
                    <h3>دخول النظام</h3>
                    <div class="login-options">
                        <a href="/login" class="login-btn primary">
                            <span class="btn-icon">🔐</span>
                            <span class="btn-text">تسجيل الدخول</span>
                        </a>
                        
                        <div class="admin-info">
                            <h4>🔑 معلومات حساب المدير الأساسي:</h4>
                            <div class="admin-credentials">
                                <p><strong>البريد الإلكتروني:</strong> admin@sama.com</p>
                                <p><strong>كلمة المرور:</strong> 123456</p>
                            </div>
                            <small>يمكنك تغيير هذه المعلومات بعد تسجيل الدخول</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="welcome-footer">
            <div class="footer-content">
                <p>&copy; 2025 مؤسسة سما البنيان التجارية - جميع الحقوق محفوظة</p>
                <p class="footer-note">نظام إدارة العقود المخصص - البيانات ديناميكية ومدارة بالكامل من قبل المستخدمين</p>
            </div>
        </div>
    </div>

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
            color: white;
            line-height: 1.6;
        }

        .welcome-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .welcome-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 0;
        }

        .company-logo h1 {
            font-size: 3.5rem;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #fff, #e0e0e0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .company-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .glass-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .welcome-section h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #fff;
        }

        .section-description {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #fff;
        }

        .feature-card p {
            opacity: 0.9;
            line-height: 1.6;
        }

        .getting-started {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
        }

        .getting-started h3 {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .step-number {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .step-content h4 {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .step-content p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .login-section {
            text-align: center;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
        }

        .login-section h3 {
            font-size: 1.8rem;
            margin-bottom: 30px;
        }

        .login-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .admin-info {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-info h4 {
            margin-bottom: 15px;
            color: #ffd700;
        }

        .admin-credentials {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .admin-credentials p {
            margin-bottom: 5px;
            font-family: monospace;
        }

        .admin-credentials strong {
            color: #4facfe;
        }

        .welcome-footer {
            text-align: center;
            margin-top: 40px;
            padding: 30px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-note {
            font-size: 0.9rem;
            opacity: 0.7;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .company-logo h1 {
                font-size: 2.5rem;
            }

            .glass-container {
                padding: 20px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .steps-container {
                grid-template-columns: 1fr;
            }

            .step {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</body>
</html>