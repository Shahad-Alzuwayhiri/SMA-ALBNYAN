<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once '../models/Contract.php';
require_once '../models/DetailedContract.php';
require_once '../controllers/DetailedContractController.php';

// معالجة طلبات التصدير
if (isset($_GET['action']) && $_GET['action'] === 'export_pdf' && isset($_GET['id'])) {
    $controller = new DetailedContractController();
    $controller->exportPdf($_GET['id']);
    exit;
}

$contractModel = new Contract();
$contracts = $contractModel->getAllForManager();

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>العقود - Sma Albnyan</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="../static/css/sma-company-theme.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
            direction: rtl;
        }
        
        .navbar {
            background: linear-gradient(135deg, #2c5530 0%, #1a3d1f 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 700;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .table th {
            background-color: #2c5530;
            color: white;
            border: none;
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2c5530 0%, #1a3d1f 100%);
            border: none;
            border-radius: 10px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 85, 48, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20754d 100%);
            border: none;
            border-radius: 8px;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            border: none;
            border-radius: 8px;
        }
        
        .badge {
            padding: 0.5em 1em;
            border-radius: 10px;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #28a745 0%, #20754d 100%);
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #333;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        .contract-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .contract-number {
            font-weight: 700;
            color: #2c5530;
            font-size: 1.1em;
        }
        
        .contract-amount {
            font-weight: 600;
            color: #28a745;
        }
        
        .page-header {
            background: linear-gradient(135deg, #2c5530 0%, #1a3d1f 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 30px 30px;
            text-align: center;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c5530;
        }
        
        .stats-label {
            color: #666;
            font-weight: 600;
        }
    </style>
</head>
<body class="sma-bg-light">
    <!-- شريط التنقل -->
    <nav class="navbar navbar-expand-lg sma-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-handshake me-2"></i>
                Sma Albnyan
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i> الرئيسية</a>
                <a class="nav-link active" href="contracts_list.php"><i class="fas fa-file-contract me-1"></i> العقود</a>
                <a class="nav-link" href="create_contract.php"><i class="fas fa-plus me-1"></i> عقد جديد</a>
                <a class="nav-link" href="renewal_contract.php"><i class="fas fa-redo me-1"></i> تجديد</a>
                <a class="nav-link" href="offer_contract.php"><i class="fas fa-file-invoice me-1"></i> عرض</a>
                <a class="nav-link" href="../includes/logout.php"><i class="fas fa-sign-out-alt me-1"></i> تسجيل خروج</a>
            </div>
        </div>
    </nav>

    <!-- رأس الصفحة -->
    <div class="container mt-4">
        <div class="card sma-card">
            <div class="card-header sma-header text-center">
                <h2 class="mb-0">
                    <i class="fas fa-file-contract me-3"></i>إدارة العقود
                </h2>
                <p class="mb-0 mt-2">عرض وإدارة جميع العقود في النظام</p>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- الإحصائيات -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card sma-card text-center">
                    <div class="card-body">
                        <h3 class="sma-text-primary"><?php echo count($contracts); ?></h3>
                        <p class="mb-0">إجمالي العقود</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card sma-card text-center">
                    <div class="card-body">
                        <h3 class="sma-text-success"><?php echo count(array_filter($contracts, fn($c) => $c['status'] === 'active')); ?></h3>
                        <p class="mb-0">العقود النشطة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card sma-card text-center">
                    <div class="card-body">
                        <h3 class="sma-text-warning"><?php echo count(array_filter($contracts, fn($c) => $c['status'] === 'pending')); ?></h3>
                        <p class="mb-0">قيد المراجعة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card sma-card text-center">
                    <div class="card-body">
                        <h3 class="sma-text-secondary"><?php echo number_format(array_sum(array_column($contracts, 'contract_amount')), 0); ?></h3>
                        <p class="mb-0">إجمالي القيمة (ريال)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- جدول العقود -->
        <div class="card sma-card">
            <div class="card-header sma-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        قائمة العقود
                    </h5>
                    <div>
                        <a href="create_contract.php" class="btn sma-btn-primary me-2">
                            <i class="fas fa-plus me-1"></i>
                            عقد جديد
                        </a>
                        <a href="renewal_contract.php" class="btn sma-btn-secondary me-2">
                            <i class="fas fa-redo me-1"></i>
                            تجديد
                        </a>
                        <a href="offer_contract.php" class="btn sma-btn-accent">
                            <i class="fas fa-file-invoice me-1"></i>
                            عرض
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($contracts)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-contract fa-5x text-muted mb-3"></i>
                        <h4 class="text-muted">لا توجد عقود حالياً</h4>
                        <p class="text-muted">ابدأ بإضافة عقد جديد</p>
                        <a href="create_contract.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            إضافة عقد جديد
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>رقم العقد</th>
                                    <th>العنوان</th>
                                    <th>الطرف الثاني</th>
                                    <th>قيمة العقد</th>
                                    <th>نسبة الأرباح</th>
                                    <th>تاريخ البداية</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contracts as $contract): ?>
                                    <tr>
                                        <td class="contract-number"><?php echo htmlspecialchars($contract['contract_number']); ?></td>
                                        <td><?php echo htmlspecialchars($contract['title']); ?></td>
                                        <td><?php echo htmlspecialchars($contract['second_party_name']); ?></td>
                                        <td class="contract-amount"><?php echo number_format($contract['contract_amount'], 2); ?> ريال</td>
                                        <td><?php echo htmlspecialchars($contract['profit_percentage']); ?>%</td>
                                        <td><?php echo htmlspecialchars($contract['start_date']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'badge-secondary';
                                            $statusText = $contract['status'];
                                            
                                            switch ($contract['status']) {
                                                case 'active':
                                                    $statusClass = 'badge-success';
                                                    $statusText = 'نشط';
                                                    break;
                                                case 'pending':
                                                    $statusClass = 'badge-warning';
                                                    $statusText = 'قيد المراجعة';
                                                    break;
                                                case 'expired':
                                                    $statusClass = 'badge-danger';
                                                    $statusText = 'منتهي';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td>
                                            <div class="contract-actions">
                                                <a href="view_contract.php?id=<?php echo $contract['id']; ?>" 
                                                   class="btn btn-sm btn-info" 
                                                   title="عرض التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($contract['status'] !== 'draft'): ?>
                                                <a href="?action=export_pdf&id=<?php echo $contract['id']; ?>" 
                                                   class="btn btn-sm btn-success" 
                                                   title="تحميل PDF - متاح بعد المعالجة"
                                                   target="_blank">
                                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                                </a>
                                                <?php else: ?>
                                                <span class="btn btn-sm btn-secondary disabled" 
                                                      title="PDF سيكون متاحاً بعد معالجة العقد">
                                                    <i class="fas fa-clock me-1"></i>PDF
                                                </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($_SESSION['user_role'] === 'manager' || $contract['created_by'] == $_SESSION['user_id']): ?>
                                                <a href="edit_contract.php?id=<?php echo $contract['id']; ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <a href="delete_contract.php?id=<?php echo $contract['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   title="حذف"
                                                   onclick="return confirm('هل أنت متأكد من حذف هذا العقد؟')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // إضافة تأثيرات تفاعلية
        document.addEventListener('DOMContentLoaded', function() {
            // تأثير التحويم على الصفوف
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.02)';
                    this.style.transition = 'transform 0.2s ease';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
            
            // تأكيد تصدير PDF
            const pdfButtons = document.querySelectorAll('a[title="تصدير PDF"]');
            pdfButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const contractNumber = this.closest('tr').querySelector('.contract-number').textContent;
                    const confirmation = confirm(`هل تريد تصدير العقد ${contractNumber} كملف PDF؟`);
                    if (!confirmation) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>