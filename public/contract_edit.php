<?php
/**
 * Contract Edit Page - Sama Al-Bunyan Contracts Platform
 * Edit draft contracts before approval
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Authentication check
$auth->requireAuth();
$user = $auth->getCurrentUser();

$contract_id = $_GET['id'] ?? null;
if (!$contract_id || !is_numeric($contract_id)) {
    header('Location: contracts_list.php?error=' . urlencode('معرف العقد مطلوب'));
    exit;
}

$contract = null;
$error = '';
$success = '';

try {
    // Get contract details
    $stmt = $pdo->prepare("
        SELECT * FROM contracts 
        WHERE id = ? AND (created_by = ? OR ? IN ('manager', 'admin'))
    ");
    $stmt->execute([$contract_id, $user['id'], $user['role']]);
    $contract = $stmt->fetch();

    if (!$contract) {
        header('Location: contracts_list.php?error=' . urlencode('العقد غير موجود أو غير مسموح لك بتعديله'));
        exit;
    }
    
    // Only drafts and rejected contracts can be edited
    if (!in_array($contract['status'], ['draft', 'rejected'])) {
        header('Location: contract_view.php?id=' . $contract_id . '&error=' . urlencode('لا يمكن تعديل هذا العقد في حالته الحالية'));
        exit;
    }
    
} catch (PDOException $e) {
    $error = 'خطأ في جلب بيانات العقد: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $contract) {
    // Collect and sanitize form data
    $formData = [
        'client_name' => trim($_POST['client_name'] ?? ''),
        'client_id' => trim($_POST['client_id'] ?? ''),
        'client_phone' => trim($_POST['client_phone'] ?? ''),
        'client_email' => trim($_POST['client_email'] ?? ''),
        'amount' => floatval($_POST['amount'] ?? 0),
        'contract_date' => $_POST['contract_date'] ?? '',
        'contract_type' => $_POST['contract_type'] ?? 'investment',
        'profit_percentage' => floatval($_POST['profit_percentage'] ?? 30),
        'contract_duration' => intval($_POST['contract_duration'] ?? 6),
        'profit_interval' => $_POST['profit_interval'] ?? 'monthly',
        'notes' => trim($_POST['notes'] ?? ''),
        'property_description' => trim($_POST['property_description'] ?? ''),
        'property_value' => floatval($_POST['property_value'] ?? 0)
    ];
    
    // Calculate net profit
    $formData['net_profit'] = $formData['amount'] * ($formData['profit_percentage'] / 100);
    
    $errors = [];
    
    // Validation
    if (empty($formData['client_name']) || strlen($formData['client_name']) < 3) {
        $errors[] = 'اسم العميل مطلوب ويجب أن يكون 3 أحرف على الأقل';
    }
    
    if (empty($formData['client_id']) || !preg_match('/^[0-9]{10}$/', $formData['client_id'])) {
        $errors[] = 'رقم الهوية يجب أن يكون 10 أرقام بالضبط';
    }
    
    if (empty($formData['client_phone']) || !preg_match('/^05[0-9]{8}$/', $formData['client_phone'])) {
        $errors[] = 'رقم الجوال غير صحيح (يجب أن يبدأ بـ 05 ويكون 10 أرقام)';
    }
    
    if (!empty($formData['client_email']) && !filter_var($formData['client_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }
    
    if ($formData['amount'] < 50000) {
        $errors[] = 'مبلغ العقد يجب أن يكون 50,000 ريال على الأقل';
    }
    
    if (empty($formData['contract_date'])) {
        $errors[] = 'تاريخ العقد مطلوب';
    }
    
    if ($formData['profit_percentage'] <= 0 || $formData['profit_percentage'] > 50) {
        $errors[] = 'نسبة الربح يجب أن تكون بين 1% و 50%';
    }
    
    if ($formData['contract_duration'] < 1 || $formData['contract_duration'] > 60) {
        $errors[] = 'مدة العقد يجب أن تكون بين شهر واحد و 60 شهر';
    }
    
    // Property investment validation
    if ($formData['contract_type'] === 'property_investment') {
        if (empty($formData['property_description'])) {
            $errors[] = 'وصف العقار مطلوب للاستثمار العقاري';
        }
        if ($formData['property_value'] <= 0) {
            $errors[] = 'قيمة العقار مطلوبة للاستثمار العقاري';
        }
    }
    
    // Check for duplicate client ID (except current contract)
    if (!$errors) {
        try {
            $checkStmt = $pdo->prepare("SELECT id FROM contracts WHERE client_id = ? AND id != ? AND status != 'rejected'");
            $checkStmt->execute([$formData['client_id'], $contract_id]);
            if ($checkStmt->fetch()) {
                $errors[] = 'يوجد عقد آخر بنفس رقم الهوية';
            }
        } catch (PDOException $e) {
            $errors[] = 'خطأ في التحقق من البيانات';
        }
    }
    
    if (empty($errors)) {
        try {
            // Update contract
            $updateSql = "
                UPDATE contracts SET 
                    client_name = ?, client_id = ?, client_phone = ?, client_email = ?,
                    amount = ?, contract_date = ?, contract_type = ?, 
                    profit_percentage = ?, net_profit = ?, contract_duration = ?,
                    profit_interval = ?, notes = ?, property_description = ?, property_value = ?,
                    updated_at = CURRENT_TIMESTAMP,
                    status = 'draft'
                WHERE id = ?
            ";
            
            $updateStmt = $pdo->prepare($updateSql);
            $updateResult = $updateStmt->execute([
                $formData['client_name'],
                $formData['client_id'], 
                $formData['client_phone'],
                $formData['client_email'],
                $formData['amount'],
                $formData['contract_date'],
                $formData['contract_type'],
                $formData['profit_percentage'],
                $formData['net_profit'],
                $formData['contract_duration'],
                $formData['profit_interval'],
                $formData['notes'],
                $formData['property_description'],
                $formData['property_value'],
                $contract_id
            ]);
            
            if ($updateResult) {
                // Log the activity
                $logStmt = $pdo->prepare("
                    INSERT INTO activity_log (user_id, contract_id, action, description, created_at)
                    VALUES (?, ?, 'contract_updated', 'تم تحديث بيانات العقد', CURRENT_TIMESTAMP)
                ");
                $logStmt->execute([$user['id'], $contract_id, 'تم تحديث العقد']);
                
                $success = 'تم تحديث العقد بنجاح';
                
                // Refresh contract data
                $stmt->execute([$contract_id, $user['id'], $user['role']]);
                $contract = $stmt->fetch();
            } else {
                $error = 'خطأ في تحديث العقد';
            }
            
        } catch (PDOException $e) {
            $error = 'خطأ في حفظ البيانات: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل العقد <?= htmlspecialchars($contract['contract_number'] ?? '') ?> - سما البنيان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/unified-theme.css" rel="stylesheet">
    <style>
        :root {
            --sma-gold: #D4AF37;
            --sma-dark-gold: #B8941F;
            --sma-gray: #6C757D;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--sma-gold), var(--sma-dark-gold));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-right: 4px solid var(--sma-gold);
        }
        
        .section-title {
            color: var(--sma-dark-gold);
            border-bottom: 2px solid var(--sma-gold);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--sma-gray);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--sma-gold);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }
        
        .btn-sma-primary {
            background: var(--sma-gold);
            border-color: var(--sma-gold);
            color: white;
        }
        
        .btn-sma-primary:hover {
            background: var(--sma-dark-gold);
            border-color: var(--sma-dark-gold);
            color: white;
        }
        
        .calculated-field {
            background-color: #f8f9fa;
            border: 2px dashed var(--sma-gold);
        }
        
        .property-fields {
            display: none;
        }
        
        .property-fields.show {
            display: block;
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 1rem;
            }
            
            .form-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <?php if ($error && !$contract): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <div class="text-center">
            <a href="contracts_list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
        <?php elseif ($contract): ?>
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="fas fa-edit me-2"></i>
                        تعديل العقد رقم: <?= htmlspecialchars($contract['contract_number']) ?>
                    </h2>
                    <p class="mb-0">
                        <strong>العميل:</strong> <?= htmlspecialchars($contract['client_name']) ?>
                        <span class="ms-3">
                            <strong>الحالة:</strong> 
                            <?php if ($contract['status'] === 'draft'): ?>
                            <span class="badge bg-secondary">مسودة</span>
                            <?php elseif ($contract['status'] === 'rejected'): ?>
                            <span class="badge bg-danger">مرفوض</span>
                            <?php endif; ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="contract_view.php?id=<?= $contract['id'] ?>" class="btn btn-outline-light me-2">
                        <i class="fas fa-eye me-1"></i> عرض العقد
                    </a>
                    <a href="contracts_list.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <form method="POST" id="contractForm" novalidate>
            <!-- Client Information -->
            <div class="form-section">
                <h4 class="section-title">معلومات العميل</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="client_name" class="form-label">اسم العميل <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="client_name" name="client_name" 
                               value="<?= htmlspecialchars($contract['client_name']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="client_id" class="form-label">رقم الهوية <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="client_id" name="client_id" 
                               pattern="[0-9]{10}" maxlength="10"
                               value="<?= htmlspecialchars($contract['client_id']) ?>" required>
                        <div class="form-text">يجب أن يكون 10 أرقام</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="client_phone" class="form-label">رقم الجوال <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="client_phone" name="client_phone" 
                               pattern="05[0-9]{8}" maxlength="10"
                               value="<?= htmlspecialchars($contract['client_phone']) ?>" required>
                        <div class="form-text">مثال: 0512345678</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="client_email" class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control" id="client_email" name="client_email" 
                               value="<?= htmlspecialchars($contract['client_email']) ?>">
                    </div>
                </div>
            </div>

            <!-- Contract Details -->
            <div class="form-section">
                <h4 class="section-title">تفاصيل العقد</h4>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="contract_type" class="form-label">نوع العقد <span class="text-danger">*</span></label>
                        <select class="form-select" id="contract_type" name="contract_type" required>
                            <option value="investment" <?= $contract['contract_type'] === 'investment' ? 'selected' : '' ?>>
                                استثمار نقدي
                            </option>
                            <option value="property_investment" <?= $contract['contract_type'] === 'property_investment' ? 'selected' : '' ?>>
                                استثمار بعقار
                            </option>
                            <option value="real_estate" <?= $contract['contract_type'] === 'real_estate' ? 'selected' : '' ?>>
                                عقاري
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="amount" class="form-label">مبلغ العقد (ريال) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="amount" name="amount" 
                               min="50000" step="1000" 
                               value="<?= $contract['amount'] ?>" required>
                        <div class="form-text">الحد الأدنى: 50,000 ريال</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="contract_date" class="form-label">تاريخ العقد <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="contract_date" name="contract_date" 
                               value="<?= $contract['contract_date'] ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="profit_percentage" class="form-label">نسبة الربح (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="profit_percentage" name="profit_percentage" 
                               min="1" max="50" step="0.1" 
                               value="<?= $contract['profit_percentage'] ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="net_profit" class="form-label">صافي الربح (ريال)</label>
                        <input type="text" class="form-control calculated-field" id="net_profit" 
                               value="<?= number_format($contract['net_profit'], 2) ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="contract_duration" class="form-label">مدة العقد (أشهر) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="contract_duration" name="contract_duration" 
                               min="1" max="60" 
                               value="<?= $contract['contract_duration'] ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="profit_interval" class="form-label">فترة دفع الأرباح</label>
                        <select class="form-select" id="profit_interval" name="profit_interval">
                            <option value="monthly" <?= $contract['profit_interval'] === 'monthly' ? 'selected' : '' ?>>شهري</option>
                            <option value="quarterly" <?= $contract['profit_interval'] === 'quarterly' ? 'selected' : '' ?>>ربع سنوي</option>
                            <option value="semi_annual" <?= $contract['profit_interval'] === 'semi_annual' ? 'selected' : '' ?>>نصف سنوي</option>
                            <option value="annual" <?= $contract['profit_interval'] === 'annual' ? 'selected' : '' ?>>سنوي</option>
                            <option value="end_of_contract" <?= $contract['profit_interval'] === 'end_of_contract' ? 'selected' : '' ?>>نهاية العقد</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Property Investment Details -->
            <div class="form-section property-fields <?= $contract['contract_type'] === 'property_investment' ? 'show' : '' ?>">
                <h4 class="section-title">تفاصيل الاستثمار العقاري</h4>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="property_description" class="form-label">وصف العقار</label>
                        <textarea class="form-control" id="property_description" name="property_description" 
                                  rows="3" placeholder="وصف مفصل للعقار المستثمر فيه..."><?= htmlspecialchars($contract['property_description']) ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="property_value" class="form-label">قيمة العقار (ريال)</label>
                        <input type="number" class="form-control" id="property_value" name="property_value" 
                               min="0" step="1000" 
                               value="<?= $contract['property_value'] ?>">
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="form-section">
                <h4 class="section-title">الملاحظات</h4>
                <div class="mb-3">
                    <label for="notes" class="form-label">ملاحظات إضافية</label>
                    <textarea class="form-control" id="notes" name="notes" rows="4" 
                              placeholder="أي ملاحظات أو شروط إضافية..."><?= htmlspecialchars($contract['notes']) ?></textarea>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-section">
                <div class="row">
                    <div class="col-md-8">
                        <button type="submit" class="btn btn-sma-primary btn-lg me-2">
                            <i class="fas fa-save me-1"></i> حفظ التغييرات
                        </button>
                        <button type="button" class="btn btn-success btn-lg me-2" onclick="submitForReview()">
                            <i class="fas fa-paper-plane me-1"></i> حفظ وإرسال للمراجعة
                        </button>
                        <a href="contract_view.php?id=<?= $contract['id'] ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-times me-1"></i> إلغاء
                        </a>
                    </div>
                    <div class="col-md-4 text-end">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            آخر تحديث: <?= date('Y/m/d H:i', strtotime($contract['updated_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
        </form>
        
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate net profit automatically
        function calculateNetProfit() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const percentage = parseFloat(document.getElementById('profit_percentage').value) || 0;
            const netProfit = amount * (percentage / 100);
            document.getElementById('net_profit').value = netProfit.toLocaleString('ar-SA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        // Show/hide property fields based on contract type
        function togglePropertyFields() {
            const contractType = document.getElementById('contract_type').value;
            const propertyFields = document.querySelector('.property-fields');
            
            if (contractType === 'property_investment') {
                propertyFields.classList.add('show');
            } else {
                propertyFields.classList.remove('show');
            }
        }
        
        // Submit for review
        function submitForReview() {
            if (confirm('هل تريد حفظ التغييرات وإرسال العقد للمراجعة؟')) {
                const form = document.getElementById('contractForm');
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'submit_for_review';
                hiddenInput.value = '1';
                form.appendChild(hiddenInput);
                form.submit();
            }
        }
        
        // Event listeners
        document.getElementById('amount').addEventListener('input', calculateNetProfit);
        document.getElementById('profit_percentage').addEventListener('input', calculateNetProfit);
        document.getElementById('contract_type').addEventListener('change', togglePropertyFields);
        
        // Form validation
        document.getElementById('contractForm').addEventListener('submit', function(e) {
            const form = this;
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
        
        // Initialize
        togglePropertyFields();
        calculateNetProfit();
    </script>
</body>
</html>