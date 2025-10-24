<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور - نظام إدارة العقود سما</title>
    <link rel="stylesheet" href="/static/css/glassmorphism.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <div class="page-container">
        <div class="auth-container">
            <div class="glass-container">
                <div class="page-header">
                    <h2>إعادة تعيين كلمة المرور</h2>
                    <p>اختر كلمة مرور جديدة قوية لحسابك</p>
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

                <form class="login-form" method="POST" action="/reset-password" id="resetPasswordForm">
                    <!-- Hidden token field (in real app, this would come from URL parameter) -->
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? 'demo-token-123') ?>">
                    
                    <!-- Account Information Section -->
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
                                        placeholder="بريدك الإلكتروني"
                                        value="<?= htmlspecialchars($_GET['email'] ?? $_SESSION['old']['email'] ?? '') ?>"
                                        readonly
                                        class="readonly-input"
                                    >
                                </div>
                                <small class="form-hint">البريد الإلكتروني المرتبط بحسابك</small>
                            </div>
                        </div>
                    </div>

                    <!-- New Password Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <span class="section-icon">🔐</span>
                            كلمة المرور الجديدة
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">كلمة المرور الجديدة</label>
                                <div class="input-container">
                                    <span class="input-icon">🔒</span>
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        placeholder="أدخل كلمة المرور الجديدة"
                                        required
                                        minlength="8"
                                    >
                                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                        <span id="password-eye">👁️</span>
                                    </button>
                                </div>
                                <div class="password-strength" id="passwordStrength" style="display: none;">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <small class="strength-text" id="strengthText"></small>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password_confirmation">تأكيد كلمة المرور الجديدة</label>
                                <div class="input-container">
                                    <span class="input-icon">🔐</span>
                                    <input 
                                        type="password" 
                                        id="password_confirmation" 
                                        name="password_confirmation" 
                                        placeholder="أعد إدخال كلمة المرور الجديدة"
                                        required
                                        minlength="8"
                                    >
                                    <button type="button" class="toggle-password" onclick="togglePassword('password_confirmation')">
                                        <span id="password_confirmation-eye">👁️</span>
                                    </button>
                                </div>
                                <div class="password-match" id="passwordMatch" style="display: none;">
                                    <small id="matchText"></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Password Requirements -->
                    <div class="password-requirements">
                        <h4>📋 متطلبات كلمة المرور:</h4>
                        <ul>
                            <li id="req-length" class="requirement">
                                <span class="req-icon">❌</span>
                                8 أحرف على الأقل
                            </li>
                            <li id="req-uppercase" class="requirement">
                                <span class="req-icon">❌</span>
                                حرف كبير واحد على الأقل (A-Z)
                            </li>
                            <li id="req-lowercase" class="requirement">
                                <span class="req-icon">❌</span>
                                حرف صغير واحد على الأقل (a-z)
                            </li>
                            <li id="req-number" class="requirement">
                                <span class="req-icon">❌</span>
                                رقم واحد على الأقل (0-9)
                            </li>
                            <li id="req-special" class="requirement">
                                <span class="req-icon">❌</span>
                                رمز خاص واحد على الأقل (!@#$%^&*)
                            </li>
                        </ul>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-btn" id="resetPasswordBtn" disabled>
                            <span class="btn-content">
                                <span class="btn-icon">🔄</span>
                                <span class="btn-text">تحديث كلمة المرور</span>
                            </span>
                            <span class="btn-loading" style="display: none;">
                                <span class="loading-spinner"></span>
                                <span class="loading-text">جاري التحديث...</span>
                            </span>
                        </button>
                    </div>
                </form>

                <div class="auth-links">
                    <p>تذكرت كلمة المرور؟ <a href="/login" class="link-primary">تسجيل الدخول</a></p>
                </div>

                <!-- Security Tips -->
                <div class="security-tips">
                    <h4>🛡️ نصائح أمنية</h4>
                    <ul>
                        <li>اختر كلمة مرور فريدة لم تستخدمها في مواقع أخرى</li>
                        <li>امزج بين الأحرف الكبيرة والصغيرة والأرقام والرموز</li>
                        <li>تجنب استخدام معلومات شخصية مثل اسمك أو تاريخ ميلادك</li>
                        <li>احفظ كلمة المرور في مكان آمن</li>
                    </ul>
                </div>

                <!-- Demo Notice -->
                <div class="demo-notice">
                    <h4>🧪 نسخة تجريبية</h4>
                    <p>في النسخة التجريبية، يمكنك استخدام كلمة مرور بسيطة للاختبار</p>
                    <button onclick="fillDemo()" class="btn-demo">تعبئة كلمة مرور تجريبية</button>
                </div>
            </div>
        </div>
    </div>

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

        function fillDemo() {
            document.getElementById('password').value = 'DemoPass123!';
            document.getElementById('password_confirmation').value = 'DemoPass123!';
            
            // Trigger validation
            checkPasswordStrength();
            checkPasswordMatch();
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthDiv = document.getElementById('passwordStrength');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            if (password.length === 0) {
                strengthDiv.style.display = 'none';
                return;
            }
            
            strengthDiv.style.display = 'block';
            
            // Check requirements
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };
            
            // Update requirement indicators
            Object.keys(requirements).forEach(req => {
                const element = document.getElementById(`req-${req}`);
                const icon = element.querySelector('.req-icon');
                if (requirements[req]) {
                    element.classList.add('met');
                    icon.textContent = '✅';
                } else {
                    element.classList.remove('met');
                    icon.textContent = '❌';
                }
            });
            
            // Calculate strength
            const metRequirements = Object.values(requirements).filter(Boolean).length;
            const strength = (metRequirements / 5) * 100;
            
            strengthFill.style.width = strength + '%';
            
            if (strength < 40) {
                strengthFill.style.backgroundColor = 'var(--error-color)';
                strengthText.textContent = 'ضعيفة';
                strengthText.style.color = 'var(--error-color)';
            } else if (strength < 80) {
                strengthFill.style.backgroundColor = 'orange';
                strengthText.textContent = 'متوسطة';
                strengthText.style.color = 'orange';
            } else {
                strengthFill.style.backgroundColor = 'var(--success-color)';
                strengthText.textContent = 'قوية';
                strengthText.style.color = 'var(--success-color)';
            }
            
            // Update submit button state
            updateSubmitButton();
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmation = document.getElementById('password_confirmation').value;
            const matchDiv = document.getElementById('passwordMatch');
            const matchText = document.getElementById('matchText');
            
            if (confirmation.length === 0) {
                matchDiv.style.display = 'none';
                return;
            }
            
            matchDiv.style.display = 'block';
            
            if (password === confirmation) {
                matchText.textContent = '✅ كلمتا المرور متطابقتان';
                matchText.style.color = 'var(--success-color)';
                document.getElementById('password_confirmation').style.borderColor = 'var(--success-color)';
            } else {
                matchText.textContent = '❌ كلمتا المرور غير متطابقتان';
                matchText.style.color = 'var(--error-color)';
                document.getElementById('password_confirmation').style.borderColor = 'var(--error-color)';
            }
            
            updateSubmitButton();
        }

        function updateSubmitButton() {
            const password = document.getElementById('password').value;
            const confirmation = document.getElementById('password_confirmation').value;
            const btn = document.getElementById('resetPasswordBtn');
            
            // Check if all requirements are met
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };
            
            const allRequirementsMet = Object.values(requirements).every(Boolean);
            const passwordsMatch = password === confirmation && confirmation.length > 0;
            
            if (allRequirementsMet && passwordsMatch) {
                btn.disabled = false;
                btn.style.opacity = '1';
            } else {
                btn.disabled = true;
                btn.style.opacity = '0.6';
            }
        }

        // Event listeners
        document.getElementById('password').addEventListener('input', checkPasswordStrength);
        document.getElementById('password_confirmation').addEventListener('input', checkPasswordMatch);

        // Form submission
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmation = document.getElementById('password_confirmation').value;
            
            if (password !== confirmation) {
                e.preventDefault();
                alert('كلمتا المرور غير متطابقتين');
                return;
            }
            
            // Show loading state
            const btn = document.getElementById('resetPasswordBtn');
            const btnText = btn.querySelector('.btn-text');
            const btnLoading = btn.querySelector('.btn-loading');
            
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-flex';
            btn.disabled = true;
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

        // Auto-focus on password field
        window.addEventListener('load', function() {
            document.getElementById('password').focus();
        });
    </script>

    <style>
        .password-strength {
            margin-top: 8px;
        }
        
        .strength-bar {
            width: 100%;
            height: 4px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .strength-text {
            display: block;
            margin-top: 4px;
            font-weight: 500;
        }
        
        .password-match {
            margin-top: 8px;
        }
        
        .password-requirements {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0;
            backdrop-filter: blur(10px);
        }
        
        .password-requirements h4 {
            margin: 0 0 12px 0;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }
        
        .requirement.met {
            color: var(--success-color);
        }
        
        .req-icon {
            margin-left: 8px;
            font-size: 12px;
        }
        
        .security-tips {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0;
            backdrop-filter: blur(10px);
        }
        
        .security-tips h4 {
            margin: 0 0 12px 0;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .security-tips ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .security-tips li {
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--text-secondary);
            padding-right: 16px;
            position: relative;
        }
        
        .security-tips li:before {
            content: "🔹";
            position: absolute;
            right: 0;
        }

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

        .readonly-input {
            background: rgba(255, 255, 255, 0.05) !important;
            cursor: not-allowed;
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

        .submit-btn:hover:not(:disabled) {
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
            background: rgba(255, 255, 255, 0.2);
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

    <?php
    // Clear old input data after displaying
    if (isset($_SESSION['old'])) {
        unset($_SESSION['old']);
    }
    ?>
</body>
</html>