<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة العقود - نظام إدارة العقود سما</title>
    <link rel="stylesheet" href="/static/css/glassmorphism.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation Header -->
        <div class="nav-container">
            <a href="/dashboard" class="nav-link">🏠 لوحة التحكم</a>
            <a href="/contracts" class="nav-link active">📄 العقود</a>
            <a href="/contracts/create" class="nav-link">➕ عقد جديد</a>
            <a href="/contracts-in-progress" class="nav-link">🔄 قيد التنفيذ</a>
            <a href="/contracts-closed" class="nav-link">✅ مكتملة</a>
            <a href="/notifications" class="nav-link">🔔 الإشعارات</a>
            <a href="/logout" class="nav-link">🚪 تسجيل الخروج</a>
        </div>

        <!-- Page Header -->
        <div class="glass-container" style="text-align: center; margin-bottom: 30px;">
            <div class="page-header">
                <h1>📄 إدارة العقود</h1>
                <p>جميع العقود والاتفاقيات في النظام</p>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message" style="margin-bottom: 20px;">
                ✅ <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['errors'])): ?>
            <div class="error-message" style="margin-bottom: 20px;">
                ❌ <?= htmlspecialchars(implode(', ', $_SESSION['errors'])) ?>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="metrics-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 30px;">
            <div class="metric-card">
                <div class="metric-number"><?= count($contracts ?? []) ?></div>
                <div class="metric-label">📋 إجمالي العقود</div>
            </div>
            <div class="metric-card">
                <div class="metric-number">
                    <?= count(array_filter($contracts ?? [], function($c) { return $c['status'] === 'pending'; })) ?>
                </div>
                <div class="metric-label">⏳ في الانتظار</div>
            </div>
            <div class="metric-card">
                <div class="metric-number">
                    <?= count(array_filter($contracts ?? [], function($c) { return $c['status'] === 'approved'; })) ?>
                </div>
                <div class="metric-label">✅ معتمدة</div>
            </div>
            <div class="metric-card">
                <div class="metric-number">
                    <?= count(array_filter($contracts ?? [], function($c) { return $c['status'] === 'completed'; })) ?>
                </div>
                <div class="metric-label">🏁 مكتملة</div>
            </div>
        </div>

        <!-- Contracts Table -->
        <div class="table-container">
            <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--glass-border);">
                <h3 class="card-title">📋 قائمة العقود</h3>
                <p class="card-subtitle">جميع العقود المسجلة في النظام</p>
            </div>
            
            <?php if (!empty($contracts)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>رقم العقد</th>
                            <th>اسم العميل</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contracts as $contract): 
                            $statusClass = 'status-' . $contract['status'];
                            $statusText = [
                                'pending' => 'في الانتظار',
                                'approved' => 'معتمد',
                                'completed' => 'مكتمل',
                                'rejected' => 'مرفوض'
                            ][$contract['status']] ?? $contract['status'];
                        ?>
                        <tr>
                            <td>
                                <strong style="color: var(--accent-blue);">
                                    <?= htmlspecialchars($contract['serial']) ?>
                                </strong>
                            </td>
                            <td>
                                <div style="font-weight: 500;">
                                    <?= htmlspecialchars($contract['client_name']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td>
                                <div style="color: var(--text-secondary);">
                                    📅 <?= htmlspecialchars($contract['created_at']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="/contracts/<?= $contract['id'] ?>" 
                                       class="btn btn-secondary" 
                                       style="padding: 6px 12px; font-size: 12px; margin: 0;">
                                        👁️ عرض
                                    </a>
                                    
                                    <?php if ($contract['status'] === 'pending' && ($_SESSION['user_role'] ?? 'employee') === 'manager'): ?>
                                        <a href="/contracts/<?= $contract['id'] ?>/approve" 
                                           class="btn" 
                                           style="padding: 6px 12px; font-size: 12px; margin: 0; background: var(--success);">
                                            ✅ اعتماد
                                        </a>
                                        <a href="/contracts/<?= $contract['id'] ?>/reject" 
                                           class="btn" 
                                           style="padding: 6px 12px; font-size: 12px; margin: 0; background: var(--error);">
                                            ❌ رفض
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="/contracts/<?= $contract['id'] ?>/pdf" 
                                       class="btn btn-secondary" 
                                       style="padding: 6px 12px; font-size: 12px; margin: 0;"
                                       target="_blank">
                                        📄 PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 60px 20px; text-align: center;">
                    <div style="font-size: 4rem; margin-bottom: 20px;">📄</div>
                    <h3 style="color: var(--text-primary); margin-bottom: 12px;">لا توجد عقود حالياً</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 24px;">
                        لم يتم إنشاء أي عقود بعد. ابدأ بإنشاء عقد جديد.
                    </p>
                    <a href="/contracts/create" class="btn btn-primary">
                        ➕ إنشاء عقد جديد
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="glass-container" style="margin-top: 30px;">
            <div class="card-header">
                <h3 class="card-title">⚡ الإجراءات السريعة</h3>
                <p class="card-subtitle">الوظائف الأكثر استخداماً في إدارة العقود</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 20px;">
                <a href="/contracts/create" class="btn btn-primary" style="margin: 0;">
                    ➕ إنشاء عقد جديد
                </a>
                <a href="/contracts-in-progress" class="btn btn-secondary" style="margin: 0;">
                    🔄 العقود قيد التنفيذ
                </a>
                <a href="/contracts-closed" class="btn btn-secondary" style="margin: 0;">
                    ✅ العقود المكتملة
                </a>
                <a href="/dashboard" class="btn btn-secondary" style="margin: 0;">
                    🏠 العودة للوحة التحكم
                </a>
            </div>
        </div>

        <!-- Filter & Search (Future Enhancement) -->
        <div class="glass-container" style="margin-top: 20px; display: none;" id="filterSection">
            <div class="card-header">
                <h3 class="card-title">🔍 البحث والتصفية</h3>
                <p class="card-subtitle">ابحث وصفّي العقود حسب معايير مختلفة</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 20px;">
                <div class="form-group" style="margin: 0;">
                    <div class="input-wrapper">
                        <input type="text" id="search" name="search" placeholder=" ">
                        <label for="search">البحث في العقود</label>
                        <span class="focus-border"></span>
                    </div>
                </div>
                <div class="form-group" style="margin: 0;">
                    <div class="input-wrapper">
                        <select id="status_filter" name="status_filter" style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 12px; padding: 16px; color: var(--text-primary); font-family: 'Tajawal', sans-serif;">
                            <option value="">جميع الحالات</option>
                            <option value="pending">في الانتظار</option>
                            <option value="approved">معتمد</option>
                            <option value="completed">مكتمل</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add smooth animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.table-container, .metric-card, .glass-container');
            
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(4px)';
                    this.style.transition = 'transform 0.2s ease';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });

            // Add click effects to action buttons
            const actionButtons = document.querySelectorAll('.table .btn');
            actionButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    // Add ripple effect
                    const ripple = document.createElement('span');
                    ripple.style.position = 'absolute';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'rgba(255, 255, 255, 0.6)';
                    ripple.style.transform = 'scale(0)';
                    ripple.style.animation = 'ripple 0.6s linear';
                    ripple.style.left = '50%';
                    ripple.style.top = '50%';
                    ripple.style.width = '20px';
                    ripple.style.height = '20px';
                    ripple.style.marginLeft = '-10px';
                    ripple.style.marginTop = '-10px';
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });
    </script>

    <style>
        /* Additional contracts-specific styles */
        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(2px);
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        /* Action buttons in table */
        .table .btn {
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .table .btn:hover {
            transform: translateY(-1px);
        }

        /* Ripple animation */
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
            }
            
            .table {
                min-width: 600px;
            }
            
            .table th,
            .table td {
                padding: 8px;
                font-size: 0.875rem;
            }
            
            .table .btn {
                padding: 4px 8px;
                font-size: 11px;
            }
            
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .table th:nth-child(3),
            .table td:nth-child(3) {
                display: none;
            }
        }
    </style>
</body>
</html>