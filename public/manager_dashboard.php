// refactor(manager_dashboard.php): render via master_layout + normalize links
<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth.php';

// التحقق من صلاحية المدير
$auth->requireAuth();
$user = $auth->getCurrentUser();

// التحقق من أن المستخدم مدير أو أدمن
if (!in_array($user['role'], ['manager', 'admin'])) {
    header('HTTP/1.0 403 Forbidden');
    die('غير مسموح لك بالوصول لهذه الصفحة');
}

try {
    // جلب جميع العقود مع بيانات المنشئ
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as created_by_name, u.email as created_by_email,
               reviewer.name as reviewed_by_name
        FROM contracts c 
        LEFT JOIN users u ON c.created_by = u.id 
        LEFT JOIN users reviewer ON c.reviewed_by = reviewer.id
        ORDER BY 
            CASE c.status
                WHEN 'pending' THEN 1
                WHEN 'draft' THEN 2
                WHEN 'active' THEN 3
                WHEN 'cancelled' THEN 4
                WHEN 'completed' THEN 5
                ELSE 6
            END,
            c.created_at DESC
    ");
    $stmt->execute();
    $contracts = $stmt->fetchAll();
    
    // إحصائيات شاملة
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_contracts,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_contracts,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_contracts,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as approved_contracts,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as signed_contracts,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as rejected_contracts,
            COALESCE(SUM(amount), 0) as total_amount,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as signed_amount
        FROM contracts
    ");
    $statsStmt->execute();
    $stats = $statsStmt->fetch();
    
    // إحصائيات الموظفين
    $employeeStatsStmt = $pdo->prepare("
        SELECT 
            u.name, u.email,
            COUNT(c.id) as total_contracts,
            SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) as signed_contracts,
            COALESCE(SUM(c.amount), 0) as total_amount
        FROM users u
        LEFT JOIN contracts c ON u.id = c.created_by
        WHERE u.role = 'employee' AND u.status = 'active'
        GROUP BY u.id, u.name, u.email
        ORDER BY total_contracts DESC
    ");
    $employeeStatsStmt->execute();
    $employeeStats = $employeeStatsStmt->fetchAll();
    
    // الإشعارات الأخيرة
    $notifications = $auth->getNotifications($user['id'], 5);
    
} catch (PDOException $e) {
    $error = "خطأ في جلب البيانات: " . $e->getMessage();
    // تعيين قيم افتراضية في حالة الخطأ
    $contracts = [];
    $employeeStats = [];
    $notifications = [];
    $stats = [
        'total_contracts' => 0,
        'draft_contracts' => 0,
        'pending_contracts' => 0,
        'approved_contracts' => 0,
        'signed_contracts' => 0,
        'rejected_contracts' => 0,
        'total_amount' => 0,
        'signed_amount' => 0
    ];
}

// التأكد من أن المتغيرات معرّفة
if (!isset($contracts)) $contracts = [];
if (!isset($employeeStats)) $employeeStats = [];
if (!isset($notifications)) $notifications = [];
if (!isset($stats)) {
    $stats = [
        'total_contracts' => 0,
        'draft_contracts' => 0,
        'pending_contracts' => 0,
        'approved_contracts' => 0,
        'signed_contracts' => 0,
        'rejected_contracts' => 0,
        'total_amount' => 0,
        'signed_amount' => 0
    ];
}

// Note: Status functions now available via autoloaded App\Helpers\Functions class
$title = "لوحة المدير";
$is_auth_page = false;
$show_sidebar = true;
$additional_head = $additional_head ?? '';
$additional_scripts = $additional_scripts ?? '';
ob_start();
?>
<!-- ...existing code... -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary text-white">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="mb-1"><?= $stats['total_contracts'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">إجمالي العقود</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-warning text-white">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="mb-1"><?= $stats['pending_contracts'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">قيد المراجعة</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success text-white">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="mb-1"><?= $stats['signed_contracts'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">عقود موقعة</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info text-white">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3 class="mb-1"><?= number_format($stats['signed_amount'] ?? 0) ?> ر.س</h3>
                    <p class="text-muted mb-0">إجمالي العقود الموقعة</p>
                </div>
            </div>
        </div>
        
        <!-- التبويبات -->
        <ul class="nav nav-tabs" id="managerTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="contracts-tab" data-bs-toggle="tab" data-bs-target="#contracts" type="button" role="tab">
                    <i class="fas fa-file-contract me-2"></i>العقود (<?= is_array($contracts) ? count($contracts) : 0 ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>الموظفين (<?= is_array($employeeStats) ? count($employeeStats) : 0 ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                    <i class="fas fa-bell me-2"></i>الإشعارات (<?= is_array($notifications) ? count($notifications) : 0 ?>)
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="managerTabsContent">
            <!-- تبويب العقود -->
            <div class="tab-pane fade show active" id="contracts" role="tabpanel">
                <div class="contracts-table">
                    <?php if (empty($contracts)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد عقود بعد</h5>
                            <p class="text-muted">سيظهر هنا العقود المرسلة من الموظفين</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>رقم العقد</th>
                                        <th>اسم العميل</th>
                                        <th>المبلغ</th>
                                        <th>الموظف</th>
                                        <th>الحالة</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (is_array($contracts) && !empty($contracts)): ?>
                                        <?php foreach ($contracts as $contract): ?>
                                        <?php $statusInfo = getStatusInfo($contract['status']); ?>
                                        <tr <?= $contract['status'] === 'pending' ? 'class="pending-highlight"' : '' ?>>
                                            <td>
                                                <strong><?= htmlspecialchars($contract['contract_number']) ?></strong>
                                                <?php if ($contract['status'] === 'pending'): ?>
                                                    <i class="fas fa-exclamation-circle text-warning ms-1" title="يحتاج مراجعة"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($contract['client_name']) ?></td>
                                            <td><?= number_format($contract['amount'] ?? 0) ?> ر.س</td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($contract['created_by_name']) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= htmlspecialchars($contract['created_by_email']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?= $statusInfo['class'] ?>">
                                                    <?= $statusInfo['text'] ?>
                                                </span>
                                            </td>
                                            <td><?= date('Y-m-d', strtotime($contract['created_at'])) ?></td>
                                            <td>
                                                                <a href="<?= asset('contract_view.php') ?>?id=<?= $contract['id'] ?>" 
                                                                    class="action-btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($contract['status'] === 'pending'): ?>
                                                    <button onclick="approveContract(<?= $contract['id'] ?>)" 
                                                            class="action-btn btn-approve">
                                                        <i class="fas fa-check"></i> موافقة
                                                    </button>
                                                    <button onclick="rejectContract(<?= $contract['id'] ?>)" 
                                                            class="action-btn btn-reject">
                                                        <i class="fas fa-times"></i> رفض
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($contract['status'] === 'active'): ?>
                                                    <button onclick="signContract(<?= $contract['id'] ?>)" 
                                                            class="action-btn btn-sign">
                                                        <i class="fas fa-signature"></i> توقيع
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($contract['status'] === 'completed'): ?>
                                                                     <a href="<?= asset('download_contract.php') ?>?id=<?= $contract['id'] ?>" 
                                                                         class="action-btn btn-outline-success">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">لا توجد عقود</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- تبويب الموظفين -->
            <div class="tab-pane fade" id="employees" role="tabpanel">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5><i class="fas fa-users me-2"></i>إحصائيات الموظفين</h5>
                        <a href="<?= asset('manage_employees.php') ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>إدارة الموظفين
                        </a>
                    </div>
                    
                    <?php if (!is_array($employeeStats) || empty($employeeStats)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا يوجد موظفين نشطين</h5>
                            <a href="<?= asset('manage_employees.php') ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>إضافة موظف جديد
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($employeeStats as $employee): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="employee-card">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <i class="fas fa-user me-2"></i><?= htmlspecialchars($employee['name']) ?>
                                                </h6>
                                                <p class="text-muted mb-2"><?= htmlspecialchars($employee['email']) ?></p>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary"><?= $employee['total_contracts'] ?> عقد</span>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-3">
                                            <div class="col-6">
                                                <small class="text-muted">العقود الموقعة</small>
                                                <div class="fw-bold text-success"><?= $employee['signed_contracts'] ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">إجمالي المبالغ</small>
                                                <div class="fw-bold"><?= number_format($employee['total_amount'] ?? 0) ?> ر.س</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- تبويب الإشعارات -->
            <div class="tab-pane fade" id="notifications" role="tabpanel">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5><i class="fas fa-bell me-2"></i>الإشعارات الأخيرة</h5>
                        <a href="<?= asset('notifications.php') ?>" class="btn btn-outline-primary btn-sm">
                            عرض جميع الإشعارات
                        </a>
                    </div>
                    
                    <?php if (!is_array($notifications) || empty($notifications)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد إشعارات جديدة</h5>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
                                <div class="d-flex justify-content-between">
                                    <strong><?= htmlspecialchars($notification['title']) ?></strong>
                                    <small class="text-muted">
                                        <?= date('Y-m-d H:i', strtotime($notification['created_at'])) ?>
                                    </small>
                                </div>
                                <p class="mb-0 mt-1"><?= htmlspecialchars($notification['message']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal للموافقة/الرفض -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="managerNotes" class="form-label">ملاحظات (اختيارية)</label>
                        <textarea class="form-control" id="managerNotes" rows="3" placeholder="أضف ملاحظاتك هنا..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" id="confirmActionBtn" class="btn"></button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentContractId = null;
        let currentAction = null;
        
        function approveContract(contractId) {
            currentContractId = contractId;
            currentAction = 'approve';
            
            document.getElementById('actionModalTitle').textContent = 'موافقة على العقد';
            document.getElementById('confirmActionBtn').textContent = 'موافقة';
            document.getElementById('confirmActionBtn').className = 'btn btn-success';
            document.getElementById('managerNotes').placeholder = 'ملاحظات الموافقة (اختيارية)';
            
            new bootstrap.Modal(document.getElementById('actionModal')).show();
        }
        
        function rejectContract(contractId) {
            currentContractId = contractId;
            currentAction = 'reject';
            
            document.getElementById('actionModalTitle').textContent = 'رفض العقد';
            document.getElementById('confirmActionBtn').textContent = 'رفض';
            document.getElementById('confirmActionBtn').className = 'btn btn-danger';
            document.getElementById('managerNotes').placeholder = 'أسباب الرفض...';
            
            new bootstrap.Modal(document.getElementById('actionModal')).show();
        }
        
        function signContract(contractId) {
            if (confirm('هل أنت متأكد من توقيع هذا العقد؟')) {
                processContractAction(contractId, 'sign', '');
            }
        }
        
        document.getElementById('confirmActionBtn').addEventListener('click', function() {
            const notes = document.getElementById('managerNotes').value;
            processContractAction(currentContractId, currentAction, notes);
            bootstrap.Modal.getInstance(document.getElementById('actionModal')).hide();
        });
        
        function processContractAction(contractId, action, notes) {
            const formData = new FormData();
            formData.append('contract_id', contractId);
            formData.append('action', action);
            formData.append('notes', notes || '');
            
            fetch('contract_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // تحديث الصف في الجدول
                    updateContractRow(contractId, data.new_status, data.status_text, data.status_class);
                    
                    // إظهار رسالة نجاح
                    showAlert(data.message, 'success');
                } else {
                    showAlert('حدث خطأ: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('حدث خطأ في الاتصال', 'danger');
            });
        }
        
        function updateContractRow(contractId, newStatus, statusText, statusClass) {
            // البحث عن الصف وتحديث الحالة
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const actionBtns = row.querySelectorAll('[onclick*="' + contractId + '"]');
                if (actionBtns.length > 0) {
                    // تحديث شارة الحالة
                    const statusBadge = row.querySelector('.badge');
                    if (statusBadge) {
                        statusBadge.className = 'badge ' + statusClass;
                        statusBadge.textContent = statusText;
                    }
                    
                    // تحديث الأزرار بناءً على الحالة الجديدة
                    const actionsCell = row.querySelector('td:last-child');
                    if (actionsCell && newStatus) {
                        updateActionButtons(actionsCell, contractId, newStatus);
                    }
                }
            });
        }
        
        function updateActionButtons(actionsCell, contractId, status) {
            let buttonsHtml = `
                <a href="<?= asset('contract_view.php') ?>?id=${contractId}" class="action-btn btn-outline-primary">
                    <i class="fas fa-eye"></i>
                </a>
            `;
            
            if (status === 'draft') {
                buttonsHtml += `
                    <button onclick="approveContract(${contractId})" class="action-btn btn-approve">
                        <i class="fas fa-check"></i> موافقة
                    </button>
                    <button onclick="rejectContract(${contractId})" class="action-btn btn-reject">
                        <i class="fas fa-times"></i> رفض
                    </button>
                `;
            } else if (status === 'active') {
                buttonsHtml += `
                    <button onclick="signContract(${contractId})" class="action-btn btn-sign">
                        <i class="fas fa-signature"></i> توقيع
                    </button>
                `;
            } else if (status === 'completed') {
                buttonsHtml += `
                    <a href="<?= asset('export_pdf.php') ?>?id=${contractId}" class="action-btn btn-outline-success">
                        <i class="fas fa-download"></i>
                    </a>
                `;
            }
            
            actionsCell.innerHTML = buttonsHtml;
        }
        
        function showAlert(message, type) {
            // إنشاء تنبيه Bootstrap
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // إزالة التنبيه تلقائياً بعد 5 ثوان
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
        
    </script>
    
    <!-- Modal للتوقيع الرقمي -->
    <div class="modal fade" id="signatureModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">التوقيع الرقمي للعقد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="signature-pad-container mb-3">
                        <label class="form-label">قم بالتوقيع هنا:</label>
                        <div class="signature-pad-wrapper">
                            <canvas id="signaturePad" width="600" height="200" style="border: 2px solid #ddd; border-radius: 8px;"></canvas>
                        </div>
                        <div class="signature-controls mt-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="clearSignature()">
                                <i class="fas fa-eraser"></i> مسح
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="signaturePassword" class="form-label">كلمة المرور للتأكيد:</label>
                        <input type="password" class="form-control" id="signaturePassword" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="signatureComments" class="form-label">ملاحظات إضافية (اختياري):</label>
                        <textarea class="form-control" id="signatureComments" rows="3" placeholder="أضف أي ملاحظات للتوقيع..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-success" onclick="submitSignature()">
                        <i class="fas fa-signature"></i> تأكيد التوقيع
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // متغيرات التوقيع الرقمي
        let signaturePad;
        let currentContractForSigning;
        
        // تهيئة لوحة التوقيع عند فتح النافذة
        document.getElementById('signatureModal').addEventListener('shown.bs.modal', function () {
            initSignaturePad();
        });
        
        function initSignaturePad() {
            const canvas = document.getElementById('signaturePad');
            const ctx = canvas.getContext('2d');
            
            // إعداد الخصائص الأساسية
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = 2;
            ctx.strokeStyle = '#000';
            
            let isDrawing = false;
            
            // أحداث الماوس
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);
            
            // أحداث اللمس للهواتف المحمولة
            canvas.addEventListener('touchstart', handleTouch);
            canvas.addEventListener('touchmove', handleTouch);
            canvas.addEventListener('touchend', stopDrawing);
            
            function startDrawing(e) {
                isDrawing = true;
                const rect = canvas.getBoundingClientRect();
                ctx.beginPath();
                ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
            }
            
            function draw(e) {
                if (!isDrawing) return;
                const rect = canvas.getBoundingClientRect();
                ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
                ctx.stroke();
            }
            
            function stopDrawing() {
                isDrawing = false;
            }
            
            function handleTouch(e) {
                e.preventDefault();
                const touch = e.touches[0];
                const rect = canvas.getBoundingClientRect();
                const mouseEvent = new MouseEvent(e.type === 'touchstart' ? 'mousedown' : 
                                                 e.type === 'touchmove' ? 'mousemove' : 'mouseup', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            }
            
            signaturePad = { canvas, ctx };
        }
        
        function clearSignature() {
            if (signaturePad) {
                const canvas = signaturePad.canvas;
                const ctx = signaturePad.ctx;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
        }
        
        function signContract(contractId) {
            currentContractForSigning = contractId;
            
            // إعادة تعيين النموذج
            document.getElementById('signaturePassword').value = '';
            document.getElementById('signatureComments').value = '';
            
            // فتح نافذة التوقيع
            const modal = new bootstrap.Modal(document.getElementById('signatureModal'));
            modal.show();
        }
        
        function submitSignature() {
            const password = document.getElementById('signaturePassword').value;
            const comments = document.getElementById('signatureComments').value;
            
            if (!password) {
                showAlert('يرجى إدخال كلمة المرور للتأكيد', 'warning');
                return;
            }
            
            // التحقق من وجود توقيع
            const canvas = signaturePad.canvas;
            const ctx = signaturePad.ctx;
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const hasDrawing = imageData.data.some(channel => channel !== 0);
            
            if (!hasDrawing) {
                showAlert('يرجى إضافة التوقيع أولاً', 'warning');
                return;
            }
            
            // تحويل التوقيع إلى base64
            const signatureData = canvas.toDataURL('image/png');
            
            // إرسال البيانات
            const formData = new FormData();
            formData.append('action', 'sign');
            formData.append('contract_id', currentContractForSigning);
            formData.append('password', password);
            formData.append('signature', signatureData);
            formData.append('comments', comments);
            
            fetch('contract_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('تم توقيع العقد بنجاح', 'success');
                    updateContractRow(currentContractForSigning, 'completed', 'مكتمل', 'badge-success');
                    
                    // إغلاق النافذة
                    const modal = bootstrap.Modal.getInstance(document.getElementById('signatureModal'));
                    modal.hide();
                } else {
                    showAlert(data.message || 'فشل في توقيع العقد', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('حدث خطأ في الاتصال', 'danger');
            });
        }
    </script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../templates/layouts/master_layout.php';