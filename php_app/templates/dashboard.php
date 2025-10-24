<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - نظام إدارة العقود سما</title>
    <link rel="stylesheet" href="/static/css/glassmorphism.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation Header -->
        <div class="nav-container">
            <a href="/dashboard" class="nav-link active">🏠 لوحة التحكم</a>
            <a href="/contracts" class="nav-link">📄 العقود</a>
            <a href="/contracts/create" class="nav-link">➕ عقد جديد</a>
            <a href="/notifications" class="nav-link">🔔 الإشعارات</a>
            <a href="/profile" class="nav-link">👤 الملف الشخصي</a>
            <a href="/logout" class="nav-link">🚪 تسجيل الخروج</a>
        </div>

        <!-- Page Header -->
        <div class="glass-container" style="text-align: center; margin-bottom: 30px;">
            <div class="page-header">
                <h1>📊 لوحة التحكم الرئيسية</h1>
                <p>مرحباً بك في نظام إدارة العقود - مؤسسة سما</p>
                <?php if (isset($_SESSION['user_name'])): ?>
                    <div style="margin-top: 16px; color: var(--text-secondary);">
                        👋 أهلاً وسهلاً، <?= htmlspecialchars($_SESSION['user_name']) ?>
                        <span style="color: var(--accent-blue);">(<?= htmlspecialchars($_SESSION['user_role']) ?>)</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message" style="margin-bottom: 20px;">
                ✅ <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Metrics Grid -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-number"><?= $metrics['total_count'] ?? 45 ?></div>
                <div class="metric-label">📋 إجمالي العقود</div>
            </div>
            <div class="metric-card">
                <div class="metric-number"><?= $metrics['pending_count'] ?? 12 ?></div>
                <div class="metric-label">⏳ في الانتظار</div>
            </div>
            <div class="metric-card">
                <div class="metric-number"><?= $metrics['in_progress'] ?? 8 ?></div>
                <div class="metric-label">🔄 قيد التنفيذ</div>
            </div>
            <div class="metric-card">
                <div class="metric-number"><?= $metrics['closed_count'] ?? 25 ?></div>
                <div class="metric-label">✅ مكتملة</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 30px;">
            <!-- Recent Contracts -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📄 العقود الحديثة</h3>
                    <p class="card-subtitle">آخر العقود المضافة للنظام</p>
                </div>
                <div class="recent-contracts">
                    <?php 
                    $recent_contracts = $recent_contracts ?? [
                        ['id' => 1, 'serial' => 'CT-0001', 'client_name' => 'شركة المستقبل للتقنية', 'status' => 'pending', 'created_at' => '2025-10-01'],
                        ['id' => 2, 'serial' => 'CT-0002', 'client_name' => 'مؤسسة الابتكار التجارية', 'status' => 'approved', 'created_at' => '2025-09-28'],
                        ['id' => 3, 'serial' => 'CT-0003', 'client_name' => 'شركة التطوير الحديث', 'status' => 'completed', 'created_at' => '2025-09-25'],
                    ];
                    
                    foreach ($recent_contracts as $contract): 
                        $statusClass = 'status-' . $contract['status'];
                        $statusText = [
                            'pending' => 'في الانتظار',
                            'approved' => 'معتمد', 
                            'completed' => 'مكتمل',
                            'rejected' => 'مرفوض'
                        ][$contract['status']] ?? $contract['status'];
                    ?>
                    <div style="padding: 16px; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                <?= htmlspecialchars($contract['serial']) ?> - <?= htmlspecialchars($contract['client_name']) ?>
                            </div>
                            <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                📅 <?= htmlspecialchars($contract['created_at']) ?>
                            </div>
                        </div>
                        <div class="status-badge <?= $statusClass ?>">
                            <?= $statusText ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 16px; text-align: center;">
                    <a href="/contracts" class="btn btn-secondary">
                        📋 عرض جميع العقود
                    </a>
                </div>
            </div>

            <!-- Recent Notifications -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🔔 الإشعارات الحديثة</h3>
                    <p class="card-subtitle">آخر الإشعارات والتحديثات</p>
                </div>
                <div class="recent-notifications">
                    <?php 
                    $recent_notifications = $recent_notifications ?? [
                        ['id' => 1, 'title' => 'عقد جديد يحتاج موافقة', 'message' => 'تم إضافة عقد جديد رقم CT-0001 ويحتاج إلى موافقة', 'type' => 'pending', 'created_at' => '2025-10-05 14:30:00'],
                        ['id' => 2, 'title' => 'تم اعتماد عقد', 'message' => 'تم اعتماد العقد رقم CT-0002 بنجاح', 'type' => 'success', 'created_at' => '2025-10-05 12:15:00'],
                        ['id' => 3, 'title' => 'انتهاء صلاحية عقد', 'message' => 'العقد رقم CT-0050 سينتهي خلال 7 أيام', 'type' => 'warning', 'created_at' => '2025-10-05 09:45:00'],
                    ];
                    
                    foreach ($recent_notifications as $notification):
                        $typeIcon = [
                            'pending' => '⏳',
                            'success' => '✅', 
                            'warning' => '⚠️',
                            'error' => '❌'
                        ][$notification['type']] ?? '📢';
                    ?>
                    <div style="padding: 16px; border-bottom: 1px solid var(--glass-border);">
                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                            <div style="font-size: 1.25rem;"><?= $typeIcon ?></div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                    <?= htmlspecialchars($notification['title']) ?>
                                </div>
                                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 8px;">
                                    <?= htmlspecialchars($notification['message']) ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    🕐 <?= date('H:i', strtotime($notification['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 16px; text-align: center;">
                    <a href="/notifications" class="btn btn-secondary">
                        🔔 عرض جميع الإشعارات
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="glass-container" style="margin-top: 30px;">
            <div class="card-header">
                <h3 class="card-title">⚡ الإجراءات السريعة</h3>
                <p class="card-subtitle">الوظائف الأكثر استخداماً</p>
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
                <a href="/notifications/mark-all-read" class="btn btn-secondary" style="margin: 0;">
                    📖 قراءة جميع الإشعارات
                </a>
            </div>
        </div>

        <!-- System Status -->
        <div class="glass-container" style="margin-top: 30px; text-align: center;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
                <div>
                    <div style="color: var(--success); font-size: 1.5rem; margin-bottom: 8px;">🟢</div>
                    <div style="font-weight: 600; color: var(--text-primary);">حالة النظام</div>
                    <div style="color: var(--text-secondary); font-size: 0.875rem;">يعمل بشكل طبيعي</div>
                </div>
                <div>
                    <div style="color: var(--accent-blue); font-size: 1.5rem; margin-bottom: 8px;">⚡</div>
                    <div style="font-weight: 600; color: var(--text-primary);">الأداء</div>
                    <div style="color: var(--text-secondary); font-size: 0.875rem;">ممتاز</div>
                </div>
                <div>
                    <div style="color: var(--warning); font-size: 1.5rem; margin-bottom: 8px;">🔄</div>
                    <div style="font-weight: 600; color: var(--text-primary);">آخر تحديث</div>
                    <div style="color: var(--text-secondary); font-size: 0.875rem;"><?= date('Y-m-d H:i') ?></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add smooth animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card, .metric-card, .glass-container');
            
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Auto-refresh metrics every 30 seconds
            setInterval(() => {
                const timestamp = document.querySelector('[data-timestamp]');
                if (timestamp) {
                    const now = new Date();
                    timestamp.textContent = now.toLocaleString('ar-SA');
                }
            }, 30000);
        });
    </script>

    <style>
        /* Additional dashboard-specific styles */
        .recent-contracts,
        .recent-notifications {
            max-height: 300px;
            overflow-y: auto;
        }

        .recent-contracts::-webkit-scrollbar,
        .recent-notifications::-webkit-scrollbar {
            width: 6px;
        }

        .recent-contracts::-webkit-scrollbar-track,
        .recent-notifications::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .recent-contracts::-webkit-scrollbar-thumb,
        .recent-notifications::-webkit-scrollbar-thumb {
            background: var(--accent-blue);
            border-radius: 3px;
        }

        .recent-contracts::-webkit-scrollbar-thumb:hover,
        .recent-notifications::-webkit-scrollbar-thumb:hover {
            background: var(--accent-purple);
        }

        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-container > div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
            
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-container {
                flex-direction: column;
            }
            
            .nav-link {
                text-align: center;
            }
        }
    </style>
</body>
</html>