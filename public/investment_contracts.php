<?php
/**
 * صفحة عقود الاستثمار - نظام سما البنيان
 * عرض وإدارة عقود المضاربة في العقارات
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../includes/auth.php';

// التحقق من المصادقة
$auth->requireAuth();
$user = $auth->getCurrentUser();

    // جلب عقود الاستثمار
    try {
        $investmentContractsStmt = $pdo->prepare("
            SELECT c.*, u.name as created_by_name 
            FROM contracts c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.contract_type IN ('investment', 'property_investment') OR c.title LIKE '%استثمار%'
            ORDER BY c.created_at DESC
        ");
        $investmentContractsStmt->execute();
        $investmentContracts = $investmentContractsStmt->fetchAll();

        // إحصائيات عقود الاستثمار
        $statsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_contracts,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_contracts,
                SUM(CASE WHEN status = 'signed' THEN 1 ELSE 0 END) as signed_contracts,
                SUM(CASE WHEN contract_type = 'investment' THEN 1 ELSE 0 END) as cash_contracts,
                SUM(CASE WHEN contract_type = 'property_investment' THEN 1 ELSE 0 END) as property_contracts,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = 'signed' THEN amount ELSE 0 END) as signed_amount,
                AVG(profit_percentage) as avg_profit
            FROM contracts 
            WHERE contract_type IN ('investment', 'property_investment') OR title LIKE '%استثمار%'
        ");
    $statsStmt->execute();
    $stats = $statsStmt->fetch();

} catch (PDOException $e) {
    $error = "خطأ في جلب البيانات: " . $e->getMessage();
    $investmentContracts = [];
    $stats = [
        'total_contracts' => 0,
        'draft_contracts' => 0,
        'signed_contracts' => 0,
        'total_amount' => 0,
        'signed_amount' => 0,
        'avg_profit' => 0
    ];
}

// Note: Status functions now available via autoloaded App\Helpers\Functions class
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عقود الاستثمار - سما البنيان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/static/css/modern-theme.css" rel="stylesheet">
</head>
<body>
    <?php include '../templates/partials/_topnav.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../templates/partials/_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-handshake me-2"></i>
                        عقود الاستثمار والمضاربة
                    </h1>
                    <div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-plus me-2"></i>
                                عقد استثمار جديد
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/create_investment_contract.php">
                                    <i class="fas fa-coins me-2"></i>استثمار نقدي
                                </a></li>
                                <li><a class="dropdown-item" href="/create_property_investment.php">
                                    <i class="fas fa-building me-2"></i>استثمار بعقار
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- إحصائيات عقود الاستثمار -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-handshake fa-2x text-primary mb-2"></i>
                                <h4><?= number_format($stats['total_contracts']) ?></h4>
                                <p class="text-muted mb-0">إجمالي العقود</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-coins fa-2x text-warning mb-2"></i>
                                <h4><?= number_format($stats['cash_contracts'] ?? 0) ?></h4>
                                <p class="text-muted mb-0">استثمار نقدي</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-building fa-2x text-success mb-2"></i>
                                <h4><?= number_format($stats['property_contracts'] ?? 0) ?></h4>
                                <p class="text-muted mb-0">استثمار بعقار</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-check-circle fa-2x text-info mb-2"></i>
                                <h4><?= number_format($stats['signed_contracts']) ?></h4>
                                <p class="text-muted mb-0">موقعة</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-money-bill fa-2x text-primary mb-2"></i>
                                <h4><?= number_format($stats['total_amount']) ?></h4>
                                <p class="text-muted mb-0">إجمالي القيمة</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-percentage fa-2x text-secondary mb-2"></i>
                                <h4><?= number_format($stats['avg_profit'] ?? 0, 1) ?>%</h4>
                                <p class="text-muted mb-0">متوسط الربح</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- قائمة عقود الاستثمار -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            قائمة عقود الاستثمار (<?= count($investmentContracts) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($investmentContracts)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-handshake fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">لا توجد عقود استثمار بعد</h5>
                                <p class="text-muted">ابدأ بإنشاء عقد استثمار جديد</p>
                                <a href="/create_investment_contract.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>
                                    إنشاء عقد استثمار
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>رقم العقد</th>
                                            <th>المستثمر</th>
                                            <th>نوع الاستثمار</th>
                                            <th>القيمة</th>
                                            <th>نسبة الربح</th>
                                            <th>المدة</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($investmentContracts as $contract): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($contract['contract_number']) ?></strong>
                                                </td>
                                                <td><?= htmlspecialchars($contract['client_name']) ?></td>
                                                <td>
                                                    <?php if ($contract['contract_type'] === 'property_investment'): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-building me-1"></i>عقار
                                                        </span>
                                                        <?php if (!empty($contract['property_number'])): ?>
                                                            <br><small class="text-muted"><?= htmlspecialchars($contract['property_number']) ?></small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-coins me-1"></i>نقدي
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="text-success">
                                                        <?= number_format($contract['amount']) ?> ر.س
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= $contract['profit_percentage'] ?>%
                                                    </span>
                                                    <?php if ($contract['profit_frequency']): ?>
                                                        <br><small class="text-muted">كل <?= $contract['profit_frequency'] ?> شهر</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= $contract['contract_duration'] ?? 6 ?> أشهر
                                                </td>
                                                <td><?= getStatusBadge($contract['status']) ?></td>
                                                <td>
                                                    <small><?= date('Y-m-d', strtotime($contract['created_at'])) ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="/view_contract.php?id=<?= $contract['id'] ?>" 
                                                           class="btn btn-outline-primary" title="عرض">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="/edit_contract.php?id=<?= $contract['id'] ?>" 
                                                           class="btn btn-outline-secondary" title="تعديل">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="/export_pdf.php?id=<?= $contract['id'] ?>" 
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
                        <?php endif; ?>
                    </div>
                </div>

                <!-- نصائح حول عقود الاستثمار -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-lightbulb me-2"></i>
                            نصائح حول عقود الاستثمار
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>المميزات:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>نسبة ربح ثابتة ومضمونة</li>
                                    <li><i class="fas fa-check text-success me-2"></i>مدة محددة للاستثمار</li>
                                    <li><i class="fas fa-check text-success me-2"></i>إمكانية الانسحاب بإشعار مسبق</li>
                                    <li><i class="fas fa-check text-success me-2"></i>عمولة تسويقية للمستثمر</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>الشروط المهمة:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>استرداد رأس المال بعد 6 أشهر</li>
                                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>إشعار مسبق 60 يوماً للانسحاب</li>
                                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>شرط جزائي عند تأخير الأرباح</li>
                                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>تحمل نسبة من الخسائر</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>