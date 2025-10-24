// refactor(welcome.php): render via master_layout + normalize links
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$user = null;
$isLoggedIn = false;
try {
    $user = $auth->getCurrentUser();
    $isLoggedIn = true;
} catch (Exception $e) {
    $isLoggedIn = false;
}

$title = 'نظام إدارة العقود - سما البنيان';
$is_auth_page = false;
$show_sidebar = false;
$additional_head = $additional_head ?? '';
$additional_scripts = '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
ob_start();
?>
<section class="hero-section py-5">
    <div class="container text-center">
        <h1 class="display-4 mb-3">نظام إدارة العقود المتطور</h1>
        <p class="lead mb-4">حلول متقدمة لإدارة العقود والاستثمارات</p>
        <?php if ($isLoggedIn): ?>
            <a href="<?= asset('employee_dashboard.php') ?>" class="btn btn-primary">الذهاب إلى النظام</a>
        <?php else: ?>
            <a href="<?= asset('login.php') ?>" class="btn btn-outline-primary">تسجيل الدخول</a>
        <?php endif; ?>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold text-primary">مميزات النظام</h2>
            <p class="lead text-muted">حلول شاملة لإدارة العقود والاستثمارات</p>
        </div>
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <h4>إدارة العقود</h4>
                    <p>إنشاء وإدارة عقود الاستثمار النقدي والعقاري بسهولة ومرونة عالية مع نظام موافقات متقدم</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h4>عقود الاستثمار</h4>
                    <p>قوالب متخصصة لعقود الاستثمار النقدي والعقاري مع حساب الأرباح والعوائد التلقائية</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <h4>إدارة الملفات</h4>
                    <p>رفع وتخزين ملفات PDF مع إمكانية العرض والتحميل المباشر وحفظ آمن في قاعدة البيانات</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h4>إدارة المستخدمين</h4>
                    <p>نظام صلاحيات متقدم للموظفين والمديرين مع إمكانية التحكم في الوصول والمراجعة</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h4>التقارير والإحصائيات</h4>
                    <p>تقارير شاملة وإحصائيات مفصلة عن العقود والأداء مع رسوم بيانية تفاعلية</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h4>تصميم متجاوب</h4>
                    <p>واجهة متجاوبة تعمل على جميع الأجهزة والشاشات مع تجربة مستخدم محسّنة</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<?php if (!$isLoggedIn): ?>
<section class="cta-section">
    <div class="container">
        <h2 class="display-5 fw-bold mb-4">ابدأ استخدام النظام الآن</h2>
        <p class="lead mb-4">انضم إلى شركة سما البنيان واستفد من أحدث أنظمة إدارة العقود</p>
    <a href="<?= asset('login.php') ?>" class="btn btn-light btn-lg">
            <i class="fas fa-sign-in-alt me-2"></i>
            تسجيل الدخول
        </a>
    </div>
</section>
<?php endif; ?>

<!-- Footer -->
<footer class="bg-dark text-white py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">
                    &copy; <?= date('Y') ?> شركة سما البنيان للتطوير والاستثمار. جميع الحقوق محفوظة.
                </p>
            </div>
            <div class="col-md-6 text-end">
                <p class="mb-0">
                    <i class="fas fa-code me-2"></i>
                    نظام إدارة العقود الإلكتروني
                </p>
            </div>
        </div>
    </div>
</footer>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layouts/master_layout.php';