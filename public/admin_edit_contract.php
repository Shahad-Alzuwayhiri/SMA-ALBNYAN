<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Contract.php';
require_once __DIR__ . '/../models/Notification.php';

$auth->requireAuth();
$user = $auth->getCurrentUser();

// التحقق من صلاحيات المدير
if ($user['role'] !== 'manager' && $user['role'] !== 'admin') {
    header('Location: /employee_dashboard.php');
    exit;
}

$contract_id = $_GET['id'] ?? null;
if (!$contract_id) {
    header('Location: /manager_dashboard.php');
    exit;
}

$contractModel = new Contract($pdo);
$notificationModel = new Notification($pdo);

try {
    // جلب بيانات العقد
    $contract = $contractModel->findById($contract_id);
    
    if (!$contract) {
        $_SESSION['error'] = 'العقد غير موجود';
        header('Location: /manager_dashboard.php');
        exit;
    }
    
    // جلب تاريخ العقد وسجل التعديلات
    $stmt = $pdo->prepare("
        SELECT * FROM contract_history 
        WHERE contract_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$contract_id]);
    $contractHistory = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'خطأ في جلب بيانات العقد: ' . $e->getMessage();
    header('Location: /manager_dashboard.php');
    exit;
}

// معالجة تحديث العقد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_contract') {
        $client_name = trim($_POST['client_name'] ?? '');
        $second_party = trim($_POST['second_party'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $terms = trim($_POST['terms'] ?? '');
        $status = $_POST['status'] ?? $contract['status'];
        $manager_notes = trim($_POST['manager_notes'] ?? '');
        
        // التحقق من الأحقول المطلوبة
        if (empty($client_name) || empty($second_party) || $amount <= 0 || empty($start_date) || empty($end_date)) {
            $error = 'جميع الحقول المطلوبة يجب ملؤها';
        } else {
            try {
                // تحديث العقد
                $stmt = $pdo->prepare("
                    UPDATE contracts SET 
                        client_name = ?, 
                        second_party_name = ?,
                        contract_amount = ?, 
                        start_date = ?, 
                        end_date = ?, 
                        description = ?,
                        terms_conditions = ?,
                        status = ?,
                        manager_notes = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $client_name, $second_party, $amount, $start_date, 
                    $end_date, $description, $terms, $status, $manager_notes, $contract_id
                ]);
                
                // إضافة سجل في تاريخ العقد
                $stmt = $pdo->prepare("
                    INSERT INTO contract_history (contract_id, action, performed_by, notes, created_at)
                    VALUES (?, 'manager_edit', ?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$contract_id, $user['id'], 'تعديل العقد بواسطة المدير: ' . $user['name']]);
                
                // إرسال إشعار للموظف المسؤول عن العقد
                if ($contract['created_by'] !== $user['id']) {
                    $notificationModel->create([
                        'user_id' => $contract['created_by'],
                        'contract_id' => $contract_id,
                        'type' => 'contract_update',
                        'title' => 'تم تعديل العقد',
                        'message' => "تم تعديل العقد {$contract['contract_number']} بواسطة المدير"
                    ]);
                }
                
                $_SESSION['success'] = 'تم تحديث العقد بنجاح';
                
                // إعادة تحميل بيانات العقد المحدثة
                $contract = $contractModel->findById($contract_id);
                
            } catch (PDOException $e) {
                $error = 'خطأ في تحديث العقد: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'return_for_revision') {
        $revision_notes = trim($_POST['revision_notes'] ?? '');
        
        if (empty($revision_notes)) {
            $error = 'يجب إدخال ملاحظات المراجعة';
        } else {
            try {
                // تغيير حالة العقد إلى "يحتاج مراجعة"
                $stmt = $pdo->prepare("
                    UPDATE contracts SET 
                        status = 'needs_revision', 
                        manager_notes = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$revision_notes, $contract_id]);
                
                // إضافة سجل في تاريخ العقد
                $stmt = $pdo->prepare("
                    INSERT INTO contract_history (contract_id, action, performed_by, notes, created_at)
                    VALUES (?, 'return_for_revision', ?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$contract_id, $user['id'], 'إرجاع للمراجعة: ' . $revision_notes]);
                
                // إرسال إشعار للموظف
                $notificationModel->create([
                    'user_id' => $contract['created_by'],
                    'contract_id' => $contract_id,
                    'type' => 'contract_reject',
                    'title' => 'العقد يحتاج مراجعة',
                    'message' => "العقد {$contract['contract_number']} تم إرجاعه للمراجعة. الملاحظات: {$revision_notes}"
                ]);
                
                $_SESSION['success'] = 'تم إرجاع العقد للمراجعة بنجاح';
                header('Location: /manager_dashboard.php');
                exit;
                
            } catch (PDOException $e) {
                $error = 'خطأ في إرجاع العقد للمراجعة: ' . $e->getMessage();
            }
        }
    }
}

$currentPage = 'contracts';
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحرير العقد - نظام إدارة العقود</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/modern-theme.css" rel="stylesheet">
    <style>
        .contract-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--secondary-color);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .status-draft { background: #e3f2fd; color: #1565c0; }
        .status-pending { background: #fff3e0; color: #f57c00; }
        .status-approved { background: #e8f5e8; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }
        .status-needs_revision { background: #fce4ec; color: #ad1457; }
        
        .history-timeline {
            position: relative;
            padding-right: 2rem;
        }
        
        .history-timeline::before {
            content: '';
            position: absolute;
            right: 1rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--secondary-color);
        }
        
        .history-item {
            position: relative;
            padding: 1rem 0;
            margin-bottom: 1rem;
        }
        
        .history-item::before {
            content: '';
            position: absolute;
            right: 0.75rem;
            top: 1.5rem;
            width: 0.5rem;
            height: 0.5rem;
            background: var(--primary-color);
            border-radius: 50%;
            z-index: 1;
        }
        
        .history-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-right: 2rem;
        }
        
        .action-buttons {
            position: sticky;
            bottom: 2rem;
            z-index: 10;
        }
        
        .btn-save {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .btn-return {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-return:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
            color: white;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(119, 188, 195, 0.25);
        }
        
        .alert-enhanced {
            border: none;
            border-radius: 15px;
            padding: 1rem 1.5rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <?php include __DIR__ . '/../templates/partials/_topnav.php'; ?>
    <?php include __DIR__ . '/../templates/partials/_sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="contract-header text-center">
                <div class="container">
                    <h1><i class="fas fa-edit me-3"></i>تحرير العقد الإداري</h1>
                    <p class="lead mb-0">إدارة وتعديل تفاصيل العقد</p>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-enhanced alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-enhanced alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <!-- معلومات العقد الأساسية -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-file-contract me-2"></i>معلومات العقد الأساسية
                        </h3>
                        
                        <form method="POST" id="contractForm">
                            <input type="hidden" name="action" value="update_contract">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">رقم العقد</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($contract['contract_number']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الحالة الحالية</label>
                                    <div>
                                        <span class="status-badge status-<?= $contract['status'] ?>">
                                            <?php
                                            $statusLabels = [
                                                'draft' => 'مسودة',
                                                'pending' => 'في الانتظار',
                                                'approved' => 'معتمد',
                                                'rejected' => 'مرفوض',
                                                'needs_revision' => 'يحتاج مراجعة'
                                            ];
                                            echo $statusLabels[$contract['status']] ?? $contract['status'];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">اسم العميل *</label>
                                    <input type="text" name="client_name" class="form-control" 
                                           value="<?= htmlspecialchars($contract['client_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الطرف الثاني *</label>
                                    <input type="text" name="second_party" class="form-control" 
                                           value="<?= htmlspecialchars($contract['second_party_name'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">قيمة العقد *</label>
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0" 
                                           value="<?= htmlspecialchars($contract['contract_amount'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">تاريخ البداية *</label>
                                    <input type="date" name="start_date" class="form-control" 
                                           value="<?= htmlspecialchars($contract['start_date'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">تاريخ النهاية *</label>
                                    <input type="date" name="end_date" class="form-control" 
                                           value="<?= htmlspecialchars($contract['end_date'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">وصف العقد</label>
                                <textarea name="description" class="form-control" rows="4" 
                                          placeholder="وصف مفصل للعقد وطبيعة العمل..."><?= htmlspecialchars($contract['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">الشروط والأحكام</label>
                                <textarea name="terms" class="form-control" rows="6" 
                                          placeholder="الشروط والأحكام الخاصة بالعقد..."><?= htmlspecialchars($contract['terms_conditions'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">تحديث الحالة</label>
                                    <select name="status" class="form-select">
                                        <option value="draft" <?= $contract['status'] === 'draft' ? 'selected' : '' ?>>مسودة</option>
                                        <option value="pending" <?= $contract['status'] === 'pending' ? 'selected' : '' ?>>في الانتظار</option>
                                        <option value="approved" <?= $contract['status'] === 'approved' ? 'selected' : '' ?>>معتمد</option>
                                        <option value="rejected" <?= $contract['status'] === 'rejected' ? 'selected' : '' ?>>مرفوض</option>
                                        <option value="needs_revision" <?= $contract['status'] === 'needs_revision' ? 'selected' : '' ?>>يحتاج مراجعة</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">ملاحظات المدير</label>
                                <textarea name="manager_notes" class="form-control" rows="3" 
                                          placeholder="ملاحظات إضافية من المدير..."><?= htmlspecialchars($contract['manager_notes'] ?? '') ?></textarea>
                            </div>
                            
                            <!-- أزرار الإجراءات -->
                            <div class="action-buttons d-flex gap-3 justify-content-center">
                                <button type="submit" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i>حفظ التعديلات
                                </button>
                                
                                <button type="button" class="btn btn-return" data-bs-toggle="modal" data-bs-target="#returnModal">
                                    <i class="fas fa-undo me-2"></i>إرجاع للمراجعة
                                </button>
                                
                                <a href="/manager_dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>العودة
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- تاريخ العقد -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-history me-2"></i>تاريخ العقد
                        </h3>
                        
                        <div class="history-timeline">
                            <?php if (empty($contractHistory)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                    <p>لا يوجد تاريخ متاح للعقد</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($contractHistory as $history): ?>
                                    <div class="history-item">
                                        <div class="history-content">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <strong>
                                                    <?php
                                                    $actionLabels = [
                                                        'created' => 'إنشاء العقد',
                                                        'updated' => 'تحديث العقد',
                                                        'manager_edit' => 'تعديل إداري',
                                                        'approved' => 'اعتماد العقد',
                                                        'rejected' => 'رفض العقد',
                                                        'return_for_revision' => 'إرجاع للمراجعة'
                                                    ];
                                                    echo $actionLabels[$history['action']] ?? $history['action'];
                                                    ?>
                                                </strong>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y H:i', strtotime($history['created_at'])) ?>
                                                </small>
                                            </div>
                                            <?php if ($history['notes']): ?>
                                                <p class="mb-0 text-muted"><?= htmlspecialchars($history['notes']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- معلومات إضافية -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-info-circle me-2"></i>معلومات إضافية
                        </h3>
                        
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <i class="fas fa-user text-primary mb-2 d-block"></i>
                                    <small class="text-muted d-block">منشئ العقد</small>
                                    <strong><?= htmlspecialchars($contract['created_by_name'] ?? 'غير محدد') ?></strong>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <i class="fas fa-calendar text-info mb-2 d-block"></i>
                                    <small class="text-muted d-block">تاريخ الإنشاء</small>
                                    <strong><?= date('d/m/Y', strtotime($contract['created_at'])) ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($contract['updated_at']): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    آخر تحديث: <?= date('d/m/Y H:i', strtotime($contract['updated_at'])) ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal إرجاع للمراجعة -->
    <div class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="return_for_revision">
                    <div class="modal-header">
                        <h5 class="modal-title">إرجاع العقد للمراجعة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ملاحظات المراجعة *</label>
                            <textarea name="revision_notes" class="form-control" rows="4" required
                                      placeholder="اكتب الملاحظات والتعديلات المطلوبة..."></textarea>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            سيتم إرسال إشعار للموظف المسؤول عن العقد مع الملاحظات المطلوبة.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-undo me-2"></i>إرجاع للمراجعة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تأكيد حفظ التعديلات
        document.getElementById('contractForm').addEventListener('submit', function(e) {
            const confirmation = confirm('هل أنت متأكد من حفظ هذه التعديلات؟');
            if (!confirmation) {
                e.preventDefault();
            }
        });
        
        // التحقق من صحة التواريخ
        const startDateInput = document.querySelector('input[name="start_date"]');
        const endDateInput = document.querySelector('input[name="end_date"]');
        
        function validateDates() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            
            if (startDate && endDate && startDate >= endDate) {
                alert('تاريخ نهاية العقد يجب أن يكون بعد تاريخ البداية');
                endDateInput.focus();
                return false;
            }
            return true;
        }
        
        startDateInput.addEventListener('change', validateDates);
        endDateInput.addEventListener('change', validateDates);
        
        // تنسيق قيمة العقد
        const amountInput = document.querySelector('input[name="amount"]');
        amountInput.addEventListener('input', function() {
            const value = parseFloat(this.value);
            if (value && value > 0) {
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#dc3545';
            }
        });
        
        // حفظ تلقائي (Draft)
        let autoSaveTimer;
        function scheduleAutoSave() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                // يمكن إضافة حفظ تلقائي هنا
                console.log('Auto-save scheduled...');
            }, 30000); // حفظ كل 30 ثانية
        }
        
        // تتبع التغييرات في النموذج
        document.querySelectorAll('#contractForm input, #contractForm textarea, #contractForm select').forEach(field => {
            field.addEventListener('input', scheduleAutoSave);
        });
    </script>
</body>
</html>