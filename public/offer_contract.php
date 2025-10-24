<?php
require_once '../includes/auth.php';

// التحقق من صلاحية إنشاء العقود
$auth->requirePermission('create_contracts');
$user = $auth->getCurrentUser();

$message = '';
$error = '';

// معالجة إنشاء العرض
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = trim($_POST['client_name'] ?? '');
    $client_id = trim($_POST['client_id'] ?? '');
    $client_phone = trim($_POST['client_phone'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $offer_date = $_POST['offer_date'] ?? date('Y-m-d');
    $profit_percentage = floatval($_POST['profit_percentage'] ?? 30);
    $notes = trim($_POST['notes'] ?? '');
    $valid_until = $_POST['valid_until'] ?? date('Y-m-d', strtotime('+30 days'));
    
    $errors = [];
    
    // التحقق من صحة البيانات
    if (empty($client_name)) $errors[] = 'اسم العميل مطلوب';
    if (empty($client_id) || !preg_match('/^[0-9]{10}$/', $client_id)) $errors[] = 'رقم الهوية يجب أن يكون 10 أرقام';
    if (empty($client_phone) || !preg_match('/^05[0-9]{8}$/', $client_phone)) $errors[] = 'رقم الجوال غير صحيح (يجب أن يبدأ بـ 05 ويكون 10 أرقام)';
    if ($amount < 50000) $errors[] = 'مبلغ العرض يجب أن يكون 50,000 ريال على الأقل';
    if ($profit_percentage <= 0) $errors[] = 'نسبة الربح يجب أن تكون أكبر من صفر';
    if (empty($offer_date)) $errors[] = 'تاريخ العرض مطلوب';
    if (empty($valid_until)) $errors[] = 'تاريخ انتهاء صلاحية العرض مطلوب';
    
    if (empty($errors)) {
        try {
            // إنشاء رقم عرض جديد
            $offerNumberStmt = $pdo->prepare("SELECT MAX(CAST(SUBSTR(contract_number, 10) AS INTEGER)) as max_num FROM contracts WHERE contract_number LIKE 'OFF-" . date('Y') . "-%'");
            $offerNumberStmt->execute();
            $result = $offerNumberStmt->fetch();
            $nextNumber = ($result['max_num'] ?? 0) + 1;
            $offer_number = 'OFF-' . date('Y') . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            
            // حساب صافي الربح تلقائياً (6 أشهر فقط)
            $contract_duration = 6; // 6 أشهر فقط للعروض
            $monthly_profit = ($amount * $profit_percentage / 100) / 12;
            $net_profit = $monthly_profit * $contract_duration;
            
            // إدراج العرض
            $insertStmt = $pdo->prepare("
                INSERT INTO contracts (
                    contract_number, client_name, client_id, client_phone, amount, 
                    profit_percentage, contract_duration, profit_interval, signature_method, 
                    contract_date, notes, status, created_by, net_profit, is_amendment,
                    parent_contract_id, amendment_duration_months, contract_type, valid_until
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'monthly', 'pending', ?, ?, 'offer', ?, ?, 0, 0, 6, 'offer', ?)
            ");
            
            $insertStmt->execute([
                $offer_number, $client_name, $client_id, $client_phone, $amount,
                $profit_percentage, $contract_duration, $offer_date, $notes, 
                $user['id'], $net_profit, $valid_until
            ]);
            
            $offerId = $pdo->lastInsertId();
            
            // تسجيل النشاط
            $logStmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, description, related_contract_id) 
                VALUES (?, 'create_offer', ?, ?)
            ");
            $logStmt->execute([
                $user['id'], 
                "إنشاء عرض رقم: $offer_number", 
                $offerId
            ]);
            
            $message = "تم إنشاء العرض بنجاح - رقم العرض: $offer_number<br>صافي الربح المحسوب تلقائياً: " . number_format($net_profit, 2) . " ريال<br>صالح حتى: " . date('Y-m-d', strtotime($valid_until));
            
            // إعادة تعيين المتغيرات
            $client_name = $client_id = $client_phone = $amount = $notes = '';
            $offer_date = date('Y-m-d');
            $valid_until = date('Y-m-d', strtotime('+30 days'));
            $profit_percentage = 30;
            
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
    <title>إنشاء عرض - Sma Albnyan</title>
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
                <a class="nav-link" href="renewal_contract.php"><i class="fas fa-redo me-1"></i> تجديد</a>
                <a class="nav-link active" href="offer_contract.php"><i class="fas fa-file-invoice me-1"></i> عرض</a>
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
                            <i class="fas fa-file-invoice me-2"></i>
                            إنشاء عرض جديد
                        </h4>
                        <p class="mb-0 mt-2">مدة العرض: 6 أشهر | الحد الأدنى: 50,000 ريال</p>
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
                                    <label for="offer_date" class="form-label">تاريخ العرض *</label>
                                    <input type="date" class="form-control" id="offer_date" name="offer_date" 
                                           value="<?= $offer_date ?? date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="amount" class="form-label">مبلغ العرض (ريال) *</label>
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
                                    <label class="form-label">مدة العرض</label>
                                    <input type="text" class="form-control" value="6 أشهر (ثابت)" readonly>
                                    <div class="form-text sma-text-primary">مدة جميع العروض محددة بـ 6 أشهر</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="valid_until" class="form-label">صالح حتى تاريخ *</label>
                                    <input type="date" class="form-control" id="valid_until" name="valid_until" 
                                           value="<?= $valid_until ?? date('Y-m-d', strtotime('+30 days')) ?>" required>
                                    <div class="form-text">صلاحية العرض (افتراضي 30 يوم)</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="net_profit_display" class="form-label">صافي الربح المتوقع (ريال)</label>
                                    <input type="text" class="form-control sma-highlight" id="net_profit_display" readonly>
                                    <div class="form-text">يتم حساب صافي الربح تلقائياً بناءً على المبلغ ونسبة الربح لمدة 6 أشهر</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">ملاحظات العرض</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="أضف أي تفاصيل إضافية أو شروط خاصة للعرض..."><?= htmlspecialchars($notes ?? '') ?></textarea>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn sma-btn-primary btn-lg">
                                    <i class="fas fa-file-invoice me-2"></i>
                                    إنشاء العرض
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