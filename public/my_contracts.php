<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../includes/auth.php';

// التأكد من تسجيل الدخول
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();

// جلب العقود الخاصة بالمستخدم الحالي
try {
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u1.name as creator_name,
            u2.name as reviewer_name
        FROM contracts c
        LEFT JOIN users u1 ON c.created_by = u1.id
        LEFT JOIN users u2 ON c.reviewed_by = u2.id
        WHERE c.created_by = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $contracts = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "خطأ في جلب العقود: " . $e->getMessage();
    $contracts = [];
}

// Note: Status functions now available via autoloaded App\Helpers\Functions class
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عقودي - نظام إدارة العقود</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/modern-theme.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-file-contract me-2"></i>
                            عقودي الشخصية
                        </h4>
                        <a href="create_contract.php" class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-1"></i>
                            إنشاء عقد جديد
                        </a>
                    </div>
                    
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($contracts)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">لا توجد عقود</h5>
                                <p class="text-muted">لم تقم بإنشاء أي عقود بعد</p>
                                <a href="create_contract.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>
                                    إنشاء عقد جديد
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>رقم العقد</th>
                                            <th>عنوان العقد</th>
                                            <th>الطرف الثاني</th>
                                            <th>قيمة العقد</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contracts as $contract): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($contract['contract_number']) ?></strong>
                                                </td>
                                                <td><?= htmlspecialchars($contract['title'] ?? 'غير محدد') ?></td>
                                                <td><?= htmlspecialchars($contract['second_party_name'] ?? 'غير محدد') ?></td>
                                                <td>
                                                    <strong class="text-success">
                                                        <?= number_format($contract['contract_amount'] ?? 0, 2) ?> ريال
                                                    </strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= getStatusClass($contract['status']) ?>">
                                                        <?= getStatusText($contract['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= date('Y-m-d', strtotime($contract['created_at'])) ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="view_contract.php?id=<?= $contract['id'] ?>" 
                                                           class="btn btn-outline-primary" title="عرض">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        <?php if ($contract['status'] === 'draft'): ?>
                                                            <a href="edit_contract.php?id=<?= $contract['id'] ?>" 
                                                               class="btn btn-outline-warning" title="تعديل">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <a href="export_pdf.php?id=<?= $contract['id'] ?>" 
                                                           class="btn btn-outline-success" title="تصدير PDF">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">إحصائيات عقودي</h6>
                                            <div class="row text-center">
                                                <div class="col-3">
                                                    <div class="text-warning">
                                                        <i class="fas fa-edit fa-2x"></i>
                                                        <div class="fw-bold">
                                                            <?= count(array_filter($contracts, fn($c) => $c['status'] === 'draft')) ?>
                                                        </div>
                                                        <small>مسودة</small>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="text-success">
                                                        <i class="fas fa-check-circle fa-2x"></i>
                                                        <div class="fw-bold">
                                                            <?= count(array_filter($contracts, fn($c) => $c['status'] === 'active')) ?>
                                                        </div>
                                                        <small>معتمد</small>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="text-primary">
                                                        <i class="fas fa-handshake fa-2x"></i>
                                                        <div class="fw-bold">
                                                            <?= count(array_filter($contracts, fn($c) => $c['status'] === 'completed')) ?>
                                                        </div>
                                                        <small>مكتمل</small>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="text-danger">
                                                        <i class="fas fa-times-circle fa-2x"></i>
                                                        <div class="fw-bold">
                                                            <?= count(array_filter($contracts, fn($c) => $c['status'] === 'cancelled')) ?>
                                                        </div>
                                                        <small>ملغي</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">القيمة الإجمالية</h6>
                                            <div class="text-center">
                                                <div class="text-success">
                                                    <i class="fas fa-money-bill-wave fa-2x"></i>
                                                    <div class="h4 fw-bold">
                                                        <?= number_format(array_sum(array_column($contracts, 'contract_amount')), 2) ?> ريال
                                                    </div>
                                                    <small>إجمالي قيمة العقود</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>