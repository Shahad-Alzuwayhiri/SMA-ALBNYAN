<?php
/**
 * صفحة إنشاء عقد استثمار - نظام سما البنيان
 * إنشاء عقود المضاربة في العقارات
 */

require_once '../includes/auth.php';
require_once '../templates/InvestmentContractTemplate.php';

// التحقق من المصادقة
$auth->requireAuth();
$user = $auth->getCurrentUser();

$message = '';
$messageType = '';
$contractHtml = '';

// معالجة إنشاء العقد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contractData = [
        'contract_number' => $_POST['contract_number'] ?? 'INV-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
        'hijri_date' => $_POST['hijri_date'] ?? '',
        'investor_name' => $_POST['investor_name'] ?? '',
        'investor_id' => $_POST['investor_id'] ?? '',
        'investor_phone' => $_POST['investor_phone'] ?? '',
        'investor_email' => $_POST['investor_email'] ?? '',
        'investor_address' => $_POST['investor_address'] ?? '',
        'investment_amount' => $_POST['investment_amount'] ?? '',
        'profit_percentage' => $_POST['profit_percentage'] ?? '40',
        'commission_rate' => $_POST['commission_rate'] ?? '2.5',
        'penalty_amount' => $_POST['penalty_amount'] ?? '3,000',
        'contract_duration' => $_POST['contract_duration'] ?? '6',
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? ''
    ];
    
    // التحقق من البيانات المطلوبة
    $requiredFields = ['investor_name', 'investor_id', 'investor_phone', 'investment_amount'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($contractData[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (empty($missingFields)) {
        // إنشاء العقد
        $contractHtml = InvestmentContractTemplate::generateContractHTML($contractData);
        
        // حفظ العقد في قاعدة البيانات
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contracts (
                    contract_number, title, client_name, client_phone, client_email,
                    amount, profit_percentage, contract_duration, start_date, end_date,
                    contract_type, status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $contractData['contract_number'],
                'عقد استثمار ومضاربة في العقارات',
                $contractData['investor_name'],
                $contractData['investor_phone'],
                $contractData['investor_email'],
                str_replace(',', '', $contractData['investment_amount']), // إزالة الفواصل
                $contractData['profit_percentage'],
                $contractData['contract_duration'],
                $contractData['start_date'],
                $contractData['end_date'],
                'investment',
                'draft',
                $user['id'],
                date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                $contractId = $pdo->lastInsertId();
                $message = 'تم إنشاء عقد الاستثمار بنجاح! رقم العقد: ' . $contractData['contract_number'];
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
    <title>إنشاء عقد استثمار - سما البنيان</title>
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
                        <i class="fas fa-handshake me-2"></i>
                        إنشاء عقد استثمار
                    </h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($contractHtml): ?>
                    <!-- معاينة العقد -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-eye me-2"></i>
                                معاينة العقد
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
                            بيانات عقد الاستثمار الجديد
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
                                                       value="INV-<?= date('Y') ?>-<?= str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT) ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">التاريخ الهجري</label>
                                                <input type="text" name="hijri_date" class="form-control" 
                                                       placeholder="مثال: 15-4-1447هـ" value="<?= $_POST['hijri_date'] ?? '' ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">مبلغ الاستثمار (ريال) *</label>
                                                <input type="text" name="investment_amount" class="form-control" 
                                                       placeholder="مثال: 100,000" value="<?= $_POST['investment_amount'] ?? '' ?>" required>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">نسبة الربح (%)</label>
                                                        <input type="number" name="profit_percentage" class="form-control" 
                                                               value="<?= $_POST['profit_percentage'] ?? '40' ?>" min="1" max="100">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">مدة العقد (شهر)</label>
                                                        <input type="number" name="contract_duration" class="form-control" 
                                                               value="<?= $_POST['contract_duration'] ?? '6' ?>" min="1" max="60">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">تاريخ البداية</label>
                                                        <input type="text" name="start_date" class="form-control" 
                                                               placeholder="15-4-1447هـ" value="<?= $_POST['start_date'] ?? '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">تاريخ النهاية</label>
                                                        <input type="text" name="end_date" class="form-control" 
                                                               placeholder="15-10-1447هـ" value="<?= $_POST['end_date'] ?? '' ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
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
                                                       value="<?= $_POST['investor_phone'] ?? '' ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">البريد الإلكتروني</label>
                                                <input type="email" name="investor_email" class="form-control" 
                                                       value="<?= $_POST['investor_email'] ?? '' ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">العنوان</label>
                                                <textarea name="investor_address" class="form-control" rows="2" 
                                                          placeholder="مثال: جدة حي الروضة شارع صاري"><?= $_POST['investor_address'] ?? '' ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">نسبة العمولة (%)</label>
                                        <input type="number" name="commission_rate" class="form-control" 
                                               value="<?= $_POST['commission_rate'] ?? '2.5' ?>" step="0.1" min="0" max="10">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">مبلغ الشرط الجزائي (ريال)</label>
                                        <input type="text" name="penalty_amount" class="form-control" 
                                               value="<?= $_POST['penalty_amount'] ?? '3,000' ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-file-contract me-2"></i>
                                    إنشاء العقد
                                </button>
                                <a href="/contracts_list.php" class="btn btn-secondary btn-lg ms-2">
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
                <title>طباعة العقد</title>
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
    
    // تنسيق مبلغ الاستثمار أثناء الكتابة
    document.querySelector('input[name="investment_amount"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/,/g, '');
        if (!isNaN(value) && value !== '') {
            e.target.value = Number(value).toLocaleString();
        }
    });
    </script>
</body>
</html>