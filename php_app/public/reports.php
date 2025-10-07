<?php
require_once '../includes/auth.php';

$auth->requireAuth();
$user = $auth->getCurrentUser();

// التحقق من صلاحية الوصول للتقارير
if (!in_array($user['role'], ['manager', 'admin'])) {
    header('HTTP/1.0 403 Forbidden');
    die('غير مسموح لك بالوصول لهذه الصفحة');
}

try {
    // إحصائيات شاملة
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_contracts,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_contracts,
            SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END) as pending_contracts,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_contracts,
            SUM(CASE WHEN status = 'signed' THEN 1 ELSE 0 END) as signed_contracts,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_contracts,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = 'signed' THEN amount ELSE 0 END) as signed_amount,
            AVG(amount) as average_amount
        FROM contracts
    ");
    $stmt->execute();
    $stats = $stmt->fetch();
    
    // إحصائيات شهرية
    $monthlyStmt = $pdo->prepare("
        SELECT 
            strftime('%Y-%m', created_at) as month,
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM contracts 
        WHERE created_at >= date('now', '-12 months')
        GROUP BY strftime('%Y-%m', created_at)
        ORDER BY month DESC
    ");
    $monthlyStmt->execute();
    $monthlyStats = $monthlyStmt->fetchAll();
    
    // إحصائيات الموظفين
    $employeeStmt = $pdo->prepare("
        SELECT 
            u.name, u.email,
            COUNT(c.id) as total_contracts,
            SUM(CASE WHEN c.status = 'signed' THEN 1 ELSE 0 END) as signed_contracts,
            SUM(c.amount) as total_amount,
            AVG(c.amount) as avg_amount
        FROM users u
        LEFT JOIN contracts c ON u.id = c.created_by
        WHERE u.role = 'employee' AND u.status = 'active'
        GROUP BY u.id, u.name, u.email
        ORDER BY total_contracts DESC
    ");
    $employeeStmt->execute();
    $employeeStats = $employeeStmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "خطأ في جلب البيانات: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير - نظام إدارة العقود</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #253355;
            --secondary-color: #77bcc3;
            --accent-color: #e8eaec;
            --text-color: #9694ac;
        }
        
        body {
            background-color: var(--accent-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 5px solid var(--secondary-color);
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-chart-bar me-3"></i>تقارير النظام</h1>
                    <p class="mb-0">إحصائيات شاملة لأداء النظام والعقود</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="/manager_dashboard.php" class="btn btn-light">
                        <i class="fas fa-arrow-right me-2"></i>العودة للوحة التحكم
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- إحصائيات عامة -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-file-contract fa-3x text-primary mb-3"></i>
                    <h3><?php echo number_format($stats['total_contracts'] ?? 0); ?></h3>
                    <p class="text-muted">إجمالي العقود</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h3><?php echo number_format($stats['signed_contracts'] ?? 0); ?></h3>
                    <p class="text-muted">العقود الموقعة</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-money-bill-wave fa-3x text-info mb-3"></i>
                    <h3><?php echo number_format($stats['total_amount'] ?? 0, 0); ?> ر.س</h3>
                    <p class="text-muted">إجمالي القيمة</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-calculator fa-3x text-warning mb-3"></i>
                    <h3><?php echo number_format($stats['average_amount'] ?? 0, 0); ?> ر.س</h3>
                    <p class="text-muted">متوسط قيمة العقد</p>
                </div>
            </div>
        </div>

        <!-- توزيع العقود حسب الحالة -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h4 class="mb-4">توزيع العقود حسب الحالة</h4>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h4 class="mb-4">الإحصائيات الشهرية</h4>
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- إحصائيات الموظفين -->
        <div class="row">
            <div class="col-12">
                <div class="chart-container">
                    <h4 class="mb-4">أداء الموظفين</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>اسم الموظف</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>إجمالي العقود</th>
                                    <th>العقود الموقعة</th>
                                    <th>إجمالي القيمة</th>
                                    <th>متوسط قيمة العقد</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employeeStats as $employee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $employee['total_contracts']; ?></span></td>
                                    <td><span class="badge bg-success"><?php echo $employee['signed_contracts']; ?></span></td>
                                    <td><?php echo number_format($employee['total_amount'] ?? 0, 0); ?> ر.س</td>
                                    <td><?php echo number_format($employee['avg_amount'] ?? 0, 0); ?> ر.س</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // رسم بياني لتوزيع العقود
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['مسودة', 'قيد المراجعة', 'موافق عليه', 'موقع', 'مرفوض'],
                datasets: [{
                    data: [
                        <?php echo $stats['draft_contracts'] ?? 0; ?>,
                        <?php echo $stats['pending_contracts'] ?? 0; ?>,
                        <?php echo $stats['approved_contracts'] ?? 0; ?>,
                        <?php echo $stats['signed_contracts'] ?? 0; ?>,
                        <?php echo $stats['rejected_contracts'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        '#ffc107',
                        '#17a2b8',
                        '#28a745',
                        '#007bff',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // رسم بياني للإحصائيات الشهرية
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    $labels = [];
                    foreach ($monthlyStats as $month) {
                        $labels[] = "'" . $month['month'] . "'";
                    }
                    echo implode(',', array_reverse($labels)); 
                ?>],
                datasets: [{
                    label: 'عدد العقود',
                    data: [<?php 
                        $counts = [];
                        foreach ($monthlyStats as $month) {
                            $counts[] = $month['count'];
                        }
                        echo implode(',', array_reverse($counts)); 
                    ?>],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0,123,255,0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>