<?php
/**
 * صفحة إنشاء عقد استثمار بعقار - نظام سما البنيان
 * إنشاء عقود المضاربة بالعقارات كمساهمة عينية
 */

require_once '../includes/auth.php';
require_once '../templates/PropertyInvestmentContractTemplate.php';

// التحقق من المصادقة
$auth->requireAuth();
$user = $auth->getCurrentUser();

$message = '';
$messageType = '';
$contractHtml = '';

// معالجة إنشاء العقد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contractData = [
        'contract_number' => $_POST['contract_number'] ?? 'PIN-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
        'hijri_date' => $_POST['hijri_date'] ?? '',
        'investor_name' => $_POST['investor_name'] ?? '',
        'investor_id' => $_POST['investor_id'] ?? '',
        'investor_phone' => $_POST['investor_phone'] ?? '',
        'investor_email' => $_POST['investor_email'] ?? '',
        'investor_address' => $_POST['investor_address'] ?? '',
        'property_number' => $_POST['property_number'] ?? '',
        'property_location' => $_POST['property_location'] ?? '',
        'property_value' => $_POST['property_value'] ?? '',
        'profit_percentage' => $_POST['profit_percentage'] ?? '30',
        'profit_frequency' => $_POST['profit_frequency'] ?? '2',
        'commission_rate' => $_POST['commission_rate'] ?? '2.5',
        'penalty_amount' => $_POST['penalty_amount'] ?? '3,000',
        'contract_duration' => $_POST['contract_duration'] ?? '6',
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? ''
    ];
    
    // التحقق من البيانات المطلوبة
    $requiredFields = ['investor_name', 'investor_id', 'investor_phone', 'property_number', 'property_location', 'property_value'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($contractData[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (empty($missingFields)) {
        // إنشاء العقد
        $contractHtml = PropertyInvestmentContractTemplate::generateContractHTML($contractData);
        
        // حفظ العقد في قاعدة البيانات
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contracts (
                    contract_number, title, client_name, client_phone, client_email,
                    amount, profit_percentage, contract_duration, start_date, end_date,
                    contract_type, property_number, property_location, status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $contractData['contract_number'],
                'عقد استثمار بعقار',
                $contractData['investor_name'],
                $contractData['investor_phone'],
                $contractData['investor_email'],
                str_replace(',', '', $contractData['property_value']), // إزالة الفواصل
                $contractData['profit_percentage'],
                $contractData['contract_duration'],
                $contractData['start_date'],
                $contractData['end_date'],
                'property_investment',
                $contractData['property_number'],
                $contractData['property_location'],
                'draft',
                $user['id'],
                date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                $contractId = $pdo->lastInsertId();
                $message = 'تم إنشاء عقد الاستثمار بالعقار بنجاح! رقم العقد: ' . $contractData['contract_number'];
                $messageType = 'success';
                
                // حفظ محتوى العقد كـ HTML
                $htmlFile = '../storage/contracts/' . $contractData['contract_number'] . '.html';
                $storageDir = dirname($htmlFile);
                if (!is_dir($storageDir)) {
                    mkdir($storageDir, 0755, true);
                }
                file_put_contents($htmlFile, $contractHtml);
                
            } else {
                $message = 'فشل في حفظ العقد في قاعدة البيانات';
                $messageType = 'danger';
            }
            
        } catch (PDOException $e) {
            $message = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
            $messageType = 'danger';
        }
        
    } else {
        $message = 'يرجى ملء جميع الحقول المطلوبة: ' . implode(', ', $missingFields);
        $messageType = 'warning';
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء عقد استثمار بعقار - سما البنيان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/static/css/modern-theme.css" rel="stylesheet">
</head>
<body>
    <?php include '../templates/partials/_topnav.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../templates/partials/_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-building me-2"></i>
                        إنشاء عقد استثمار بعقار
                    </h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- شرح نوع العقد -->
                <div class="alert alert-info mb-4">
                    <h6><i class="fas fa-info-circle me-2"></i>عقد الاستثمار بالعقار</h6>
                    <p class="mb-0">هذا النوع من العقود يتم فيه تقديم العقار كمساهمة عينية بدلاً من المبلغ النقدي، حيث يقوم المستثمر بنقل ملكية العقار للشركة مقابل حصة في الأرباح.</p>
                </div>

                <?php if ($contractHtml): ?>
                    <!-- معاينة العقد -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-eye me-2"></i>
                                معاينة عقد الاستثمار بالعقار
                            </h5>
                            <div>
                                <button onclick="printContract()" class="btn btn-primary btn-sm me-2">
                                    <i class="fas fa-print me-1"></i>
                                    طباعة
                                </button>
                                <button onclick="downloadContract()" class="btn btn-success btn-sm">
                                    <i class="fas fa-download me-1"></i>
                                    تنزيل PDF
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="contract-preview" style="border: 1px solid #ddd; padding: 20px; background: white;">
                                <?= $contractHtml ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- نموذج إنشاء العقد -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus me-2"></i>
                            بيانات عقد الاستثمار بالعقار الجديد
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0">بيانات العقد</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">رقم العقد</label>
                                                <input type="text" name="contract_number" class="form-control" 
                                                       value="PIN-<?= date('Y') ?>-<?= str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT) ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">التاريخ الهجري</label>
                                                <input type="text" name="hijri_date" class="form-control" 
                                                       placeholder="مثال: 9-03-1447هـ" value="<?= $_POST['hijri_date'] ?? '' ?>">
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">نسبة الربح (%)</label>
                                                        <input type="number" name="profit_percentage" class="form-control" 
                                                               value="<?= $_POST['profit_percentage'] ?? '30' ?>" min="1" max="100">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">تكرار الأرباح (شهر)</label>
                                                        <select name="profit_frequency" class="form-select">
                                                            <option value="1" <?= ($_POST['profit_frequency'] ?? '2') == '1' ? 'selected' : '' ?>>كل شهر</option>
                                                            <option value="2" <?= ($_POST['profit_frequency'] ?? '2') == '2' ? 'selected' : '' ?>>كل شهرين</option>
                                                            <option value="3" <?= ($_POST['profit_frequency'] ?? '2') == '3' ? 'selected' : '' ?>>كل 3 أشهر</option>
                                                            <option value="6" <?= ($_POST['profit_frequency'] ?? '2') == '6' ? 'selected' : '' ?>>كل 6 أشهر</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">مدة العقد (شهر)</label>
                                                        <input type="number" name="contract_duration" class="form-control" 
                                                               value="<?= $_POST['contract_duration'] ?? '6' ?>" min="1" max="60">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">نسبة العمولة (%)</label>
                                                        <input type="number" name="commission_rate" class="form-control" 
                                                               value="<?= $_POST['commission_rate'] ?? '2.5' ?>" step="0.1" min="0" max="10">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">تاريخ البداية</label>
                                                        <input type="text" name="start_date" class="form-control" 
                                                               placeholder="29-٣-1447هـ" value="<?= $_POST['start_date'] ?? '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">تاريخ النهاية</label>
                                                        <input type="text" name="end_date" class="form-control" 
                                                               placeholder="29-09-1447هـ" value="<?= $_POST['end_date'] ?? '' ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">مبلغ الشرط الجزائي (ريال)</label>
                                                <input type="text" name="penalty_amount" class="form-control" 
                                                       value="<?= $_POST['penalty_amount'] ?? '3,000' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">بيانات المستثمر</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">اسم المستثمر *</label>
                                                <input type="text" name="investor_name" class="form-control" 
                                                       value="<?= $_POST['investor_name'] ?? '' ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">رقم الهوية / الإقامة *</label>
                                                <input type="text" name="investor_id" class="form-control" 
                                                       value="<?= $_POST['investor_id'] ?? '' ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">رقم الجوال *</label>
                                                <input type="tel" name="investor_phone" class="form-control" 
                                                       placeholder="(0505652929)" value="<?= $_POST['investor_phone'] ?? '' ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">البريد الإلكتروني</label>
                                                <input type="email" name="investor_email" class="form-control" 
                                                       value="<?= $_POST['investor_email'] ?? '' ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">العنوان</label>
                                                <textarea name="investor_address" class="form-control" rows="2" 
                                                          placeholder="مثال: جدة حي الصفا"><?= $_POST['investor_address'] ?? '' ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card">
                                        <div class="card-header bg-warning text-dark">
                                            <h6 class="mb-0"><i class="fas fa-building me-2"></i>بيانات العقار</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">رقم العقار *</label>
                                                <input type="text" name="property_number" class="form-control" 
                                                       placeholder="مثال: 220204019361" value="<?= $_POST['property_number'] ?? '' ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">موقع العقار *</label>
                                                <textarea name="property_location" class="form-control" rows="2" 
                                                          placeholder="مثال: مدينة ثول حي الشرائع مخطط رقم 412/ج/س" required><?= $_POST['property_location'] ?? '' ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">قيمة العقار (ريال) *</label>
                                                <input type="text" name="property_value" class="form-control" 
                                                       placeholder="مثال: 400,000" value="<?= $_POST['property_value'] ?? '' ?>" required>
                                                <div class="form-text">حسب تقييم البورصة العقارية</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-file-contract me-2"></i>
                                    إنشاء عقد الاستثمار بالعقار
                                </button>
                                <a href="/investment_contracts.php" class="btn btn-secondary btn-lg ms-2">
                                    <i class="fas fa-list me-2"></i>
                                    قائمة العقود
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function printContract() {
        const contractContent = document.getElementById('contract-preview').innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>طباعة عقد الاستثمار بالعقار</title>
                <style>
                    body { font-family: Arial, sans-serif; direction: rtl; }
                    @media print { 
                        body { margin: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                ${contractContent}
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
    
    function downloadContract() {
        alert('وظيفة تنزيل PDF ستكون متاحة قريباً');
    }
    
    // تنسيق قيمة العقار أثناء الكتابة
    document.querySelector('input[name="property_value"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/,/g, '');
        if (!isNaN(value) && value !== '') {
            e.target.value = Number(value).toLocaleString();
        }
    });
    
    // تنسيق رقم الجوال
    document.querySelector('input[name="investor_phone"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^\d]/g, '');
        if (value.length > 0 && !value.startsWith('05')) {
            if (value.startsWith('5')) {
                value = '0' + value;
            }
        }
        if (value.length > 10) {
            value = value.substr(0, 10);
        }
        if (value.length >= 3) {
            value = '(' + value.substr(0, 4) + ')' + value.substr(4);
        }
        e.target.value = value;
    });
    </script>
</body>
</html>