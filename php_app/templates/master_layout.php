<?php
/**
 * Master Layout Template
 * يتبع نمط مشابه لـ Laravel Blade templates لكن باستخدام PHP خالص
 */

// التحقق من المتغيرات المطلوبة
$title = $title ?? 'نظام إدارة العقود - سما البنيان التجارية';
$is_auth_page = $is_auth_page ?? false;
$show_sidebar = $show_sidebar ?? true;
$additional_head = $additional_head ?? '';
$additional_scripts = $additional_scripts ?? '';
$content = $content ?? '';

// الحصول على معلومات المستخدم
$user = null;
$userRole = $_SESSION['user_role'] ?? null;
$isAuthenticated = isset($_SESSION['user_id']);

if ($isAuthenticated) {
    $user = (object)[
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Base Styles -->
    <link rel="stylesheet" href="/static/css/glassmorphism.css">
    
    <!-- Additional Head Content -->
    <?= $additional_head ?>
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --sidebar-width: 280px;
            --topnav-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Layout Structure */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .content-wrapper {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        /* Auth Page Styling */
        .auth-layout {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .auth-container {
            max-width: 450px;
            width: 100%;
        }

        /* Dashboard Content */
        .dashboard-content {
            background: var(--glass-bg);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }

        /* Footer */
        .app-footer {
            background: rgba(0,0,0,0.2);
            color: white;
            text-align: center;
            padding: 1.5rem;
            margin-top: auto;
        }

        .app-footer p {
            margin: 0.25rem 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .app-container {
                flex-direction: column;
            }
            
            .content-wrapper {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php if (!$is_auth_page): ?>
        <!-- Application Layout with Navigation -->
        <div class="app-container">
            <!-- Sidebar -->
            <?php if ($show_sidebar && $isAuthenticated): ?>
                <?php include __DIR__ . '/partials/_sidebar.php'; ?>
            <?php endif; ?>
            
            <div class="main-content">
                <!-- Top Navigation -->
                <?php if ($isAuthenticated): ?>
                    <?php include __DIR__ . '/partials/_topnav.php'; ?>
                <?php endif; ?>
                
                <!-- Content Area -->
                <div class="content-wrapper">
                    <!-- Flash Messages -->
                    <?php include __DIR__ . '/partials/_messages.php'; ?>
                    
                    <!-- Main Content -->
                    <div class="dashboard-content">
                        <?= $content ?>
                    </div>
                </div>
                
                <!-- Footer -->
                <footer class="app-footer">
                    <p><strong>جميع الحقوق محفوظة © <?= date('Y') ?> - مؤسسة سما البنيان التجارية</strong></p>
                    <p>نظام إدارة العقود المتقدم</p>
                </footer>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Auth Page Layout -->
        <div class="auth-layout">
            <div class="auth-container">
                <!-- Flash Messages for Auth Pages -->
                <?php include __DIR__ . '/partials/_messages.php'; ?>
                
                <!-- Auth Content -->
                <?= $content ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Additional Scripts -->
    <?= $additional_scripts ?>
    
    <!-- Base JavaScript -->
    <script>
        // Global JavaScript functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide flash messages after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
            
            // Confirm logout
            const logoutLinks = document.querySelectorAll('a[href="/logout"]');
            logoutLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('هل أنت متأكد من تسجيل الخروج؟')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>