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
        SELECT * FROM contracts 
        WHERE id = ? AND (created_by = ? OR ? IN ('manager', 'admin'))
    ");
    $stmt->execute([$contract_id, $user['id'], $user['role']]);
    $contract = $stmt->fetch();

    if (!$contract) {
        die('العقد غير موجود أو غير مسموح لك بتعديله');
    }
    
    // فقط المسودات والعقود المرفوضة يمكن تعديلها
    if (!in_array($contract['status'], ['draft', 'rejected'])) {
        die('لا يمكن تعديل هذا العقد في حالته الحالية');
    }
    
} catch (PDOException $e) {
    die('خطأ في جلب بيانات العقد: ' . $e->getMessage());
}

// معالجة تحديث العقد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = $_POST['client_name'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $status = $_POST['status'] ?? 'draft';
    
    if ($client_name && $amount > 0) {
        $contract['client_name'] = $client_name;
        $contract['amount'] = $amount;
        $contract['status'] = $status;
        
        // حفظ التحديثات في قاعدة البيانات
        try {
            $stmt = $pdo->prepare("
                UPDATE contracts 
                SET client_name = ?, amount = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $updated = $stmt->execute([$client_name, $amount, $status, $contract_id]);
        } catch (PDOException $e) {
            $updated = false;
            $error = 'خطأ في تحديث العقد: ' . $e->getMessage();
        }
        
        if ($updated) {
            $_SESSION['success'] = 'تم تحديث العقد بنجاح';
            header('Location: /view_contract.php?id=' . $contract_id);
            exit;
        } else {
            $error = 'حدث خطأ في تحديث العقد';
        }
    } else {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    }
}

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل العقد - <?php echo htmlspecialchars($contract['contract_number']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            background-color: #f8f9fa;
        }
        .form-header {
            background: linear-gradient(135deg, #2c5530 0%, #1a3d1f 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="form-header text-center">
                    <h2><i class="fas fa-edit"></i> تعديل العقد</h2>
                    <h3><?php echo htmlspecialchars($contract['contract_number']); ?></h3>
                </div>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="form-card">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="client_name" class="form-label">
                                <i class="fas fa-user"></i> اسم العميل
                            </label>
                            <input type="text" class="form-control" id="client_name" name="client_name" 
                                   value="<?php echo htmlspecialchars($contract['client_name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">
                                <i class="fas fa-money-bill-wave"></i> المبلغ (ريال سعودي)
                            </label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                   value="<?php echo $contract['amount']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">
                                <i class="fas fa-flag"></i> حالة العقد
                            </label>
                            <select class="form-control" id="status" name="status">
                                <option value="draft" <?php echo $contract['status'] == 'draft' ? 'selected' : ''; ?>>مسودة</option>
                                <option value="active" <?php echo $contract['status'] == 'active' ? 'selected' : ''; ?>>نشط</option>
                                <option value="completed" <?php echo $contract['status'] == 'completed' ? 'selected' : ''; ?>>مكتمل</option>
                                <option value="cancelled" <?php echo $contract['status'] == 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                            </select>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-success btn-lg me-2">
                                <i class="fas fa-save"></i> حفظ التعديلات
                            </button>
                            <a href="/view_contract.php?id=<?php echo $contract['id']; ?>" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>