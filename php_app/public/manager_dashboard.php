<?php
require_once '../includes/auth.php';

// التحقق من صلاحية المدير
$auth->requireAuth();
$user = $auth->getCurrentUser();

// التحقق من أن المستخدم مدير أو أدمن
if (!in_array($user['role'], ['manager', 'admin'])) {
    header('HTTP/1.0 403 Forbidden');
    die('غير مسموح لك بالوصول لهذه الصفحة');
}

try {
    // جلب جميع العقود مع بيانات المنشئ
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as created_by_name, u.email as created_by_email,
               approver.name as approved_by_name
        FROM contracts c 
        LEFT JOIN users u ON c.created_by = u.id 
        LEFT JOIN users approver ON c.approved_by = approver.id
        ORDER BY 
            CASE c.status
                WHEN 'pending_review' THEN 1
                WHEN 'draft' THEN 2
                WHEN 'approved' THEN 3
                WHEN 'rejected' THEN 4
                WHEN 'signed' THEN 5
                ELSE 6
            END,
            c.created_at DESC
    ");
    $stmt->execute();
    $contracts = $stmt->fetchAll();
    
    // إحصائيات شاملة
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_contracts,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_contracts,
            SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END) as pending_contracts,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_contracts,
            SUM(CASE WHEN status = 'signed' THEN 1 ELSE 0 END) as signed_contracts,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_contracts,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = 'signed' THEN amount ELSE 0 END) as signed_amount
        FROM contracts
    ");
    $statsStmt->execute();
    $stats = $statsStmt->fetch();
    
    // إحصائيات الموظفين
    $employeeStatsStmt = $pdo->prepare("
        SELECT 
            u.name, u.email,
            COUNT(c.id) as total_contracts,
            SUM(CASE WHEN c.status = 'signed' THEN 1 ELSE 0 END) as signed_contracts,
            SUM(c.amount) as total_amount
        FROM users u
        LEFT JOIN contracts c ON u.id = c.created_by
        WHERE u.role = 'employee' AND u.status = 'active'
        GROUP BY u.id, u.name, u.email
        ORDER BY total_contracts DESC
    ");
    $employeeStatsStmt->execute();
    $employeeStats = $employeeStatsStmt->fetchAll();
    
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
    <title>لوحة المدير - نظام إدارة العقود</title>
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
            position: relative;
            overflow: hidden;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
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
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background: #218838;
            color: white;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-reject:hover {
            background: #c82333;
            color: white;
        }
        
        .btn-sign {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-sign:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .pending-highlight {
            background: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
        }
        
        .nav-tabs .nav-link {
            border: none;
            background: transparent;
            color: var(--text-color);
            font-weight: 600;
            padding: 1rem 1.5rem;
        }
        
        .nav-tabs .nav-link.active {
            background: white;
            color: var(--primary-color);
            border-radius: 10px 10px 0 0;
        }
        
        .tab-content {
            background: white;
            border-radius: 0 0 15px 15px;
            padding: 0;
        }
        
        .employee-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--secondary-color);
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
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-crown me-2"></i>سما البنيان - لوحة المدير
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-tie me-2"></i><?= htmlspecialchars($user['name']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/manage_employees.php"><i class="fas fa-users me-2"></i>إدارة الموظفين</a></li>
                        <li><a class="dropdown-item" href="/reports.php"><i class="fas fa-chart-bar me-2"></i>التقارير</a></li>
                        <li><a class="dropdown-item" href="/profile.php"><i class="fas fa-user-edit me-2"></i>الملف الشخصي</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <!-- إحصائيات شاملة -->
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
                    <h3 class="mb-1"><?= number_format($stats['signed_amount'] ?? 0) ?> ر.س</h3>
                    <p class="text-muted mb-0">إجمالي العقود الموقعة</p>
                </div>
            </div>
        </div>
        
        <!-- التبويبات -->
        <ul class="nav nav-tabs" id="managerTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="contracts-tab" data-bs-toggle="tab" data-bs-target="#contracts" type="button" role="tab">
                    <i class="fas fa-file-contract me-2"></i>العقود (<?= count($contracts) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>الموظفين (<?= count($employeeStats) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                    <i class="fas fa-bell me-2"></i>الإشعارات (<?= count($notifications) ?>)
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="managerTabsContent">
            <!-- تبويب العقود -->
            <div class="tab-pane fade show active" id="contracts" role="tabpanel">
                <div class="contracts-table">
                    <?php if (empty($contracts)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد عقود بعد</h5>
                            <p class="text-muted">سيظهر هنا العقود المرسلة من الموظفين</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>رقم العقد</th>
                                        <th>اسم العميل</th>
                                        <th>المبلغ</th>
                                        <th>الموظف</th>
                                        <th>الحالة</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contracts as $contract): ?>
                                        <?php $statusInfo = getStatusInfo($contract['status']); ?>
                                        <tr <?= $contract['status'] === 'pending_review' ? 'class="pending-highlight"' : '' ?>>
                                            <td>
                                                <strong><?= htmlspecialchars($contract['contract_number']) ?></strong>
                                                <?php if ($contract['status'] === 'pending_review'): ?>
                                                    <i class="fas fa-exclamation-circle text-warning ms-1" title="يحتاج مراجعة"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($contract['client_name']) ?></td>
                                            <td><?= number_format($contract['amount']) ?> ر.س</td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($contract['created_by_name']) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= htmlspecialchars($contract['created_by_email']) ?></small>
                                                </div>
                                            </td>
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
                                                
                                                <?php if ($contract['status'] === 'pending_review'): ?>
                                                    <button onclick="approveContract(<?= $contract['id'] ?>)" 
                                                            class="action-btn btn-approve">
                                                        <i class="fas fa-check"></i> موافقة
                                                    </button>
                                                    <button onclick="rejectContract(<?= $contract['id'] ?>)" 
                                                            class="action-btn btn-reject">
                                                        <i class="fas fa-times"></i> رفض
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($contract['status'] === 'approved'): ?>
                                                    <button onclick="signContract(<?= $contract['id'] ?>)" 
                                                            class="action-btn btn-sign">
                                                        <i class="fas fa-signature"></i> توقيع
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
            
            <!-- تبويب الموظفين -->
            <div class="tab-pane fade" id="employees" role="tabpanel">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5><i class="fas fa-users me-2"></i>إحصائيات الموظفين</h5>
                        <a href="/manage_employees.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>إدارة الموظفين
                        </a>
                    </div>
                    
                    <?php if (empty($employeeStats)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا يوجد موظفين نشطين</h5>
                            <a href="/manage_employees.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>إضافة موظف جديد
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($employeeStats as $employee): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="employee-card">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <i class="fas fa-user me-2"></i><?= htmlspecialchars($employee['name']) ?>
                                                </h6>
                                                <p class="text-muted mb-2"><?= htmlspecialchars($employee['email']) ?></p>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary"><?= $employee['total_contracts'] ?> عقد</span>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-3">
                                            <div class="col-6">
                                                <small class="text-muted">العقود الموقعة</small>
                                                <div class="fw-bold text-success"><?= $employee['signed_contracts'] ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">إجمالي المبالغ</small>
                                                <div class="fw-bold"><?= number_format($employee['total_amount'] ?? 0) ?> ر.س</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- تبويب الإشعارات -->
            <div class="tab-pane fade" id="notifications" role="tabpanel">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5><i class="fas fa-bell me-2"></i>الإشعارات الأخيرة</h5>
                        <a href="/notifications.php" class="btn btn-outline-primary btn-sm">
                            عرض جميع الإشعارات
                        </a>
                    </div>
                    
                    <?php if (empty($notifications)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد إشعارات جديدة</h5>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal للموافقة/الرفض -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="managerNotes" class="form-label">ملاحظات (اختيارية)</label>
                        <textarea class="form-control" id="managerNotes" rows="3" placeholder="أضف ملاحظاتك هنا..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" id="confirmActionBtn" class="btn"></button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentContractId = null;
        let currentAction = null;
        
        function approveContract(contractId) {
            currentContractId = contractId;
            currentAction = 'approve';
            
            document.getElementById('actionModalTitle').textContent = 'موافقة على العقد';
            document.getElementById('confirmActionBtn').textContent = 'موافقة';
            document.getElementById('confirmActionBtn').className = 'btn btn-success';
            document.getElementById('managerNotes').placeholder = 'ملاحظات الموافقة (اختيارية)';
            
            new bootstrap.Modal(document.getElementById('actionModal')).show();
        }
        
        function rejectContract(contractId) {
            currentContractId = contractId;
            currentAction = 'reject';
            
            document.getElementById('actionModalTitle').textContent = 'رفض العقد';
            document.getElementById('confirmActionBtn').textContent = 'رفض';
            document.getElementById('confirmActionBtn').className = 'btn btn-danger';
            document.getElementById('managerNotes').placeholder = 'أسباب الرفض...';
            
            new bootstrap.Modal(document.getElementById('actionModal')).show();
        }
        
        function signContract(contractId) {
            if (confirm('هل أنت متأكد من توقيع هذا العقد؟')) {
                processContractAction(contractId, 'sign', '');
            }
        }
        
        document.getElementById('confirmActionBtn').addEventListener('click', function() {
            const notes = document.getElementById('managerNotes').value;
            processContractAction(currentContractId, currentAction, notes);
            bootstrap.Modal.getInstance(document.getElementById('actionModal')).hide();
        });
        
        function processContractAction(contractId, action, notes) {
            fetch('/process_contract_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    contract_id: contractId,
                    action: action,
                    notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('حدث خطأ: ' + data.message);
                }
            })
            .catch(error => {
                alert('حدث خطأ في الاتصال');
            });
        }
    </script>
</body>
</html>