<?php
require_once '../includes/auth.php';

$auth->requireAuth();
$user = $auth->getCurrentUser();

$message = '';
$error = '';

// معالجة تحديث الملف الشخصي
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($name)) {
        $error = 'الاسم مطلوب';
    } else {
        try {
            // تحديث الاسم والهاتف
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $user['id']]);
            
            // تحديث كلمة المرور إذا تم إدخالها
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error = 'كلمة المرور الحالية مطلوبة';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'كلمة المرور الجديدة وتأكيدها غير متطابقتين';
                } elseif (strlen($new_password) < 6) {
                    $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
                } else {
                    // التحقق من كلمة المرور الحالية
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $userData = $stmt->fetch();
                    
                    if (!password_verify($current_password, $userData['password'])) {
                        $error = 'كلمة المرور الحالية غير صحيحة';
                    } else {
                        // تحديث كلمة المرور
                        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashedPassword, $user['id']]);
                        $message = 'تم تحديث الملف الشخصي وكلمة المرور بنجاح';
                    }
                }
            } else {
                $message = 'تم تحديث الملف الشخصي بنجاح';
            }
            
            // تحديث الجلسة
            if (empty($error)) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_phone'] = $phone;
            }
            
        } catch (PDOException $e) {
            $error = 'خطأ في التحديث: ' . $e->getMessage();
        }
    }
}

// جلب بيانات المستخدم الحالية
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();
} catch (PDOException $e) {
    $error = 'خطأ في جلب البيانات: ' . $e->getMessage();
    $userData = $user;
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - نظام إدارة العقود</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/static/css/modern-theme.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-user text-primary"></i> الملف الشخصي</h2>
                    <a href="<?php echo $user['role'] === 'employee' ? '/employee_dashboard.php' : '/manager_dashboard.php'; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-edit"></i> تحديث البيانات الشخصية</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">الاسم</label>
                                        <input type="text" name="name" class="form-control" 
                                               value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">البريد الإلكتروني</label>
                                        <input type="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($userData['email']); ?>" disabled>
                                        <small class="text-muted">لا يمكن تغيير البريد الإلكتروني</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">رقم الهاتف</label>
                                        <input type="tel" name="phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">الدور</label>
                                        <input type="text" class="form-control" 
                                               value="<?php 
                                               $roles = ['admin' => 'مشرف', 'manager' => 'مدير', 'employee' => 'موظف'];
                                               echo $roles[$userData['role']] ?? $userData['role']; 
                                               ?>" disabled>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h6><i class="fas fa-key"></i> تغيير كلمة المرور (اختياري)</h6>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="form-label">كلمة المرور الحالية</label>
                                        <input type="password" name="current_password" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="form-label">كلمة المرور الجديدة</label>
                                        <input type="password" name="new_password" class="form-control" minlength="6">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                                        <input type="password" name="confirm_password" class="form-control" minlength="6">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ التغييرات
                                </button>
                                <a href="<?php echo $user['role'] === 'employee' ? '/employee_dashboard.php' : '/manager_dashboard.php'; ?>" 
                                   class="btn btn-secondary">
                                    <i class="fas fa-times"></i> إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- معلومات إضافية -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> معلومات الحساب</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>تاريخ الإنضمام:</strong> <?php echo date('Y-m-d', strtotime($userData['created_at'])); ?></p>
                                <p><strong>حالة الحساب:</strong> 
                                    <span class="badge badge-<?php echo $userData['status'] === 'active' ? 'approved' : 'rejected'; ?>">
                                        <?php echo $userData['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>آخر تحديث:</strong> <?php echo date('Y-m-d H:i', strtotime($userData['updated_at'])); ?></p>
                                <p><strong>معرف المستخدم:</strong> #<?php echo str_pad($userData['id'], 4, '0', STR_PAD_LEFT); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // التحقق من تطابق كلمات المرور
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('كلمة المرور الجديدة وتأكيدها غير متطابقتين');
            }
        });
    </script>
</body>
</html>