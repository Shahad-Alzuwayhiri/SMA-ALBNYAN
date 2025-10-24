<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الموظفين - نظام إدارة العقود</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
</head>
<body>
    <?php include __DIR__ . '/../../includes/navigation.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="main-content">
                    <!-- Header -->
                    <div class="page-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h1 class="page-title">
                                    <i class="fas fa-users me-2"></i>
                                    إدارة الموظفين
                                </h1>
                                <p class="page-subtitle">إدارة ومتابعة جميع الموظفين في النظام</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                                    <i class="fas fa-plus me-2"></i>
                                    إضافة موظف جديد
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?= $stats['total'] ?></h3>
                                    <p>إجمالي الموظفين</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?= $stats['active'] ?></h3>
                                    <p>نشط</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?= $stats['inactive'] ?></h3>
                                    <p>غير نشط</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employees Table -->
                    <div class="content-card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-list me-2"></i>
                                قائمة الموظفين
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($employees)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-users-slash"></i>
                                    <h4>لا يوجد موظفين</h4>
                                    <p>لم يتم العثور على أي موظفين في النظام</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>الاسم</th>
                                                <th>البريد الإلكتروني</th>
                                                <th>الهاتف</th>
                                                <th>الدور</th>
                                                <th>الحالة</th>
                                                <th>العقود</th>
                                                <th>النشاط</th>
                                                <th>تاريخ الإنشاء</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($employees as $employee): ?>
                                                <tr>
                                                    <td>
                                                        <div class="user-info">
                                                            <div class="user-avatar">
                                                                <?= strtoupper(substr($employee['name'], 0, 1)) ?>
                                                            </div>
                                                            <span><?= htmlspecialchars($employee['name']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($employee['email']) ?></td>
                                                    <td><?= htmlspecialchars($employee['phone'] ?? 'غير محدد') ?></td>
                                                    <td>
                                                        <?php
                                                        $roleText = [
                                                            'admin' => 'مدير النظام',
                                                            'manager' => 'مدير',
                                                            'employee' => 'موظف'
                                                        ];
                                                        $roleClass = [
                                                            'admin' => 'badge bg-danger',
                                                            'manager' => 'badge bg-warning',
                                                            'employee' => 'badge bg-info'
                                                        ];
                                                        ?>
                                                        <span class="<?= $roleClass[$employee['role']] ?? 'badge bg-secondary' ?>">
                                                            <?= $roleText[$employee['role']] ?? $employee['role'] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $employee['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                                            <?= $employee['status'] === 'active' ? 'نشط' : 'غير نشط' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary"><?= $employee['contract_count'] ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?= $employee['activity_count'] ?></span>
                                                    </td>
                                                    <td><?= date('d/m/Y', strtotime($employee['created_at'])) ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-outline-primary" 
                                                                    onclick="editEmployee(<?= $employee['id'] ?>)"
                                                                    data-bs-toggle="tooltip" title="تعديل">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($user['role'] === 'admin'): ?>
                                                                <button class="btn btn-sm btn-outline-danger" 
                                                                        onclick="deleteEmployee(<?= $employee['id'] ?>)"
                                                                        data-bs-toggle="tooltip" title="حذف">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة موظف جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="/manage_employees/create">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">الاسم</label>
                            <input type="text" class="form-control" name="name" autocomplete="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" name="email" autocomplete="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الهاتف</label>
                            <input type="text" class="form-control" name="phone" autocomplete="tel">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الدور</label>
                            <select class="form-control" name="role" autocomplete="organization-title" required>
                                <option value="employee">موظف</option>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <option value="manager">مدير</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور</label>
                            <input type="password" class="form-control" name="password" autocomplete="new-password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إضافة الموظف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h4 {
            margin-bottom: 10px;
            color: #495057;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
        }
        
        .btn-group .btn {
            border-radius: 4px;
            margin-left: 2px;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEmployee(id) {
            // إعادة توجيه لصفحة التعديل
            window.location.href = '/manage_employees/edit/' + id;
        }
        
        function deleteEmployee(id) {
            if (confirm('هل أنت متأكد من حذف هذا الموظف؟')) {
                // إرسال طلب حذف
                window.location.href = '/manage_employees/delete/' + id;
            }
        }
        
        // تفعيل tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>