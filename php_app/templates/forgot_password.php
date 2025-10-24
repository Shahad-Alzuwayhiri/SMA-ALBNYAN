<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نسيت كلمة المرور - نظام إدارة العقود سما</title>
    <link rel="stylesheet" href="/static/css/glassmorphism.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <div class="page-container">
        <div class="auth-container">
            <div class="glass-container">
                <div class="page-header">
                    <h2>استعادة كلمة المرور</h2>
                    <p>أدخل بريدك الإلكتروني وسنرسل لك رابط لإعادة تعيين كلمة المرور</p>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <span class="alert-icon">✅</span>
                        <?= htmlspecialchars($_SESSION['success']) ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">❌</span>
                        <?= htmlspecialchars($_SESSION['error']) ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['info'])): ?>
                    <div class="alert alert-info">
                        <span class="alert-icon">ℹ️</span>
                        <?= htmlspecialchars($_SESSION['info']) ?>
                    </div>
                    <?php unset($_SESSION['info']); ?>
                <?php endif; ?>

                <form class="login-form" method="POST" action="/forgot-password" id="forgotPasswordForm">
                    <div class="form-section">
                        <h3 class="section-title">
                            <span class="section-icon">📧</span>
                            معلومات الحساب
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">البريد الإلكتروني</label>
                                <div class="input-container">
                                    <span class="input-icon">📧</span>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        placeholder="أدخل بريدك الإلكتروني المسجل"
                                        value="<?= htmlspecialchars($_SESSION['old']['email'] ?? '') ?>"
                                        required
                                        autocomplete="email"
                                    >
                                </div>
                                <small class="form-hint">سنرسل لك رابط إعادة تعيين كلمة المرور على هذا البريد</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-btn" id="forgotPasswordBtn">
                            <span class="btn-content">
                                <span class="btn-icon">📬</span>
                                <span class="btn-text">إرسال رابط الاستعادة</span>
                            </span>
                            <span class="btn-loading" style="display: none;">
                                <span class="loading-spinner"></span>
                                <span class="loading-text">جاري الإرسال...</span>
                            </span>
                        </button>
                    </div>
                </form>

                <div class="auth-links">
                    <p>تذكرت كلمة المرور؟ <a href="/login" class="link-primary">تسجيل الدخول</a></p>
                    <p>ليس لديك حساب؟ <a href="/register" class="link-secondary">إنشاء حساب جديد</a></p>
                </div>

                <!-- Info Box -->
                <div class="info-box">
                    <h4>💡 نصائح مهمة</h4>
                    <ul>
                        <li>تأكد من إدخال البريد الإلكتروني الصحيح المسجل في النظام</li>
                        <li>تحقق من صندوق البريد الوارد وملف الرسائل غير المرغوب فيها</li>
                        <li>رابط إعادة التعيين صالح لمدة 24 ساعة فقط</li>
                        <li>إذا لم تستلم الرسالة خلال 10 دقائق، يمكنك إعادة المحاولة</li>
                    </ul>
                </div>

                <!-- Demo Notice -->
                <div class="demo-notice">
                    <h4>🧪 نسخة تجريبية</h4>
                    <p>في النسخة التجريبية، سيتم عرض رابط إعادة التعيين مباشرة بدلاً من إرساله عبر البريد</p>
                    <div class="demo-accounts">
                        <h5>بريد إلكتروني تجريبي:</h5>
                        <div class="demo-account">
                            <span>manager@sama.com</span>
                            <button onclick="fillDemo('manager@sama.com')" class="btn-demo">تعبئة</button>
                        </div>
                        <div class="demo-account">
                            <span>employee@sama.com</span>
                            <button onclick="fillDemo('employee@sama.com')" class="btn-demo">تعبئة</button>
                        </div>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="security-notice">
                    <h4>🔒 ملاحظة أمنية</h4>
                    <p>لأغراض الأمان، لن نخبرك ما إذا كان البريد الإلكتروني موجود في نظامنا أم لا. إذا كان البريد مسجلاً، ستستلم رسالة إعادة التعيين.</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Enhanced Form Styling - Same as signup */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .form-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 16px 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .section-icon {
            font-size: 18px;
        }

        .form-row {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-row:last-child {
            margin-bottom: 0;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 14px;
        }

        .input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-container .input-icon {
            position: absolute;
            right: 12px;
            z-index: 2;
            font-size: 16px;
            color: var(--text-secondary);
        }

        .input-container input {
            width: 100%;
            padding: 14px 45px 14px 16px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .input-container input:focus {
            outline: none;
            border-color: var(--accent-color);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
        }

        .input-container input::placeholder {
            color: var(--text-secondary);
        }

        .form-hint {
            margin-top: 6px;
            font-size: 12px;
            color: var(--text-secondary);
            display: block;
        }

        .form-actions {
            margin-top: 8px;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            background: var(--primary-gradient);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .loading-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-section {
                padding: 16px;
            }
        }
    </style>

    <script>
        function fillDemo(email) {
            document.getElementById('email').value = email;
            
            // Add visual feedback
            const input = document.getElementById('email');
            input.style.backgroundColor = 'rgba(144, 238, 144, 0.1)';
            setTimeout(() => {
                input.style.backgroundColor = '';
            }, 1000);
        }

        // Form submission handler
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('forgotPasswordBtn');
            const btnText = btn.querySelector('.btn-text');
            const btnLoading = btn.querySelector('.btn-loading');
            
            // Show loading state
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-flex';
            btn.disabled = true;
            
            // In a real application, you would submit the form here
            // For demo purposes, we'll show a success message after a delay
            setTimeout(() => {
                btnText.style.display = 'inline-flex';
                btnLoading.style.display = 'none';
                btn.disabled = false;
            }, 2000);
        });

        // Email validation feedback
        document.getElementById('email').addEventListener('input', function() {
            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = 'var(--error-color)';
            } else {
                this.style.borderColor = '';
            }
        });

        // Add interactive effects
        document.querySelectorAll('.input-group input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.boxShadow = '0 8px 32px rgba(255, 255, 255, 0.3)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = '';
                this.parentElement.style.boxShadow = '';
            });
        });

        // Auto-focus on email field
        window.addEventListener('load', function() {
            document.getElementById('email').focus();
        });
    </script>

    <?php
    // Clear old input data after displaying
    if (isset($_SESSION['old'])) {
        unset($_SESSION['old']);
    }
    ?>
</body>
</html>