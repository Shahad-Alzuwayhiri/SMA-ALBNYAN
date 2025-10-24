<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'نظام إدارة العقود' ?> - شركة سما البنيان</title>
    
    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <?php
    // Compute BASE_URL by stripping repeated /public from dirname($_SERVER['SCRIPT_NAME'])
    $d = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $root = preg_replace('#(/public)+$#', '', rtrim($d, '/')) . '/';
    if (!defined('BASE_URL')) define('BASE_URL', $root);

    if (!function_exists('asset')) {
        function asset(string $p): string {
            return BASE_URL . 'public/' . ltrim($p, '/');
        }
    }
    ?>
    <base href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>">
    <link href="<?php echo asset('assets/css/unified-theme.css'); ?>" rel="stylesheet">
    
    <!-- Additional CSS -->
    <?= $additionalCss ?? '' ?>
    
    <style>
        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= $auth->isLoggedIn() ? asset('employee_dashboard.php') : asset('welcome.php') ?>">
                <i class="fas fa-building me-2"></i>
                <div>
                    <div>شركة سما البنيان</div>
                    <small style="font-size: 0.7em; opacity: 0.8;">SMA ALBNYAN</small>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isset($auth) && $auth->isLoggedIn()): ?>
                        <?php $user = $auth->getCurrentUser(); ?>
                        
                        <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/manager_dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>
                                    لوحة التحكم
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/contracts_list_improved.php">
                                    <i class="fas fa-file-contract me-1"></i>
                                    العقود
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/manage_employees_improved.php">
                                    <i class="fas fa-users me-1"></i>
                                    الموظفين
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/reports.php">
                                    <i class="fas fa-chart-bar me-1"></i>
                                    التقارير
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/employee_dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>
                                    لوحة التحكم
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/my_contracts.php">
                                    <i class="fas fa-file-contract me-1"></i>
                                    عقودي
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/create_contract.php">
                                    <i class="fas fa-plus me-1"></i>
                                    عقد جديد
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/notifications_improved.php">
                                <i class="fas fa-bell me-1"></i>
                                الإشعارات
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isset($auth) && $auth->isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?= htmlspecialchars($user['name']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/profile.php">
                                    <i class="fas fa-user-edit me-2"></i>الملف الشخصي
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                تسجيل الدخول
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/signup.php">
                                <i class="fas fa-user-plus me-1"></i>
                                حساب جديد
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="main-content">
        <div class="container-fluid py-4">
            <!-- Alerts Section -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['warning'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($_SESSION['warning']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['warning']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['info'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['info']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['info']); ?>
            <?php endif; ?>
            
            <!-- Page Content -->
            <?= $content ?? '' ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="company-brand">شركة سما البنيان التجارية</h5>
                    <p class="mb-1">SMA ALBNYAN COMPANY</p>
                    <p class="text-muted">للتطوير والاستثمار العقاري</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1">
                        <i class="fas fa-phone me-2"></i>
                        +966 12 234 5678
                    </p>
                    <p class="mb-1">
                        <i class="fas fa-envelope me-2"></i>
                        info@sama-albonyan.com
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        جدة، المملكة العربية السعودية
                    </p>
                </div>
            </div>
            <hr class="my-3">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        © <?= date('Y') ?> شركة سما البنيان التجارية. جميع الحقوق محفوظة.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted">
                        نظام إدارة العقود المتطور v2.0
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Additional JavaScript -->
    <?= $additionalJs ?? '' ?>
    
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.classList.contains('show')) {
                    alert.classList.remove('show');
                    setTimeout(function() {
                        alert.remove();
                    }, 150);
                }
            });
        }, 5000);
        
        // Confirm delete actions
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-delete') || e.target.closest('.btn-delete')) {
                e.preventDefault();
                const button = e.target.classList.contains('btn-delete') ? e.target : e.target.closest('.btn-delete');
                const url = button.href || button.getAttribute('data-url');
                const message = button.getAttribute('data-message') || 'هل أنت متأكد من الحذف؟';
                
                Swal.fire({
                    title: 'تأكيد الحذف',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'نعم، احذف',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (url) {
                            window.location.href = url;
                        } else if (button.tagName === 'BUTTON' && button.type === 'submit') {
                            button.form.submit();
                        }
                    }
                });
            }
        });
        
        // Loading indicator for forms
        document.addEventListener('submit', function(e) {
            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري المعالجة...';
                submitBtn.disabled = true;
                
                // Re-enable after 10 seconds to prevent permanent lock
                setTimeout(function() {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 10000);
            }
        });
    </script>
</body>
</html>