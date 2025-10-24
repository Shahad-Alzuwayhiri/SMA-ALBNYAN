<div class="contracts-container">
    <div class="page-header">
        <div class="header-left">
            <h2>إدارة العقود</h2>
            <p>عرض وإدارة العقود في النظام</p>
        </div>
        <div class="header-actions">
            <div class="btn-group">
                <a href="/contracts/create" class="btn btn-secondary">
                    <span class="btn-icon">📝</span>
                    عقد بسيط
                </a>
                <a href="/contracts/create-detailed" class="btn btn-primary">
                    <span class="btn-icon">📋</span>
                    عقد مفصل
                </a>
                <a href="/contracts/import-text" class="btn btn-outline">
                    <span class="btn-icon">📥</span>
                    استيراد من النص
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✅</span>
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <span class="alert-icon">❌</span>
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filters-section">
        <div class="filters">
            <select id="status-filter" onchange="filterContracts()">
                <option value="">جميع الحالات</option>
                <option value="draft">مسودة</option>
                <option value="pending">في الانتظار</option>
                <option value="approved">معتمد</option>
                <option value="completed">مكتمل</option>
                <option value="rejected">مرفوض</option>
            </select>
            
            <input type="text" id="search-input" placeholder="البحث في العقود..." onkeyup="searchContracts()">
        </div>
    </div>

    <!-- Contracts Table -->
    <div class="table-container">
        <?php if (empty($contracts)): ?>
            <div class="empty-state">
                <div class="empty-icon">📄</div>
                <h3>لا توجد عقود</h3>
                <p>لم يتم إنشاء أي عقود بعد</p>
                <a href="/contracts/create" class="btn btn-primary">إنشاء أول عقد</a>
            </div>
        <?php else: ?>
            <table class="contracts-table" id="contracts-table">
                <thead>
                    <tr>
                        <th>رقم العقد</th>
                        <th>العنوان</th>
                        <th>العميل</th>
                        <th>القيمة</th>
                        <th>الحالة</th>
                        <th>تاريخ الإنشاء</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contracts as $contract): ?>
                        <tr data-status="<?= htmlspecialchars($contract['status']) ?>">
                            <td class="serial">
                                <strong><?= htmlspecialchars($contract['serial']) ?></strong>
                            </td>
                            <td class="title">
                                <?= htmlspecialchars($contract['title'] ?? 'عقد بدون عنوان') ?>
                            </td>
                            <td class="client">
                                <?= htmlspecialchars($contract['client_name']) ?>
                            </td>
                            <td class="amount">
                                <?php if ($contract['amount'] > 0): ?>
                                    <?= number_format($contract['amount'], 2) ?> ر.س
                                <?php else: ?>
                                    غير محدد
                                <?php endif; ?>
                            </td>
                            <td class="status">
                                <span class="status-badge status-<?= htmlspecialchars($contract['status']) ?>">
                                    <?php
                                    $statusLabels = [
                                        'draft' => 'مسودة',
                                        'pending' => 'في الانتظار',
                                        'approved' => 'معتمد',
                                        'completed' => 'مكتمل',
                                        'rejected' => 'مرفوض'
                                    ];
                                    echo $statusLabels[$contract['status']] ?? $contract['status'];
                                    ?>
                                </span>
                            </td>
                            <td class="date">
                                <?= date('Y-m-d', strtotime($contract['created_at'])) ?>
                            </td>
                            <td class="actions">
                                <div class="action-buttons">
                                    <?php if (isset($contract['is_detailed']) && $contract['is_detailed']): ?>
                                        <a href="/contracts/view-detailed/<?= $contract['id'] ?>" class="btn-action btn-view-detailed" title="عرض مفصل">
                                            �
                                        </a>
                                        <a href="/contracts/export-pdf/<?= $contract['id'] ?>" class="btn-action btn-pdf" title="تصدير PDF">
                                            📄
                                        </a>
                                    <?php else: ?>
                                        <a href="/contracts/<?= $contract['id'] ?>" class="btn-action btn-view" title="عرض">
                                            �👁️
                                        </a>
                                    <?php endif; ?>
                                    <a href="/contracts/<?= $contract['id'] ?>/edit" class="btn-action btn-edit" title="تعديل">
                                        ✏️
                                    </a>
                                    <?php if ($_SESSION['user_role'] === 'manager'): ?>
                                        <button class="btn-action btn-delete" onclick="deleteContract(<?= $contract['id'] ?>)" title="حذف">
                                            🗑️
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.contracts-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.header-left h2 {
    color: var(--text-primary);
    margin-bottom: 8px;
}

.header-left p {
    color: var(--text-secondary);
    margin: 0;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: var(--primary-gradient);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.filters-section {
    margin-bottom: 20px;
}

.filters {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.filters select,
.filters input {
    padding: 10px 16px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
    backdrop-filter: blur(10px);
}

.filters select:focus,
.filters input:focus {
    outline: none;
    border-color: var(--accent-color);
}

.table-container {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    overflow-x: auto;
}

.contracts-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.contracts-table th,
.contracts-table td {
    padding: 12px;
    text-align: right;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.contracts-table th {
    background: rgba(255, 255, 255, 0.1);
    font-weight: 600;
    color: var(--text-primary);
    position: sticky;
    top: 0;
}

.contracts-table td {
    color: var(--text-primary);
}

.serial strong {
    color: var(--accent-color);
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-draft {
    background: rgba(108, 117, 125, 0.2);
    color: #6c757d;
}

.status-pending {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
}

.status-approved {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
}

.status-completed {
    background: rgba(23, 162, 184, 0.2);
    color: #17a2b8;
}

.status-rejected {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.1);
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-action:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.1);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: var(--text-primary);
    margin-bottom: 10px;
}

.empty-state p {
    color: var(--text-secondary);
    margin-bottom: 30px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filters {
        flex-direction: column;
    }
    
    .table-container {
        padding: 10px;
    }
    
    .contracts-table {
        font-size: 14px;
    }
    
    .contracts-table th,
    .contracts-table td {
        padding: 8px 4px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
function filterContracts() {
    const statusFilter = document.getElementById('status-filter').value;
    const table = document.getElementById('contracts-table');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let row of rows) {
        const status = row.getAttribute('data-status');
        if (statusFilter === '' || status === statusFilter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

function searchContracts() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const table = document.getElementById('contracts-table');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let row of rows) {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

function deleteContract(id) {
    if (confirm('هل أنت متأكد من حذف هذا العقد؟')) {
        // TODO: Implement delete functionality
        alert('سيتم تنفيذ وظيفة الحذف قريباً');
    }
}
</script>