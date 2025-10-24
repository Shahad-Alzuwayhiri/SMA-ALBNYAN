<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة العقود - <?= $config['company']['name_ar'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/unified-theme.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="/assets/images/sma-logo.svg" alt="شعار الشركة" height="30" class="me-2">
                <?= $config['company']['name_ar'] ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <?php if ($user): ?>
                    <span class="navbar-text me-3">
                        <i class="fas fa-user me-1"></i>
                        مرحباً، <?= htmlspecialchars($user['name']) ?>
                    </span>
                    <a class="nav-link" href="/logout">
                        <i class="fas fa-sign-out-alt me-1"></i>
                        خروج
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="/login">تسجيل الدخول</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-primary">
                        <i class="fas fa-file-contract me-2"></i>
                        قائمة العقود
                    </h2>
                    
                    <?php if ($user): ?>
                        <a href="/contracts/create" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>
                            إنشاء عقد جديد
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <?php if (!$user): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        يجب عليك <a href="/login" class="alert-link">تسجيل الدخول</a> لعرض العقود.
                    </div>
                <?php elseif (empty($contracts)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-file-contract fa-3x mb-3 text-muted"></i>
                        <h5>لا توجد عقود حتى الآن</h5>
                        <p class="text-muted">ابدأ بإنشاء عقد جديد لترى العقود هنا</p>
                        <a href="/contracts/create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            إنشاء أول عقد
                        </a>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="mb-0">
                                        إجمالي العقود: <?= $totalContracts ?>
                                    </h6>
                                </div>
                                <div class="col-auto">
                                    <small class="text-muted">
                                        الصفحة <?= $currentPage ?> من <?= $totalPages ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>العنوان</th>
                                            <th>النوع</th>
                                            <th>المبلغ</th>
                                            <th>الحالة</th>
                                            <th>المنشئ</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contracts as $contract): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($contract['title'] ?? 'بدون عنوان') ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= ucfirst($contract['type']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($contract['amount']): ?>
                                                        <span class="text-success fw-bold">
                                                            <?= number_format($contract['amount']) ?> ر.س
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">غير محدد</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = [
                                                        'draft' => 'bg-secondary',
                                                        'active' => 'bg-success',
                                                        'completed' => 'bg-primary',
                                                        'cancelled' => 'bg-danger'
                                                    ][$contract['status']] ?? 'bg-secondary';
                                                    
                                                    $statusText = [
                                                        'draft' => 'مسودة',
                                                        'active' => 'نشط',
                                                        'completed' => 'مكتمل',
                                                        'cancelled' => 'ملغي'
                                                    ][$contract['status']] ?? $contract['status'];
                                                    ?>
                                                    <span class="badge <?= $statusClass ?>">
                                                        <?= $statusText ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($contract['creator_name'] ?? 'غير معروف') ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('Y-m-d', strtotime($contract['created_at'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="/contracts/<?= $contract['id'] ?>" class="btn btn-outline-primary btn-sm" title="عرض">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="/contracts/<?= $contract['id'] ?>/edit" class="btn btn-outline-warning btn-sm" title="تعديل">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="/contracts/<?= $contract['id'] ?>/pdf" class="btn btn-outline-danger btn-sm" title="تصدير PDF">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <?php if ($totalPages > 1): ?>
                            <div class="card-footer">
                                <nav aria-label="صفحات العقود">
                                    <ul class="pagination pagination-sm justify-content-center mb-0">
                                        <?php if ($currentPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="/contracts?page=<?= $currentPage - 1 ?>">
                                                    السابق
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                                <a class="page-link" href="/contracts?page=<?= $i ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($currentPage < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="/contracts?page=<?= $currentPage + 1 ?>">
                                                    التالي
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>