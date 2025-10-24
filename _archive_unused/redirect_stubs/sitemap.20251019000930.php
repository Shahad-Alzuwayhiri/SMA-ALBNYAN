<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دليل الصفحات - سما البنيان</title>
    <link href="<?php echo asset('assets/css/unified-theme.css'); ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h1 class="mb-0">
                            <i class="fas fa-building"></i>
                            نظام سما البنيان لإدارة العقود
                        </h1>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- العقود -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5><i class="fas fa-file-contract"></i> إدارة العقود</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <a href="create_contract.php" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> إنشاء عقد جديد
                                            </a>
                                            <a href="contracts_list.php" class="btn btn-secondary">
                                                <i class="fas fa-list"></i> قائمة العقود
                                            </a>
                                            <a href="<?php echo asset('contract_view.php'); ?>" class="btn btn-info">
                                                <i class="fas fa-eye"></i> عرض العقود
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- الإدارة -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5><i class="fas fa-users-cog"></i> إدارة النظام</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <a href="dashboard.php" class="btn btn-success">
                                                <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                                            </a>
                                            <a href="<?php echo asset('manager_dashboard.php'); ?>" class="btn btn-warning">
                                                <i class="fas fa-user-tie"></i> لوحة المدير
                                            </a>
                                            <a href="<?php echo asset('employee_dashboard.php'); ?>" class="btn btn-info">
                                                <i class="fas fa-user"></i> لوحة الموظف
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- التقارير والأدوات -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5><i class="fas fa-chart-bar"></i> التقارير والأدوات</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <a href="<?php echo asset('reports.php'); ?>" class="btn btn-primary">
                                                <i class="fas fa-chart-line"></i> التقارير
                                            </a>
                                            <a href="diagnostic.php" class="btn btn-warning">
                                                <i class="fas fa-tools"></i> تشخيص النظام
                                            </a>
                                            <a href="test.php" class="btn btn-info">
                                                <i class="fas fa-flask"></i> اختبار النظام
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- المستخدمين -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5><i class="fas fa-user-friends"></i> إدارة المستخدمين</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <a href="<?php echo asset('signup.php'); ?>" class="btn btn-success">
                                                <i class="fas fa-user-plus"></i> تسجيل مستخدم جديد
                                            </a>
                                            <a href="<?php echo asset('welcome.php'); ?>" class="btn btn-primary">
                                                <i class="fas fa-home"></i> صفحة الترحيب
                                            </a>
                                            <a href="<?php echo asset('profile.php'); ?>" class="btn btn-secondary">
                                                <i class="fas fa-user-edit"></i> الملف الشخصي
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-4">
                            <h6><i class="fas fa-info-circle"></i> معلومات مهمة:</h6>
                            <ul class="mb-0">
                                <li>النظام يدعم نوعين من العقود: <strong>العقود النقدية</strong> (40% ربح) و <strong>العقود العقارية</strong> (30% ربح)</li>
                                <li>جميع الصفحات تستخدم تصميم موحد بألوان شركة سما البنيان (الكحلي والأزرق)</li>
                                <li>النظام يدعم اللغة العربية بالكامل مع دعم RTL</li>
                                <li>قاعدة البيانات SQLite متوافقة مع XAMPP والاستضافة المشتركة</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>