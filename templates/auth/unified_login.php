<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - SMA البنيان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/unified-theme.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
        <div class="row w-100">
            <div class="col-md-6 mx-auto">
                <div class="glass-container">
                    <!-- Company Header -->
                    <div class="text-center mb-4">
                        <div class="company-logo mb-3">
                            <img src="/assets/images/sma-logo.svg" alt="SMA Logo" width="80" height="80" 
                                 onerror="this.style.display='none'">
                        </div>
                        <h1 class="company-name">شركة SMA البنيان</h1>
                        <p class="company-subtitle">نظام إدارة العقود الإلكترونية</p>
                    </div>

                    <!-- Error Display -->
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="POST" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Helpers\InputValidator::generateCsrfToken()) ?>">
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="البريد الإلكتروني" 
                                   autocomplete="email"
                                   value="<?= htmlspecialchars($_SESSION['old']['email'] ?? '') ?>" required>
                            <label for="email">
                                <i class="fas fa-envelope me-2"></i>البريد الإلكتروني
                            </label>
                        </div>

                        <div class="form-floating mb-4 position-relative">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="كلمة المرور" 
                                   autocomplete="current-password" required>
                            <label for="password">
                                <i class="fas fa-lock me-2"></i>كلمة المرور
                            </label>
                            <span class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </span>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 mb-4">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            تسجيل الدخول
                        </button>
                    </form>

                    <!-- Quick Login Options -->
                    <div class="quick-login">
                        <h6 class="text-center mb-3">
                            <i class="fas fa-users me-2"></i>حسابات تجريبية سريعة
                        </h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-outline-light btn-sm w-100" 
                                        onclick="quickLogin('admin@sma-albnyan.com', 'admin123')">
                                    <i class="fas fa-user-shield me-1"></i>مدير
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-light btn-sm w-100" 
                                        onclick="quickLogin('manager@sma-albnyan.com', 'manager123')">
                                    <i class="fas fa-user-tie me-1"></i>مشرف
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-light btn-sm w-100" 
                                        onclick="quickLogin('twoovi2000@hotmail.com', 'employee123')">
                                    <i class="fas fa-user me-1"></i>شهد
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-light btn-sm w-100" 
                                        onclick="quickLogin('employee@sma-albnyan.com', 'employee123')">
                                    <i class="fas fa-user me-1"></i>سارة
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- System Info -->
                    <div class="text-center mt-4 text-muted">
                        <small>
                            <i class="fas fa-shield-alt me-1"></i>
                            نظام محمي ومشفر | PHP 8.2.12
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .password-toggle {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #fff;
        }
        
        .quick-login {
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .quick-login .btn {
            font-size: 12px;
            padding: 8px 12px;
        }
        
        .company-logo img {
            border-radius: 50%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .form-floating label {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: #3498DB;
            color: #fff;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        function quickLogin(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            
            // تأثير بصري
            event.target.style.transform = 'scale(0.95)';
            setTimeout(() => {
                event.target.style.transform = 'scale(1)';
            }, 150);
        }

        // التركيز التلقائي
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>