<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض العقد - <?= htmlspecialchars($contract['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/unified-theme.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .contract-content {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            line-height: 1.8;
        }
        .contract-header img {
            max-height: 60px;
        }
        @media print {
            .no-print { display: none !important; }
            .contract-content { background: white; border: none; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="/assets/images/sma-logo.svg" alt="شعار الشركة" height="30" class="me-2">
                <?= $config['company']['name_ar'] ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>
                    مرحباً، <?= htmlspecialchars($user['name']) ?>
                </span>
                <a class="nav-link" href="/logout">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    خروج
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Action Bar -->
        <div class="row no-print mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="/contracts">العقود</a></li>
                            <li class="breadcrumb-item active" aria-current="page">عرض العقد</li>
                        </ol>
                    </nav>
                    
                    <div class="btn-group">
                        <a href="/contracts/<?= $contract['id'] ?>/edit" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>
                            تعديل
                        </a>
                        <a href="/contracts/<?= $contract['id'] ?>/pdf" target="_blank" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-2"></i>
                            تصدير PDF
                        </a>
                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fas fa-print me-2"></i>
                            طباعة
                        </button>
                        <a href="/contracts" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-right me-2"></i>
                            العودة
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contract Document -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg">
                    <!-- Contract Header -->
                    <div class="contract-header text-center p-4 border-bottom">
                        <img src="/assets/images/sma-logo.svg" alt="شعار الشركة" class="mb-3">
                        <h2 class="text-primary mb-2"><?= $config['company']['name_ar'] ?></h2>
                        <h5 class="text-muted"><?= $config['company']['name_en'] ?></h5>
                        <p class="mb-0">
                            <i class="fas fa-phone me-2"></i><?= $config['company']['phone'] ?>
                            <span class="mx-3">|</span>
                            <i class="fas fa-envelope me-2"></i><?= $config['company']['email'] ?>
                        </p>
                    </div>
                    
                    <!-- Contract Info -->
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <h3 class="text-primary mb-3">
                                    <i class="fas fa-file-contract me-2"></i>
                                    <?= htmlspecialchars($contract['title']) ?>
                                </h3>
                            </div>
                            <div class="col-md-4 text-start">
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
                                <span class="badge <?= $statusClass ?> fs-6 px-3 py-2">
                                    <?= $statusText ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Contract Details -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="border p-3 rounded bg-light">
                                    <h6 class="text-muted mb-2">معلومات العقد</h6>
                                    <p class="mb-1"><strong>رقم العقد:</strong> #<?= str_pad($contract['id'], 6, '0', STR_PAD_LEFT) ?></p>
                                    <p class="mb-1"><strong>نوع العقد:</strong> 
                                        <span class="badge bg-info"><?= ucfirst($contract['type']) ?></span>
                                    </p>
                                    <p class="mb-1"><strong>تاريخ الإنشاء:</strong> <?= date('Y-m-d H:i', strtotime($contract['created_at'])) ?></p>
                                    <?php if ($contract['updated_at']): ?>
                                        <p class="mb-0"><strong>آخر تحديث:</strong> <?= date('Y-m-d H:i', strtotime($contract['updated_at'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border p-3 rounded bg-light">
                                    <h6 class="text-muted mb-2">التفاصيل المالية</h6>
                                    <?php if ($contract['amount']): ?>
                                        <p class="mb-1"><strong>المبلغ:</strong> 
                                            <span class="text-success fw-bold fs-5">
                                                <?= number_format($contract['amount'], 2) ?> ريال سعودي
                                            </span>
                                        </p>
                                    <?php else: ?>
                                        <p class="mb-1"><strong>المبلغ:</strong> <span class="text-muted">غير محدد</span></p>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($contract['client_name']) && $contract['client_name']): ?>
                                        <p class="mb-1"><strong>العميل:</strong> <?= htmlspecialchars($contract['client_name']) ?></p>
                                    <?php endif; ?>
                                    
                                    <p class="mb-0"><strong>منشئ العقد:</strong> <?= htmlspecialchars($contract['creator_name'] ?? 'غير معروف') ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contract Content -->
                        <div class="contract-content">
                            <h5 class="mb-4 text-center text-primary border-bottom pb-2">محتوى العقد</h5>
                            
                            <div class="contract-text">
                                <?= nl2br(htmlspecialchars($contract['content'])) ?>
                            </div>
                            
                            <!-- Signature Section -->
                            <div class="row mt-5 pt-4 border-top">
                                <div class="col-md-6">
                                    <div class="text-center">
                                        <h6>الطرف الأول</h6>
                                        <div class="border-top mt-4 pt-2">
                                            <p class="mb-0"><?= $config['company']['name_ar'] ?></p>
                                            <small class="text-muted">التوقيع والختم</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-center">
                                        <h6>الطرف الثاني</h6>
                                        <div class="border-top mt-4 pt-2">
                                            <p class="mb-0">
                                                <?= isset($contract['client_name']) && $contract['client_name'] 
                                                    ? htmlspecialchars($contract['client_name']) 
                                                    : '.............................' ?>
                                            </p>
                                            <small class="text-muted">التوقيع والختم</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Date and Place -->
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <p><strong>التاريخ:</strong> <?= date('Y/m/d') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>المكان:</strong> <?= $config['company']['address'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions (No Print) -->
                <div class="row mt-4 no-print">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>إجراءات متاحة:</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>تعديل العقد:</strong></p>
                                    <small>يمكنك تعديل محتوى العقد وتفاصيله</small>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>تصدير PDF:</strong></p>
                                    <small>احصل على نسخة PDF بعلامة الشركة المائية</small>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>طباعة:</strong></p>
                                    <small>اطبع العقد مباشرة من المتصفح</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Related Actions -->
                <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
                <div class="row mt-3 no-print">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">إجراءات إدارية</h6>
                            </div>
                            <div class="card-body">
                                <div class="btn-group">
                                    <button class="btn btn-outline-success" onclick="updateStatus('active')">
                                        <i class="fas fa-check me-2"></i>تفعيل العقد
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="updateStatus('completed')">
                                        <i class="fas fa-flag-checkered me-2"></i>إكمال العقد
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="updateStatus('cancelled')">
                                        <i class="fas fa-times me-2"></i>إلغاء العقد
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(newStatus) {
            if (confirm('هل تريد تحديث حالة العقد؟')) {
                // يمكن إضافة Ajax request لتحديث الحالة
                console.log('Updating status to:', newStatus);
                // window.location.href = `/contracts/<?= $contract['id'] ?>/status/${newStatus}`;
            }
        }
        
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>