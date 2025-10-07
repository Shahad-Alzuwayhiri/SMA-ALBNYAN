<?php
require_once '../includes/auth.php';

// التحقق من صلاحية المدير
$auth->requirePermission('manage_employees');
$user = $auth->getCurrentUser();

$message = '';
$error = '';

// معالجة إضافة موظف جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_employee') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'employee';
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'جميع الحقول الأساسية مطلوبة';
    } else {
        try {
            // التحقق من عدم وجود البريد الإلكتروني مسبقاً
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            
            if ($checkStmt->fetch()) {
                $error = 'البريد الإلكتروني موجود مسبقاً';
            } else {
                // إضافة الموظف الجديد
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, phone, role, created_by, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'active')
                ");
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt->execute([$name, $email, $hashedPassword, $phone, $role, $user['id']]);
                
                $message = 'تم إضافة الموظف بنجاح';
                
                // تسجيل النشاط
                $logStmt = $pdo->prepare("
                    INSERT INTO activity_log (user_id, action, description) 
                    VALUES (?, 'add_employee', ?)
                ");
                $logStmt->execute([$user['id'], "إضافة موظف جديد: $name ($email)"]);
            }
        } catch (PDOException $e) {
            $error = 'خطأ في إضافة الموظف: ' . $e->getMessage();
        }
    }
}

// معالجة تعديل حالة الموظف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $employeeId = $_POST['employee_id'] ?? null;
    $newStatus = $_POST['new_status'] ?? null;
    
    if ($employeeId && $newStatus) {
        try {
            $updateStmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
            $updateStmt->execute([$newStatus, $employeeId]);
            
            $message = 'تم تحديث حالة الموظف بنجاح';
            
            // تسجيل النشاط
            $logStmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, description) 
                VALUES (?, 'update_employee_status', ?)
            ");
            $logStmt->execute([$user['id'], "تغيير حالة الموظف إلى: $newStatus"]);
        } catch (PDOException $e) {
            $error = 'خطأ في تحديث حالة الموظف: ' . $e->getMessage();
        }
    }
}

// جلب جميع الموظفين
try {
    $stmt = $pdo->prepare("
        SELECT u.*, 
               creator.name as created_by_name,
               COUNT(c.id) as total_contracts,
               SUM(CASE WHEN c.status = 'signed' THEN 1 ELSE 0 END) as signed_contracts,
               SUM(CASE WHEN c.status = 'signed' THEN c.amount ELSE 0 END) as total_amount
        FROM users u
        LEFT JOIN users creator ON u.created_by = creator.id
        LEFT JOIN contracts c ON u.id = c.created_by
        WHERE u.role IN ('employee', 'manager')
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $employees = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "خطأ في جلب البيانات: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الموظفين - نظام إدارة العقود</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #253355;
            --secondary-color: #77bcc3;
            --accent-color: #e8eaec;
            --text-color: #9694ac;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .employee-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
            border-left: 4px solid var(--secondary-color);
        }
        
        .employee-card:hover {
            transform: translateY(-3px);
        }
        
        .employee-avatar {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .role-manager {
            background: var(--primary-color);
            color: white;
        }
        
        .role-employee {
            background: var(--secondary-color);
            color: white;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin: 0 0.25rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .stats-mini {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .stats-mini .row > div {
            text-align: center;
            padding: 0.5rem;
        }
        
        .add-employee-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/manager_dashboard.php">
                <i class="fas fa-arrow-right me-2"></i>العودة للوحة المدير
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item">
                    <span class="navbar-text">
                        <i class="fas fa-user-tie me-2"></i><?= htmlspecialchars($user['name']) ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users me-2"></i>إدارة الموظفين</h2>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- نموذج إضافة موظف جديد -->
        <div class="add-employee-form">
            <h5 class="mb-3"><i class="fas fa-user-plus me-2"></i>إضافة موظف جديد</h5>
            
            <form method="POST">
                <input type="hidden" name="action" value="add_employee">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">الاسم الكامل</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">كلمة المرور</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">رقم الجوال</label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="05xxxxxxxx">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="role" class="form-label">الدور</label>
                        <select class="form-control" id="role" name="role">
                            <option value="employee">موظف</option>
                            <?php if ($user['role'] === 'admin'): ?>
                                <option value="manager">مدير</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>إضافة الموظف
                </button>
            </form>
        </div>
        
        <!-- قائمة الموظفين -->
        <div class="content-card">
            <div class="p-3 bg-light">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>قائمة الموظفين (<?= count($employees) ?>)</h5>
            </div>
            
            <?php if (empty($employees)): ?>
                <div class="text-center p-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لا يوجد موظفين بعد</h5>
                    <p class="text-muted">ابدأ بإضافة موظف جديد باستخدام النموذج أعلاه</p>
                </div>
            <?php else: ?>
                <div class="p-3">
                    <div class="row">
                        <?php foreach ($employees as $employee): ?>
                            <div class="col-lg-6 mb-3">
                                <div class="employee-card">
                                    <div class="d-flex align-items-start">
                                        <div class="employee-avatar me-3">
                                            <?= strtoupper(substr($employee['name'], 0, 2)) ?>
                                        </div>
                                        
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($employee['name']) ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($employee['email']) ?></small>
                                                </div>
                                                
                                                <div>
                                                    <span class="role-badge role-<?= $employee['role'] ?>">
                                                        <?= $employee['role'] === 'manager' ? 'مدير' : 'موظف' ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="status-badge status-<?= $employee['status'] ?>">
                                                    <?= $employee['status'] === 'active' ? 'نشط' : 'غير نشط' ?>
                                                </span>
                                                
                                                <div>
                                                    <?php if ($employee['phone']): ?>
                                                        <small class="text-muted">
                                                            <i class="fas fa-phone me-1"></i><?= htmlspecialchars($employee['phone']) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- إحصائيات مصغرة -->
                                            <div class="stats-mini">
                                                <div class="row">
                                                    <div class="col-4">
                                                        <div class="fw-bold text-primary"><?= $employee['total_contracts'] ?></div>
                                                        <small class="text-muted">إجمالي العقود</small>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="fw-bold text-success"><?= $employee['signed_contracts'] ?></div>
                                                        <small class="text-muted">موقعة</small>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="fw-bold text-info"><?= number_format($employee['total_amount'] ?? 0) ?></div>
                                                        <small class="text-muted">ر.س</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- أزرار الإجراءات -->
                                            <div class="mt-3">
                                                <?php if ($employee['status'] === 'active'): ?>
                                                    <button onclick="toggleEmployeeStatus(<?= $employee['id'] ?>, 'inactive')" 
                                                            class="action-btn btn-outline-warning">
                                                        <i class="fas fa-pause"></i> إيقاف
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="toggleEmployeeStatus(<?= $employee['id'] ?>, 'active')" 
                                                            class="action-btn btn-outline-success">
                                                        <i class="fas fa-play"></i> تفعيل
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button onclick="viewEmployeeDetails(<?= $employee['id'] ?>)" 
                                                        class="action-btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i> التفاصيل
                                                </button>
                                                
                                                <button onclick="resetEmployeePassword(<?= $employee['id'] ?>)" 
                                                        class="action-btn btn-outline-secondary">
                                                    <i class="fas fa-key"></i> إعادة تعيين كلمة المرور
                                                </button>
                                            </div>
                                            
                                            <?php if ($employee['created_by_name']): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        أضيف بواسطة: <?= htmlspecialchars($employee['created_by_name']) ?>
                                                        في <?= date('Y-m-d', strtotime($employee['created_at'])) ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleEmployeeStatus(employeeId, newStatus) {
            const action = newStatus === 'active' ? 'تفعيل' : 'إيقاف';
            
            if (confirm(`هل أنت متأكد من ${action} هذا الموظف؟`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="employee_id" value="${employeeId}">
                    <input type="hidden" name="new_status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewEmployeeDetails(employeeId) {
            // يمكن تطوير هذه الوظيفة لعرض تفاصيل أكثر
            alert('عرض تفاصيل الموظف - ستتم إضافة هذه الميزة قريباً');
        }
        
        function resetEmployeePassword(employeeId) {
            if (confirm('هل أنت متأكد من إعادة تعيين كلمة المرور؟ سيتم إرسال كلمة مرور جديدة للموظف.')) {
                // يمكن تطوير هذه الوظيفة لإعادة تعيين كلمة المرور
                alert('إعادة تعيين كلمة المرور - ستتم إضافة هذه الميزة قريباً');
            }
        }
    </script>
</body>
</html>