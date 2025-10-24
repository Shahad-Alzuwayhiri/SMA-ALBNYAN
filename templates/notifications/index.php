<?php
// This file is a fragment to be injected into the master layout. Do NOT emit DOCTYPE or <html>/<head> tags here.
// It expects the layout to have already set up <head> and assets (CSS/JS). Only render page content.
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="main-content">
                <!-- Header -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h1 class="page-title">
                                <i class="fas fa-bell me-2"></i>
                                الإشعارات
                            </h1>
                            <p class="page-subtitle">إدارة ومتابعة جميع الإشعارات</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <?php if ($unreadCount > 0): ?>
                                <a href="/notifications/mark-all-read" class="btn btn-primary">
                                    <i class="fas fa-check-double me-2"></i>
                                    تحديد الكل كمقروء
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?= $totalCount ?></h3>
                                <p>إجمالي الإشعارات</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?= $unreadCount ?></h3>
                                <p>غير مقروءة</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?= $totalCount - $unreadCount ?></h3>
                                <p>مقروءة</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-list me-2"></i>
                            قائمة الإشعارات
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                            <div class="empty-state">
                                <i class="fas fa-bell-slash"></i>
                                <h4>لا توجد إشعارات</h4>
                                <p>لم يتم العثور على أي إشعارات حتى الآن</p>
                            </div>
                        <?php else: ?>
                            <div class="notifications-list">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>">
                                        <div class="notification-icon">
                                            <?php
                                            $iconClass = 'fas fa-info-circle';
                                            $iconColor = 'text-primary';
                                            
                                            switch ($notification['type']) {
                                                case 'contract_created':
                                                    $iconClass = 'fas fa-file-plus';
                                                    $iconColor = 'text-success';
                                                    break;
                                                case 'contract_updated':
                                                    $iconClass = 'fas fa-file-edit';
                                                    $iconColor = 'text-warning';
                                                    break;
                                                case 'contract_signed':
                                                    $iconClass = 'fas fa-signature';
                                                    $iconColor = 'text-info';
                                                    break;
                                                case 'warning':
                                                    $iconClass = 'fas fa-exclamation-triangle';
                                                    $iconColor = 'text-danger';
                                                    break;
                                            }
                                            ?>
                                            <i class="<?= $iconClass ?> <?= $iconColor ?>"></i>
                                        </div>
                                        <div class="notification-content">
                                            <h6 class="notification-title"><?= htmlspecialchars($notification['title']) ?></h6>
                                            <p class="notification-message"><?= htmlspecialchars($notification['message']) ?></p>
                                            <?php if ($notification['contract_number']): ?>
                                                <span class="notification-meta">
                                                    <i class="fas fa-file-contract me-1"></i>
                                                    العقد: <?= htmlspecialchars($notification['contract_number']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="notification-time">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?>
                                            </span>
                                        </div>
                                        <?php if (!$notification['is_read']): ?>
                                            <div class="notification-actions">
                                                <a href="/notifications/mark-read/<?= $notification['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-check"></i>
                                                    تحديد كمقروء
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .notifications-list {
        max-height: 600px;
        overflow-y: auto;
    }
    
    .notification-item {
        display: flex;
        align-items: flex-start;
        padding: 20px;
        border-bottom: 1px solid #eee;
        transition: all 0.3s ease;
    }
    
    .notification-item:hover {
        background-color: #f8f9fa;
    }
    
    .notification-item.unread {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
    }
    
    .notification-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 50%;
        margin-left: 15px;
        flex-shrink: 0;
    }
    
    .notification-content {
        flex: 1;
    }
    
    .notification-title {
        font-weight: 600;
        margin-bottom: 5px;
        color: #2c3e50;
    }
    
    .notification-message {
        color: #6c757d;
        margin-bottom: 10px;
        line-height: 1.5;
    }
    
    .notification-meta,
    .notification-time {
        font-size: 12px;
        color: #6c757d;
        margin-left: 15px;
    }
    
    .notification-actions {
        flex-shrink: 0;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .empty-state h4 {
        margin-bottom: 10px;
        color: #495057;
    }
</style>