<?php
/**
 * Contract Creation Page - Sama Al-Bunyan Contracts Platform
 * Create new contracts with validation and file attachments
 */

require_once '../includes/auth.php';
require_once '../includes/helpers.php';

$title = 'إنشاء عقد جديد';
$is_auth_page = false;
$show_sidebar = true;
// Authentication and permission check
$auth->requireAuth();
$user = $auth->getCurrentUser();

// Check permission to create contracts
if (!in_array($user['role'], ['manager', 'admin', 'employee'])) {
    header('Location: contracts_list.php?error=' . urlencode('غير مسموح لك بإنشاء العقود'));
    exit;
}

ob_start();
?>
<div class="container mt-4">
        'second_party_district' => trim($_POST['second_party_district'] ?? ''),
        'second_party_street' => trim($_POST['second_party_street'] ?? ''),
        
        // Contract terms (different defaults based on investment type)
        'profit_percentage' => floatval($_POST['profit_percentage'] ?? (($_POST['investment_type'] ?? 'cash') === 'cash' ? 40 : 30)),
        'contract_duration_months' => intval($_POST['contract_duration_months'] ?? 6),
        'is_renewable' => isset($_POST['is_renewable']) ? 1 : 0,
        'loss_responsibility' => $_POST['loss_responsibility'] ?? 'shared',
        
        // Payment and withdrawal terms
        'withdrawal_notice_days' => intval($_POST['withdrawal_notice_days'] ?? 60),
        'minimum_investment_period_months' => intval($_POST['minimum_investment_period_months'] ?? 6),
        'profit_payment_deadline_days' => intval($_POST['profit_payment_deadline_days'] ?? 15),
        
        // Commission and penalties
        'commission_percentage' => floatval($_POST['commission_percentage'] ?? 2.5),
        'commission_conditions' => trim($_POST['commission_conditions'] ?? ''),
        'penalty_amount' => floatval($_POST['penalty_amount'] ?? 3000),
        'penalty_period_days' => intval($_POST['penalty_period_days'] ?? 30),
        
        // Additional details
        'project_description' => trim($_POST['project_description'] ?? ''),
        'inheritance_clause' => trim($_POST['inheritance_clause'] ?? ''),
        'force_majeure_clause' => trim($_POST['force_majeure_clause'] ?? ''),
        'special_conditions' => trim($_POST['special_conditions'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
        
        // Legacy fields for compatibility
        'contract_type' => $_POST['contract_type'] ?? 'real_estate',
        'contract_duration' => intval($_POST['contract_duration_months'] ?? 6),
        'profit_interval' => $_POST['profit_interval'] ?? 'end_of_contract',
        'is_amendment' => isset($_POST['is_amendment']) ? 1 : 0,
        'parent_contract_id' => intval($_POST['parent_contract_id'] ?? 0),
        'amendment_duration_months' => intval($_POST['contract_duration_months'] ?? 6)
    ];
    
    // Calculate net profit automatically (6 months fixed duration)
    $investment_value = $formData['investment_type'] === 'property' ? $formData['property_market_value'] : $formData['amount'];
    $formData['net_profit'] = ($investment_value * ($formData['profit_percentage'] / 100) / 12) * 6;
    
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
    
    // Validate investment amount based on type
    if ($formData['investment_type'] === 'cash') {
        if ($formData['amount'] < 50000) {
            $errors[] = 'مبلغ العقد يجب أن يكون 50,000 ريال على الأقل (متطلبات سما البنيان)';
        }
    } else { // property
        if ($formData['property_market_value'] < 100000) {
            $errors[] = 'قيمة العقار يجب أن تكون 100,000 ريال على الأقل (متطلبات سما البنيان)';
        }
        if (empty($formData['property_number'])) {
            $errors[] = 'رقم العقار مطلوب للعقود العقارية';
        }
        if (empty($formData['property_location_city'])) {
            $errors[] = 'مدينة العقار مطلوبة';
        }
        if (empty($formData['property_plan_number'])) {
            $errors[] = 'رقم المخطط مطلوب';
        }
    }
    
    if (empty($formData['contract_date'])) {
        $errors[] = 'تاريخ العقد مطلوب';
    }
    
    if ($formData['profit_percentage'] <= 0 || $formData['profit_percentage'] > 50) {
        $errors[] = 'نسبة الربح يجب أن تكون بين 1% و 50%';
    }
    
    // Amendment validation
    if ($formData['is_amendment']) {
        if ($formData['parent_contract_id'] <= 0) {
            $errors[] = 'يجب تحديد العقد الأصلي للتعديل';
        }
    }
    
    // Check for duplicate client ID
    if (!$errors) {
        try {
            $checkStmt = $pdo->prepare("SELECT id FROM contracts WHERE client_id = ? AND status != 'rejected'");
            $checkStmt->execute([$formData['client_id']]);
            if ($checkStmt->fetch()) {
                $errors[] = 'يوجد عقد آخر بنفس رقم الهوية';
            }
        } catch (PDOException $e) {
            $errors[] = 'خطأ في التحقق من البيانات';
        }
    }
    
    if (empty($errors)) {
        try {
            // إنشاء رقم عقد جديد
            if ($formData['is_amendment'] && $formData['parent_contract_id'] > 0) {
                // للتعديلات: الحصول على رقم العقد الأصلي وإنشاء رقم تعديل
                $parentStmt = $pdo->prepare("SELECT contract_number FROM contracts WHERE id = ?");
                $parentStmt->execute([$formData['parent_contract_id']]);
                $parentContract = $parentStmt->fetch();
                
                if ($parentContract) {
                    // الحصول على عدد التعديلات الموجودة لهذا العقد
                    $amendmentCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM contracts WHERE parent_contract_id = ? AND is_amendment = 1");
                    $amendmentCountStmt->execute([$formData['parent_contract_id']]);
                    $amendmentResult = $amendmentCountStmt->fetch();
                    $amendmentNumber = ($amendmentResult['count'] ?? 0) + 1;
                    
                    $contract_number = $parentContract['contract_number'] . '-AMD' . str_pad($amendmentNumber, 2, '0', STR_PAD_LEFT);
                } else {
                    throw new Exception('العقد الأصلي المحدد غير موجود');
                }
            } else {
                // للعقود العادية: استخدام النظام الحالي
                $contractNumberStmt = $pdo->prepare("SELECT MAX(CAST(SUBSTR(contract_number, 10) AS INTEGER)) as max_num FROM contracts WHERE contract_number LIKE 'CON-" . date('Y') . "-%' AND contract_number NOT LIKE '%-AMD%'");
                $contractNumberStmt->execute();
                $result = $contractNumberStmt->fetch();
                $nextNumber = ($result['max_num'] ?? 0) + 1;
                $contract_number = 'CON-' . date('Y') . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
            
            // إدراج العقد في قاعدة البيانات مع جميع الحقول الجديدة
            $insertStmt = $pdo->prepare("
                INSERT INTO contracts (
                    contract_number, client_name, client_id, client_phone, client_email, amount, 
                    investment_amount, second_party_name, second_party_id, second_party_phone, second_party_email,
                    second_party_nationality, second_party_city, second_party_district, second_party_street,
                    profit_percentage, contract_duration, contract_duration_months, profit_interval, 
                    contract_date, hijri_date, location, is_renewable, loss_responsibility,
                    withdrawal_notice_days, minimum_investment_period_months, profit_payment_deadline_days,
                    commission_percentage, commission_conditions, penalty_amount, penalty_period_days,
                    project_description, inheritance_clause, force_majeure_clause, special_conditions,
                    notes, status, created_by, net_profit, is_amendment,
                    parent_contract_id, amendment_duration_months, contract_type, investment_type,
                    property_number, property_location_city, property_location_district, property_plan_number,
                    property_description_detailed, property_market_value, property_exchange_date, property_deed_number,
                    property_area, property_type, profit_distribution_frequency, profit_distribution_months,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
            ");
            
            $insertStmt->execute([
                $contract_number, 
                $formData['client_name'], $formData['client_id'], $formData['client_phone'], $formData['client_email'], 
                $formData['amount'], $formData['investment_amount'],
                $formData['second_party_name'], $formData['second_party_id'], $formData['second_party_phone'], $formData['second_party_email'],
                $formData['second_party_nationality'], $formData['second_party_city'], $formData['second_party_district'], $formData['second_party_street'],
                $formData['profit_percentage'], $formData['contract_duration'], $formData['contract_duration_months'], $formData['profit_interval'],
                $formData['contract_date'], $formData['hijri_date'], $formData['location'], $formData['is_renewable'], $formData['loss_responsibility'],
                $formData['withdrawal_notice_days'], $formData['minimum_investment_period_months'], $formData['profit_payment_deadline_days'],
                $formData['commission_percentage'], $formData['commission_conditions'], $formData['penalty_amount'], $formData['penalty_period_days'],
                $formData['project_description'], $formData['inheritance_clause'], $formData['force_majeure_clause'], $formData['special_conditions'],
                $formData['notes'], $user['id'], $formData['net_profit'], 
                $formData['is_amendment'], $formData['parent_contract_id'], 
                $formData['amendment_duration_months'], $formData['contract_type'], $formData['investment_type'],
                $formData['property_number'], $formData['property_location_city'], $formData['property_location_district'], $formData['property_plan_number'],
                $formData['property_description_detailed'], $formData['property_market_value'], $formData['property_exchange_date'], $formData['property_deed_number'],
                $formData['property_area'], $formData['property_type'], $formData['profit_distribution_frequency'], $formData['profit_distribution_months']
            ]);
            
            $contractId = $pdo->lastInsertId();
            
            // تسجيل النشاط
            $logStmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, description, related_contract_id) 
                VALUES (?, 'create_contract', ?, ?)
            ");
            $logStmt->execute([
                $user['id'], 
                "إنشاء عقد جديد رقم: $contract_number", 
                $contractId
            ]);
            
            $message = "تم إنشاء العقد بنجاح - رقم العقد: $contract_number";
            
            // إعادة تعيين المتغيرات
            $formData = [];
            
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
    <title>إنشاء عقد جديد - سما البنيان للتطوير والاستثمار العقاري</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/static/css/sma-company-theme.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        
        /* Additional custom styles for contract form */
        .form-section {
            margin-bottom: 3rem;
        }
        
        .company-logo-form {
            position: absolute;
            top: 20px;
            right: 20px;
            opacity: 0.1;
            width: 150px;
        }
        
        .amendment-alert {
            background: rgba(243, 156, 18, 0.1);
            border: 2px solid var(--sma-warning);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .minimum-amount-info {
            background: rgba(27, 59, 90, 0.05);
            border: 1px solid var(--sma-primary);
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="form-header text-center">
                    <h2><i class="fas fa-plus-circle"></i> إنشاء عقد جديد</h2>
                    <p>أدخل بيانات العقد والشريك لإنشاء عقد استثمار جديد</p>
                </div>
            </div>
        </div>
        
        <!-- رسائل النجاح والخطأ -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- عرض معلومات المستخدم -->
        <div class="alert alert-info">
            <i class="fas fa-user me-2"></i>مرحباً <strong><?= htmlspecialchars($user['name']) ?></strong> - سيتم حفظ العقد كمسودة ويمكنك إرساله للمراجعة لاحقاً
        </div>
        
        <form method="POST">
            <!-- النموذج الجديد وفقاً للعقد الرسمي -->
            <div class="row">
                <!-- بيانات الطرف الثاني (العميل) -->
                <div class="col-md-6">
                    <div class="form-card">
                        <h4 class="section-title">
                            <i class="fas fa-user"></i> بيانات الطرف الثاني (العميل)
                        </h4>
                        
                        <div class="mb-3">
                            <label for="client_name" class="form-label">
                                <i class="fas fa-user"></i> الاسم الكامل *
                            </label>
                            <input type="text" class="form-control" id="client_name" name="client_name" 
                                   value="<?php echo htmlspecialchars($formData['client_name'] ?? ''); ?>" 
                                   placeholder="الاسم الثلاثي أو الرباعي كاملاً" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="second_party_nationality" class="form-label">
                                <i class="fas fa-flag"></i> الجنسية
                            </label>
                            <select class="form-select" id="second_party_nationality" name="second_party_nationality">
                                <option value="سعودي الجنسية" <?php echo ($formData['second_party_nationality'] ?? 'سعودي الجنسية') === 'سعودي الجنسية' ? 'selected' : ''; ?>>سعودي الجنسية</option>
                                <option value="مقيم" <?php echo ($formData['second_party_nationality'] ?? '') === 'مقيم' ? 'selected' : ''; ?>>مقيم</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="client_id" class="form-label">
                                <i class="fas fa-id-card"></i> رقم الهوية الوطنية/الإقامة *
                            </label>
                            <input type="text" class="form-control" id="client_id" name="client_id" 
                                   value="<?php echo htmlspecialchars($formData['client_id'] ?? ''); ?>" 
                                   placeholder="10 أرقام للهوية الوطنية" 
                                   pattern="[0-9]{10}" title="رقم الهوية يجب أن يكون 10 أرقام" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="client_phone" class="form-label">
                                <i class="fas fa-phone"></i> رقم الجوال *
                            </label>
                            <input type="tel" class="form-control" id="client_phone" name="client_phone" 
                                   value="<?php echo htmlspecialchars($formData['client_phone'] ?? ''); ?>" 
                                   placeholder="05xxxxxxxx" 
                                   pattern="05[0-9]{8}" title="رقم الجوال يجب أن يبدأ بـ 05 ويكون 10 أرقام" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="client_email" class="form-label">
                                <i class="fas fa-envelope"></i> البريد الإلكتروني (اختياري)
                            </label>
                            <input type="email" class="form-control" id="client_email" name="client_email" 
                                   value="<?php echo htmlspecialchars($formData['client_email'] ?? ''); ?>" 
                                   placeholder="example@email.com">
                        </div>
                        
                        <h5 class="mt-4 mb-3" style="color: #d4af37;">📍 عنوان السكن</h5>
                        
                        <div class="row">
                            <div class="col-6">
                                <label for="second_party_city" class="form-label">المدينة</label>
                                <input type="text" class="form-control" id="second_party_city" name="second_party_city" 
                                       value="<?php echo htmlspecialchars($formData['second_party_city'] ?? 'جدة'); ?>" 
                                       placeholder="المدينة">
                            </div>
                            <div class="col-6">
                                <label for="second_party_district" class="form-label">الحي</label>
                                <input type="text" class="form-control" id="second_party_district" name="second_party_district" 
                                       value="<?php echo htmlspecialchars($formData['second_party_district'] ?? ''); ?>" 
                                       placeholder="اسم الحي">
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label for="second_party_street" class="form-label">الشارع</label>
                            <input type="text" class="form-control" id="second_party_street" name="second_party_street" 
                                   value="<?php echo htmlspecialchars($formData['second_party_street'] ?? ''); ?>" 
                                   placeholder="اسم الشارع">
                        </div>
                    </div>
                </div>
                
                <!-- تفاصيل العقد والمضاربة -->
                <div class="col-md-6">
                    <div class="form-card">
                        <h4 class="section-title">
                            <i class="fas fa-file-contract"></i> تفاصيل العقد والمضاربة
                        </h4>
                        
                        <div class="mb-3">
                            <label for="contract_date" class="form-label">
                                <i class="fas fa-calendar"></i> تاريخ العقد (ميلادي) *
                            </label>
                            <input type="date" class="form-control" id="contract_date" name="contract_date" 
                                   value="<?php echo $formData['contract_date'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="hijri_date" class="form-label">
                                <i class="fas fa-calendar-alt"></i> التاريخ الهجري
                            </label>
                            <input type="text" class="form-control" id="hijri_date" name="hijri_date" 
                                   value="<?php echo htmlspecialchars($formData['hijri_date'] ?? ''); ?>" 
                                   placeholder="مثال: 9-03-1447هـ">
                        </div>
                        
                        <!-- نوع الاستثمار -->
                        <div class="mb-3">
                            <label for="investment_type" class="form-label">
                                <i class="fas fa-coins"></i> نوع الاستثمار *
                            </label>
                            <select class="form-select" id="investment_type" name="investment_type" onchange="toggleInvestmentFields()" required>
                                <option value="cash" <?php echo ($formData['investment_type'] ?? 'cash') === 'cash' ? 'selected' : ''; ?>>استثمار نقدي</option>
                                <option value="property" <?php echo ($formData['investment_type'] ?? '') === 'property' ? 'selected' : ''; ?>>استثمار عقاري</option>
                            </select>
                            <div class="form-text">اختر نوع الاستثمار (نقدي أو عقاري)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">
                                <i class="fas fa-map-marker-alt"></i> مكان التعاقد
                            </label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($formData['location'] ?? 'محافظة جدة'); ?>" 
                                   placeholder="محافظة جدة">
                        </div>
                        
                        <!-- حقول الاستثمار النقدي -->
                        <div id="cash_investment_fields" style="display: block;">
                            <div class="mb-3">
                                <label for="amount" class="form-label">
                                    <i class="fas fa-money-bill-wave"></i> مبلغ المضاربة النقدية (ريال سعودي) *
                                </label>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                       value="<?php echo $formData['amount'] ?? ''; ?>" 
                                       placeholder="مثال: 100,000" min="50000">
                                <div class="form-text text-info">
                                    <i class="fas fa-info-circle"></i>
                                    الحد الأدنى: 50,000 ريال سعودي - نسبة الربح: 40%
                                </div>
                            </div>
                        </div>
                        
                        <!-- حقول الاستثمار العقاري -->
                        <div id="property_investment_fields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="property_number" class="form-label">
                                            <i class="fas fa-home"></i> رقم العقار *
                                        </label>
                                        <input type="text" class="form-control" id="property_number" name="property_number" 
                                               value="<?php echo htmlspecialchars($formData['property_number'] ?? ''); ?>" 
                                               placeholder="مثال: 220204019361">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="property_plan_number" class="form-label">
                                            <i class="fas fa-map"></i> رقم المخطط *
                                        </label>
                                        <input type="text" class="form-control" id="property_plan_number" name="property_plan_number" 
                                               value="<?php echo htmlspecialchars($formData['property_plan_number'] ?? ''); ?>" 
                                               placeholder="رقم المخطط العقاري">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="property_location_city" class="form-label">
                                            <i class="fas fa-city"></i> مدينة العقار *
                                        </label>
                                        <input type="text" class="form-control" id="property_location_city" name="property_location_city" 
                                               value="<?php echo htmlspecialchars($formData['property_location_city'] ?? ''); ?>" 
                                               placeholder="مثال: ثول">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="property_location_district" class="form-label">
                                            <i class="fas fa-building"></i> حي العقار
                                        </label>
                                        <input type="text" class="form-control" id="property_location_district" name="property_location_district" 
                                               value="<?php echo htmlspecialchars($formData['property_location_district'] ?? ''); ?>" 
                                               placeholder="اسم الحي">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="property_market_value" class="form-label">
                                            <i class="fas fa-money-bill-wave"></i> قيمة العقار السوقية (ريال) *
                                        </label>
                                        <input type="number" step="0.01" class="form-control" id="property_market_value" name="property_market_value" 
                                               value="<?php echo $formData['property_market_value'] ?? ''; ?>" 
                                               placeholder="مثال: 400,000" min="100000">
                                        <div class="form-text text-info">
                                            <i class="fas fa-info-circle"></i>
                                            الحد الأدنى: 100,000 ريال - نسبة الربح: 30%
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="property_area" class="form-label">
                                            <i class="fas fa-ruler-combined"></i> مساحة العقار (متر مربع)
                                        </label>
                                        <input type="number" step="0.01" class="form-control" id="property_area" name="property_area" 
                                               value="<?php echo $formData['property_area'] ?? ''; ?>" 
                                               placeholder="مثال: 500">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="property_description_detailed" class="form-label">
                                    <i class="fas fa-clipboard-list"></i> وصف العقار التفصيلي
                                </label>
                                <textarea class="form-control" id="property_description_detailed" name="property_description_detailed" rows="3" 
                                          placeholder="اكتب وصفاً مفصلاً للعقار (النوع، الخصائص، الموقع)"><?php echo htmlspecialchars($formData['property_description_detailed'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="property_deed_number" class="form-label">
                                            <i class="fas fa-file-alt"></i> رقم الصك
                                        </label>
                                        <input type="text" class="form-control" id="property_deed_number" name="property_deed_number" 
                                               value="<?php echo htmlspecialchars($formData['property_deed_number'] ?? ''); ?>" 
                                               placeholder="رقم صك العقار">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="property_exchange_date" class="form-label">
                                            <i class="fas fa-calendar"></i> تاريخ نقل العقار
                                        </label>
                                        <input type="date" class="form-control" id="property_exchange_date" name="property_exchange_date" 
                                               value="<?php echo $formData['property_exchange_date'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profit_distribution_frequency" class="form-label">
                                    <i class="fas fa-calendar-check"></i> توزيع الأرباح
                                </label>
                                <select class="form-select" id="profit_distribution_frequency" name="profit_distribution_frequency">
                                    <option value="end_of_contract" <?php echo ($formData['profit_distribution_frequency'] ?? 'end_of_contract') === 'end_of_contract' ? 'selected' : ''; ?>>نهاية العقد</option>
                                    <option value="bimonthly" <?php echo ($formData['profit_distribution_frequency'] ?? '') === 'bimonthly' ? 'selected' : ''; ?>>كل شهرين</option>
                                    <option value="quarterly" <?php echo ($formData['profit_distribution_frequency'] ?? '') === 'quarterly' ? 'selected' : ''; ?>>كل 3 أشهر</option>
                                </select>
                                <div class="form-text">للعقود العقارية يفضل التوزيع كل شهرين</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <label for="profit_percentage" class="form-label">
                                    <i class="fas fa-percentage"></i> نسبة الربح *
                                </label>
                                <input type="number" step="0.1" class="form-control" id="profit_percentage" name="profit_percentage" 
                                       value="<?php echo $formData['profit_percentage'] ?? '40'; ?>" 
                                       placeholder="40" min="1" max="50" required>
                                <div class="form-text">الافتراضي: 40%</div>
                            </div>
                            <div class="col-6">
                                <label for="contract_duration_months" class="form-label">
                                    <i class="fas fa-clock"></i> مدة العقد (شهر)
                                </label>
                                <select class="form-select" id="contract_duration_months" name="contract_duration_months">
                                    <option value="6" <?php echo ($formData['contract_duration_months'] ?? 6) == 6 ? 'selected' : ''; ?>>6 أشهر</option>
                                    <option value="12" <?php echo ($formData['contract_duration_months'] ?? 6) == 12 ? 'selected' : ''; ?>>12 شهر</option>
                                    <option value="18" <?php echo ($formData['contract_duration_months'] ?? 6) == 18 ? 'selected' : ''; ?>>18 شهر</option>
                                    <option value="24" <?php echo ($formData['contract_duration_months'] ?? 6) == 24 ? 'selected' : ''; ?>>24 شهر</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-6">
                                <label for="withdrawal_notice_days" class="form-label">
                                    <i class="fas fa-bell"></i> فترة إخطار الانسحاب (يوم)
                                </label>
                                <input type="number" class="form-control" id="withdrawal_notice_days" name="withdrawal_notice_days" 
                                       value="<?php echo $formData['withdrawal_notice_days'] ?? '60'; ?>" 
                                       placeholder="60" min="30" max="120">
                            </div>
                            <div class="col-6">
                                <label for="profit_payment_deadline_days" class="form-label">
                                    <i class="fas fa-calendar-check"></i> مهلة دفع الأرباح (يوم)
                                </label>
                                <input type="number" class="form-control" id="profit_payment_deadline_days" name="profit_payment_deadline_days" 
                                       value="<?php echo $formData['profit_payment_deadline_days'] ?? '15'; ?>" 
                                       placeholder="15" min="1" max="30">
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label class="form-label">
                                <i class="fas fa-sync-alt"></i> قابلية التجديد
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_renewable" name="is_renewable" 
                                       <?php echo ($formData['is_renewable'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_renewable">
                                    العقد قابل للتجديد
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            سيتم توليد رقم العقد تلقائياً عند الحفظ
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- القسم الثالث: العمولة والشرط الجزائي -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="form-card">
                        <h4 class="section-title">
                            <i class="fas fa-percent"></i> العمولة والحوافز
                        </h4>
                        
                        <div class="mb-3">
                            <label for="commission_percentage" class="form-label">
                                <i class="fas fa-percentage"></i> نسبة العمولة
                            </label>
                            <input type="number" step="0.1" class="form-control" id="commission_percentage" name="commission_percentage" 
                                   value="<?php echo $formData['commission_percentage'] ?? '2.5'; ?>" 
                                   placeholder="2.5" min="0" max="10">
                            <div class="form-text">الافتراضي: 2.5%</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="commission_conditions" class="form-label">
                                <i class="fas fa-list-ul"></i> شروط العمولة
                            </label>
                            <textarea class="form-control" id="commission_conditions" name="commission_conditions" rows="3" 
                                      placeholder="اكتب شروط العمولة إن وجدت"><?php echo htmlspecialchars($formData['commission_conditions'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-card">
                        <h4 class="section-title">
                            <i class="fas fa-exclamation-triangle"></i> الشرط الجزائي
                        </h4>
                        
                        <div class="mb-3">
                            <label for="penalty_amount" class="form-label">
                                <i class="fas fa-money-bill"></i> مبلغ الشرط الجزائي (ريال)
                            </label>
                            <input type="number" step="0.01" class="form-control" id="penalty_amount" name="penalty_amount" 
                                   value="<?php echo $formData['penalty_amount'] ?? '3000'; ?>" 
                                   placeholder="3000" min="0">
                            <div class="form-text">الافتراضي: 3,000 ريال</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="penalty_period_days" class="form-label">
                                <i class="fas fa-calendar"></i> فترة الشرط الجزائي (يوم)
                            </label>
                            <input type="number" class="form-control" id="penalty_period_days" name="penalty_period_days" 
                                   value="<?php echo $formData['penalty_period_days'] ?? '30'; ?>" 
                                   placeholder="30" min="1">
                            <div class="form-text">كل 30 يوم تأخير</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- القسم الرابع: التفاصيل الإضافية والملاحظات -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="form-card">
                        <h4 class="section-title">
                            <i class="fas fa-clipboard-list"></i> التفاصيل الإضافية والشروط الخاصة
                        </h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="project_description" class="form-label">
                                        <i class="fas fa-building"></i> وصف المشروع
                                    </label>
                                    <textarea class="form-control" id="project_description" name="project_description" rows="3" 
                                              placeholder="وصف تفصيلي للمشروع العقاري"><?php echo htmlspecialchars($formData['project_description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="loss_responsibility" class="form-label">
                                        <i class="fas fa-shield-alt"></i> مسؤولية الخسائر
                                    </label>
                                    <select class="form-select" id="loss_responsibility" name="loss_responsibility">
                                        <option value="first_party" <?php echo ($formData['loss_responsibility'] ?? 'first_party') === 'first_party' ? 'selected' : ''; ?>>الطرف الأول يتحمل الخسائر</option>
                                        <option value="shared" <?php echo ($formData['loss_responsibility'] ?? '') === 'shared' ? 'selected' : ''; ?>>خسائر مشتركة</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="inheritance_clause" class="form-label">
                                        <i class="fas fa-users"></i> بند الوراثة
                                    </label>
                                    <textarea class="form-control" id="inheritance_clause" name="inheritance_clause" rows="2" 
                                              placeholder="تفاصيل انتقال العقد للورثة"><?php echo htmlspecialchars($formData['inheritance_clause'] ?? 'في حالة الوفاة، تنتقل النسبة إلى الورثة حسب الشريعة الإسلامية'); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="force_majeure_clause" class="form-label">
                                        <i class="fas fa-exclamation-circle"></i> بند القوة القاهرة
                                    </label>
                                    <textarea class="form-control" id="force_majeure_clause" name="force_majeure_clause" rows="2" 
                                              placeholder="شروط التعامل مع الظروف الاستثنائية"><?php echo htmlspecialchars($formData['force_majeure_clause'] ?? 'في حال حدوث كوارث طبيعية أو جوائح كونية، يتم مراعاة ذلك من الطرفين'); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="special_conditions" class="form-label">
                                <i class="fas fa-edit"></i> شروط خاصة
                            </label>
                            <textarea class="form-control" id="special_conditions" name="special_conditions" rows="3" 
                                      placeholder="أي شروط خاصة أو تفاصيل إضافية متفق عليها"><?php echo htmlspecialchars($formData['special_conditions'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note"></i> ملاحظات إدارية
                            </label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" 
                                      placeholder="ملاحظات داخلية للإدارة"><?php echo htmlspecialchars($formData['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- قسم التأكيد والحفظ -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="form-card">
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-info-circle"></i> معلومات مهمة:</h5>
                            <ul class="mb-0">
                                <li>جميع العقود تخضع لأحكام الشريعة الإسلامية</li>
                                <li>تم إعداد هذا النموذج وفقاً للعقد الرسمي لشركة سما البنيان</li>
                                <li>سيتم مراجعة العقد من قبل الإدارة قبل الاعتماد</li>
                                <li>المستشار القانوني: مكتب المحامي بشير بن عبد الله صديق كنسارة</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-shield-alt"></i> 
                            <strong>تأكيد إنشاء العقد:</strong><br>
                            بإنشاء هذا العقد، أؤكد أن جميع البيانات صحيحة وأوافق على الشروط والأحكام المنصوص عليها في العقد الرسمي لشركة سما البنيان.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- حقول مخفية للتوافق مع النظام القديم -->
            <input type="hidden" name="contract_type" value="real_estate">
            <input type="hidden" name="amount" id="hidden_amount">
            <input type="hidden" name="contract_duration" value="6">
            <input type="hidden" name="profit_interval" value="end_of_contract">
            


            <!-- حقول الشركة الجديدة -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-card">
                        <h4 class="section-title">
                            <i class="fas fa-chart-bar"></i> تفاصيل الربحية (متطلبات سما البنيان)
                        </h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="net-profit-field mb-3">
                                    <label for="net_profit_display" class="form-label">
                                        <i class="fas fa-money-check-alt"></i> صافي الربح المحسوب تلقائياً (ريال سعودي)
                                    </label>
                                    <input type="text" class="form-control sma-highlight" id="net_profit_display" readonly>
                                    <input type="hidden" id="net_profit" name="net_profit" value="0">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-calculator"></i> يتم حساب صافي الربح تلقائياً بناءً على المبلغ ونسبة الربح لمدة 6 أشهر
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-edit"></i> نوع العقد
                                    </label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_amendment" 
                                               name="is_amendment" value="1" 
                                               <?php echo ($formData['is_amendment'] ?? '') ? 'checked' : ''; ?>
                                               onchange="toggleAmendmentFields()">
                                        <label class="form-check-label" for="is_amendment">
                                            <span class="badge bg-warning text-dark">تعديل على عقد موجود</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- حقول التعديل (تظهر فقط عند اختيار التعديل) -->
                        <div id="amendment_fields" style="display: none;">
                            <div class="amendment-alert">
                                <h6><i class="fas fa-exclamation-triangle"></i> متطلبات تعديل العقد</h6>
                                <ul>
                                    <li>مدة التعديل: 6 أشهر فقط (غير قابلة للتغيير)</li>
                                    <li>الحد الأدنى للمبلغ: 50,000 ريال سعودي</li>
                                    <li>يجب تحديد العقد الأصلي للتعديل</li>
                                </ul>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="parent_contract_id" class="form-label">
                                            <i class="fas fa-link"></i> رقم العقد الأصلي *
                                        </label>
                                        <input type="number" class="form-control" id="parent_contract_id" 
                                               name="parent_contract_id" 
                                               value="<?php echo $formData['parent_contract_id'] ?? ''; ?>"
                                               placeholder="أدخل رقم العقد المراد تعديله">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amendment_duration_months" class="form-label">
                                            <i class="fas fa-calendar-alt"></i> مدة التعديل (أشهر)
                                        </label>
                                        <input type="number" class="form-control" id="amendment_duration_months" 
                                               name="amendment_duration_months" value="6" readonly
                                               style="background-color: #f8f9fa;">
                                        <small class="form-text text-muted">
                                            <i class="fas fa-lock"></i> مدة ثابتة حسب سياسة الشركة
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Employee Submission Workflow -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-paper-plane me-2"></i>سير العمل لتقديم العقد</h5>
                        <p><strong>للموظفين:</strong> بعد إنشاء العقد، سيتم حفظه كمسودة ويمكنك:</p>
                        <ul class="mb-2">
                            <li>مراجعة البيانات والتأكد من صحتها</li>
                            <li>تحميل العقد بصيغة PDF بعد الإنشاء</li>
                            <li><strong>إرسال العقد للمدير للمراجعة والموافقة</strong></li>
                        </ul>
                        <div class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            ملاحظة: العقد سيظل في حالة "مسودة" حتى يتم اعتماده من المدير
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn sma-btn-primary btn-lg me-2">
                    <i class="fas fa-save"></i> إنشاء العقد
                </button>
                <a href="/contracts_list.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-arrow-right"></i> إلغاء
                </a>
            </div>
        </form>
    </div>
    
    <!-- Company Logo -->
    <div class="company-watermark">
        <img src="static/img/SMA-LOGO.png" alt="شعار شركة سما البنيان" style="width: 150px; opacity: 0.3;" onerror="this.style.display='none'">
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-calculate net profit
        function calculateNetProfit() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const profitPercentage = parseFloat(document.getElementById('profit_percentage').value) || 0;
            
            if (amount > 0 && profitPercentage > 0) {
                const monthlyProfit = (amount * profitPercentage / 100) / 12;
                const netProfit = monthlyProfit * 6; // 6 months fixed duration
                
                document.getElementById('net_profit_display').value = netProfit.toLocaleString('ar-SA', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' ريال';
                
                document.getElementById('net_profit').value = netProfit;
            } else {
                document.getElementById('net_profit_display').value = '';
                document.getElementById('net_profit').value = '0';
            }
        }

        // Toggle amendment fields visibility
        function toggleAmendmentFields() {
            const checkbox = document.getElementById('is_amendment');
            const amendmentFields = document.getElementById('amendment_fields');
            const amountField = document.getElementById('amount');
            
            if (checkbox.checked) {
                amendmentFields.style.display = 'block';
                // Ensure minimum amount for amendments
                amountField.min = '50000';
                amountField.placeholder = 'الحد الأدنى للتعديل: 50,000 ريال';
            } else {
                amendmentFields.style.display = 'none';
                amountField.min = '50000';
                amountField.placeholder = 'أدخل مبلغ الاستثمار';
            }
        }
        
        // Sync investment amount with legacy amount field
        function syncAmountFields() {
            const investmentAmount = document.getElementById('investment_amount').value;
            const hiddenAmount = document.getElementById('hidden_amount');
            if (hiddenAmount) {
                hiddenAmount.value = investmentAmount;
            }
        }
        
        // Calculate expected profit and total return
        function calculateProfits() {
            const amount = parseFloat(document.getElementById('investment_amount').value) || 0;
            const profitPercent = parseFloat(document.getElementById('profit_percentage').value) || 40;
            const duration = parseInt(document.getElementById('contract_duration_months').value) || 6;
            
            if (amount > 0) {
                // Calculate profit for the contract duration
                const expectedProfit = (amount * profitPercent / 100);
                const totalReturn = amount + expectedProfit;
                
                // Display calculations in a summary
                updateProfitSummary(amount, expectedProfit, totalReturn, duration);
            }
        }
        
        // Update profit summary display
        function updateProfitSummary(principal, profit, total, months) {
            // Create or update summary element
            let summaryElement = document.getElementById('profit-summary');
            if (!summaryElement) {
                summaryElement = document.createElement('div');
                summaryElement.id = 'profit-summary';
                summaryElement.className = 'alert alert-info mt-3';
                
                const investmentField = document.getElementById('investment_amount');
                investmentField.parentNode.appendChild(summaryElement);
            }
            
            summaryElement.innerHTML = `
                <h6><i class="fas fa-calculator"></i> ملخص الاستثمار:</h6>
                <div class="row">
                    <div class="col-4">
                        <strong>رأس المال:</strong><br>
                        <span class="text-primary">${principal.toLocaleString()} ريال</span>
                    </div>
                    <div class="col-4">
                        <strong>الربح المتوقع:</strong><br>
                        <span class="text-success">${profit.toLocaleString()} ريال</span>
                    </div>
                    <div class="col-4">
                        <strong>إجمالي العائد:</strong><br>
                        <span class="text-warning">${total.toLocaleString()} ريال</span>
                    </div>
                </div>
                <small class="text-muted">لمدة ${months} شهر</small>
            `;
        }
        
        // Toggle investment type fields (cash vs property)
        function toggleInvestmentFields() {
            const investmentType = document.getElementById('investment_type').value;
            const cashFields = document.getElementById('cash_investment_fields');
            const propertyFields = document.getElementById('property_investment_fields');
            const profitPercentField = document.getElementById('profit_percentage');
            
            if (investmentType === 'cash') {
                cashFields.style.display = 'block';
                propertyFields.style.display = 'none';
                profitPercentField.value = '40'; // نسبة الربح للعقود النقدية
                
                // Make cash fields required
                document.getElementById('amount').required = true;
                document.getElementById('property_number').required = false;
                document.getElementById('property_location_city').required = false;
                document.getElementById('property_plan_number').required = false;
                document.getElementById('property_market_value').required = false;
                
            } else { // property
                cashFields.style.display = 'none';
                propertyFields.style.display = 'block';
                profitPercentField.value = '30'; // نسبة الربح للعقود العقارية
                
                // Make property fields required
                document.getElementById('amount').required = false;
                document.getElementById('property_number').required = true;
                document.getElementById('property_location_city').required = true;
                document.getElementById('property_plan_number').required = true;
                document.getElementById('property_market_value').required = true;
            }
            
            // Recalculate profits after changing type
            calculateProfits();
        }
        
        // Update profit calculations for both investment types
        function calculateProfits() {
            const investmentType = document.getElementById('investment_type').value;
            let principal = 0;
            
            if (investmentType === 'cash') {
                principal = parseFloat(document.getElementById('amount').value) || 0;
            } else {
                principal = parseFloat(document.getElementById('property_market_value').value) || 0;
            }
            
            const profitPercent = parseFloat(document.getElementById('profit_percentage').value) || 0;
            const duration = parseInt(document.getElementById('contract_duration_months').value) || 6;
            
            if (principal > 0 && profitPercent > 0) {
                const expectedProfit = (principal * profitPercent / 100 / 12) * duration;
                const totalReturn = principal + expectedProfit;
                
                updateProfitSummary(principal, expectedProfit, totalReturn, duration, investmentType);
            }
        }
        
        // Update profit summary display with investment type info
        function updateProfitSummary(principal, profit, total, months, investmentType) {
            let summaryElement = document.getElementById('profit-summary');
            if (!summaryElement) {
                summaryElement = document.createElement('div');
                summaryElement.id = 'profit-summary';
                summaryElement.className = 'alert alert-success mt-3';
                
                const targetField = investmentType === 'cash' ? 
                    document.getElementById('amount') : 
                    document.getElementById('property_market_value');
                targetField.parentNode.appendChild(summaryElement);
            }
            
            const investmentTypeText = investmentType === 'cash' ? 'الاستثمار النقدي' : 'الاستثمار العقاري';
            const profitRate = investmentType === 'cash' ? '40%' : '30%';
            
            summaryElement.innerHTML = `
                <h6><i class="fas fa-calculator"></i> ملخص ${investmentTypeText}:</h6>
                <div class="row">
                    <div class="col-3">
                        <strong>قيمة الاستثمار:</strong><br>
                        <span class="text-primary">${principal.toLocaleString()} ريال</span>
                    </div>
                    <div class="col-3">
                        <strong>نسبة الربح:</strong><br>
                        <span class="text-info">${profitRate}</span>
                    </div>
                    <div class="col-3">
                        <strong>الربح المتوقع:</strong><br>
                        <span class="text-success">${profit.toLocaleString()} ريال</span>
                    </div>
                    <div class="col-3">
                        <strong>إجمالي العائد:</strong><br>
                        <span class="text-warning">${total.toLocaleString()} ريال</span>
                    </div>
                </div>
                <small class="text-muted">لمدة ${months} شهر - ${investmentTypeText}</small>
            `;
        }

        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize investment type fields display
            toggleInvestmentFields();
            
            // Initialize amendment fields display
            toggleAmendmentFields();
            
            // Add event listeners for automatic calculations
            const amountField = document.getElementById('amount');
            const propertyValueField = document.getElementById('property_market_value');
            const profitPercentField = document.getElementById('profit_percentage');
            const durationField = document.getElementById('contract_duration_months');
            const investmentTypeField = document.getElementById('investment_type');
            
            if (amountField) {
                amountField.addEventListener('input', calculateProfits);
            }
            
            if (propertyValueField) {
                propertyValueField.addEventListener('input', calculateProfits);
            }
            
            if (profitPercentField) {
                profitPercentField.addEventListener('input', calculateProfits);
            }
            
            if (durationField) {
                durationField.addEventListener('change', calculateProfits);
            }
            
            if (investmentTypeField) {
                investmentTypeField.addEventListener('change', toggleInvestmentFields);
            }
            
            // Initial calculations
            calculateProfits();
            
            // Form validation for new contract format
            document.querySelector('form').addEventListener('submit', function(e) {
                const investmentType = document.getElementById('investment_type').value;
                const clientName = document.getElementById('client_name').value.trim();
                const clientId = document.getElementById('client_id').value.trim();
                const clientPhone = document.getElementById('client_phone').value.trim();
                const profitPercent = parseFloat(document.getElementById('profit_percentage').value) || 0;
                
                let investmentAmount = 0;
                
                // Validate investment amount based on type
                if (investmentType === 'cash') {
                    investmentAmount = parseFloat(document.getElementById('amount').value) || 0;
                    if (investmentAmount < 50000) {
                        e.preventDefault();
                        alert('مبلغ المضاربة النقدية يجب أن يكون 50,000 ريال على الأقل وفقاً لمتطلبات شركة سما البنيان');
                        document.getElementById('amount').focus();
                        return false;
                    }
                } else { // property
                    investmentAmount = parseFloat(document.getElementById('property_market_value').value) || 0;
                    if (investmentAmount < 100000) {
                        e.preventDefault();
                        alert('قيمة العقار يجب أن تكون 100,000 ريال على الأقل وفقاً لمتطلبات شركة سما البنيان');
                        document.getElementById('property_market_value').focus();
                        return false;
                    }
                    
                    // Validate property-specific fields
                    const propertyNumber = document.getElementById('property_number').value.trim();
                    const propertyCity = document.getElementById('property_location_city').value.trim();
                    const propertyPlan = document.getElementById('property_plan_number').value.trim();
                    
                    if (!propertyNumber) {
                        e.preventDefault();
                        alert('رقم العقار مطلوب للعقود العقارية');
                        document.getElementById('property_number').focus();
                        return false;
                    }
                    
                    if (!propertyCity) {
                        e.preventDefault();
                        alert('مدينة العقار مطلوبة');
                        document.getElementById('property_location_city').focus();
                        return false;
                    }
                    
                    if (!propertyPlan) {
                        e.preventDefault();
                        alert('رقم المخطط مطلوب');
                        document.getElementById('property_plan_number').focus();
                        return false;
                    }
                }
                
                // Validate client name
                if (clientName.length < 3) {
                    e.preventDefault();
                    alert('اسم العميل يجب أن يكون 3 أحرف على الأقل');
                    document.getElementById('client_name').focus();
                    return false;
                }
                
                // Validate client ID
                if (!/^[0-9]{10}$/.test(clientId)) {
                    e.preventDefault();
                    alert('رقم الهوية يجب أن يكون 10 أرقام بالضبط');
                    document.getElementById('client_id').focus();
                    return false;
                }
                
                // Validate phone number
                if (!/^05[0-9]{8}$/.test(clientPhone)) {
                    e.preventDefault();
                    alert('رقم الجوال غير صحيح (يجب أن يبدأ بـ 05 ويكون 10 أرقام)');
                    document.getElementById('client_phone').focus();
                    return false;
                }
                
                // Validate profit percentage
                if (profitPercent <= 0 || profitPercent > 50) {
                    e.preventDefault();
                    alert('نسبة الربح يجب أن تكون بين 1% و 50%');
                    document.getElementById('profit_percentage').focus();
                    return false;
                }
                
                // Final confirmation
                const investmentTypeText = investmentType === 'cash' ? 'النقدي' : 'العقاري';
                const investmentDetails = investmentType === 'cash' 
                    ? `• مبلغ المضاربة: ${investmentAmount.toLocaleString()} ريال`
                    : `• قيمة العقار: ${investmentAmount.toLocaleString()} ريال\n• رقم العقار: ${document.getElementById('property_number').value}\n• المدينة: ${document.getElementById('property_location_city').value}`;
                    
                const confirmation = confirm(`
تأكيد إنشاء عقد المضاربة ${investmentTypeText}:
• العميل: ${clientName}
• رقم الهوية: ${clientId}
${investmentDetails}
• نسبة الربح: ${profitPercent}%
• الربح المتوقع: ${(investmentAmount * profitPercent / 100 / 12 * 6).toLocaleString()} ريال

هل تريد المتابعة؟
                `);
                
                if (!confirmation) {
                    e.preventDefault();
                    return false;
                }
                
                return true;
            });
        });
        
        // Add fade-in animation to form cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.form-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('fade-in');
                }, index * 200);
            });
        });
    </script>
<?php
$content = ob_get_clean();
$additional_scripts = '<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>';
include __DIR__ . '/../templates/master_layout.php';