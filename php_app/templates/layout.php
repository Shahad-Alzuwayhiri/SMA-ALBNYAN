<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'نظام إدارة العقود - سما البنيان التجارية' ?></title>
    <link rel="stylesheet" href="/static/css/glassmorphism.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Additional head content -->
    <?php if (isset($additional_head)): ?>
        <?= $additional_head ?>
    <?php endif; ?>
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .main-container {
            display: flex;
            min-height: 100vh;
        }
        
        .content-area {
            flex: 1;
            padding: 2rem;
        }
        
        .auth-container {
            max-width: 400px;
            margin: 2rem auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .footer {
            text-align: center;
            padding: 2rem;
            color: white;
            background: rgba(0,0,0,0.2);
            margin-top: auto;
        }
    </style>
</head>
<body>
    <?php 
    // Include navigation for authenticated users
    if (!isset($is_auth_page) || !$is_auth_page): 
        require_once __DIR__ . '/../includes/navigation.php';
        $userRole = $_SESSION['user_role'] ?? null;
        echo renderNavigation($userRole);
        
        // Include sidebar for dashboard pages
        if (isset($show_sidebar) && $show_sidebar && $userRole): ?>
            <div class="main-container">
                <?php include __DIR__ . '/partials/sidebar.php'; ?>
                <div class="content-area">
                    <div class="dashboard-container">
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if (isset($is_auth_page) && $is_auth_page): ?>
        <div class="auth-container">
    <?php endif; ?>
    
    <!-- Flash messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>
    
    <!-- Main content -->
    <?= $content ?? '' ?>
    
    <?php if (isset($is_auth_page) && $is_auth_page): ?>
        </div>
    <?php endif; ?>
    
    <?php 
    if (isset($show_sidebar) && $show_sidebar && (!isset($is_auth_page) || !$is_auth_page)): ?>
                    </div>
                </div>
            </div>
    <?php endif; ?>
    
    <?php if (!isset($is_auth_page) || !$is_auth_page): ?>
        <footer class="footer">
            <p>جميع الحقوق محفوظة © 2025 - مؤسسة سما البنيان التجارية</p>
            <p>نظام إدارة العقود المتقدم</p>
        </footer>
    <?php endif; ?>
    
    <!-- Alert styles -->
    <style>
        .alert {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
            border: 1px solid;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert ul {
            margin: 0;
            padding-right: 1rem;
        }
    </style>
    
    <!-- Additional scripts -->
    <?php if (isset($additional_scripts)): ?>
        <?= $additional_scripts ?>
    <?php endif; ?>
</body>
</html>