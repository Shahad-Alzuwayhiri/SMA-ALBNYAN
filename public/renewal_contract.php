<?php
require_once '../includes/auth.php';

// التحقق من صلاحية إنشاء العقود
$auth->requirePermission('create_contracts');
$user = $auth->getCurrentUser();

$message = '';
$error = '';

// الحصول على العقود المتاحة للتجديد
$contractsForRenewal = [];
try {
    $stmt = $pdo->prepare("SELECT id, contract_number, client_name, amount FROM contracts WHERE status = 'active' AND is_amendment = 0 ORDER BY contract_number");
    $stmt->execute();
    $contractsForRenewal = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'خطأ في تحميل العقود: ' . $e->getMessage();
}

// معالجة إنشاء عقد التجديد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parent_contract_id = intval($_POST['parent_contract_id'] ?? 0);
    $client_name = trim($_POST['client_name'] ?? '');
    $client_id = trim($_POST['client_id'] ?? '');
    $client_phone = trim($_POST['client_phone'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $contract_date = $_POST['contract_date'] ?? date('Y-m-d');
    $profit_percentage = floatval($_POST['profit_percentage'] ?? 30);
    $notes = trim($_POST['notes'] ?? '');
    
    $errors = [];
    
    // التحقق من صحة البيانات
    if ($parent_contract_id <= 0) $errors[] = 'يجب اختيار العقد المراد تجديده';
    if (empty($client_name)) $errors[] = 'اسم العميل مطلوب';
    if (empty($client_id) || !preg_match('/^[0-9]{10}$/', $client_id)) $errors[] = 'رقم الهوية يجب أن يكون 10 أرقام';
    if (empty($client_phone) || !preg_match('/^05[0-9]{8}$/', $client_phone)) $errors[] = 'رقم الجوال غير صحيح (يجب أن يبدأ بـ 05 ويكون 10 أرقام)';
    if ($amount < 50000) $errors[] = 'مبلغ التجديد يجب أن يكون 50,000 ريال على الأقل';
    if ($profit_percentage <= 0) $errors[] = 'نسبة الربح يجب أن تكون أكبر من صفر';
    
    if (empty($errors)) {
        try {
            // الحصول على معلومات العقد الأصلي
            $parentStmt = $pdo->prepare("SELECT contract_number FROM contracts WHERE id = ?");
            $parentStmt->execute([$parent_contract_id]);
            $parentContract = $parentStmt->fetch();
            
            if (!$parentContract) {
                throw new Exception('العقد المحدد غير موجود');
            }
            
            // إنشاء رقم التجديد
            $renewalCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM contracts WHERE parent_contract_id = ? AND contract_number LIKE '%-REN%'");
            $renewalCountStmt->execute([$parent_contract_id]);
            $renewalResult = $renewalCountStmt->fetch();
            $renewalNumber = ($renewalResult['count'] ?? 0) + 1;
            
            $contract_number = $parentContract['contract_number'] . '-REN' . str_pad($renewalNumber, 2, '0', STR_PAD_LEFT);
            
            // حساب صافي الربح تلقائياً (6 أشهر فقط)
            $contract_duration = 6; // 6 أشهر فقط للتجديدات
            $monthly_profit = ($amount * $profit_percentage / 100) / 12;
            $net_profit = $monthly_profit * $contract_duration;
            
            // إدراج عقد التجديد
            $insertStmt = $pdo->prepare("
                INSERT INTO contracts (
                    contract_number, client_name, client_id, client_phone, amount, 
                    profit_percentage, contract_duration, profit_interval, signature_method, 
                    contract_date, notes, status, created_by, net_profit, is_amendment,
                    parent_contract_id, amendment_duration_months, contract_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'monthly', 'electronic', ?, ?, 'draft', ?, ?, 0, ?, 6, 'renewal')
            ");
            
            $insertStmt->execute([
                $contract_number, $client_name, $client_id, $client_phone, $amount,
                $profit_percentage, $contract_duration, $contract_date, $notes, 
                $user['id'], $net_profit, $parent_contract_id
            ]);
            
            $contractId = $pdo->lastInsertId();
            
            // تسجيل النشاط
            $logStmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, description, related_contract_id) 
                VALUES (?, 'create_renewal', ?, ?)
            ");
            $logStmt->execute([
                $user['id'], 
                "إنشاء عقد تجديد رقم: $contract_number", 
                $contractId
            ]);
            
            $message = "تم إنشاء عقد التجديد بنجاح - رقم العقد: $contract_number<br>صافي الربح المحسوب تلقائياً: " . number_format($net_profit, 2) . " ريال";
            
        } catch (Exception $e) {
            $error = 'خطأ في النظام: ' . $e->getMessage();
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
    <title>تجديد العقود - Sma Albnyan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../static/css/sma-company-theme.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="sma-bg-light">
    <nav class="navbar navbar-expand-lg sma-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/dashboard.php">
                <i class="fas fa-handshake me-2"></i>
                Sma Albnyan
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i> الرئيسية</a>
                <a class="nav-link" href="contracts_list.php"><i class="fas fa-file-contract me-1"></i> العقود</a>
                <a class="nav-link" href="create_contract.php"><i class="fas fa-plus me-1"></i> عقد جديد</a>
                <a class="nav-link active" href="renewal_contract.php"><i class="fas fa-redo me-1"></i> تجديد</a>
                <a class="nav-link" href="../includes/logout.php"><i class="fas fa-sign-out-alt me-1"></i> تسجيل خروج</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card sma-card">
                    <div class="card-header sma-header text-center">
                        <h4 class="mb-0">
                            <i class="fas fa-redo me-2"></i>
                            تجديد العقود
                        </h4>
                        <p class="mb-0 mt-2">مدة التجديد: 6 أشهر فقط | الحد الأدنى: 50,000 ريال</p>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert sma-alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert sma-alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="parent_contract_id" class="form-label">العقد المراد تجديده *</label>
                                    <select class="form-select" id="parent_contract_id" name="parent_contract_id" required>
                                        <option value="">اختر العقد</option>
                                        <?php foreach ($contractsForRenewal as $contract): ?>
                                            <option value="<?= $contract['id'] ?>" 
                                                data-client="<?= htmlspecialchars($contract['client_name']) ?>"
                                                data-amount="<?= $contract['amount'] ?>">
                                                <?= $contract['contract_number'] ?> - <?= htmlspecialchars($contract['client_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">يرجى اختيار العقد المراد تجديده</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="client_name" class="form-label">اسم العميل *</label>
                                    <input type="text" class="form-control" id="client_name" name="client_name" 
                                           value="<?= htmlspecialchars($client_name ?? '') ?>" required>
                                    <div class="invalid-feedback">اسم العميل مطلوب</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="client_id" class="form-label">رقم الهوية *</label>
                                    <input type="text" class="form-control" id="client_id" name="client_id" 
                                           value="<?= htmlspecialchars($client_id ?? '') ?>" 
                                           pattern="[0-9]{10}" maxlength="10" required>
                                    <div class="invalid-feedback">رقم الهوية يجب أن يكون 10 أرقام</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="client_phone" class="form-label">رقم الجوال *</label>
                                    <input type="tel" class="form-control" id="client_phone" name="client_phone" 
                                           value="<?= htmlspecialchars($client_phone ?? '') ?>" 
                                           pattern="05[0-9]{8}" maxlength="10" required>
                                    <div class="invalid-feedback">يجب أن يبدأ بـ 05 ويكون 10 أرقام</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="contract_date" class="form-label">تاريخ التجديد *</label>
                                    <input type="date" class="form-control" id="contract_date" name="contract_date" 
                                           value="<?= $contract_date ?? date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="amount" class="form-label">مبلغ التجديد (ريال) *</label>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           value="<?= $amount ?? '' ?>" min="50000" step="0.01" required>
                                    <div class="form-text">الحد الأدنى: 50,000 ريال</div>
                                    <div class="invalid-feedback">المبلغ يجب أن يكون 50,000 ريال على الأقل</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="profit_percentage" class="form-label">نسبة الربح (%) *</label>
                                    <input type="number" class="form-control" id="profit_percentage" name="profit_percentage" 
                                           value="<?= $profit_percentage ?? 30 ?>" min="0.1" max="100" step="0.1" required>
                                    <div class="invalid-feedback">نسبة الربح مطلوبة</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">مدة التجديد</label>
                                    <input type="text" class="form-control" value="6 أشهر (ثابت)" readonly>
                                    <div class="form-text sma-text-primary">مدة التجديد محددة بـ 6 أشهر حسب سياسة الشركة</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="net_profit_display" class="form-label">صافي الربح المتوقع (ريال)</label>
                                    <input type="text" class="form-control sma-highlight" id="net_profit_display" readonly>
                                    <div class="form-text">يتم حساب صافي الربح تلقائياً بناءً على المبلغ ونسبة الربح</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">ملاحظات</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($notes ?? '') ?></textarea>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn sma-btn-primary btn-lg">
                                    <i class="fas fa-redo me-2"></i>
                                    إنشاء عقد التجديد
                                </button>
                                <a href="contracts_list.php" class="btn btn-secondary btn-lg ms-2">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    العودة للعقود
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-calculate net profit
        function calculateNetProfit() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const profitPercentage = parseFloat(document.getElementById('profit_percentage').value) || 0;
            
            if (amount > 0 && profitPercentage > 0) {
                const monthlyProfit = (amount * profitPercentage / 100) / 12;
                const netProfit = monthlyProfit * 6; // 6 months only
                document.getElementById('net_profit_display').value = netProfit.toLocaleString('ar-SA', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' ريال';
            } else {
                document.getElementById('net_profit_display').value = '';
            }
        }

        // Fill client data when contract is selected
        document.getElementById('parent_contract_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                document.getElementById('client_name').value = selectedOption.dataset.client || '';
                document.getElementById('amount').value = selectedOption.dataset.amount || '';
                calculateNetProfit();
            }
        });

        // Calculate profit when amount or percentage changes
        document.getElementById('amount').addEventListener('input', calculateNetProfit);
        document.getElementById('profit_percentage').addEventListener('input', calculateNetProfit);

        // Form validation
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

        // Initial calculation
        calculateNetProfit();
    </script>
</body>
</html>