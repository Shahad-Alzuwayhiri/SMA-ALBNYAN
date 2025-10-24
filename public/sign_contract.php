<?php
require_once __DIR__ . '/../includes/auth.php';

// التحقق من تسجيل الدخول والصلاحية
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();

if (!in_array($user['role'], ['manager', 'admin'])) {
    header('Location: employee_dashboard.php');
    exit;
}

$contractId = $_GET['id'] ?? null;

if (!$contractId) {
    header('Location: manager_dashboard.php');
    exit;
}

// جلب بيانات العقد
try {
    $stmt = $pdo->prepare("SELECT * FROM contracts WHERE id = ?");
    $stmt->execute([$contractId]);
    $contract = $stmt->fetch();
    
    if (!$contract) {
        header('Location: manager_dashboard.php');
        exit;
    }
} catch (Exception $e) {
    $error = "خطأ في جلب بيانات العقد: " . $e->getMessage();
}

// معالجة التوقيع
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $signature_method = $_POST['signature_method'] ?? '';
    $signature_notes = $_POST['signature_notes'] ?? '';
    
    if (empty($signature_method)) {
        $error = 'يرجى اختيار طريقة التوقيع';
    } else {
        try {
            // تحديث العقد بطريقة التوقيع وتوقيعه
            $stmt = $pdo->prepare("
                UPDATE contracts 
                SET signature_method = ?, status = 'completed', 
                    reviewed_by = ?, updated_at = CURRENT_TIMESTAMP,
                    notes = CONCAT(COALESCE(notes, ''), '\nتم التوقيع: ', ?)
                WHERE id = ?
            ");
            
            $signatureNote = "تم توقيع العقد بطريقة: $signature_method";
            if ($signature_notes) {
                $signatureNote .= " - ملاحظات: $signature_notes";
            }
            
            $stmt->execute([$signature_method, $user['id'], $signatureNote, $contractId]);
            
            // تسجيل النشاط
            $auth->logActivity($user['id'], 'sign_contract', "تم توقيع العقد رقم: {$contract['contract_number']}", $contractId);
            
            // إشعار الموظف المنشئ
            if ($contract['created_by']) {
                $auth->createNotification(
                    $contract['created_by'],
                    'تم توقيع العقد',
                    "تم توقيع العقد رقم {$contract['contract_number']} وأصبح ساري المفعول",
                    'success',
                    $contractId
                );
            }
            
            // إضافة سجل في تاريخ العقد
            $stmt = $pdo->prepare("
                INSERT INTO contract_history (contract_id, action, performed_by, notes, created_at) 
                VALUES (?, 'sign', ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$contractId, $user['id'], $signatureNote]);
            
            $success = 'تم توقيع العقد بنجاح!';
            
        } catch (Exception $e) {
            $error = 'خطأ في توقيع العقد: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>توقيع العقد - نظام إدارة العقود</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/modern-theme.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-signature me-2"></i>
                            توقيع العقد رقم: <?= htmlspecialchars($contract['contract_number'] ?? '') ?>
                        </h4>
                    </div>
                    
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= $error ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= $success ?>
                                <div class="mt-3">
                                    <a href="manager_dashboard.php" class="btn btn-primary">
                                        العودة للوحة التحكم
                                    </a>
                                    <a href="view_contract.php?id=<?= $contractId ?>" class="btn btn-outline-primary">
                                        عرض العقد
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- معلومات العقد -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">تفاصيل العقد</h6>
                                            <p><strong>العنوان:</strong> <?= htmlspecialchars($contract['title']) ?></p>
                                            <p><strong>الطرف الثاني:</strong> <?= htmlspecialchars($contract['second_party_name']) ?></p>
                                            <p><strong>قيمة العقد:</strong> <?= number_format($contract['contract_amount'], 2) ?> ريال</p>
                                            <p><strong>الحالة الحالية:</strong> 
                                                <span class="badge bg-success">معتمد - جاهز للتوقيع</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">معلومات إضافية</h6>
                                            <p><strong>تاريخ الإنشاء:</strong> <?= date('Y-m-d', strtotime($contract['created_at'])) ?></p>
                                            <p><strong>المدة:</strong> <?= $contract['contract_duration'] ?> شهر</p>
                                            <p><strong>نسبة الربح:</strong> <?= $contract['profit_percentage'] ?>%</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- نموذج التوقيع -->
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-4">
                                    <label for="signature_method" class="form-label">
                                        <i class="fas fa-signature"></i> طريقة التوقيع *
                                    </label>
                                    <select class="form-control" id="signature_method" name="signature_method" required>
                                        <option value="">اختر طريقة التوقيع</option>
                                        <option value="electronic">توقيع إلكتروني</option>
                                        <option value="handwritten">توقيع يدوي</option>
                                        <option value="digital">توقيع رقمي معتمد</option>
                                        <option value="witness">بحضور شاهد</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        يرجى اختيار طريقة التوقيع
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="signature_notes" class="form-label">
                                        <i class="fas fa-sticky-note"></i> ملاحظات التوقيع (اختياري)
                                    </label>
                                    <textarea class="form-control" id="signature_notes" name="signature_notes" 
                                              rows="3" placeholder="أدخل أي ملاحظات حول عملية التوقيع..."></textarea>
                                </div>

                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>تنبيه:</strong> بعد التوقيع، سيصبح العقد ساري المفعول ولا يمكن تعديله.
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-signature me-1"></i>
                                        توقيع العقد
                                    </button>
                                    <a href="view_contract.php?id=<?= $contractId ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-eye me-1"></i>
                                        مراجعة العقد
                                    </a>
                                    <a href="manager_dashboard.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>
                                        العودة
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>