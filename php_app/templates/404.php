<div class="d-flex align-items-center justify-content-center vh-100">
    <div class="text-center">
        <div class="error mx-auto" style="width: 200px;">
            <h1 class="display-1" style="color: #1f2937; font-size: 8rem; font-weight: bold;">404</h1>
        </div>
        <p class="fs-3"><span class="text-danger">عذراً!</span> الصفحة غير موجودة.</p>
        <p class="lead">
            الصفحة التي تبحث عنها غير متوفرة.
        </p>
        <a href="/" class="btn btn-primary">العودة للرئيسية</a>
        
        <?php if (isAuthenticated()): ?>
            <div class="mt-4">
                <?php if (hasRole('manager')): ?>
                    <a href="/manager-dashboard" class="btn btn-outline-secondary">لوحة المدير</a>
                <?php else: ?>
                    <a href="/employee-dashboard" class="btn btn-outline-secondary">لوحة الموظف</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.error {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.btn {
    border-radius: 25px;
    padding: 10px 30px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
</style>