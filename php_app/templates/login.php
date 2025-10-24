<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام إدارة العقود سما</title>
    <link rel="stylesheet" href="/static/css/glassmorphism.css">
</head>
<body>
    <div class="page-container">
        <div class="auth-container">
            <div class="glass-container">
                <div class="page-header">
                    <h2>أهلاً وسهلاً بك</h2>
                    <p>سجل دخولك لحسابك في نظام إدارة العقود</p>
                </div>
                
                <form class="login-form" method="POST" action="/login" id="loginForm">
                    <div class="form-section">
                        <h3 class="section-title">
                            <span class="section-icon"></span>
                            بيانات تسجيل الدخول
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">البريد الإلكتروني</label>
                                <div class="input-container">
                                    <span class="input-icon"></span>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        placeholder="أدخل بريدك الإلكتروني"
                                        required
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">كلمة المرور</label>
                                <div class="input-container">
                                    <span class="input-icon"></span>
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        placeholder="أدخل كلمة المرور"
                                        required
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-btn" id="loginBtn">
                            <span class="btn-text">تسجيل الدخول</span>
                        </button>
                    </div>
                </form>

                <div class="auth-links">
                    <p>ليس لديك حساب؟ <a href="/register" class="link-primary">إنشاء حساب جديد</a></p>
                </div>

                <div class="demo-notice">
                    <h4> نسخة تجريبية</h4>
                    <p>هذا النظام في مرحلة التطوير والاختبار</p>
                    <div class="demo-accounts">
                        <div class="demo-account">
                            <strong>مدير:</strong> admin@sama.com - كلمة المرور: 123456
                            <button onclick="fillDemo('admin@sama.com', '123456')" class="btn-demo">تعبئة</button>
                        </div>
                        <div class="demo-account">
                            <strong>موظف:</strong> manager@sama.com - كلمة المرور: 123456
                            <button onclick="fillDemo('manager@sama.com', '123456')" class="btn-demo">تعبئة</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillDemo(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
