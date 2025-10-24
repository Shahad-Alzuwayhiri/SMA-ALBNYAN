<?php
/**
 * صفحة رفع الملفات - نظام سما البنيان
 * اختبار وظيفة رفع ملفات PDF وحفظها كـ base64
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/FileManager.php';

// التحقق من المصادقة
$auth->requireAuth();
$user = $auth->getCurrentUser();

$message = '';
$messageType = '';

// معالجة رفع الملف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    
    $contractId = $_POST['contract_id'] ?? null;
    
    if (!$contractId) {
        $message = 'يجب اختيار عقد';
        $messageType = 'danger';
    } else {
        $uploadedFile = $_FILES['pdf_file'];
        
        if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
            $fileManager = new FileManager($pdo);
            
            // رفع الملف
            $result = $fileManager->uploadPdfFile(
                $uploadedFile['tmp_name'], 
                $contractId, 
                $user['id']
            );
            
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            
        } else {
            $message = 'خطأ في رفع الملف';
            $messageType = 'danger';
        }
    }
}

// جلب قائمة العقود للاختيار
try {
    $contractsStmt = $pdo->prepare("
        SELECT id, contract_number, title, client_name 
        FROM contracts 
        WHERE status != 'rejected' 
        ORDER BY created_at DESC
    ");
    $contractsStmt->execute();
    $contracts = $contractsStmt->fetchAll();
} catch (PDOException $e) {
    $contracts = [];
}

// جلب الملفات المرفوعة مؤخراً
try {
    $fileManager = new FileManager($pdo);
    $recentFilesStmt = $pdo->prepare("
        SELECT f.*, c.contract_number, c.title as contract_title, u.name as uploaded_by_name
        FROM files f
        LEFT JOIN contracts c ON f.contract_id = c.id
        LEFT JOIN users u ON f.uploaded_by = u.id
        ORDER BY f.created_at DESC
        LIMIT 10
    ");
    $recentFilesStmt->execute();
    $recentFiles = $recentFilesStmt->fetchAll();
    
    $filesStats = $fileManager->getFilesStats();
} catch (PDOException $e) {
    $recentFiles = [];
    $filesStats = ['total_files' => 0, 'total_size' => 0];
}

// دالة تنسيق حجم الملف
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفع الملفات - نظام سما البنيان</title>
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
                        <i class="fas fa-upload me-2"></i>
                        رفع الملفات
                    </h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- إحصائيات سريعة -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-file-pdf fa-2x text-danger mb-2"></i>
                                <h4><?= number_format($filesStats['total_files']) ?></h4>
                                <p class="text-muted mb-0">إجمالي الملفات</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-hdd fa-2x text-info mb-2"></i>
                                <h4><?= formatFileSize($filesStats['total_size']) ?></h4>
                                <p class="text-muted mb-0">إجمالي الحجم</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-chart-bar fa-2x text-success mb-2"></i>
                                <h4><?= $filesStats['total_files'] > 0 ? formatFileSize($filesStats['total_size'] / $filesStats['total_files']) : '0' ?></h4>
                                <p class="text-muted mb-0">متوسط حجم الملف</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- نموذج رفع الملف -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>
                                    رفع ملف PDF جديد
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="contract_id" class="form-label">العقد المرتبط</label>
                                        <select name="contract_id" id="contract_id" class="form-select" required>
                                            <option value="">اختر عقد...</option>
                                            <?php foreach ($contracts as $contract): ?>
                                                <option value="<?= $contract['id'] ?>">
                                                    <?= htmlspecialchars($contract['contract_number']) ?> - 
                                                    <?= htmlspecialchars($contract['title']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="pdf_file" class="form-label">ملف PDF</label>
                                        <input type="file" class="form-control" name="pdf_file" id="pdf_file" 
                                               accept=".pdf" required>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            يجب أن يكون الملف من نوع PDF وحجمه أقل من 10MB
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>
                                        رفع الملف
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- قائمة الملفات المرفوعة مؤخراً -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-history me-2"></i>
                                    الملفات المرفوعة مؤخراً
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentFiles)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-file-pdf fa-3x mb-3"></i>
                                        <p>لا توجد ملفات مرفوعة بعد</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recentFiles as $file): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <i class="fas fa-file-pdf text-danger me-2"></i>
                                                        <?= htmlspecialchars($file['file_name']) ?>
                                                    </h6>
                                                    <p class="mb-1 text-muted small">
                                                        العقد: <?= htmlspecialchars($file['contract_number']) ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        <?= formatFileSize($file['file_size']) ?> • 
                                                        <?= date('Y-m-d H:i', strtotime($file['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/view_file.php?id=<?= $file['id'] ?>" 
                                                       class="btn btn-outline-primary" target="_blank" title="عرض">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="/download_file.php?id=<?= $file['id'] ?>" 
                                                       class="btn btn-outline-success" title="تنزيل">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // معاينة الملف المختار
        document.getElementById('pdf_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileSize = file.size;
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                if (fileSize > maxSize) {
                    alert('حجم الملف كبير جداً. الحد الأقصى 10MB');
                    this.value = '';
                    return;
                }
                
                if (file.type !== 'application/pdf') {
                    alert('يجب أن يكون الملف من نوع PDF');
                    this.value = '';
                    return;
                }
            }
        });
    </script>
</body>
</html>