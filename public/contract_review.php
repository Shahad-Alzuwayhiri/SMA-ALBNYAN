<?php
/**
 * Contract Review Page - Sama Al-Bunyan Contracts Platform
 * Manager review page for approve/reject with notes functionality
 */

require_once '../includes/auth.php';
require_once '../includes/helpers.php';

// Authentication and authorization check
$auth->requireAuth();
$user = $auth->getCurrentUser();

// Only managers and admins can review contracts
if (!in_array($user['role'], ['manager', 'admin'])) {
    header('Location: contracts_list.php?error=' . urlencode('غير مسموح لك بمراجعة العقود'));
    exit;
}

$contract_id = $_GET['id'] ?? null;
if (!$contract_id || !is_numeric($contract_id)) {
    header('Location: contracts_list.php?error=' . urlencode('معرف العقد مطلوب'));
    exit;
}

$contract = null;
$contractHistory = [];
$error = '';
$success = '';

try {
    // Get contract details
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u1.name as created_by_name,
            u1.email as created_by_email,
            u2.name as reviewed_by_name
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
    
    // Only pending contracts can be reviewed
    if ($contract['status'] !== 'pending_review') {
        header('Location: contract_view.php?id=' . $contract_id . '&error=' . urlencode('هذا العقد ليس قيد المراجعة'));
        exit;
    }
    
    // Get contract review history
    $historyStmt = $pdo->prepare("
        SELECT 
            al.*,
            u.name as user_name
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.contract_id = ?
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $historyStmt->execute([$contract_id]);
    $contractHistory = $historyStmt->fetchAll();

} catch (PDOException $e) {
    $error = 'خطأ في جلب بيانات العقد: ' . $e->getMessage();
}

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $contract) {
    $action = $_POST['action'] ?? '';
    $review_notes = trim($_POST['review_notes'] ?? '');
    
    if (!in_array($action, ['approve', 'reject'])) {
        $error = 'إجراء غير صحيح';
    } elseif ($action === 'reject' && empty($review_notes)) {
        $error = 'يجب إدخال سبب الرفض';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update contract status
            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
            $updateStmt = $pdo->prepare("
                UPDATE contracts SET 
                    status = ?, 
                    review_notes = ?, 
                    reviewed_by = ?, 
                    reviewed_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $updateStmt->execute([$newStatus, $review_notes, $user['id'], $contract_id]);
            
            // Log the activity
            $actionText = ($action === 'approve') ? 'تم اعتماد العقد' : 'تم رفض العقد';
            $logStmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, contract_id, action, description, created_at)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $logStmt->execute([$user['id'], $contract_id, $actionText, $review_notes]);
            
            // Create notification for contract creator
            if (class_exists('Notification')) {
                $notification = new Notification($pdo);
                $message = ($action === 'approve') 
                    ? "تم اعتماد العقد رقم {$contract['contract_number']}"
                    : "تم رفض العقد رقم {$contract['contract_number']}";
                
                $notification->create(
                    $contract['created_by'],
                    $message,
                    'contract_review',
                    $contract_id
                );
            }
            
            $pdo->commit();
            
            $success = ($action === 'approve') ? 'تم اعتماد العقد بنجاح' : 'تم رفض العقد';
            
            // Refresh contract data
            $stmt->execute([$contract_id]);
            $contract = $stmt->fetch();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'خطأ في حفظ المراجعة: ' . $e->getMessage();
        }
    }
}

// Helper functions
function formatCurrency($amount) {
    return number_format($amount, 2) . ' ريال سعودي';
}

function formatDate($date, $includeTime = false) {
    if (!$date) return 'غير محدد';
    $format = $includeTime ? 'Y/m/d H:i' : 'Y/m/d';
    return date($format, strtotime($date));
}

function getContractTypeName($type) {
    $types = [
        'investment' => 'استثمار نقدي',
        'property_investment' => 'استثمار بعقار',
        'real_estate' => 'عقاري'
    ];
    return $types[$type] ?? 'غير محدد';
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مراجعة العقد <?= htmlspecialchars($contract['contract_number'] ?? '') ?> - سما البنيان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/unified-theme.css" rel="stylesheet">
    <style>
        :root {
            --sma-gold: #D4AF37;
            --sma-dark-gold: #B8941F;
            --sma-gray: #6C757D;
        }
        
        .review-header {
            background: linear-gradient(135deg, #28a745, #20c997);
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
        
        .review-actions {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            border: 2px dashed #28a745;
        }
        
        .btn-approve {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background: #218838;
            border-color: #1e7e34;
            color: white;
        }
        
        .btn-reject {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        
        .btn-reject:hover {
            background: #c82333;
            border-color: #bd2130;
            color: white;
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
        
        .risk-assessment {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
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
        <?php if ($error && !$contract): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <div class="text-center">
            <a href="contracts_list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
        <?php elseif ($contract): ?>
        
        <!-- Review Header -->
        <div class="review-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="fas fa-clipboard-check me-2"></i>
                        مراجعة العقد رقم: <?= htmlspecialchars($contract['contract_number']) ?>
                    </h2>
                    <p class="mb-2">
                        <strong>العميل:</strong> <?= htmlspecialchars($contract['client_name']) ?>
                        <span class="ms-3">
                            <strong>المبلغ:</strong> <?= formatCurrency($contract['amount']) ?>
                        </span>
                    </p>
                    <p class="mb-0">
                        <strong>أنشأ بواسطة:</strong> <?= htmlspecialchars($contract['created_by_name']) ?>
                        <span class="ms-3">
                            <strong>تاريخ الإنشاء:</strong> <?= formatDate($contract['created_at'], true) ?>
                        </span>
                    require_once __DIR__ . '/../includes/auth.php';
                    require_once __DIR__ . '/../includes/helpers.php';
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-warning fs-6 p-2">
                        <i class="fas fa-clock me-1"></i>
                        قيد المراجعة
                    </span>
                    <div class="mt-2">
                        <a href="contract_view.php?id=<?= $contract['id'] ?>" class="btn btn-outline-light me-2">
                            <i class="fas fa-eye me-1"></i> عرض العقد
                        </a>
                        <a href="contracts_list.php" class="btn btn-outline-light">
                            <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

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

                <!-- Contract Financial Details -->
                <div class="info-card">
                    <h4 class="section-title">التفاصيل المالية</h4>
                    <div class="detail-row">
                        <span class="detail-label">نوع العقد:</span>
                        <span class="detail-value">
                            <span class="badge bg-info"><?= getContractTypeName($contract['contract_type']) ?></span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">مبلغ العقد:</span>
                        <span class="detail-value"><strong class="text-primary"><?= formatCurrency($contract['amount']) ?></strong></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">نسبة الربح:</span>
                        <span class="detail-value"><?= number_format($contract['profit_percentage'], 1) ?>%</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">صافي الربح المتوقع:</span>
                        <span class="detail-value"><strong class="text-success"><?= formatCurrency($contract['net_profit']) ?></strong></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">مدة العقد:</span>
                        <span class="detail-value"><?= $contract['contract_duration'] ?> أشهر</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">فترة دفع الأرباح:</span>
                        <span class="detail-value">
                            <?php
                            $intervals = [
                                'monthly' => 'شهري',
                                'quarterly' => 'ربع سنوي',
                                'semi_annual' => 'نصف سنوي',
                                'annual' => 'سنوي',
                                'end_of_contract' => 'نهاية العقد'
                            ];
                            echo $intervals[$contract['profit_interval']] ?? 'غير محدد';
                            ?>
                        </span>
                    </div>
                </div>

                <!-- Property Details (if applicable) -->
                <?php if ($contract['contract_type'] === 'property_investment' && 
                         (!empty($contract['property_description']) || $contract['property_value'] > 0)): ?>
                <div class="info-card">
                    <h4 class="section-title">تفاصيل العقار</h4>
                    <?php if (!empty($contract['property_description'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">وصف العقار:</span>
                        <span class="detail-value"><?= nl2br(htmlspecialchars($contract['property_description'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($contract['property_value'] > 0): ?>
                    <div class="detail-row">
                        <span class="detail-label">قيمة العقار:</span>
                        <span class="detail-value"><?= formatCurrency($contract['property_value']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Notes -->
                <?php if (!empty($contract['notes'])): ?>
                <div class="info-card">
                    <h4 class="section-title">ملاحظات العقد</h4>
                    <p><?= nl2br(htmlspecialchars($contract['notes'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- Review Actions -->
                <?php if ($contract['status'] === 'pending_review'): ?>
                <div class="review-actions">
                    <h4 class="section-title">إجراءات المراجعة</h4>
                    <form method="POST" id="reviewForm">
                        <div class="mb-3">
                            <label for="review_notes" class="form-label">ملاحظات المراجعة</label>
                            <textarea class="form-control" id="review_notes" name="review_notes" 
                                      rows="4" placeholder="أدخل ملاحظاتك حول العقد..."></textarea>
                            <div class="form-text">الملاحظات مطلوبة في حالة رفض العقد</div>
                        </div>
                        
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-approve btn-lg" onclick="submitReview('approve')">
                                <i class="fas fa-check me-1"></i> اعتماد العقد
                            </button>
                            <button type="button" class="btn btn-reject btn-lg" onclick="submitReview('reject')">
                                <i class="fas fa-times me-1"></i> رفض العقد
                            </button>
                        </div>
                        
                        <input type="hidden" name="action" id="reviewAction">
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Risk Assessment -->
                <div class="risk-assessment">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-line me-2"></i>
                        تقييم المخاطر
                    </h5>
                    
                    <div class="mb-3">
                        <small class="text-white-50">نسبة المبلغ للحد الأدنى:</small>
                        <div class="progress mt-1" style="height: 8px;">
                            <?php
                            $percentage = min(100, ($contract['amount'] / 100000) * 100);
                            $color = $percentage < 50 ? 'bg-warning' : ($percentage < 80 ? 'bg-info' : 'bg-success');
                            ?>
                            <div class="progress-bar <?= $color ?>" style="width: <?= $percentage ?>%"></div>
                        </div>
                        <small><?= number_format($percentage, 1) ?>%</small>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-white-50">نسبة الربح:</small>
                        <div class="progress mt-1" style="height: 8px;">
                            <?php
                            $profitPercentage = min(100, ($contract['profit_percentage'] / 50) * 100);
                            $profitColor = $profitPercentage < 40 ? 'bg-success' : ($profitPercentage < 70 ? 'bg-warning' : 'bg-danger');
                            ?>
                            <div class="progress-bar <?= $profitColor ?>" style="width: <?= $profitPercentage ?>%"></div>
                        </div>
                        <small><?= number_format($contract['profit_percentage'], 1) ?>%</small>
                    </div>
                    
                    <div class="text-center mt-3">
                        <?php
                        $riskScore = 0;
                        if ($contract['amount'] >= 100000) $riskScore += 1;
                        if ($contract['profit_percentage'] <= 30) $riskScore += 1;
                        if ($contract['contract_duration'] <= 12) $riskScore += 1;
                        
                        if ($riskScore >= 2) {
                            echo '<span class="badge bg-success">مخاطر منخفضة</span>';
                        } elseif ($riskScore === 1) {
                            echo '<span class="badge bg-warning">مخاطر متوسطة</span>';
                        } else {
                            echo '<span class="badge bg-danger">مخاطر عالية</span>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Contract History -->
                <div class="info-card">
                    <h4 class="section-title">سجل العقد</h4>
                    <?php if (!empty($contractHistory)): ?>
                    <div class="timeline">
                        <?php foreach ($contractHistory as $history): ?>
                        <div class="timeline-item">
                            <div>
                                <strong><?= htmlspecialchars($history['action']) ?></strong>
                                <?php if (!empty($history['description'])): ?>
                                <p class="mb-1 text-muted small"><?= htmlspecialchars($history['description']) ?></p>
                                <?php endif; ?>
                                <small class="text-muted">
                                    <?= htmlspecialchars($history['user_name']) ?> - 
                                    <?= formatDate($history['created_at'], true) ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">لا يوجد سجل متاح</p>
                    <?php endif; ?>
                </div>

                <!-- Quick Stats -->
                <div class="info-card">
                    <h4 class="section-title">إحصائيات سريعة</h4>
                    <div class="text-center">
                        <div class="row">
                            <div class="col-6">
                                <h5 class="text-primary"><?= formatCurrency($contract['amount']) ?></h5>
                                <small class="text-muted">قيمة العقد</small>
                            </div>
                            <div class="col-6">
                                <h5 class="text-success"><?= formatCurrency($contract['net_profit']) ?></h5>
                                <small class="text-muted">الربح المتوقع</small>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <h5 class="text-info"><?= number_format($contract['profit_percentage'], 1) ?>%</h5>
                                <small class="text-muted">نسبة الربح</small>
                            </div>
                            <div class="col-6">
                                <h5 class="text-warning"><?= $contract['contract_duration'] ?></h5>
                                <small class="text-muted">أشهر</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">تأكيد الإجراء</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="modalMessage"></p>
                    <div id="notesWarning" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        يجب إدخال سبب الرفض في حقل الملاحظات
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn" id="confirmBtn">تأكيد</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentAction = '';
        
        function submitReview(action) {
            currentAction = action;
            const notes = document.getElementById('review_notes').value.trim();
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const confirmBtn = document.getElementById('confirmBtn');
            const notesWarning = document.getElementById('notesWarning');
            
            // Reset warnings
            notesWarning.classList.add('d-none');
            
            if (action === 'approve') {
                modalTitle.textContent = 'تأكيد اعتماد العقد';
                modalMessage.textContent = 'هل أنت متأكد من رغبتك في اعتماد هذا العقد؟';
                confirmBtn.textContent = 'اعتماد العقد';
                confirmBtn.className = 'btn btn-success';
            } else if (action === 'reject') {
                modalTitle.textContent = 'تأكيد رفض العقد';
                modalMessage.textContent = 'هل أنت متأكد من رغبتك في رفض هذا العقد؟';
                confirmBtn.textContent = 'رفض العقد';
                confirmBtn.className = 'btn btn-danger';
                
                // Check if notes are required for rejection
                if (!notes) {
                    notesWarning.classList.remove('d-none');
                }
            }
            
            modal.show();
        }
        
        document.getElementById('confirmBtn').addEventListener('click', function() {
            const notes = document.getElementById('review_notes').value.trim();
            
            // Validate notes for rejection
            if (currentAction === 'reject' && !notes) {
                document.getElementById('notesWarning').classList.remove('d-none');
                return;
            }
            
            // Submit the form
            document.getElementById('reviewAction').value = currentAction;
            document.getElementById('reviewForm').submit();
        });
        
        // Auto-resize textarea
        document.getElementById('review_notes').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html>