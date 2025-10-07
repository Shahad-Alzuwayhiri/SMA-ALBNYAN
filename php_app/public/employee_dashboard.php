<?php
require_once '../includes/auth.php';

// التحقق من صلاحية الموظف
$auth->requireAuth();

$user = $auth->getCurrentUser();

// إذا كان مدير، توجيهه للوحة المدير
if ($user['role'] === 'admin' || $user['role'] === 'manager') {
    header('Location: /manager_dashboard.php');
    exit;
}

// إذا لم يكن موظف، منع الوصول
if ($user['role'] !== 'employee') {
    header('HTTP/1.0 403 Forbidden');
    die('غير مسموح لك بالوصول لهذه الصفحة');
}

// جلب عقود الموظف فقط
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as created_by_name 
        FROM contracts c 
        LEFT JOIN users u ON c.created_by = u.id 
        WHERE c.created_by = ? 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $contracts = $stmt->fetchAll();
    
    // إحصائيات الموظف
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_contracts,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_contracts,
            SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END) as pending_contracts,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_contracts,
            SUM(CASE WHEN status = 'signed' THEN 1 ELSE 0 END) as signed_contracts,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_contracts,
            SUM(amount) as total_amount
        FROM contracts 
        WHERE created_by = ?
    ");
    $statsStmt->execute([$user['id']]);
    $stats = $statsStmt->fetch();
    
    // الإشعارات الأخيرة
    $notifications = $auth->getNotifications($user['id'], 5);
    
} catch (PDOException $e) {
    $error = "خطأ في جلب البيانات: " . $e->getMessage();
}

// تحديد اللون والنص حسب حالة العقد
function getStatusInfo($status) {
    $statusMap = [
        'draft' => ['class' => 'bg-secondary', 'text' => 'مسودة'],
        'pending_review' => ['class' => 'bg-warning', 'text' => 'قيد المراجعة'],
        'approved' => ['class' => 'bg-success', 'text' => 'موافق عليه'],
        'rejected' => ['class' => 'bg-danger', 'text' => 'مرفوض'],
        'signed' => ['class' => 'bg-primary', 'text' => 'موقع']
    ];
    
    return $statusMap[$status] ?? ['class' => 'bg-secondary', 'text' => 'غير معروف'];
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الموظف - نظام إدارة العقود</title>
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
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
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
            transform: translateY(-5px);
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
        
        .contracts-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .table thead th {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
        }
        
        .table tbody td {
            padding: 1rem;
            border-color: #f8f9fa;
            vertical-align: middle;
        }
        
        .action-btn {
            padding: 0.375rem 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin: 0 0.25rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-submit:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .notification-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid var(--secondary-color);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .notification-item.unread {
            background: #f8f9ff;
            border-left-color: var(--primary-color);
        }
        
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .quick-action-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 12px;
            color: white;
            padding: 1rem;
            width: 100%;
            margin-bottom: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 51, 85, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-file-contract me-2"></i>سما البنيان - لوحة الموظف
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?= htmlspecialchars($user['name']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/profile.php"><i class="fas fa-user-edit me-2"></i>الملف الشخصي</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary text-white">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="mb-1"><?= $stats['total_contracts'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">إجمالي العقود</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-warning text-white">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="mb-1"><?= $stats['pending_contracts'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">قيد المراجعة</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success text-white">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="mb-1"><?= $stats['signed_contracts'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">عقود موقعة</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info text-white">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3 class="mb-1"><?= number_format($stats['total_amount'] ?? 0) ?> ر.س</h3>
                    <p class="text-muted mb-0">إجمالي المبالغ</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- العقود -->
            <div class="col-lg-8">
                <div class="contracts-table">
                    <div class="d-flex justify-content-between align-items-center p-3 bg-light">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>عقودي</h5>
                        <a href="/create_contract.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>عقد جديد
                        </a>
                    </div>
                    
                    <?php if (empty($contracts)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد عقود بعد</h5>
                            <p class="text-muted">ابدأ بإنشاء عقد جديد</p>
                            <a href="/create_contract.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>إنشاء عقد جديد
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>رقم العقد</th>
                                        <th>اسم العميل</th>
                                        <th>المبلغ</th>
                                        <th>الحالة</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contracts as $contract): ?>
                                        <?php $statusInfo = getStatusInfo($contract['status']); ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($contract['contract_number']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($contract['client_name']) ?></td>
                                            <td><?= number_format($contract['amount']) ?> ر.س</td>
                                            <td>
                                                <span class="badge <?= $statusInfo['class'] ?>">
                                                    <?= $statusInfo['text'] ?>
                                                </span>
                                            </td>
                                            <td><?= date('Y-m-d', strtotime($contract['created_at'])) ?></td>
                                            <td>
                                                <a href="/view_contract.php?id=<?= $contract['id'] ?>" 
                                                   class="action-btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($contract['status'] === 'draft' || $contract['status'] === 'rejected'): ?>
                                                    <a href="/edit_contract.php?id=<?= $contract['id'] ?>" 
                                                       class="action-btn btn-outline-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($contract['status'] === 'draft'): ?>
                                                    <button onclick="submitForReview(<?= $contract['id'] ?>)" 
                                                            class="action-btn btn-submit">
                                                        <i class="fas fa-paper-plane"></i> إرسال للمراجعة
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($contract['status'] === 'signed'): ?>
                                                    <a href="/download_contract.php?id=<?= $contract['id'] ?>" 
                                                       class="action-btn btn-outline-success">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- الجانب الأيمن -->
            <div class="col-lg-4">
                <!-- الإجراءات السريعة -->
                <div class="quick-actions mb-4">
                    <h6><i class="fas fa-bolt me-2"></i>إجراءات سريعة</h6>
                    <button onclick="location.href='/create_contract.php'" class="quick-action-btn">
                        <i class="fas fa-plus me-2"></i>إنشاء عقد جديد
                    </button>
                    <button onclick="location.href='/my_contracts.php'" class="quick-action-btn">
                        <i class="fas fa-list me-2"></i>جميع عقودي
                    </button>
                    <button onclick="location.href='/notifications.php'" class="quick-action-btn">
                        <i class="fas fa-bell me-2"></i>الإشعارات
                    </button>
                </div>
                
                <!-- الإشعارات الأخيرة -->
                <div class="quick-actions">
                    <h6><i class="fas fa-bell me-2"></i>الإشعارات الأخيرة</h6>
                    
                    <?php if (empty($notifications)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-bell-slash fa-2x mb-2"></i>
                            <p class="mb-0">لا توجد إشعارات جديدة</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
                                <div class="d-flex justify-content-between">
                                    <strong><?= htmlspecialchars($notification['title']) ?></strong>
                                    <small class="text-muted">
                                        <?= date('Y-m-d H:i', strtotime($notification['created_at'])) ?>
                                    </small>
                                </div>
                                <p class="mb-0 mt-1"><?= htmlspecialchars($notification['message']) ?></p>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center mt-3">
                            <a href="/notifications.php" class="btn btn-outline-primary btn-sm">
                                عرض جميع الإشعارات
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function submitForReview(contractId) {
            if (confirm('هل أنت متأكد من إرسال هذا العقد للمراجعة؟')) {
                fetch('/submit_for_review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({contract_id: contractId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('تم إرسال العقد للمراجعة بنجاح');
                        location.reload();
                    } else {
                        alert('حدث خطأ: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('حدث خطأ في الاتصال');
                });
            }
        }
    </script>
</body>
</html>