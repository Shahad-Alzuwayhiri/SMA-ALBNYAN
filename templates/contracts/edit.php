<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل العقد - <?= htmlspecialchars($contract['title']) ?></title>
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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg">
                    <div class="card-header bg-warning text-dark">
                        <h3 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            تعديل العقد
                        </h3>
                        <nav aria-label="breadcrumb" class="mt-2">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="/" class="text-dark">الرئيسية</a></li>
                                <li class="breadcrumb-item"><a href="/contracts" class="text-dark">العقود</a></li>
                                <li class="breadcrumb-item"><a href="/contracts/<?= $contract['id'] ?>" class="text-dark">عرض العقد</a></li>
                                <li class="breadcrumb-item active text-dark" aria-current="page">تعديل</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= $_SESSION['error'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>
                        
                        <!-- Contract Info Alert -->
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-8">
                                    <strong>رقم العقد:</strong> #<?= str_pad($contract['id'], 6, '0', STR_PAD_LEFT) ?>
                                    <br>
                                    <strong>تاريخ الإنشاء:</strong> <?= date('Y-m-d H:i', strtotime($contract['created_at'])) ?>
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
                                    <span class="badge <?= $statusClass ?> fs-6">
                                        <?= $statusText ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" action="/contracts/<?= $contract['id'] ?>/edit" id="contractEditForm">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="title" class="form-label">
                                        <i class="fas fa-heading me-2"></i>
                                        عنوان العقد *
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="title" name="title" required
                                           value="<?= htmlspecialchars($contract['title']) ?>"
                                           placeholder="أدخل عنوان العقد" autocomplete="off">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="type" class="form-label">
                                        <i class="fas fa-tag me-2"></i>
                                        نوع العقد *
                                    </label>
                                    <select class="form-select form-select-lg" id="type" name="type" required>
                                        <option value="">اختر نوع العقد</option>
                                        <option value="investment" <?= $contract['type'] === 'investment' ? 'selected' : '' ?>>عقد استثمار</option>
                                        <option value="property_investment" <?= $contract['type'] === 'property_investment' ? 'selected' : '' ?>>عقد استثمار عقاري</option>
                                        <option value="speculation" <?= $contract['type'] === 'speculation' ? 'selected' : '' ?>>عقد مضاربة</option>
                                        <option value="partnership" <?= $contract['type'] === 'partnership' ? 'selected' : '' ?>>عقد شراكة</option>
                                        <option value="service" <?= $contract['type'] === 'service' ? 'selected' : '' ?>>عقد خدمة</option>
                                        <option value="general" <?= $contract['type'] === 'general' ? 'selected' : '' ?>>عقد عام</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="client_id" class="form-label">
                                        <i class="fas fa-user-tie me-2"></i>
                                        العميل (اختياري)
                                    </label>
                                    <select class="form-select" id="client_id" name="client_id">
                                        <option value="">اختر العميل</option>
                                        <option value="1" <?= $contract['client_id'] == 1 ? 'selected' : '' ?>>عميل تجريبي 1</option>
                                        <option value="2" <?= $contract['client_id'] == 2 ? 'selected' : '' ?>>عميل تجريبي 2</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="amount" class="form-label">
                                        <i class="fas fa-money-bill-wave me-2"></i>
                                        المبلغ (ريال سعودي)
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="amount" name="amount" 
                                               value="<?= $contract['amount'] ?>"
                                               placeholder="0.00" min="0" step="0.01" autocomplete="off">
                                        <span class="input-group-text">ر.س</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="content" class="form-label">
                                    <i class="fas fa-file-alt me-2"></i>
                                    محتوى العقد *
                                </label>
                                <textarea class="form-control" id="content" name="content" rows="12" required
                                          placeholder="أدخل تفاصيل العقد وبنوده..."><?= htmlspecialchars($contract['content']) ?></textarea>
                                <div class="form-text">
                                    يمكنك استخدام النص العادي أو HTML لتنسيق المحتوى
                                </div>
                            </div>
                            
                            <!-- Status Update (for managers) -->
                            <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="status" class="form-label">
                                        <i class="fas fa-info-circle me-2"></i>
                                        حالة العقد
                                    </label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="draft" <?= $contract['status'] === 'draft' ? 'selected' : '' ?>>مسودة</option>
                                        <option value="active" <?= $contract['status'] === 'active' ? 'selected' : '' ?>>نشط</option>
                                        <option value="completed" <?= $contract['status'] === 'completed' ? 'selected' : '' ?>>مكتمل</option>
                                        <option value="cancelled" <?= $contract['status'] === 'cancelled' ? 'selected' : '' ?>>ملغي</option>
                                    </select>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="backup_original" name="backup_original" checked>
                                    <label class="form-check-label" for="backup_original">
                                        الاحتفاظ بنسخة احتياطية من النسخة الأصلية
                                    </label>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="/contracts/<?= $contract['id'] ?>" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-times me-2"></i>
                                        إلغاء التعديل
                                    </a>
                                    <a href="/contracts/<?= $contract['id'] ?>/pdf" target="_blank" class="btn btn-outline-primary btn-lg">
                                        <i class="fas fa-file-pdf me-2"></i>
                                        معاينة PDF
                                    </a>
                                </div>
                                
                                <div>
                                    <button type="submit" class="btn btn-warning btn-lg">
                                        <i class="fas fa-save me-2"></i>
                                        حفظ التعديلات
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Edit History (if available) -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            تاريخ التعديلات
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">إنشاء العقد</h6>
                                    <small class="text-muted">
                                        <?= date('Y-m-d H:i', strtotime($contract['created_at'])) ?>
                                        بواسطة <?= htmlspecialchars($contract['creator_name'] ?? 'غير معروف') ?>
                                    </small>
                                </div>
                            </div>
                            
                            <?php if ($contract['updated_at'] && $contract['updated_at'] !== $contract['created_at']): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-warning"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">آخر تعديل</h6>
                                    <small class="text-muted">
                                        <?= date('Y-m-d H:i', strtotime($contract['updated_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Important Notes -->
                <div class="alert alert-warning mt-4">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>تنبيهات مهمة:</h6>
                    <ul class="mb-0">
                        <li>تأكد من مراجعة جميع التعديلات قبل الحفظ</li>
                        <li>سيتم إشعار جميع الأطراف المعنية بالتعديلات</li>
                        <li>يمكنك معاينة العقد بتنسيق PDF قبل الحفظ</li>
                        <?php if ($contract['status'] === 'completed'): ?>
                        <li class="text-danger">تحذير: العقد في حالة "مكتمل" - تعديله قد يؤثر على صحته القانونية</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-marker {
            position: absolute;
            left: -35px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 17px;
            width: 2px;
            height: calc(100% + 5px);
            background-color: #dee2e6;
        }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('contractEditForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const type = document.getElementById('type').value;
            const content = document.getElementById('content').value.trim();
            
            if (!title || !type || !content) {
                e.preventDefault();
                alert('يرجى ملء جميع الحقول المطلوبة (العنوان، النوع، المحتوى)');
                return false;
            }
            
            if (content.length < 50) {
                e.preventDefault();
                alert('محتوى العقد يجب أن يكون 50 حرف على الأقل');
                return false;
            }
            
            // Confirmation for completed contracts
            const status = document.getElementById('status');
            if (status && status.value === 'completed') {
                if (!confirm('تحذير: تعديل عقد مكتمل قد يؤثر على صحته القانونية. هل تريد المتابعة؟')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return confirm('هل تريد حفظ جميع التعديلات؟');
        });
        
        // Track changes
        let hasChanges = false;
        const formInputs = document.querySelectorAll('#contractEditForm input, #contractEditForm textarea, #contractEditForm select');
        
        formInputs.forEach(input => {
            input.addEventListener('change', function() {
                hasChanges = true;
            });
        });
        
        // Warn before leaving if there are unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = 'لديك تعديلات غير محفوظة. هل تريد المغادرة؟';
            }
        });
        
        // Auto-save draft (every 2 minutes)
        setInterval(function() {
            if (hasChanges) {
                // يمكن إضافة Ajax لحفظ المسودة تلقائياً
                console.log('Auto-saving draft...');
            }
        }, 120000);
    </script>
</body>
</html>