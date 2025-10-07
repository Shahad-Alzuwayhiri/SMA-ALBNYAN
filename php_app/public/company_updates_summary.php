<?php
require_once '../includes/auth.php';
$auth->requireAuth();
$user = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملخص تحديثات الشركة - سما البنيان للتطوير والاستثمار العقاري</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/static/css/sma-company-theme.css" rel="stylesheet">
</head>
<body>
    <div class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-building me-3"></i>سما البنيان للتطوير والاستثمار العقاري</h1>
                    <p class="mb-0">ملخص التحديثات والمتطلبات المطبقة حديثاً</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="/manager_dashboard.php" class="btn btn-light">
                        <i class="fas fa-arrow-right me-2"></i>العودة للوحة التحكم
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- ملخص التحديثات -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3><i class="fas fa-check-circle"></i> تم تطبيق جميع متطلبات الشركة بنجاح</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h5><i class="fas fa-thumbs-up"></i> جميع المتطلبات المطلوبة تم تنفيذها بنجاح!</h5>
                            <p class="mb-0">تم تحديث النظام ليتوافق مع جميع سياسات ومتطلبات سما البنيان للتطوير والاستثمار العقاري.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- المتطلبات المنفذة -->
        <div class="row">
            <!-- تعديل العقود -->
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h4>تعديل العقود</h4>
                    <div class="alert alert-info">
                        <strong>✅ تم التطبيق:</strong>
                        <ul class="mt-2 mb-0">
                            <li>مدة التعديل: 6 أشهر فقط (ثابتة)</li>
                            <li>حقل اختيار التعديل في النموذج</li>
                            <li>ربط بالعقد الأصلي</li>
                            <li>التحقق من المتطلبات تلقائياً</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- الحد الأدنى للمبلغ -->
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-money-check-alt"></i>
                    </div>
                    <h4>الحد الأدنى للمبلغ</h4>
                    <div class="alert alert-info">
                        <strong>✅ تم التطبيق:</strong>
                        <ul class="mt-2 mb-0">
                            <li>50,000 ريال كحد أدنى</li>
                            <li>تطبيق على جميع العقود</li>
                            <li>تطبيق على تعديل العقود</li>
                            <li>رسالة تنبيه في النموذج</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- صافي الربح -->
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4>حقل صافي الربح</h4>
                    <div class="alert alert-info">
                        <strong>✅ تم التطبيق:</strong>
                        <ul class="mt-2 mb-0">
                            <li>حقل منفصل لصافي الربح</li>
                            <li>حساب تلقائي بناءً على المبلغ</li>
                            <li>تصميم مميز بألوان الشركة</li>
                            <li>حفظ في قاعدة البيانات</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- ألوان الشركة -->
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h4>ألوان الشركة</h4>
                    <div class="alert alert-info">
                        <strong>✅ تم التطبيق:</strong>
                        <ul class="mt-2 mb-0">
                            <li>الأزرق الداكن (#1B3B5A)</li>
                            <li>الأزرق المتوسط (#2E5A7A)</li>
                            <li>الأزرق الفاتح (#5BB3C7)</li>
                            <li>تطبيق شامل على جميع الصفحات</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- تحميل PDF -->
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <h4>تحميل PDF</h4>
                    <div class="alert alert-info">
                        <strong>✅ تم التطبيق:</strong>
                        <ul class="mt-2 mb-0">
                            <li>تحميل العقود بصيغة PDF</li>
                            <li>تصميم بألوان الشركة</li>
                            <li>علامة مائية للشركة</li>
                            <li>معلومات شاملة ومنظمة</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- قاعدة البيانات -->
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h4>قاعدة البيانات</h4>
                    <div class="alert alert-info">
                        <strong>✅ تم التطبيق:</strong>
                        <ul class="mt-2 mb-0">
                            <li>حقول جديدة للتعديلات</li>
                            <li>حقل صافي الربح</li>
                            <li>ربط الإشعارات بالعقود</li>
                            <li>سجل الأنشطة محدث</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- عرض الألوان المطبقة -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-eye"></i> ألوان الشركة المطبقة</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center p-3 mb-3" style="background-color: #1B3B5A; color: white; border-radius: 10px;">
                                    <h6>الأزرق الأساسي</h6>
                                    <small>#1B3B5A</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 mb-3" style="background-color: #2E5A7A; color: white; border-radius: 10px;">
                                    <h6>الأزرق الثانوي</h6>
                                    <small>#2E5A7A</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 mb-3" style="background-color: #5BB3C7; color: white; border-radius: 10px;">
                                    <h6>الأزرق الفاتح</h6>
                                    <small>#5BB3C7</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 mb-3" style="background-color: #E8F4F8; color: #1B3B5A; border-radius: 10px; border: 1px solid #5BB3C7;">
                                    <h6>الأزرق الفاتح جداً</h6>
                                    <small>#E8F4F8</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- روابط سريعة -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-link"></i> روابط سريعة للاختبار</h4>
                    </div>
                    <div class="card-body text-center">
                        <a href="/create_contract.php" class="btn btn-primary btn-lg me-2 mb-2">
                            <i class="fas fa-plus"></i> إنشاء عقد جديد
                        </a>
                        <a href="/contracts_list.php" class="btn btn-info btn-lg me-2 mb-2">
                            <i class="fas fa-list"></i> قائمة العقود
                        </a>
                        <a href="/download_contract.php?id=1" class="btn btn-success btn-lg me-2 mb-2">
                            <i class="fas fa-download"></i> تجربة تحميل PDF
                        </a>
                        <a href="/reports.php" class="btn btn-warning btn-lg mb-2">
                            <i class="fas fa-chart-bar"></i> التقارير
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Company Watermark -->
    <div class="company-watermark">
        <svg width="200" height="120" viewBox="0 0 400 240" xmlns="http://www.w3.org/2000/svg">
            <rect x="50" y="50" width="300" height="140" fill="none" stroke="#1B3B5A" stroke-width="2" opacity="0.2"/>
            <polygon points="200,60 180,120 220,120" fill="#5BB3C7" opacity="0.2"/>
            <rect x="170" y="120" width="60" height="60" fill="#1B3B5A" opacity="0.2"/>
            <text x="200" y="200" text-anchor="middle" font-family="Arial" font-size="16" fill="#1B3B5A" opacity="0.2">SMA ALBNYAN</text>
        </svg>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>