<?php
require_once '../includes/auth.php';

// التحقق من صلاحية إنشاء العقود
$auth->requirePermission('create_contracts');
$user = $auth->getCurrentUser();

$message = '';
$error = '';

// معالجة إنشاء العقد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = trim($_POST['client_name'] ?? '');
    $client_id = trim($_POST['client_id'] ?? '');
    $client_phone = trim($_POST['client_phone'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $contract_date = $_POST['contract_date'] ?? date('Y-m-d');
    $signature_method = $_POST['signature_method'] ?? 'electronic';
    $profit_percentage = floatval($_POST['profit_percentage'] ?? 30);
    $contract_duration = 6; // Fixed at 6 months per company policy
    $profit_interval = $_POST['profit_interval'] ?? 'monthly';
    $notes = trim($_POST['notes'] ?? '');
    
    // Calculate net profit automatically
    $monthly_profit = ($amount * $profit_percentage / 100) / 12;
    $net_profit = $monthly_profit * $contract_duration;
    $is_amendment = isset($_POST['is_amendment']) ? 1 : 0;
    $parent_contract_id = intval($_POST['parent_contract_id'] ?? 0);
    $amendment_duration_months = intval($_POST['amendment_duration_months'] ?? 6);
    
    $errors = [];
    
    // التحقق من صحة البيانات الأساسية
    if (empty($client_name)) $errors[] = 'اسم العميل مطلوب';
    if (empty($client_id) || !preg_match('/^[0-9]{10}$/', $client_id)) $errors[] = 'رقم الهوية يجب أن يكون 10 أرقام';
    if (empty($client_phone) || !preg_match('/^05[0-9]{8}$/', $client_phone)) $errors[] = 'رقم الجوال غير صحيح (يجب أن يبدأ بـ 05 ويكون 10 أرقام)';
    if ($amount < 50000) $errors[] = 'مبلغ العقد يجب أن يكون 50,000 ريال على الأقل (متطلبات الشركة)';
    if (empty($contract_date)) $errors[] = 'تاريخ العقد مطلوب';
    if ($profit_percentage <= 0) $errors[] = 'نسبة الربح يجب أن تكون أكبر من صفر';
    if ($net_profit < 0) $errors[] = 'صافي الربح لا يمكن أن يكون سالب';
    
    // التحقق من متطلبات التعديل
    if ($is_amendment) {
        if ($parent_contract_id <= 0) $errors[] = 'يجب تحديد العقد الأصلي للتعديل';
        if ($amendment_duration_months != 6) $errors[] = 'مدة التعديل يجب أن تكون 6 أشهر فقط (متطلبات الشركة)';
        if ($amount < 50000) $errors[] = 'تعديل العقد يتطلب مبلغ 50,000 ريال كحد أدنى';
    }
    
    if (empty($errors)) {
        try {
            // إنشاء رقم عقد جديد
            if ($is_amendment && $parent_contract_id > 0) {
                // للتعديلات: الحصول على رقم العقد الأصلي وإنشاء رقم تعديل
                $parentStmt = $pdo->prepare("SELECT contract_number FROM contracts WHERE id = ?");
                $parentStmt->execute([$parent_contract_id]);
                $parentContract = $parentStmt->fetch();
                
                if ($parentContract) {
                    // الحصول على عدد التعديلات الموجودة لهذا العقد
                    $amendmentCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM contracts WHERE parent_contract_id = ? AND is_amendment = 1");
                    $amendmentCountStmt->execute([$parent_contract_id]);
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
            
            // إدراج العقد في قاعدة البيانات
            $insertStmt = $pdo->prepare("
                INSERT INTO contracts (
                    contract_number, client_name, client_id, client_phone, amount, 
                    profit_percentage, contract_duration, profit_interval, signature_method, 
                    contract_date, notes, status, created_by, net_profit, is_amendment,
                    parent_contract_id, amendment_duration_months
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?, ?)
            ");
            
            $insertStmt->execute([
                $contract_number, $client_name, $client_id, $client_phone, $amount,
                $profit_percentage, $contract_duration, $profit_interval, $signature_method,
                $contract_date, $notes, $user['id'], $net_profit, $is_amendment,
                $parent_contract_id, $amendment_duration_months
            ]);
            
            $contractId = $pdo->lastInsertId();
            
            // تسجيل النشاط
            $logStmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, description, contract_id) 
                VALUES (?, 'create_contract', ?, ?)
            ");
            $logStmt->execute([
                $user['id'], 
                "إنشاء عقد جديد رقم: $contract_number", 
                $contractId
            ]);
            
            $message = "تم إنشاء العقد بنجاح - رقم العقد: $contract_number";
            
            // إعادة تعيين المتغيرات
            $client_name = $client_id = $client_phone = $amount = $notes = '';
            $contract_date = date('Y-m-d');
            $signature_method = 'electronic';
            $profit_percentage = 30;
            $contract_duration = 12;
            $profit_interval = 'monthly';
            
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
            <div class="row">
                <div class="col-md-6">
                    <div class="form-card">
                        <h4 class="section-title">
                            <i class="fas fa-file-contract"></i> بيانات العقد الأساسية
                        </h4>
                        
                        <div class="mb-3">
                            <label for="client_name" class="form-label">
                                <i class="fas fa-user"></i> اسم العميل الكامل *
                            </label>
                            <input type="text" class="form-control" id="client_name" name="client_name" 
                                   value="<?php echo htmlspecialchars($_POST['client_name'] ?? ''); ?>" 
                                   placeholder="أدخل الاسم الكامل للعميل" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="client_id" class="form-label">
                                <i class="fas fa-id-card"></i> رقم الهوية الوطنية *
                            </label>
                            <input type="text" class="form-control" id="client_id" name="client_id" 
                                   value="<?php echo htmlspecialchars($_POST['client_id'] ?? ''); ?>" 
                                   placeholder="أدخل رقم الهوية (10 أرقام)" 
                                   pattern="[0-9]{10}" title="رقم الهوية يجب أن يكون 10 أرقام" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="client_phone" class="form-label">
                                <i class="fas fa-phone"></i> رقم الجوال *
                            </label>
                            <input type="tel" class="form-control" id="client_phone" name="client_phone" 
                                   value="<?php echo htmlspecialchars($_POST['client_phone'] ?? ''); ?>" 
                                   placeholder="05xxxxxxxx" 
                                   pattern="05[0-9]{8}" title="رقم الجوال يجب أن يبدأ بـ 05 ويكون 10 أرقام" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">
                                <i class="fas fa-money-bill-wave"></i> مبلغ العقد (ريال سعودي) *
                            </label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                   value="<?php echo $_POST['amount'] ?? ''; ?>" 
                                   placeholder="أدخل مبلغ الاستثمار" min="50000" required>
                            <div class="minimum-amount-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>متطلبات الشركة:</strong> الحد الأدنى للعقد 50,000 ريال سعودي
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contract_date" class="form-label">
                                <i class="fas fa-calendar"></i> تاريخ العقد *
                            </label>
                            <input type="date" class="form-control" id="contract_date" name="contract_date" 
                                   value="<?php echo $_POST['contract_date'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="signature_method" class="form-label">
                                <i class="fas fa-signature"></i> طريقة التوقيع *
                            </label>
                            <select class="form-control" id="signature_method" name="signature_method" required>
                                <option value="">اختر طريقة التوقيع</option>
                                <option value="handwritten" <?php echo ($_POST['signature_method'] ?? '') === 'handwritten' ? 'selected' : ''; ?>>توقيع يدوي</option>
                                <option value="digital" <?php echo ($_POST['signature_method'] ?? '') === 'digital' ? 'selected' : ''; ?>>توقيع رقمي</option>
                                <option value="witness" <?php echo ($_POST['signature_method'] ?? '') === 'witness' ? 'selected' : ''; ?>>بحضور شاهد</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            سيتم توليد رقم العقد تلقائياً عند الحفظ
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-card">
                        <h4 class="section-title">
                            <i class="fas fa-file-alt"></i> تفاصيل العقد الإضافية
                        </h4>
                        
                        <div class="mb-3">
                            <label for="profit_percent" class="form-label">
                                <i class="fas fa-percentage"></i> نسبة الأرباح المتوقعة (%)
                            </label>
                            <input type="number" step="0.1" class="form-control" id="profit_percent" name="profit_percent" 
                                   value="<?php echo $_POST['profit_percent'] ?? '30'; ?>" min="0" max="100">
                            <small class="form-text text-muted">النسبة الافتراضية: 30%</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contract_duration" class="form-label">
                                <i class="fas fa-clock"></i> مدة العقد
                            </label>
                            <input type="text" class="form-control" value="6 أشهر (ثابت)" readonly>
                            <div class="alert alert-info mt-2">
                                <i class="fas fa-info-circle"></i> جميع العقود محددة بمدة 6 أشهر حسب سياسة الشركة
                            </div>
                            <input type="hidden" name="contract_duration" value="6">
                        </div>
                        
                        <div class="mb-3">
                            <label for="profit_interval" class="form-label">
                                <i class="fas fa-calendar-check"></i> فترة توزيع الأرباح
                            </label>
                            <select class="form-control" id="profit_interval" name="profit_interval">
                                <option value="6" <?php echo ($_POST['profit_interval'] ?? '6') === '6' ? 'selected' : ''; ?>>كل 6 أشهر</option>
                                <option value="3" <?php echo ($_POST['profit_interval'] ?? '') === '3' ? 'selected' : ''; ?>>كل 3 أشهر</option>
                                <option value="12" <?php echo ($_POST['profit_interval'] ?? '') === '12' ? 'selected' : ''; ?>>سنوياً</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note"></i> ملاحظات إضافية
                            </label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="أدخل أي ملاحظات خاصة بالعقد"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-shield-alt"></i> 
                            <strong>تأكيد التوقيع:</strong><br>
                            بإنشاء هذا العقد، أؤكد أن جميع البيانات صحيحة وأوافق على الشروط والأحكام المنصوص عليها في العقد.
                            <input type="text" class="form-control" id="partner_id" name="partner_id" 
                                   value="<?php echo htmlspecialchars($_POST['partner_id'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="partner_phone" class="form-label">
                                <i class="fas fa-phone"></i> رقم الهاتف *
                            </label>
                            <input type="tel" class="form-control" id="partner_phone" name="partner_phone" 
                                   value="<?php echo htmlspecialchars($_POST['partner_phone'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
            


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
                                               <?php echo ($_POST['is_amendment'] ?? '') ? 'checked' : ''; ?>
                                               onchange="toggleAmendmentFields()">
                                        <label class="form-check-label" for="is_amendment">
                                            <span class="badge amendment-badge">تعديل على عقد موجود</span>
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
                                            <i class="fas fa-link"></i> رقم العقد الأصلي
                                        </label>
                                        <input type="number" class="form-control" id="parent_contract_id" 
                                               name="parent_contract_id" 
                                               value="<?php echo $_POST['parent_contract_id'] ?? ''; ?>"
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
    
    <!-- Company Watermark -->
    <div class="company-watermark">
        <svg width="200" height="120" viewBox="0 0 400 240" xmlns="http://www.w3.org/2000/svg">
            <!-- Company Logo Representation -->
            <rect x="50" y="50" width="300" height="140" fill="none" stroke="#1B3B5A" stroke-width="2" opacity="0.3"/>
            <polygon points="200,60 180,120 220,120" fill="#5BB3C7" opacity="0.3"/>
            <rect x="170" y="120" width="60" height="60" fill="#1B3B5A" opacity="0.3"/>
            <text x="200" y="200" text-anchor="middle" font-family="Arial" font-size="16" fill="#1B3B5A" opacity="0.3">SMA ALBNYAN</text>
        </svg>
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
        
        // Calculate net profit automatically based on amount and percentage
        function calculateNetProfit() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const profitPercent = parseFloat(document.getElementById('profit_percentage').value) || 30;
            const netProfitField = document.getElementById('net_profit');
            
            if (amount > 0) {
                const netProfit = (amount * profitPercent / 100).toFixed(2);
                netProfitField.value = netProfit;
            }
        }
        
        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize amendment fields display
            toggleAmendmentFields();
            
            // Add event listeners for automatic calculations
            document.getElementById('amount').addEventListener('input', calculateNetProfit);
            document.getElementById('profit_percentage').addEventListener('input', calculateNetProfit);
            
            // Initial calculation
            calculateNetProfit();
            
            // Form validation for company requirements
            document.querySelector('form').addEventListener('submit', function(e) {
                const amount = parseFloat(document.getElementById('amount').value) || 0;
                const isAmendment = document.getElementById('is_amendment').checked;
                
                if (amount < 50000) {
                    e.preventDefault();
                    alert('مبلغ العقد يجب أن يكون 50,000 ريال على الأقل وفقاً لمتطلبات سما البنيان للتطوير والاستثمار العقاري');
                    return false;
                }
                
                if (isAmendment) {
                    const parentContractId = document.getElementById('parent_contract_id').value;
                    if (!parentContractId) {
                        e.preventDefault();
                        alert('يجب تحديد رقم العقد الأصلي للتعديل');
                        return false;
                    }
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
</body>
</html>