// refactor(activity_log.php): render via master_layout + normalize links
<?php
require_once '../includes/auth.php';

// التحقق من الصلاحيات
$auth->requireAuth();
$user = $auth->getCurrentUser();

// التحقق من صلاحيات عرض السجل
if ($user['role'] !== 'manager' && $user['role'] !== 'admin') {
    header('Location: /employee_dashboard.php');
    exit;
}

// معاملات الفلترة والبحث
$filterUser = $_GET['user'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterDate = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

    $currentPage = 'activity_log';
    $title = "سجل النشاطات";
    $is_auth_page = false;
    $show_sidebar = true;
    $additional_head = $additional_head ?? '';
    $additional_scripts = $additional_scripts ?? '';
    ob_start();
try {
    // بناء استعلام البحث
    $whereConditions = [];
    $params = [];
    
    if ($filterUser) {
        $whereConditions[] = "al.user_id = ?";
        $params[] = $filterUser;
    }
    
    if ($filterAction) {
        $whereConditions[] = "al.action = ?";
        $params[] = $filterAction;
    }
    
    if ($filterDate) {
        $whereConditions[] = "DATE(al.created_at) = ?";
        $params[] = $filterDate;
    }
    
    if ($search) {
        $whereConditions[] = "(al.description LIKE ? OR u.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // جلب السجلات
    $stmt = $pdo->prepare("
        SELECT al.*, u.name as user_name, u.role as user_role
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        $whereClause
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $activities = $stmt->fetchAll();
    
    // حساب إجمالي السجلات
    $countParams = array_slice($params, 0, -2); // إزالة limit و offset
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        $whereClause
    ");
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // جلب قائمة المستخدمين للفلترة
    $usersStmt = $pdo->prepare("SELECT id, name FROM users ORDER BY name");
    $usersStmt->execute();
    $users = $usersStmt->fetchAll();
    
    // جلب أنواع النشاطات المختلفة
    $actionsStmt = $pdo->prepare("SELECT DISTINCT action FROM activity_log ORDER BY action");
    $actionsStmt->execute();
    $actions = $actionsStmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'خطأ في جلب سجل النشاطات: ' . $e->getMessage();
    $activities = [];
    $totalRecords = 0;
    $totalPages = 0;
}

$currentPage = 'activity_log';
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل النشاطات - نظام إدارة العقود</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/modern-theme.css" rel="stylesheet">
    <style>
        .activity-header {
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .activity-log {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .activity-item {
            padding: 1.5rem;
            border-bottom: 1px solid #f8f9fa;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .activity-item:hover {
            background: linear-gradient(90deg, rgba(119, 188, 195, 0.05) 0%, rgba(255, 255, 255, 1) 10%);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-left: 1rem;
        }
        
        .activity-time {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .activity-user {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .activity-description {
            color: #6c757d;
            margin: 0.5rem 0;
        }
        
        .action-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .action-login { background: #e8f5e8; color: #2e7d32; }
        .action-logout { background: #fff3e0; color: #f57c00; }
        .action-create_contract { background: #e3f2fd; color: #1565c0; }
        .action-update_contract { background: #f3e5f5; color: #7b1fa2; }
        .action-approve_contract { background: #e8f5e8; color: #2e7d32; }
        .action-reject_contract { background: #ffebee; color: #c62828; }
        .action-add_employee { background: #e1f5fe; color: #0277bd; }
        .action-update_employee { background: #f9fbe7; color: #689f38; }
        .action-reset_password { background: #fff8e1; color: #f57c00; }
        .action-default { background: #f5f5f5; color: #757575; }
        
        .pagination-wrapper {
            background: white;
            border-radius: 15px;
            padding: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-top: 2rem;
        }
        
        .stats-cards {
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            height: 100%;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <?php include __DIR__ . '/../templates/partials/_topnav.php'; ?>
    <?php include __DIR__ . '/../templates/partials/_sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="activity-header text-center">
                <div class="container">
                    <h1><i class="fas fa-history me-3"></i>سجل النشاطات</h1>
                    <p class="lead mb-0">مراقبة جميع الأنشطة والعمليات في النظام</p>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- إحصائيات سريعة -->
            <div class="row stats-cards">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-primary text-white">
                            <i class="fas fa-list"></i>
                        </div>
                        <h3 class="mb-1"><?= number_format($totalRecords) ?></h3>
                        <p class="text-muted mb-0">إجمالي السجلات</p>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-success text-white">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <h3 class="mb-1">
                            <?php
                            $todayCount = 0;
                            foreach ($activities as $activity) {
                                if (date('Y-m-d', strtotime($activity['created_at'])) === date('Y-m-d')) {
                                    $todayCount++;
                                }
                            }
                            echo $todayCount;
                            ?>
                        </h3>
                        <p class="text-muted mb-0">نشاطات اليوم</p>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-info text-white">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="mb-1"><?= count(array_unique(array_column($activities, 'user_id'))) ?></h3>
                        <p class="text-muted mb-0">مستخدمين نشطين</p>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-warning text-white">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="mb-1"><?= $page ?>/<?= $totalPages ?></h3>
                        <p class="text-muted mb-0">الصفحة الحالية</p>
                    </div>
                </div>
            </div>
            
            <!-- فلاتر البحث -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">المستخدم</label>
                        <select name="user" class="form-select">
                            <option value="">جميع المستخدمين</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">نوع النشاط</label>
                        <select name="action" class="form-select">
                            <option value="">جميع الأنشطة</option>
                            <?php foreach ($actions as $a): ?>
                                <option value="<?= $a['action'] ?>" <?= $filterAction == $a['action'] ? 'selected' : '' ?>>
                                    <?php
                                    $actionLabels = [
                                        'login' => 'تسجيل دخول',
                                        'logout' => 'تسجيل خروج',
                                        'create_contract' => 'إنشاء عقد',
                                        'update_contract' => 'تحديث عقد',
                                        'approve_contract' => 'اعتماد عقد',
                                        'reject_contract' => 'رفض عقد',
                                        'add_employee' => 'إضافة موظف',
                                        'update_employee' => 'تحديث موظف',
                                        'reset_password' => 'إعادة تعيين كلمة مرور'
                                    ];
                                    echo $actionLabels[$a['action']] ?? $a['action'];
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">التاريخ</label>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate) ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">البحث</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="بحث في الوصف..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-2"></i>بحث
                        </button>
                        <a href="<?= asset('activity_log.php') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>مسح الفلاتر
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- سجل النشاطات -->
            <div class="activity-log">
                <?php if (empty($activities)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h5>لا توجد نشاطات</h5>
                        <p class="text-muted">لم يتم العثور على نشاطات تطابق معايير البحث</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="d-flex align-items-start">
                                <div class="activity-icon bg-<?php
                                    $iconColors = [
                                        'login' => 'success',
                                        'logout' => 'warning', 
                                        'create_contract' => 'primary',
                                        'update_contract' => 'info',
                                        'approve_contract' => 'success',
                                        'reject_contract' => 'danger',
                                        'add_employee' => 'info',
                                        'update_employee' => 'warning',
                                        'reset_password' => 'warning'
                                    ];
                                    echo $iconColors[$activity['action']] ?? 'secondary';
                                ?> text-white">
                                    <i class="fas <?php
                                        $icons = [
                                            'login' => 'fa-sign-in-alt',
                                            'logout' => 'fa-sign-out-alt',
                                            'create_contract' => 'fa-file-plus',
                                            'update_contract' => 'fa-edit',
                                            'approve_contract' => 'fa-check',
                                            'reject_contract' => 'fa-times',
                                            'add_employee' => 'fa-user-plus',
                                            'update_employee' => 'fa-user-edit',
                                            'reset_password' => 'fa-key'
                                        ];
                                        echo $icons[$activity['action']] ?? 'fa-cog';
                                    ?>"></i>
                                </div>
                                
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="activity-user"><?= htmlspecialchars($activity['user_name'] ?? 'مستخدم محذوف') ?></span>
                                            <span class="action-badge action-<?= $activity['action'] ?>">
                                                <?php
                                                $actionLabels = [
                                                    'login' => 'تسجيل دخول',
                                                    'logout' => 'تسجيل خروج',
                                                    'create_contract' => 'إنشاء عقد',
                                                    'update_contract' => 'تحديث عقد',
                                                    'approve_contract' => 'اعتماد عقد',
                                                    'reject_contract' => 'رفض عقد',
                                                    'add_employee' => 'إضافة موظف',
                                                    'update_employee' => 'تحديث موظف',
                                                    'reset_password' => 'إعادة تعيين كلمة مرور'
                                                ];
                                                echo $actionLabels[$activity['action']] ?? $activity['action'];
                                                ?>
                                            </span>
                                        </div>
                                        <div class="activity-time">
                                            <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="activity-description">
                                        <?= htmlspecialchars($activity['description']) ?>
                                    </div>
                                    
                                    <?php if ($activity['user_role']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-tag me-1"></i>
                                                الدور: <?php
                                                    $roleLabels = [
                                                        'admin' => 'مدير نظام',
                                                        'manager' => 'مدير',
                                                        'employee' => 'موظف'
                                                    ];
                                                    echo $roleLabels[$activity['user_role']] ?? $activity['user_role'];
                                                ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- التنقل بين الصفحات -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper">
                    <nav aria-label="تنقل الصفحات">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            ?>
                            
                            <?php if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تحديث تلقائي كل 30 ثانية
        let autoRefreshInterval;
        
        function startAutoRefresh() {
            autoRefreshInterval = setInterval(() => {
                if (!document.hidden) {
                    location.reload();
                }
            }, 30000);
        }
        
        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        }
        
        // إيقاف التحديث عند إخفاء الصفحة
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
            }
        });
        
        // بدء التحديث التلقائي
        startAutoRefresh();
        
        // تحسين تجربة البحث
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    // يمكن إضافة بحث تلقائي هنا
                }, 500);
            });
        }
    </script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layouts/master_layout.php';