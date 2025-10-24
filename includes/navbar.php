<?php
// التأكد من وجود متغير المستخدم
if (!isset($user)) {
    global $auth;
    $user = $auth->getCurrentUser();
}

// تحديد الصفحة الحالية
$current_page = basename($_SERVER['PHP_SELF']);
$is_dashboard = in_array($current_page, ['manager_dashboard.php', 'employee_dashboard.php']);
$is_contracts = in_array($current_page, ['contracts_list.php', 'create_contract.php', 'edit_contract.php', 'view_contract.php']);
$is_reports = in_array($current_page, ['reports.php']);
$is_employees = in_array($current_page, ['manage_employees.php']);
$is_notifications = in_array($current_page, ['notifications.php']);
?>

<!-- شريط التنقل الحديث -->
<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #253355 0%, #77bcc3 100%); box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
    <div class="container-fluid">
        <!-- شعار الشركة -->
        <a class="navbar-brand d-flex align-items-center" href="<?= $user['role'] === 'employee' ? '/employee_dashboard.php' : '/manager_dashboard.php' ?>">
            <i class="fas fa-building me-2" style="font-size: 1.5rem; color: #77bcc3;"></i>
            <div>
                <span style="font-size: 1.2rem; font-weight: bold;">سما البنيان</span>
                <br>
                <small style="font-size: 0.8rem; opacity: 0.9;">نظام إدارة العقود</small>
            </div>
        </a>

        <!-- زر القائمة للشاشات الصغيرة -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- عناصر القائمة -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- القائمة الرئيسية -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <!-- لوحة التحكم -->
                <li class="nav-item">
                    <a class="nav-link <?= $is_dashboard ? 'active' : '' ?>" 
                       href="<?= $user['role'] === 'employee' ? '/employee_dashboard.php' : '/manager_dashboard.php' ?>">
                        <i class="fas fa-chart-line me-1"></i>
                        لوحة التحكم
                    </a>
                </li>

                <!-- قائمة العقود -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $is_contracts ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-file-contract me-1"></i>
                        العقود
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" style="background: rgba(37, 51, 85, 0.95);">
                        <li><a class="dropdown-item" href="/contracts_list.php">
                            <i class="fas fa-list me-2"></i>قائمة العقود
                        </a></li>
                        <li><a class="dropdown-item" href="/create_contract.php">
                            <i class="fas fa-plus me-2"></i>عقد جديد
                        </a></li>
                        <?php if ($user['role'] !== 'employee'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/renewal_contract.php">
                            <i class="fas fa-redo me-2"></i>تجديد عقد
                        </a></li>
                        <li><a class="dropdown-item" href="/offer_contract.php">
                            <i class="fas fa-handshake me-2"></i>عرض سعر
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <?php if ($user['role'] !== 'employee'): ?>
                <!-- التقارير -->
                <li class="nav-item">
                    <a class="nav-link <?= $is_reports ? 'active' : '' ?>" href="/reports.php">
                        <i class="fas fa-chart-bar me-1"></i>
                        التقارير
                    </a>
                </li>

                <!-- إدارة الموظفين -->
                <?php if ($user['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $is_employees ? 'active' : '' ?>" href="/manage_employees.php">
                        <i class="fas fa-users me-1"></i>
                        الموظفين
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>

            <!-- القائمة اليمنى -->
            <ul class="navbar-nav">
                <!-- الإشعارات -->
                <li class="nav-item dropdown position-relative">
                    <a class="nav-link <?= $is_notifications ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                              id="notification-count" style="font-size: 0.7rem;">
                            <?php
                            // عدد الإشعارات غير المقروءة
                            try {
                                $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                                $unreadStmt->execute([$user['id']]);
                                $unread_count = $unreadStmt->fetchColumn();
                                echo $unread_count > 0 ? $unread_count : '';
                            } catch (Exception $e) {
                                echo '';
                            }
                            ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" 
                        style="background: rgba(37, 51, 85, 0.95); min-width: 300px;">
                        <li class="dropdown-header">الإشعارات الأخيرة</li>
                        <?php
                        try {
                            $notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                            $notifStmt->execute([$user['id']]);
                            $recent_notifications = $notifStmt->fetchAll();
                            
                            if (empty($recent_notifications)):
                        ?>
                            <li><span class="dropdown-item-text text-muted">لا توجد إشعارات جديدة</span></li>
                        <?php else: foreach ($recent_notifications as $notif): ?>
                            <li>
                                <a class="dropdown-item <?= $notif['is_read'] ? '' : 'fw-bold' ?>" 
                                   href="/notifications.php">
                                    <div class="d-flex">
                                        <i class="fas fa-circle me-2 mt-1" 
                                           style="font-size: 0.5rem; color: <?= $notif['is_read'] ? '#6c757d' : '#77bcc3' ?>;"></i>
                                        <div>
                                            <div style="font-size: 0.9rem;"><?= htmlspecialchars($notif['title']) ?></div>
                                            <small class="text-muted"><?= date('Y-m-d H:i', strtotime($notif['created_at'])) ?></small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="/notifications.php">
                            عرض جميع الإشعارات
                        </a></li>
                        <?php } catch (Exception $e) { ?>
                            <li><span class="dropdown-item-text text-muted">خطأ في تحميل الإشعارات</span></li>
                        <?php } ?>
                    </ul>
                </li>

                <!-- معلومات المستخدم -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" 
                             style="width: 32px; height: 32px;">
                            <i class="fas fa-user text-dark"></i>
                        </div>
                        <div class="d-none d-md-block text-end">
                            <div style="font-size: 0.9rem; font-weight: 500;"><?= htmlspecialchars($user['name']) ?></div>
                            <small style="opacity: 0.8;">
                                <?= $user['role'] === 'admin' ? 'مدير النظام' : ($user['role'] === 'manager' ? 'مدير عام' : 'موظف') ?>
                            </small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" 
                        style="background: rgba(37, 51, 85, 0.95);">
                        <li class="dropdown-header">
                            <?= htmlspecialchars($user['name']) ?><br>
                            <small><?= htmlspecialchars($user['email']) ?></small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/profile.php">
                            <i class="fas fa-user-edit me-2"></i>الملف الشخصي
                        </a></li>
                        <li><a class="dropdown-item" href="/notifications.php">
                            <i class="fas fa-bell me-2"></i>الإشعارات
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Styles إضافية للـ Navbar -->
<style>
.navbar-nav .nav-link {
    padding: 0.7rem 1rem;
    border-radius: 8px;
    margin: 0 0.2rem;
    transition: all 0.3s ease;
    position: relative;
}

.navbar-nav .nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-1px);
}

.navbar-nav .nav-link.active {
    background: rgba(119, 188, 195, 0.3);
    font-weight: 600;
}

.navbar-nav .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 30px;
    height: 3px;
    background: #77bcc3;
    border-radius: 2px;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    border-radius: 12px;
    margin-top: 0.5rem;
}

.dropdown-item {
    padding: 0.7rem 1rem;
    border-radius: 8px;
    margin: 0.2rem;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background: rgba(119, 188, 195, 0.2);
    transform: translateX(5px);
}

.navbar-brand:hover {
    transform: scale(1.05);
    transition: all 0.3s ease;
}

@media (max-width: 768px) {
    .navbar-nav .nav-link {
        margin: 0.2rem 0;
    }
    
    .navbar-brand div {
        font-size: 0.9rem !important;
    }
    
    .navbar-brand small {
        font-size: 0.7rem !important;
    }
}
</style>