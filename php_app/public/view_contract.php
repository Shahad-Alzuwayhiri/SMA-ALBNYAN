<?php
require_once '../includes/auth.php';

$auth->requireAuth();
$user = $auth->getCurrentUser();

$contract_id = $_GET['id'] ?? null;
if (!$contract_id) {
    $dashboardUrl = ($user['role'] === 'employee') ? '/employee_dashboard.php' : '/manager_dashboard.php';
    header("Location: $dashboardUrl");
    exit;
}

try {
    // جلب بيانات العقد
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as created_by_name 
        FROM contracts c 
        LEFT JOIN users u ON c.created_by = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$contract_id]);
    $contract = $stmt->fetch();

    if (!$contract) {
        die('العقد غير موجود');
    }
    
    // التحقق من الصلاحية - الموظف يرى عقوده فقط، المدير يرى جميع العقود
    if ($user['role'] === 'employee' && $contract['created_by'] != $user['id']) {
        die('غير مسموح لك بعرض هذا العقد');
    }
    
} catch (PDOException $e) {
    die('خطأ في جلب بيانات العقد: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض العقد - <?php echo htmlspecialchars($contract['contract_number']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            background-color: #f8f9fa;
        }
        .contract-header {
            background: linear-gradient(135deg, #2c5530 0%, #1a3d1f 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="contract-header text-center">
                    <h2><i class="fas fa-file-contract"></i> عرض العقد</h2>
                    <h3><?php echo htmlspecialchars($contract['contract_number']); ?></h3>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h5><i class="fas fa-info-circle text-primary"></i> معلومات العقد الأساسية</h5>
                    <hr>
                    <p><strong>رقم العقد:</strong> <?php echo htmlspecialchars($contract['contract_number']); ?></p>
                    <p><strong>اسم العميل:</strong> <?php echo htmlspecialchars($contract['client_name']); ?></p>
                    <p><strong>المبلغ:</strong> <?php echo number_format($contract['amount'], 2); ?> ريال</p>
                    <p><strong>الحالة:</strong> 
                        <span class="badge bg-<?php echo $contract['status'] == 'active' ? 'success' : 'warning'; ?>">
                            <?php echo $contract['status'] == 'active' ? 'نشط' : 'معلق'; ?>
                        </span>
                    </p>
                    <p><strong>تاريخ الإنشاء:</strong> <?php echo date('Y-m-d H:i', strtotime($contract['created_at'])); ?></p>
                </div>
            </div>
            
            <?php if ($detailedContract): ?>
            <div class="col-md-6">
                <div class="info-card">
                    <h5><i class="fas fa-user text-info"></i> معلومات الشريك</h5>
                    <hr>
                    <p><strong>اسم الشريك:</strong> <?php echo htmlspecialchars($detailedContract['partner_name']); ?></p>
                    <p><strong>رقم الهوية:</strong> <?php echo htmlspecialchars($detailedContract['partner_id']); ?></p>
                    <p><strong>رقم الهاتف:</strong> <?php echo htmlspecialchars($detailedContract['partner_phone']); ?></p>
                    <p><strong>مبلغ الاستثمار:</strong> <?php echo number_format($detailedContract['investment_amount'], 2); ?> ريال</p>
                    <p><strong>نسبة الربح:</strong> <?php echo $detailedContract['profit_percent']; ?>%</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="info-card text-center">
                    <h5><i class="fas fa-cogs"></i> الإجراءات</h5>
                    <hr>
                    <a href="/edit_contract.php?id=<?php echo $contract['id']; ?>" class="btn btn-warning me-2">
                        <i class="fas fa-edit"></i> تعديل العقد
                    </a>
                    <a href="/export_pdf.php?id=<?php echo $contract['id']; ?>" class="btn btn-success me-2" target="_blank">
                        <i class="fas fa-file-pdf"></i> تصدير PDF
                    </a>
                    <a href="/contracts_list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i> العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>