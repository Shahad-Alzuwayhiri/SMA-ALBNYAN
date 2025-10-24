<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../models/Contract.php';
require_once __DIR__ . '/../models/DetailedContract.php';

// فقط المدير والأدمن يمكنهم حذف العقود
$auth->requirePermission('manage_employees');
$user = $auth->getCurrentUser();

$contract_id = $_GET['id'] ?? null;
if (!$contract_id) {
    header('Location: /manager_dashboard.php');
    exit;
}

try {
    // جلب بيانات العقد
    $stmt = $pdo->prepare("SELECT * FROM contracts WHERE id = ?");
    $stmt->execute([$contract_id]);
    $contract = $stmt->fetch();
    $contractModel = new Contract($pdo);
    
    if (!$contract) {
        $_SESSION['error'] = 'العقد غير موجود';
        header('Location: /manager_dashboard.php');
        exit;
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
    header('Location: /manager_dashboard.php');
    exit;
}

// معالجة تأكيد الحذف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // حذف العقد المفصل أولاً
        $detailedContractModel = new DetailedContract();
        $detailedContractModel->deleteByContractId($contract_id);
        
        // حذف العقد الأساسي
        $deleted = $contractModel->delete($contract_id);
        
        if ($deleted) {
            $_SESSION['success'] = 'تم حذف العقد بنجاح';
        } else {
            $_SESSION['error'] = 'حدث خطأ في حذف العقد';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'خطأ في النظام: ' . $e->getMessage();
    }
    
    header('Location: /contracts_list.php');
    exit;
}

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حذف العقد - <?php echo htmlspecialchars($contract['contract_number']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            background-color: #f8f9fa;
        }
        .danger-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .warning-card {
            background: white;
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(220, 53, 69, 0.2);
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="danger-header text-center">
                    <h2><i class="fas fa-trash-alt"></i> حذف العقد</h2>
                    <h3><?php echo htmlspecialchars($contract['contract_number']); ?></h3>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="warning-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h4 class="text-center text-danger mb-4">تحذير: عملية حذف نهائية!</h4>
                    
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-info-circle"></i> معلومات العقد المراد حذفه:</h5>
                        <hr>
                        <p><strong>رقم العقد:</strong> <?php echo htmlspecialchars($contract['contract_number']); ?></p>
                        <p><strong>اسم العميل:</strong> <?php echo htmlspecialchars($contract['client_name']); ?></p>
                        <p><strong>المبلغ:</strong> <?php echo number_format($contract['amount'], 2); ?> ريال</p>
                        <p><strong>تاريخ الإنشاء:</strong> <?php echo date('Y-m-d H:i', strtotime($contract['created_at'])); ?></p>
                    </div>
                    
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> تنبيه مهم:</h6>
                        <ul>
                            <li>سيتم حذف العقد نهائياً ولا يمكن استرداده</li>
                            <li>سيتم حذف جميع البيانات المرتبطة بالعقد</li>
                            <li>لن تتمكن من الوصول لهذا العقد مرة أخرى</li>
                        </ul>
                    </div>
                    
                    <form method="POST" class="text-center">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirm_checkbox" required>
                                <label class="form-check-label" for="confirm_checkbox">
                                    أؤكد أنني أريد حذف هذا العقد نهائياً
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" name="confirm_delete" class="btn btn-danger btn-lg me-2" 
                                onclick="return confirm('هل أنت متأكد من حذف العقد نهائياً؟')">
                            <i class="fas fa-trash-alt"></i> نعم، احذف العقد نهائياً
                        </button>
                        
                        <a href="/view_contract.php?id=<?php echo $contract['id']; ?>" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-right"></i> إلغاء العملية
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>