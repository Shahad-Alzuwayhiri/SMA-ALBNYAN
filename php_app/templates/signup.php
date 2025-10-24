<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد - نظام إدارة العقود سما</title>
    <link rel="stylesheet" href="/static/css/glassmorphism.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <div class="page-container">
        <div class="auth-container">
            <div class="glass-container">
                <div class="page-header">
                    <h2>إنشاء حساب جديد</h2>
                    <p>أنشئ حسابك الجديد للانضمام إلى نظام إدارة العقود</p>
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

                <?php if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">❌</span>
                        <ul>
                            <?php foreach ($_SESSION['errors'] as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php unset($_SESSION['errors']); ?>
                <?php endif; ?>

                <form class="login-form" method="POST" action="/register" id="signupForm">
                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <span class="section-icon">👤</span>
                            المعلومات الشخصية
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">الاسم الكامل</label>
                                <div class="input-container">
                                    <span class="input-icon">👤</span>
                                    <input 
                                        type="text" 
                                        id="name" 
                                        name="name" 
                                        placeholder="أدخل اسمك الكامل"
                                        value="<?= htmlspecialchars($_SESSION['old']['name'] ?? '') ?>"
                                        required
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">البريد الإلكتروني</label>
                                <div class="input-container">
                                    <span class="input-icon">📧</span>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        placeholder="أدخل بريدك الإلكتروني"
                                        value="<?= htmlspecialchars($_SESSION['old']['email'] ?? '') ?>"
                                        required
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">رقم الهاتف</label>
                                <div class="input-container">
                                    <span class="input-icon">📱</span>
                                    <input 
                                        type="tel" 
                                        id="phone" 
                                        name="phone" 
                                        placeholder="05xxxxxxxx"
                                        value="<?= htmlspecialchars($_SESSION['old']['phone'] ?? '') ?>"
                                        required
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <span class="section-icon">🔐</span>
                            كلمة المرور
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">كلمة المرور</label>
                                <div class="input-container">
                                    <span class="input-icon">🔒</span>
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        placeholder="أدخل كلمة المرور"
                                        required
                                        minlength="6"
                                    >
                                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                        <span id="password-eye">👁️</span>
                                    </button>
                                </div>
                                <small class="form-hint">كلمة المرور يجب أن تحتوي على 6 أحرف على الأقل</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password_confirmation">تأكيد كلمة المرور</label>
                                <div class="input-container">
                                    <span class="input-icon">🔐</span>
                                    <input 
                                        type="password" 
                                        id="password_confirmation" 
                                        name="password_confirmation" 
                                        placeholder="أعد إدخال كلمة المرور"
                                        required
                                        minlength="6"
                                    >
                                    <button type="button" class="toggle-password" onclick="togglePassword('password_confirmation')">
                                        <span id="password_confirmation-eye">👁️</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms & Conditions -->
                    <div class="form-section">
                        <div class="checkbox-group">
                            <label class="checkbox-container">
                                <input type="checkbox" name="terms" required>
                                <span class="checkmark"></span>
                                <span class="checkbox-text">
                                    أوافق على <a href="/terms" target="_blank" class="link-primary">الشروط والأحكام</a> 
                                    و <a href="/privacy" target="_blank" class="link-primary">سياسة الخصوصية</a>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-btn" id="signupBtn">
                            <span class="btn-content">
                                <span class="btn-icon">🚀</span>
                                <span class="btn-text">إنشاء الحساب</span>
                            </span>
                            <span class="btn-loading" style="display: none;">
                                <span class="loading-spinner"></span>
                                <span class="loading-text">جاري إنشاء الحساب...</span>
                            </span>
                        </button>
                    </div>
                </form>

                <div class="auth-links">
                    <p>هل تمتلك حساب بالفعل؟ <a href="/login" class="link-primary">تسجيل الدخول</a></p>
                </div>

                <!-- Demo Notice -->
                <div class="demo-notice">
                    <h4>🧪 نسخة تجريبية</h4>
                    <p>هذا النظام في مرحلة التطوير والاختبار</p>
                    <div class="demo-accounts">
                        <h5>حسابات تجريبية:</h5>
                        <div class="demo-account">
                            <strong>مدير:</strong> manager@sama.com - كلمة المرور: 123456
                            <button onclick="fillDemo('manager@sama.com', '123456')" class="btn-demo">تعبئة</button>
                        </div>
                        <div class="demo-account">
                            <strong>موظف:</strong> employee@sama.com - كلمة المرور: 123456
                            <button onclick="fillDemo('employee@sama.com', '123456')" class="btn-demo">تعبئة</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Enhanced Form Styling */
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

        .form-group.half-width {
            flex: 0 0 calc(50% - 8px);
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

        .input-container input,
        .input-container select {
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

        .input-container input:focus,
        .input-container select:focus {
            outline: none;
            border-color: var(--accent-color);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
        }

        .input-container input::placeholder {
            color: var(--text-secondary);
        }

        .toggle-password {
            position: absolute;
            left: 12px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            z-index: 3;
        }

        .toggle-password:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .form-hint {
            margin-top: 6px;
            font-size: 12px;
            color: var(--text-secondary);
            display: block;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .checkbox-container {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            font-size: 14px;
            line-height: 1.5;
        }

        .checkbox-container input[type="checkbox"] {
            display: none;
        }

        .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .checkbox-container input[type="checkbox"]:checked + .checkmark {
            background: var(--accent-color);
            border-color: var(--accent-color);
        }

        .checkbox-container input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            color: white;
            font-size: 14px;
            font-weight: bold;
        }

        .checkbox-text {
            color: var(--text-primary);
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
            .form-row {
                flex-direction: column;
                gap: 16px;
            }

            .form-group.half-width {
                flex: 1;
            }

            .form-section {
                padding: 16px;
            }
        }

        /* Focus animations */
        .input-container input:focus + .toggle-password,
        .input-container select:focus {
            animation: focusGlow 0.3s ease;
        }

        @keyframes focusGlow {
            0% { box-shadow: 0 0 0 rgba(255, 255, 255, 0.1); }
            50% { box-shadow: 0 0 20px rgba(255, 255, 255, 0.2); }
            100% { box-shadow: 0 0 0 rgba(255, 255, 255, 0.1); }
        }
    </style>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eye = document.getElementById(fieldId + '-eye');
            
            if (field.type === 'password') {
                field.type = 'text';
                eye.textContent = '🙈';
            } else {
                field.type = 'password';
                eye.textContent = '👁️';
            }
        }

        function fillDemo(email, password) {
            // For signup form, we'll fill name and email fields
            const name = email.includes('manager') ? 'أحمد المدير' : 'سارة الموظفة';
            const phone = email.includes('manager') ? '0501234567' : '0509876543';
            
            document.getElementById('name').value = name;
            document.getElementById('email').value = email;
            document.getElementById('phone').value = phone;
            document.getElementById('password').value = password;
            document.getElementById('password_confirmation').value = password;
            document.querySelector('input[name="terms"]').checked = true;
        }

        // Form validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;
            
            if (password !== passwordConfirmation) {
                e.preventDefault();
                alert('كلمتا المرور غير متطابقتين');
                return;
            }
            
            // Show loading state
            const btn = document.getElementById('signupBtn');
            const btnText = btn.querySelector('.btn-text');
            const btnLoading = btn.querySelector('.btn-loading');
            
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-flex';
            btn.disabled = true;
        });

        // Real-time password confirmation validation
        document.getElementById('password_confirmation').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmation = this.value;
            const confirmationField = this;
            
            if (confirmation && password !== confirmation) {
                confirmationField.style.borderColor = 'var(--error-color)';
            } else {
                confirmationField.style.borderColor = '';
            }
        });

        // Add some interactive effects
        document.querySelectorAll('.input-group input, .input-group select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.boxShadow = '0 8px 32px rgba(255, 255, 255, 0.3)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = '';
                this.parentElement.style.boxShadow = '';
            });
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