<?php
/**
 * Contract View Page - Sama Al-Bunyan Contracts Platform
 * Display full contract details, terms, attachments, and signatures
 */

require_once '../includes/auth.php';
require_once '../includes/helpers.php';

// Authentication check
$auth->requireAuth();
$user = $auth->getCurrentUser();

$contract_id = $_GET['id'] ?? null;
if (!$contract_id || !is_numeric($contract_id)) {
    header('Location: contracts_list.php');
    exit;
}

$contract = null;
$contractFiles = [];
$contractHistory = [];
$error = '';

try {
    // Get contract details with related information
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u1.name as created_by_name,
            u1.email as created_by_email,
            u2.name as reviewed_by_name,
            u2.email as reviewed_by_email
        FROM contracts c
        LEFT JOIN users u1 ON c.created_by = u1.id
        LEFT JOIN users u2 ON c.reviewed_by = u2.id
        WHERE c.id = ?
    ");
    $stmt->execute([$contract_id]);
    $contract = $stmt->fetch();

    if (!$contract) {
        header('Location: contracts_list.php?error=' . urlencode('العقد غير موجود'));
        exit;
    }
    
    // Role-based access control
    if ($user['role'] === 'employee' && $contract['created_by'] != $user['id']) {
        header('Location: contracts_list.php?error=' . urlencode('غير مسموح لك بعرض هذا العقد'));
        exit;
    }
    
    // Get contract files/attachments
    $filesStmt = $pdo->prepare("
        SELECT * FROM contract_files 
        WHERE contract_id = ? 
        ORDER BY uploaded_at DESC
    ");
    $filesStmt->execute([$contract_id]);
    $contractFiles = $filesStmt->fetchAll();
    
    // Get contract history/activity log
    $historyStmt = $pdo->prepare("
        SELECT 
            al.*,
            u.name as user_name
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.contract_id = ?
        ORDER BY al.created_at DESC
        LIMIT 20
    ");
    $historyStmt->execute([$contract_id]);
    $contractHistory = $historyStmt->fetchAll();

} catch (PDOException $e) {
    $error = 'خطأ في جلب بيانات العقد: ' . $e->getMessage();
}

// Helper functions
function getStatusInfo($status) {
    $statuses = [
        'draft' => ['class' => 'secondary', 'icon' => 'fas fa-edit', 'text' => 'مسودة'],
        'pending_review' => ['class' => 'warning', 'icon' => 'fas fa-clock', 'text' => 'قيد المراجعة'],
        'approved' => ['class' => 'success', 'icon' => 'fas fa-check', 'text' => 'معتمد'],
        'rejected' => ['class' => 'danger', 'icon' => 'fas fa-times', 'text' => 'مرفوض'],
        'signed' => ['class' => 'primary', 'icon' => 'fas fa-signature', 'text' => 'موقع']
    ];
    return $statuses[$status] ?? ['class' => 'light', 'icon' => 'fas fa-question', 'text' => 'غير محدد'];
}

function formatCurrency($amount) {
    return number_format($amount, 2) . ' ريال سعودي';
}

function formatDate($date, $includeTime = false) {
    if (!$date) return 'غير محدد';
    $format = $includeTime ? 'Y/m/d H:i' : 'Y/m/d';
    return date($format, strtotime($date));
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض العقد <?= htmlspecialchars($contract['contract_number'] ?? '') ?> - سما البنيان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/unified-theme.css" rel="stylesheet">
    <style>
        :root {
            --sma-gold: #D4AF37;
            --sma-dark-gold: #B8941F;
            --sma-gray: #6C757D;
        }
        
        .contract-header {
            background: linear-gradient(135deg, var(--sma-gold), var(--sma-dark-gold));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-right: 4px solid var(--sma-gold);
        }
        
        .section-title {
            color: var(--sma-dark-gold);
            border-bottom: 2px solid var(--sma-gold);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--sma-gray);
            flex: 0 0 150px;
        }
        
        .detail-value {
            flex: 1;
            text-align: left;
        }
        
        .action-btn {
            margin: 0.25rem;
        }
        
        .timeline-item {
            border-right: 3px solid var(--sma-gold);
            padding-right: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            right: -6px;
            top: 0.5rem;
            width: 10px;
            height: 10px;
            background: var(--sma-gold);
            border-radius: 50%;
        }
        
        .file-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .detail-label {
                flex: none;
                margin-bottom: 0.25rem;
            }
            
            .detail-value {
                text-align: right;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php else: ?>
        
        <!-- Contract Header -->
        <div class="contract-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="fas fa-file-contract me-2"></i>
                        عقد رقم: <?= htmlspecialchars($contract['contract_number']) ?>
                    </h2>
                    <p class="mb-2">
                        <strong>العميل:</strong> <?= htmlspecialchars($contract['client_name']) ?>
                        <span class="ms-3">
                            <strong>المبلغ:</strong> <?= formatCurrency($contract['amount']) ?>
                        </span>
                    </p>
                    <p class="mb-0">
                        <strong>تاريخ الإنشاء:</strong> <?= formatDate($contract['created_at'], true) ?>
                        <span class="ms-3">
                            <strong>بواسطة:</strong> <?= htmlspecialchars($contract['created_by_name']) ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <?php $statusInfo = getStatusInfo($contract['status']); ?>
                    <div class="mb-3">
                        <span class="badge bg-<?= $statusInfo['class'] ?> fs-6 p-2">
                            <i class="<?= $statusInfo['icon'] ?> me-1"></i>
                            <?= $statusInfo['text'] ?>
                        </span>
                    </div>
                    <div class="action-buttons">
                        <!-- Action buttons based on status and role -->
                        <?php if (in_array($contract['status'], ['draft', 'rejected']) && 
                                 ($user['role'] !== 'employee' || $contract['created_by'] == $user['id'])): ?>
                        <a href="contract_edit.php?id=<?= $contract['id'] ?>" class="btn btn-warning action-btn">
                            <i class="fas fa-edit me-1"></i> تعديل
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($contract['status'] === 'pending_review' && in_array($user['role'], ['manager', 'admin'])): ?>
                        <a href="contract_review.php?id=<?= $contract['id'] ?>" class="btn btn-success action-btn">
                            <i class="fas fa-check-double me-1"></i> مراجعة
                        </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($contract['status'], ['approved', 'signed'])): ?>
                        <a href="contract_pdf.php?id=<?= $contract['id'] ?>" class="btn btn-info action-btn">
                            <i class="fas fa-file-pdf me-1"></i> تحميل PDF
                        </a>
                        <?php endif; ?>
                        
                        <a href="contract_files.php?id=<?= $contract['id'] ?>" class="btn btn-secondary action-btn">
                            <i class="fas fa-paperclip me-1"></i> الملفات
                        </a>
                        
                        <a href="contracts_list.php" class="btn btn-outline-light action-btn">
                            <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Contract Details -->
            <div class="col-lg-8">
                <!-- Client Information -->
                <div class="info-card">
                    <h4 class="section-title">معلومات العميل</h4>
                    <div class="detail-row">
                        <span class="detail-label">الاسم الكامل:</span>
                        <span class="detail-value"><?= htmlspecialchars($contract['client_name']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">رقم الهوية:</span>
                        <span class="detail-value"><?= htmlspecialchars($contract['client_id']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">رقم الجوال:</span>
                        <span class="detail-value"><?= htmlspecialchars($contract['client_phone']) ?></span>
                    </div>
                    <?php if (!empty($contract['client_email'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">البريد الإلكتروني:</span>
                        <span class="detail-value"><?= htmlspecialchars($contract['client_email']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Contract Terms -->
                <div class="info-card">
                    <h4 class="section-title">تفاصيل العقد</h4>
                    <div class="detail-row">
                        <span class="detail-label">نوع العقد:</span>
                        <span class="detail-value">
                            <?php 
                            $types = [
                                'investment' => 'استثمار نقدي',
                                'property_investment' => 'استثمار بعقار',
                                'real_estate' => 'عقاري'
                            ];
                            echo $types[$contract['contract_type']] ?? 'غير محدد';
                            ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">مبلغ العقد:</span>
                        <span class="detail-value"><strong><?= formatCurrency($contract['amount']) ?></strong></span>
                    </div>
                    <?php if ($contract['profit_percentage']): ?>
                    <div class="detail-row">
                        <span class="detail-label">نسبة الربح:</span>
                        <span class="detail-value"><?= number_format($contract['profit_percentage'], 1) ?>%</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($contract['net_profit']): ?>
                    <div class="detail-row">
                        <span class="detail-label">صافي الربح:</span>
                        <span class="detail-value"><?= formatCurrency($contract['net_profit']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="detail-label">تاريخ العقد:</span>
                        <span class="detail-value"><?= formatDate($contract['contract_date']) ?></span>
                    </div>
                    <?php if ($contract['contract_duration']): ?>
                    <div class="detail-row">
                        <span class="detail-label">مدة العقد:</span>
                        <span class="detail-value"><?= $contract['contract_duration'] ?> أشهر</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Notes and Comments -->
                <?php if (!empty($contract['notes']) || !empty($contract['review_notes'])): ?>
                <div class="info-card">
                    <h4 class="section-title">الملاحظات</h4>
                    <?php if (!empty($contract['notes'])): ?>
                    <div class="mb-3">
                        <strong>ملاحظات العقد:</strong>
                        <p class="mt-2"><?= nl2br(htmlspecialchars($contract['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($contract['review_notes'])): ?>
                    <div>
                        <strong>ملاحظات المراجعة:</strong>
                        <p class="mt-2"><?= nl2br(htmlspecialchars($contract['review_notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Contract Files -->
                <?php if (!empty($contractFiles)): ?>
                <div class="info-card">
                    <h4 class="section-title">الملفات المرفقة</h4>
                    <?php foreach ($contractFiles as $file): ?>
                    <div class="file-item">
                        <div>
                            <i class="fas fa-file me-2"></i>
                            <strong><?= htmlspecialchars($file['original_name']) ?></strong>
                            <small class="text-muted d-block">
                                رُفع في: <?= formatDate($file['uploaded_at'], true) ?>
                                (<?= number_format($file['file_size'] / 1024, 1) ?> KB)
                            </small>
                        </div>
                        <div>
                            <a href="download_file.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i> تحميل
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Status Timeline -->
                <div class="info-card">
                    <h4 class="section-title">سجل العقد</h4>
                    <?php if (!empty($contractHistory)): ?>
                    <div class="timeline">
                        <?php foreach ($contractHistory as $history): ?>
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?= htmlspecialchars($history['action']) ?></strong>
                                    <p class="mb-1 text-muted"><?= htmlspecialchars($history['description'] ?? '') ?></p>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($history['user_name']) ?> - 
                                        <?= formatDate($history['created_at'], true) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">لا يوجد سجل متاح</p>
                    <?php endif; ?>
                </div>

                <!-- Contract Summary -->
                <div class="info-card">
                    <h4 class="section-title">ملخص العقد</h4>
                    <div class="text-center">
                        <div class="mb-3">
                            <h5 class="text-primary"><?= formatCurrency($contract['amount']) ?></h5>
                            <small class="text-muted">قيمة العقد</small>
                        </div>
                        <?php if ($contract['net_profit']): ?>
                        <div class="mb-3">
                            <h5 class="text-success"><?= formatCurrency($contract['net_profit']) ?></h5>
                            <small class="text-muted">الربح المتوقع</small>
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <span class="badge bg-<?= $statusInfo['class'] ?> fs-6">
                                <?= $statusInfo['text'] ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="info-card">
                    <h4 class="section-title">إجراءات سريعة</h4>
                    <div class="d-grid gap-2">
                        <?php if ($contract['status'] === 'draft' || $contract['status'] === 'pending_review'): ?>
                        <button type="button" class="btn btn-outline-primary" onclick="submitForReview()">
                            <i class="fas fa-paper-plane me-1"></i> إرسال للمراجعة
                        </button>
                        <?php endif; ?>
                        
                        <a href="contract_files.php?id=<?= $contract['id'] ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-paperclip me-1"></i> إدارة الملفات
                        </a>
                        
                        <?php if (in_array($contract['status'], ['approved', 'signed'])): ?>
                        <a href="contract_pdf.php?id=<?= $contract['id'] ?>" class="btn btn-outline-info">
                            <i class="fas fa-print me-1"></i> طباعة العقد
                        </a>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-outline-warning" onclick="printView()">
                            <i class="fas fa-eye me-1"></i> معاينة للطباعة
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function submitForReview() {
            if (confirm('هل تريد إرسال هذا العقد للمراجعة؟')) {
                fetch('submit_for_review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({contract_id: <?= $contract['id'] ?>})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('خطأ: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ أثناء إرسال العقد للمراجعة');
                });
            }
        }
        
        function printView() {
            window.print();
        }
        
        // Print styles
        const printStyles = `
            <style media="print">
                .contract-header { background: #f8f9fa !important; color: #333 !important; }
                .action-buttons, .navbar, .btn { display: none !important; }
                .info-card { border: 1px solid #ddd !important; page-break-inside: avoid; }
            </style>
        `;
        document.head.insertAdjacentHTML('beforeend', printStyles);
    </script>
</body>
</html>