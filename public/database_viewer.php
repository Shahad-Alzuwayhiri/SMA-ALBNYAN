<?php
session_start();
require_once '../includes/auth.php';

// التحقق من تسجيل الدخول والصلاحية
if (!$auth->isLoggedIn() || !in_array($auth->getCurrentUser()['role'], ['admin', 'manager'])) {
    header('Location: login.php');
    exit;
}

$database_path = __DIR__ . '/../database/contracts.db';
$pdo = new PDO("sqlite:$database_path");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// الحصول على قائمة الجداول
$tables_query = "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'";
$tables = $pdo->query($tables_query)->fetchAll(PDO::FETCH_COLUMN);

// Sanitize and validate inputs
$current_table = isset($_GET['table']) && in_array($_GET['table'], $tables) ? $_GET['table'] : $tables[0];
$search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search']), ENT_QUOTES, 'UTF-8') : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// بناء استعلام البحث
$where_clause = '';
$params = [];

if (!empty($search) && in_array($current_table, $tables)) {
    // الحصول على أعمدة الجدول
    $columns_query = "PRAGMA table_info($current_table)";
    $columns = $pdo->query($columns_query)->fetchAll(PDO::FETCH_ASSOC);
    
    $search_conditions = [];
    foreach ($columns as $column) {
        $search_conditions[] = "{$column['name']} LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($search_conditions)) {
        $where_clause = "WHERE " . implode(" OR ", $search_conditions);
    }
}

// الحصول على بيانات الجدول
$data_query = "SELECT * FROM $current_table $where_clause LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($data_query);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// عدد إجمالي السجلات
$count_query = "SELECT COUNT(*) as count FROM $current_table $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
$total_pages = ceil($total_records / $limit);

// الحصول على هيكل الجدول
$schema_query = "PRAGMA table_info($current_table)";
$columns = $pdo->query($schema_query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قاعدة البيانات - نظام إدارة العقود</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .table th {
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
            font-weight: 600;
        }
        
        .badge-primary-key {
            background: #dc3545;
            color: white;
        }
        
        .badge-not-null {
            background: #28a745;
            color: white;
        }
        
        .search-box {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-pills .nav-link {
            border-radius: 20px;
            margin: 0 0.25rem;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .column-info {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .text-truncate-custom {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-database me-2"></i>قاعدة البيانات - نظام إدارة العقود
            </a>
            <div>
                <a href="manager_dashboard.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
                </a>
                <a href="logout.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- إحصائيات قاعدة البيانات -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stats-card">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <h3><i class="fas fa-table me-2"></i><?= count($tables) ?></h3>
                            <p class="mb-0">إجمالي الجداول</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h3><i class="fas fa-list me-2"></i><?= $total_records ?></h3>
                            <p class="mb-0">السجلات في الجدول الحالي</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h3><i class="fas fa-columns me-2"></i><?= count($columns) ?></h3>
                            <p class="mb-0">الأعمدة في الجدول</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h3><i class="fas fa-eye me-2"></i><?= $current_table ?></h3>
                            <p class="mb-0">الجدول المعروض</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- قائمة الجداول -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="search-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <ul class="nav nav-pills">
                                <?php foreach ($tables as $table): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $table === $current_table ? 'active' : '' ?>" 
                                           href="?table=<?= $table ?>">
                                            <i class="fas fa-table me-1"></i><?= $table ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <form method="GET" class="d-flex">
                                <input type="hidden" name="table" value="<?= $current_table ?>">
                                <input type="text" name="search" class="form-control me-2" 
                                       placeholder="بحث في الجدول..." value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- معلومات الجدول -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="table-container">
                    <div class="table-header">
                        <h4><i class="fas fa-info-circle me-2"></i>هيكل جدول <?= $current_table ?></h4>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>اسم العمود</th>
                                    <th>نوع البيانات</th>
                                    <th>خصائص</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($columns as $column): ?>
                                    <tr>
                                        <td><strong><?= $column['name'] ?></strong></td>
                                        <td><code><?= $column['type'] ?></code></td>
                                        <td>
                                            <?php if ($column['pk']): ?>
                                                <span class="badge badge-primary-key me-1">PRIMARY KEY</span>
                                            <?php endif; ?>
                                            <?php if ($column['notnull']): ?>
                                                <span class="badge badge-not-null">NOT NULL</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- بيانات الجدول -->
        <div class="row">
            <div class="col-12">
                <div class="table-container">
                    <div class="table-header d-flex justify-content-between align-items-center">
                        <h4><i class="fas fa-database me-2"></i>بيانات جدول <?= $current_table ?></h4>
                        <small>صفحة <?= $page ?> من <?= $total_pages ?> (<?= $total_records ?> سجل)</small>
                    </div>
                    <div class="table-responsive">
                        <?php if (!empty($records)): ?>
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($records[0]) as $column): ?>
                                            <th><?= $column ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record): ?>
                                        <tr>
                                            <?php foreach ($record as $value): ?>
                                                <td>
                                                    <div class="text-truncate-custom" title="<?= htmlspecialchars($value ?? 'NULL') ?>">
                                                        <?php if ($value === null): ?>
                                                            <span class="text-muted">NULL</span>
                                                        <?php elseif (is_bool($value)): ?>
                                                            <span class="badge <?= $value ? 'bg-success' : 'bg-danger' ?>">
                                                                <?= $value ? 'TRUE' : 'FALSE' ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($value) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="p-5 text-center">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">لا توجد بيانات في هذا الجدول</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="p-3 border-top">
                            <nav>
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?table=<?= $current_table ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">السابق</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?table=<?= $current_table ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?table=<?= $current_table ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">التالي</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تحسين تجربة المستخدم
        document.querySelectorAll('.text-truncate-custom').forEach(cell => {
            cell.addEventListener('click', function() {
                if (this.style.whiteSpace === 'normal') {
                    this.style.whiteSpace = 'nowrap';
                    this.style.overflow = 'hidden';
                    this.style.textOverflow = 'ellipsis';
                } else {
                    this.style.whiteSpace = 'normal';
                    this.style.overflow = 'visible';
                    this.style.textOverflow = 'clip';
                }
            });
        });
    </script>
</body>
</html>