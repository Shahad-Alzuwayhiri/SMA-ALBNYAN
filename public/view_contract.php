<?php
require_once __DIR__ . '/../includes/auth.php';

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
    
    // جلب التفاصيل المفصلة للعقد
    $stmt = $pdo->prepare("SELECT * FROM detailed_contracts WHERE contract_id = ?");
    $stmt->execute([$contract_id]);
    $detailedContract = $stmt->fetch();
    
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .contract-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border-left: 5px solid #1e3d59;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-top: 4px solid #667eea;
            transition: transform 0.3s ease;
        }
        .info-card:hover {
            transform: translateY(-5px);
        }
        .info-card h5 {
            color: #1e3d59;
            margin-bottom: 1rem;
        }
        .company-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="contract-header text-center">
                    <div class="company-badge">
                        <i class="fas fa-building"></i> سما البنيان للتطوير والاستثمار العقاري
                    </div>
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
                    <p><strong>نوع العقد:</strong> 
                        <span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <?php 
                            $contractType = $contract['contract_type'] ?? 'real_estate';
                            echo $contractType === 'company' ? '🏭 عقد استثمار شركة' : '🏢 عقد استثمار عقاري'; 
                            ?>
                        </span>
                    </p>
                    <p><strong>اسم العميل:</strong> <?php echo htmlspecialchars($contract['client_name']); ?></p>
                    <p><strong>مبلغ الاستثمار:</strong> <?php echo number_format($contract['amount'], 2); ?> ريال</p>
                    <p><strong>صافي الربح المتوقع:</strong> 
                        <span class="amount" style="color: #28a745; font-weight: bold;">
                            <?php echo number_format($contract['amount'] * 0.30, 2); ?> ريال (30%)
                        </span>
                    </p>
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
                    <a href="/edit_contract.php?id=<?php echo $contract['id']; ?>" class="btn me-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; padding: 10px 20px;">
                        <i class="fas fa-edit"></i> تعديل العقد
                    </a>
                    <a href="/contract_view_enhanced.php?id=<?php echo $contract['id']; ?>" class="btn me-2" style="background: linear-gradient(135deg, #4299e1 0%, #2d5aa0 100%); color: white; border: none; border-radius: 10px; padding: 10px 20px;" target="_blank">
                        <i class="fas fa-eye"></i> عرض مفصل
                    </a>
                    <a href="/contract_view_enhanced.php?id=<?php echo $contract['id']; ?>&print=1" class="btn me-2" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; border-radius: 10px; padding: 10px 20px;" target="_blank">
                        <i class="fas fa-file-pdf"></i> طباعة PDF
                    </a>
                    <a href="/contracts_list.php" class="btn" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; border: none; border-radius: 10px; padding: 10px 20px;">
                        <i class="fas fa-arrow-right"></i> العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>