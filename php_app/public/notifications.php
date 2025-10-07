<?php
require_once '../includes/auth.php';

// التحقق من تسجيل الدخول
$auth->requireAuth();
$user = $auth->getCurrentUser();

// معالجة تحديد الإشعار كمقروء
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notificationId = $_POST['notification_id'] ?? null;
    if ($notificationId) {
        $auth->markNotificationAsRead($notificationId, $user['id']);
        header('Location: /notifications.php');
        exit;
    }
}

// معالجة تحديد جميع الإشعارات كمقروءة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $message = 'تم تحديد جميع الإشعارات كمقروءة';
    } catch (PDOException $e) {
        $error = 'خطأ في تحديث الإشعارات';
    }
}

try {
    // جلب جميع الإشعارات للمستخدم الحالي
    $stmt = $pdo->prepare("
        SELECT n.*, c.contract_number 
        FROM notifications n
        LEFT JOIN contracts c ON n.related_id = c.id
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll();
    
    // إحصائيات الإشعارات
    $unreadCount = 0;
    $todayCount = 0;
    $thisWeekCount = 0;
    
    $today = date('Y-m-d');
    $weekAgo = date('Y-m-d', strtotime('-7 days'));
    
    foreach ($notifications as $notification) {
        if (!$notification['is_read']) {
            $unreadCount++;
        }
        
        $notificationDate = date('Y-m-d', strtotime($notification['created_at']));
        if ($notificationDate === $today) {
            $todayCount++;
        }
        
        if ($notificationDate >= $weekAgo) {
            $thisWeekCount++;
        }
    }
    
} catch (PDOException $e) {
    $error = "خطأ في جلب الإشعارات: " . $e->getMessage();
}

// تحديد أيقونة ولون الإشعار حسب النوع
function getNotificationStyle($type) {
    $styles = [
        'contract_created' => ['icon' => 'fas fa-file-plus', 'color' => 'primary'],
        'contract_approve' => ['icon' => 'fas fa-check-circle', 'color' => 'success'],
        'contract_reject' => ['icon' => 'fas fa-times-circle', 'color' => 'danger'],
        'contract_sign' => ['icon' => 'fas fa-signature', 'color' => 'info'],
        'default' => ['icon' => 'fas fa-bell', 'color' => 'secondary']
    ];
    
    return $styles[$type] ?? $styles['default'];
}

// تنسيق التاريخ
function formatDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 3600) { // أقل من ساعة
        $minutes = floor($diff / 60);
        return "منذ {$minutes} دقيقة";
    } elseif ($diff < 86400) { // أقل من يوم
        $hours = floor($diff / 3600);
        return "منذ {$hours} ساعة";
    } elseif ($diff < 604800) { // أقل من أسبوع
        $days = floor($diff / 86400);
        return "منذ {$days} يوم";
    } else {
        return date('Y-m-d H:i', $timestamp);
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإشعارات - نظام إدارة العقود</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #253355;
            --secondary-color: #77bcc3;
            --accent-color: #e8eaec;
            --text-color: #9694ac;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: none;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .notifications-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .notification-item {
            padding: 1.5rem;
            border-bottom: 1px solid #f8f9fa;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item.unread {
            background: linear-gradient(90deg, rgba(119, 188, 195, 0.1) 0%, rgba(255, 255, 255, 1) 5%);
            border-left: 4px solid var(--secondary-color);
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .notification-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 10px;
            height: 10px;
            background: var(--secondary-color);
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-mark-read {
            background: var(--secondary-color);
            border: none;
            color: white;
            padding: 0.375rem 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        
        .btn-mark-read:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .filter-tabs {
            background: white;
            border-radius: 15px 15px 0 0;
            padding: 0;
            margin: 0;
        }
        
        .filter-tabs .nav-link {
            border: none;
            color: var(--text-color);
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-radius: 0;
        }
        
        .filter-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .filter-tabs .nav-link:first-child.active {
            border-radius: 15px 0 0 0;
        }
        
        .filter-tabs .nav-link:last-child.active {
            border-radius: 0 15px 0 0;
        }
        
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: var(--text-color);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= $user['role'] === 'employee' ? '/employee_dashboard.php' : '/manager_dashboard.php' ?>">
                <i class="fas fa-arrow-right me-2"></i>العودة للوحة التحكم
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item">
                    <span class="navbar-text">
                        <i class="fas fa-user me-2"></i><?= htmlspecialchars($user['name']) ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-bell me-2"></i>الإشعارات</h2>
            
            <?php if ($unreadCount > 0): ?>
                <form method="POST" class="d-inline">
                    <button type="submit" name="mark_all_read" class="btn btn-outline-primary">
                        <i class="fas fa-check-double me-2"></i>تحديد الكل كمقروء
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- إحصائيات الإشعارات -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-warning text-white">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3 class="mb-1"><?= $unreadCount ?></h3>
                    <p class="text-muted mb-0">إشعارات غير مقروءة</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary text-white">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <h3 class="mb-1"><?= $todayCount ?></h3>
                    <p class="text-muted mb-0">إشعارات اليوم</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info text-white">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <h3 class="mb-1"><?= $thisWeekCount ?></h3>
                    <p class="text-muted mb-0">إشعارات الأسبوع</p>
                </div>
            </div>
        </div>
        
        <!-- فلاتر الإشعارات -->
        <ul class="nav nav-tabs filter-tabs" id="notificationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                    جميع الإشعارات (<?= count($notifications) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="unread-tab" data-bs-toggle="tab" data-bs-target="#unread" type="button" role="tab">
                    غير المقروءة (<?= $unreadCount ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="today-tab" data-bs-toggle="tab" data-bs-target="#today" type="button" role="tab">
                    اليوم (<?= $todayCount ?>)
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="notificationTabsContent">
            <!-- جميع الإشعارات -->
            <div class="tab-pane fade show active" id="all" role="tabpanel">
                <div class="notifications-container">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h5>لا توجد إشعارات</h5>
                            <p class="text-muted">ستظهر هنا الإشعارات عند وجود أنشطة جديدة</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <?php $style = getNotificationStyle($notification['type']); ?>
                            <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
                                <?php if (!$notification['is_read']): ?>
                                    <div class="notification-badge"></div>
                                <?php endif; ?>
                                
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon bg-<?= $style['color'] ?> text-white me-3">
                                        <i class="<?= $style['icon'] ?>"></i>
                                    </div>
                                    
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0"><?= htmlspecialchars($notification['title']) ?></h6>
                                            <small class="text-muted"><?= formatDate($notification['created_at']) ?></small>
                                        </div>
                                        
                                        <p class="mb-2 text-muted"><?= htmlspecialchars($notification['message']) ?></p>
                                        
                                        <?php if ($notification['contract_number']): ?>
                                            <div class="mb-2">
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-file-contract me-1"></i>
                                                    العقد: <?= htmlspecialchars($notification['contract_number']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!$notification['is_read']): ?>
                                            <div class="notification-actions">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                    <button type="submit" name="mark_read" class="btn-mark-read">
                                                        <i class="fas fa-check me-1"></i>تحديد كمقروء
                                                    </button>
                                                </form>
                                                
                                                <?php if ($notification['related_id']): ?>
                                                    <a href="/view_contract.php?id=<?= $notification['related_id'] ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>عرض العقد
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- الإشعارات غير المقروءة -->
            <div class="tab-pane fade" id="unread" role="tabpanel">
                <div class="notifications-container">
                    <?php
                    $unreadNotifications = array_filter($notifications, function($n) { return !$n['is_read']; });
                    if (empty($unreadNotifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <h5>لا توجد إشعارات غير مقروءة</h5>
                            <p class="text-muted">جميع الإشعارات مقروءة!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($unreadNotifications as $notification): ?>
                            <?php $style = getNotificationStyle($notification['type']); ?>
                            <div class="notification-item unread">
                                <div class="notification-badge"></div>
                                
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon bg-<?= $style['color'] ?> text-white me-3">
                                        <i class="<?= $style['icon'] ?>"></i>
                                    </div>
                                    
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0"><?= htmlspecialchars($notification['title']) ?></h6>
                                            <small class="text-muted"><?= formatDate($notification['created_at']) ?></small>
                                        </div>
                                        
                                        <p class="mb-2 text-muted"><?= htmlspecialchars($notification['message']) ?></p>
                                        
                                        <?php if ($notification['contract_number']): ?>
                                            <div class="mb-2">
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-file-contract me-1"></i>
                                                    العقد: <?= htmlspecialchars($notification['contract_number']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="notification-actions">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                <button type="submit" name="mark_read" class="btn-mark-read">
                                                    <i class="fas fa-check me-1"></i>تحديد كمقروء
                                                </button>
                                            </form>
                                            
                                            <?php if ($notification['related_id']): ?>
                                                <a href="/view_contract.php?id=<?= $notification['related_id'] ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>عرض العقد
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- إشعارات اليوم -->
            <div class="tab-pane fade" id="today" role="tabpanel">
                <div class="notifications-container">
                    <?php
                    $todayNotifications = array_filter($notifications, function($n) use ($today) {
                        return date('Y-m-d', strtotime($n['created_at'])) === $today;
                    });
                    
                    if (empty($todayNotifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-day"></i>
                            <h5>لا توجد إشعارات اليوم</h5>
                            <p class="text-muted">لم تصل أي إشعارات جديدة اليوم</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todayNotifications as $notification): ?>
                            <?php $style = getNotificationStyle($notification['type']); ?>
                            <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
                                <?php if (!$notification['is_read']): ?>
                                    <div class="notification-badge"></div>
                                <?php endif; ?>
                                
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon bg-<?= $style['color'] ?> text-white me-3">
                                        <i class="<?= $style['icon'] ?>"></i>
                                    </div>
                                    
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0"><?= htmlspecialchars($notification['title']) ?></h6>
                                            <small class="text-muted"><?= formatDate($notification['created_at']) ?></small>
                                        </div>
                                        
                                        <p class="mb-2 text-muted"><?= htmlspecialchars($notification['message']) ?></p>
                                        
                                        <?php if ($notification['contract_number']): ?>
                                            <div class="mb-2">
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-file-contract me-1"></i>
                                                    العقد: <?= htmlspecialchars($notification['contract_number']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!$notification['is_read']): ?>
                                            <div class="notification-actions">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                    <button type="submit" name="mark_read" class="btn-mark-read">
                                                        <i class="fas fa-check me-1"></i>تحديد كمقروء
                                                    </button>
                                                </form>
                                                
                                                <?php if ($notification['related_id']): ?>
                                                    <a href="/view_contract.php?id=<?= $notification['related_id'] ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>عرض العقد
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>