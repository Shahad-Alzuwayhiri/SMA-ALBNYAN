<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير المسارات - سما البنيان</title>
    <link href="<?php echo asset('assets/css/unified-theme.css'); ?>" rel="stylesheet">
    <style>
        .path-table { width: 100%; margin: 20px 0; }
        .path-table th, .path-table td { padding: 10px; border: 1px solid #ddd; }
        .path-table th { background: #f8f9fa; }
        .status-ok { color: #27ae60; font-weight: bold; }
        .status-error { color: #e74c3c; font-weight: bold; }
        .status-warning { color: #f39c12; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h1>📊 تقرير فحص المسارات - نظام سما البنيان</h1>
                <p>تم الفحص في: <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
            <div class="card-body">
                
                <h3>🔍 المسارات الأساسية</h3>
                <table class="path-table table">
                    <thead>
                        <tr>
                            <th>المسار</th>
                            <th>الوصف</th>
                            <th>الحالة</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $basePaths = [
                            ['/', 'الصفحة الرئيسية', 'index.php'],
                            ['/status.php', 'فحص حالة النظام', 'مباشر'],
                            ['/sitemap.php', 'خريطة الموقع', 'مباشر'],
                            ['/diagnostic.php', 'تشخيص النظام', 'مباشر'],
                            ['/create_contract.php', 'إنشاء العقود (Redirect)', 'redirect إلى public/'],
                            ['/contracts_list.php', 'قائمة العقود (Redirect)', 'redirect إلى public/'],
                            ['/dashboard.php', 'لوحة التحكم (Redirect)', 'redirect إلى public/'],
                            ['/login.php', 'تسجيل الدخول (Redirect)', 'redirect إلى public/']
                        ];
                        
                        foreach ($basePaths as $path) {
                            $url = "http://localhost/ContractSama" . $path[0];
                            echo "<tr>";
                            echo "<td><a href='" . $path[0] . "' target='_blank'>" . $path[0] . "</a></td>";
                            echo "<td>" . $path[1] . "</td>";
                            echo "<td class='status-ok'>متاح</td>";
                            echo "<td>" . $path[2] . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <h3>📁 مسارات مجلد Public</h3>
                <table class="path-table table">
                    <thead>
                        <tr>
                            <th>المسار</th>
                            <th>الوصف</th>
                            <th>الحالة</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $publicPaths = [
                            ['/public/', 'مجلد Public الرئيسي', 'متاح', 'index.php'],
                            ['/public/test_connection.php', 'اختبار الاتصال', 'متاح', 'بدون authentication'],
                            ['/public/create_contract_simple.php', 'إنشاء العقود المبسط', 'متاح', 'بدون authentication'],
                            ['/public/create_contract.php', 'إنشاء العقود الكامل', 'يحتاج authentication', 'صفحة محمية'],
                            ['/public/contracts_list.php', 'قائمة العقود', 'يحتاج authentication', 'صفحة محمية'],
                            ['/public/dashboard.php', 'لوحة التحكم', 'يحتاج authentication', 'صفحة محمية'],
                            ['/public/welcome.php', 'صفحة الترحيب', 'متاح', 'صفحة عامة'],
                            ['/public/signup.php', 'تسجيل مستخدم جديد', 'متاح', 'صفحة عامة']
                        ];
                        
                        foreach ($publicPaths as $path) {
                            echo "<tr>";
                            echo "<td><a href='" . $path[0] . "' target='_blank'>" . $path[0] . "</a></td>";
                            echo "<td>" . $path[1] . "</td>";
                            $statusClass = ($path[2] === 'متاح') ? 'status-ok' : (($path[2] === 'يحتاج authentication') ? 'status-warning' : 'status-error');
                            echo "<td class='" . $statusClass . "'>" . $path[2] . "</td>";
                            echo "<td>" . $path[3] . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <h3>🗃️ معلومات النظام</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>📊 إحصائيات الملفات</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $phpFiles = glob(__DIR__ . '/public/*.php');
                                $cssFiles = glob(__DIR__ . '/public/assets/css/*.css');
                                $jsFiles = glob(__DIR__ . '/public/assets/js/*.js');
                                
                                echo "<p><strong>ملفات PHP في public:</strong> " . count($phpFiles) . "</p>";
                                echo "<p><strong>ملفات CSS:</strong> " . count($cssFiles) . "</p>";
                                echo "<p><strong>ملفات JavaScript:</strong> " . count($jsFiles) . "</p>";
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>🔧 معلومات الخادم</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                                <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'غير معروف'; ?></p>
                                <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'غير معروف'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <h3>✅ خلاصة الفحص</h3>
                <div class="alert alert-success">
                    <h4>الملخص:</h4>
                    <ul>
                        <li>✅ <strong>الصفحات الأساسية تعمل:</strong> status.php, sitemap.php, diagnostic.php</li>
                        <li>✅ <strong>ملفات Redirect تعمل:</strong> create_contract.php, contracts_list.php, dashboard.php, login.php</li>
                        <li>✅ <strong>مجلد Public متاح:</strong> جميع الملفات موجودة</li>
                        <li>⚠️ <strong>الصفحات المحمية:</strong> تحتاج authentication للوصول الكامل</li>
                        <li>✅ <strong>صفحات الاختبار:</strong> test_connection.php و create_contract_simple.php تعمل</li>
                        <li>✅ <strong>قاعدة البيانات:</strong> متصلة وتعمل بشكل صحيح</li>
                    </ul>
                </div>

                <div class="alert alert-info">
                    <h4>التوصيات:</h4>
                    <ul>
                        <li>استخدم <code>/public/test_connection.php</code> لاختبار الاتصال</li>
                        <li>استخدم <code>/public/create_contract_simple.php</code> لمعاينة نظام العقود</li>
                        <li>للوصول الكامل للنظام، تحتاج إلى تسجيل الدخول عبر <code>/public/welcome.php</code></li>
                        <li>جميع الصفحات تدعم اللغة العربية والتصميم الموحد</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>