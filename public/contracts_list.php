<?php
/**
 * Contract List Page - Sama Al-Bunyan Contracts Platform
 * Displays all contracts with filters, search, and action buttons
 */

require_once '../includes/auth.php';
require_once '../includes/helpers.php';

$title = 'قائمة العقود';
$is_auth_page = false;
$show_sidebar = true;

// Authentication check
$auth->requireAuth();
$user = $auth->getCurrentUser();

// Initialize variables
$contracts = [];
$error = '';
$success = '';
$totalContracts = 0;
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build WHERE clause based on filters
$whereConditions = [];
$params = [];

// Role-based access control
if ($user['role'] === 'employee') {
    $whereConditions[] = "c.created_by = ?";
    $params[] = $user['id'];
}

// Status filter
if ($statusFilter && in_array($statusFilter, ['draft', 'pending_review', 'approved', 'rejected', 'signed'])) {
    $whereConditions[] = "c.status = ?";
    $params[] = $statusFilter;
}

// Type filter
if ($typeFilter && in_array($typeFilter, ['investment', 'property_investment', 'real_estate'])) {
    $whereConditions[] = "c.contract_type = ?";
    $params[] = $typeFilter;
}

// Search query
if ($searchQuery) {
    $whereConditions[] = "(c.client_name LIKE ? OR c.contract_number LIKE ? OR c.client_id LIKE ?)";
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM contracts c $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalContracts = $countStmt->fetchColumn();

    // Get contracts with pagination
    $sql = "
        SELECT 
            c.*,
            u1.name as created_by_name,
            u2.name as reviewed_by_name
        FROM contracts c
        LEFT JOIN users u1 ON c.created_by = u1.id
        LEFT JOIN users u2 ON c.reviewed_by = u2.id
        $whereClause
        ORDER BY c.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contracts = $stmt->fetchAll();

    // Get statistics
    $statsSql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'signed' THEN 1 ELSE 0 END) as signed,
            SUM(amount) as total_amount
        FROM contracts c " . ($user['role'] === 'employee' ? "WHERE c.created_by = {$user['id']}" : "");
    
    $statsStmt = $pdo->query($statsSql);
    $stats = $statsStmt->fetch();

} catch (PDOException $e) {
    $error = "خطأ في جلب البيانات: " . $e->getMessage();
    $contracts = [];
    $stats = ['total' => 0, 'draft' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'signed' => 0, 'total_amount' => 0];
}

// Helper functions
function getStatusBadge($status) {
    $badges = [
        'draft' => '<span class="badge bg-secondary">مسودة</span>',
        'pending_review' => '<span class="badge bg-warning">قيد المراجعة</span>',
        'approved' => '<span class="badge bg-success">معتمد</span>',
        'rejected' => '<span class="badge bg-danger">مرفوض</span>',
        'signed' => '<span class="badge bg-primary">موقع</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-light text-dark">غير محدد</span>';
}

function getContractTypeName($type) {
    $types = [
        'investment' => 'استثمار نقدي',
        'property_investment' => 'استثمار بعقار',
        'real_estate' => 'عقاري'
    ];
    return $types[$type] ?? 'غير محدد';
}

// Buffer all page content for master layout
$totalPages = ceil($totalContracts / $limit);
ob_start();
?>
<div class="container-fluid mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="text-dark mb-1">
                            <i class="fas fa-file-contract text-warning me-2"></i>
                            قائمة العقود
                        </h2>
                        <p class="text-muted mb-0">عرض وإدارة جميع العقود في النظام</p>
                    </div>
                    <?php if (in_array($user['role'], ['manager', 'admin', 'employee'])): ?>
                    <div>
                        <a href="<?= asset('create_contract.php') ?>" class="btn btn-sma-primary">
                            <i class="fas fa-plus me-1"></i>
                            عقد جديد
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2 col-6">
                <div class="stats-card text-center">
                    <h3 class="mb-1"><?= number_format($stats['total']) ?></h3>
                    <small>إجمالي العقود</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card text-center">
                    <h3 class="mb-1"><?= number_format($stats['draft']) ?></h3>
                    <small>مسودات</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card text-center">
                    <h3 class="mb-1"><?= number_format($stats['pending']) ?></h3>
                    <small>قيد المراجعة</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card text-center">
                    <h3 class="mb-1"><?= number_format($stats['approved']) ?></h3>
                    <small>معتمدة</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card text-center">
                    <h3 class="mb-1"><?= number_format($stats['signed']) ?></h3>
                    <small>موقعة</small>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card text-center">
                    <h3 class="mb-1"><?= number_format($stats['total_amount']) ?></h3>
                    <small>إجمالي المبالغ</small>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">البحث</label>
                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($searchQuery) ?>" 
                           placeholder="اسم العميل، رقم العقد، أو رقم الهوية">
                </div>
                <div class="col-md-2">
                    <label class="form-label">الحالة</label>
                    <select class="form-select" name="status">
                        <option value="">جميع الحالات</option>
                        <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>مسودة</option>
                        <option value="pending_review" <?= $statusFilter === 'pending_review' ? 'selected' : '' ?>>قيد المراجعة</option>
                        <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>معتمد</option>
                        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>مرفوض</option>
                        <option value="signed" <?= $statusFilter === 'signed' ? 'selected' : '' ?>>موقع</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">نوع العقد</label>
                    <select class="form-select" name="type">
                        <option value="">جميع الأنواع</option>
                        <option value="investment" <?= $typeFilter === 'investment' ? 'selected' : '' ?>>استثمار نقدي</option>
                        <option value="property_investment" <?= $typeFilter === 'property_investment' ? 'selected' : '' ?>>استثمار بعقار</option>
                        <option value="real_estate" <?= $typeFilter === 'real_estate' ? 'selected' : '' ?>>عقاري</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sma-primary">
                            <i class="fas fa-search me-1"></i> بحث
                        </button>
                        <a href="contracts_list.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> مسح
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Error/Success Messages -->
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
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

        <!-- Contracts Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>رقم العقد</th>
                            <th>اسم العميل</th>
                            <th>نوع العقد</th>
                            <th>نوع الاستثمار</th>
                            <th>المبلغ/القيمة</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>منشئ العقد</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contracts)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">لا توجد عقود مطابقة للبحث</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td>
                                <strong class="text-primary"><?= htmlspecialchars($contract['contract_number']) ?></strong>
                            </td>
                            <td>
                                <?= htmlspecialchars($contract['client_name']) ?>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($contract['client_id']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= getContractTypeName($contract['contract_type']) ?></span>
                            </td>
                            <td>
                                <?php 
                                $investmentType = $contract['investment_type'] ?? 'cash';
                                $badgeClass = $investmentType === 'cash' ? 'bg-success' : 'bg-warning';
                                $typeText = $investmentType === 'cash' ? 'نقدي' : 'عقاري';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $typeText ?></span>
                            </td>
                            <td>
                                <?php if (($contract['investment_type'] ?? 'cash') === 'cash'): ?>
                                    <strong><?= number_format($contract['amount']) ?> ريال</strong>
                                <?php else: ?>
                                    <strong><?= number_format($contract['property_market_value'] ?? 0) ?> ريال</strong>
                                    <br><small class="text-muted">قيمة العقار</small>
                                <?php endif; ?>
                            </td>
                            <td><?= getStatusBadge($contract['status']) ?></td>
                            <td>
                                <?= date('Y/m/d', strtotime($contract['created_at'])) ?>
                                <br>
                                <small class="text-muted"><?= date('H:i', strtotime($contract['created_at'])) ?></small>
                            </td>
                            <td><?= htmlspecialchars($contract['created_by_name'] ?? 'غير محدد') ?></td>
                            <td>
                                <div class="action-buttons">
                                    <!-- View Contract -->
                                    <a href="<?= asset('contract_view.php') ?>?id=<?= $contract['id'] ?>" 
                                       class="btn btn-outline-primary btn-sm" title="عرض العقد">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <!-- Edit Contract (if draft or rejected) -->
                                    <?php if (in_array($contract['status'], ['draft', 'rejected']) && 
                                             ($user['role'] !== 'employee' || $contract['created_by'] == $user['id'])): ?>
                                    <a href="<?= asset('contract_edit.php') ?>?id=<?= $contract['id'] ?>" 
                                       class="btn btn-outline-warning btn-sm" title="تعديل العقد">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <!-- Review Contract (managers only) -->
                                    <?php if ($contract['status'] === 'pending_review' && in_array($user['role'], ['manager', 'admin'])): ?>
                                    <a href="<?= asset('contract_review.php') ?>?id=<?= $contract['id'] ?>" 
                                       class="btn btn-outline-success btn-sm" title="مراجعة العقد">
                                        <i class="fas fa-check-double"></i>
                                    </a>
                                    <?php endif; ?>
                                    <!-- Download PDF -->
                                    <?php if (in_array($contract['status'], ['approved', 'signed'])): ?>
                                    <a href="<?= asset('contract_pdf.php') ?>?id=<?= $contract['id'] ?>" 
                                       class="btn btn-outline-info btn-sm" title="تحميل PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <?php endif; ?>
                                    <!-- Files Management -->
                                    <a href="<?= asset('contract_files.php') ?>?id=<?= $contract['id'] ?>" 
                                       class="btn btn-outline-secondary btn-sm" title="إدارة الملفات">
                                        <i class="fas fa-paperclip"></i>
                                    </a>
                                    <!-- Delete Contract (drafts only, by creator or manager) -->
                                    <?php if ($contract['status'] === 'draft' && 
                                             (in_array($user['role'], ['manager', 'admin']) || $contract['created_by'] == $user['id'])): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                            onclick="deleteContract(<?= $contract['id'] ?>)" title="حذف العقد">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Contract pages navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأكيد الحذف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من رغبتك في حذف هذا العقد؟</p>
                    <p class="text-danger"><small>هذا الإجراء لا يمكن التراجع عنه.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">حذف العقد</button>
                </div>
            </div>
        </div>
    </div>

<?php
$content = ob_get_clean();
$additional_scripts = '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
?>
<?php include __DIR__ . '/../templates/master_layout.php'; ?>
<script>
        let contractToDelete = null;
        
        function deleteContract(contractId) {
            contractToDelete = contractId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
        
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (contractToDelete) {
                // Send delete request
                fetch('delete_contract.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({id: contractToDelete})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('خطأ في حذف العقد: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ أثناء حذف العقد');
                });
                
                bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            }
        });
    </script>