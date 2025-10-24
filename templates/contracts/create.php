<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء عقد جديد - <?= $config['company']['name_ar'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php
    // Defensive bootstrap for asset() helper
    if (!function_exists('asset')) {
        $helpers = __DIR__ . '/../../includes/helpers.php';
        if (file_exists($helpers)) {
            require_once $helpers;
        }
        if (!function_exists('asset')) {
            function asset($path) { return $path; }
        }
    }
    ?>
    <link href="<?php echo asset('assets/css/unified-theme.css'); ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo asset('index.php'); ?>">
                <img src="<?php echo asset('assets/images/sma-logo.svg'); ?>" alt="شعار الشركة" height="30" class="me-2">
                <?= $config['company']['name_ar'] ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>
                    مرحباً، <?= htmlspecialchars($user['name']) ?>
                </span>
                <a class="nav-link" href="<?php echo asset('logout.php'); ?>">
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
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>
                            إنشاء عقد جديد
                        </h3>
                        <nav aria-label="breadcrumb" class="mt-2">
                            <ol class="breadcrumb breadcrumb-dark mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo asset('index.php'); ?>" class="text-light">الرئيسية</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo asset('contracts.php'); ?>" class="text-light">العقود</a></li>
                                <li class="breadcrumb-item active text-light" aria-current="page">إنشاء جديد</li>
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
                        
                        <form method="POST" action="<?php echo asset('contracts/create'); ?>" id="contractForm">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="title" class="form-label">
                                        <i class="fas fa-heading me-2"></i>
                                        عنوان العقد *
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="title" name="title" required
                                           placeholder="أدخل عنوان العقد" autocomplete="off">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="type" class="form-label">
                                        <i class="fas fa-tag me-2"></i>
                                        نوع العقد *
                                    </label>
                                    <select class="form-select form-select-lg" id="type" name="type" required>
                                        <option value="">اختر نوع العقد</option>
                                        <option value="investment">عقد استثمار</option>
                                        <option value="property_investment">عقد استثمار عقاري</option>
                                        <option value="speculation">عقد مضاربة</option>
                                        <option value="partnership">عقد شراكة</option>
                                        <option value="service">عقد خدمة</option>
                                        <option value="general">عقد عام</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="client_id" class="form-label">
                                        <i class="fas fa-user-tie me-2"></i>
                                        العميل (اختياري)
                                    </label>
                                    <select class="form-select" id="client_id" name="client_id" autocomplete="name">
                                        <option value="">اختر العميل</option>
                                        <option value="1">عميل تجريبي 1</option>
                                        <option value="2">عميل تجريبي 2</option>
                                        <!-- يمكن تحميل العملاء من قاعدة البيانات -->
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="amount" class="form-label">
                                        <i class="fas fa-money-bill-wave me-2"></i>
                                        المبلغ (ريال سعودي)
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="amount" name="amount" 
                                               placeholder="0.00" min="0" step="0.01">
                                        <span class="input-group-text">ر.س</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="content" class="form-label">
                                    <i class="fas fa-file-alt me-2"></i>
                                    محتوى العقد *
                                </label>
                                <textarea class="form-control" id="content" name="content" rows="10" required
                                          placeholder="أدخل تفاصيل العقد وبنوده..." autocomplete="off"></textarea>
                                <div class="form-text">
                                    يمكنك استخدام النص العادي أو HTML لتنسيق المحتوى
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="save_draft" name="save_draft" checked>
                                    <label class="form-check-label" for="save_draft">
                                        حفظ كمسودة (يمكن تعديلها لاحقاً)
                                    </label>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="/contracts" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-arrow-right me-2"></i>
                                        إلغاء والعودة
                                    </a>
                                </div>
                                
                                <div>
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-save me-2"></i>
                                        حفظ العقد
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- نصائح مفيدة -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-lightbulb me-2"></i>
                            نصائح لكتابة عقد فعال
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>حدد الأطراف بوضوح</li>
                                    <li><i class="fas fa-check text-success me-2"></i>اذكر تواريخ البداية والانتهاء</li>
                                    <li><i class="fas fa-check text-success me-2"></i>حدد المبالغ والطرق المالية</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>اذكر الالتزامات والحقوق</li>
                                    <li><i class="fas fa-check text-success me-2"></i>حدد إجراءات فض النزاعات</li>
                                    <li><i class="fas fa-check text-success me-2"></i>راجع العقد قبل التوقيع</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('contractForm').addEventListener('submit', function(e) {
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
        });
        
        // Auto-save draft (مسودة تلقائية)
        let autoSaveTimer;
        const formInputs = document.querySelectorAll('#contractForm input, #contractForm textarea, #contractForm select');
        
        formInputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(function() {
                    // يمكن إضافة Ajax لحفظ المسودة تلقائياً
                    console.log('Auto-saving draft...'); 
                }, 30000); // حفظ كل 30 ثانية
            });
        });
    </script>
</body>
</html>