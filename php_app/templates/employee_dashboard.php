<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الموظف - نظام إدارة العقود سما</title>
    <link rel="stylesheet" href="/static/css/glassmorphism.css">
    <link rel="stylesheet" href="/static/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🏢 سما البنيان</h2>
                <p>لوحة تحكم الموظف</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#overview" class="nav-item active" onclick="showSection('overview')">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">نظرة عامة</span>
                </a>
                <a href="#my-contracts" class="nav-item" onclick="showSection('my-contracts')">
                    <span class="nav-icon">📄</span>
                    <span class="nav-text">عقودي</span>
                    <span class="nav-badge" id="my-contracts-badge"><?= count($contracts ?? []) ?></span>
                </a>
                <a href="#create-contract" class="nav-item" onclick="showSection('create-contract')">
                    <span class="nav-icon">➕</span>
                    <span class="nav-text">إنشاء عقد جديد</span>
                </a>
                <a href="#uploads" class="nav-item" onclick="showSection('uploads')">
                    <span class="nav-icon">📤</span>
                    <span class="nav-text">الملفات المرفوعة</span>
                </a>
                <a href="#notifications" class="nav-item" onclick="showSection('notifications')">
                    <span class="nav-icon">🔔</span>
                    <span class="nav-text">الإشعارات</span>
                    <span class="nav-badge" id="notifications-badge"><?= count($notifications ?? []) ?></span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-avatar">👨‍💼</span>
                    <div class="user-details">
                        <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'الموظف') ?></span>
                        <span class="user-role">موظف</span>
                    </div>
                </div>
                <a href="/logout" class="logout-btn">
                    <span>🚪</span>
                    تسجيل الخروج
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="content-header">
                <div class="header-title">
                    <h1 id="section-title">نظرة عامة</h1>
                    <p id="section-subtitle">مرحباً بك في لوحة تحكم الموظف</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="refreshData()">
                        <span>🔄</span>
                        تحديث البيانات
                    </button>
                </div>
            </div>

            <!-- Overview Section -->
            <div id="overview-section" class="content-section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📄</div>
                        <div class="stat-info">
                            <h3 id="total-my-contracts">0</h3>
                            <p>إجمالي عقودي</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📝</div>
                        <div class="stat-info">
                            <h3 id="draft-contracts">0</h3>
                            <p>مسودات</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <h3 id="pending-contracts">0</h3>
                            <p>بانتظار التوقيع</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3 id="signed-contracts">0</h3>
                            <p>عقود موقعة</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>الأدوات السريعة</h3>
                    <div class="actions-grid">
                        <button class="action-card" onclick="showSection('create-contract')">
                            <div class="action-icon">➕</div>
                            <div class="action-info">
                                <h4>إنشاء عقد جديد</h4>
                                <p>ابدأ بإنشاء عقد جديد</p>
                            </div>
                        </button>
                        
                        <button class="action-card" onclick="uploadFile()">
                            <div class="action-icon">📤</div>
                            <div class="action-info">
                                <h4>رفع ملف</h4>
                                <p>رفع ملف PDF أو Word</p>
                            </div>
                        </button>
                        
                        <button class="action-card" onclick="exportToPDF()">
                            <div class="action-icon">📑</div>
                            <div class="action-info">
                                <h4>تصدير PDF</h4>
                                <p>تصدير عقد بصيغة PDF</p>
                            </div>
                        </button>
                        
                        <button class="action-card" onclick="showSection('my-contracts')">
                            <div class="action-icon">👁️</div>
                            <div class="action-info">
                                <h4>متابعة العقود</h4>
                                <p>مراجعة العقود السابقة</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h3>النشاط الأخير</h3>
                    <div class="activity-list" id="recent-activity-list">
                        <?php if (!empty($notifications)): ?>
                            <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                                <div class="activity-item">
                                    <span class="activity-icon">
                                        <?php
                                        $icons = [
                                            'contract_created' => '📄',
                                            'contract_signed' => '✅',
                                            'contract_rejected' => '❌',
                                            'contract_returned' => '🔄'
                                        ];
                                        echo $icons[$notification['type']] ?? '📄';
                                        ?>
                                    </span>
                                    <div class="activity-content">
                                        <h4><?= htmlspecialchars($notification['title']) ?></h4>
                                        <p><?= htmlspecialchars($notification['message']) ?></p>
                                        <small><?= date('Y-m-d H:i', strtotime($notification['created_at'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-activity">
                                <span class="no-activity-icon">😴</span>
                                <p>لا يوجد نشاط حديث</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- My Contracts Section -->
            <div id="my-contracts-section" class="content-section">
                <div class="section-header">
                    <div class="filters">
                        <select id="my-contract-status-filter" onchange="filterMyContracts()">
                            <option value="">جميع الحالات</option>
                            <option value="draft">مسودة</option>
                            <option value="pending_review">بانتظار المراجعة</option>
                            <option value="signed">موقعة</option>
                            <option value="rejected">مرفوضة</option>
                        </select>
                        
                        <button class="btn btn-primary" onclick="showSection('create-contract')">
                            <span>➕</span>
                            عقد جديد
                        </button>
                    </div>
                </div>

                <div class="contracts-grid" id="my-contracts-grid">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>

            <!-- Create Contract Section -->
            <div id="create-contract-section" class="content-section">
                <div class="create-contract-form">
                    <form id="create-contract-form" onsubmit="createContract(event)">
                        <div class="form-sections">
                            <!-- Basic Info Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <span class="section-icon">📄</span>
                                    معلومات العقد الأساسية
                                </h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="contract-title">عنوان العقد</label>
                                        <input type="text" id="contract-title" name="title" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="contract-description">وصف العقد</label>
                                        <textarea id="contract-description" name="description" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Second Party Info Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <span class="section-icon">🏢</span>
                                    معلومات الطرف الثاني
                                </h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="second-party-name">اسم الشركة/المؤسسة</label>
                                        <input type="text" id="second-party-name" name="second_party_name" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group half-width">
                                        <label for="second-party-phone">رقم الهاتف</label>
                                        <input type="tel" id="second-party-phone" name="second_party_phone">
                                    </div>
                                    
                                    <div class="form-group half-width">
                                        <label for="second-party-email">البريد الإلكتروني</label>
                                        <input type="email" id="second-party-email" name="second_party_email">
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Info Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <span class="section-icon">💰</span>
                                    المعلومات المالية
                                </h3>
                                
                                <div class="form-row">
                                    <div class="form-group half-width">
                                        <label for="contract-amount">قيمة العقد (ريال سعودي)</label>
                                        <input type="number" id="contract-amount" name="contract_amount" step="0.01" min="0" required>
                                    </div>
                                    
                                    <div class="form-group half-width">
                                        <label for="profit-percentage">نسبة الربح (%)</label>
                                        <input type="number" id="profit-percentage" name="profit_percentage" step="0.01" min="0" max="100">
                                    </div>
                                </div>
                            </div>

                            <!-- Date Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <span class="section-icon">📅</span>
                                    تواريخ العقد
                                </h3>
                                
                                <div class="form-row">
                                    <div class="form-group half-width">
                                        <label for="start-date">تاريخ البداية</label>
                                        <input type="date" id="start-date" name="start_date">
                                    </div>
                                    
                                    <div class="form-group half-width">
                                        <label for="end-date">تاريخ النهاية</label>
                                        <input type="date" id="end-date" name="end_date">
                                    </div>
                                </div>
                            </div>

                            <!-- Terms Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <span class="section-icon">📋</span>
                                    الشروط والأحكام
                                </h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="terms-conditions">نص الشروط والأحكام</label>
                                        <textarea id="terms-conditions" name="terms_conditions" rows="6"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="create-contract-btn">
                                <span>💾</span>
                                حفظ كمسودة
                            </button>
                            
                            <button type="button" class="btn btn-success" onclick="submitForReview()">
                                <span>📤</span>
                                رفع للمراجعة
                            </button>
                            
                            <button type="button" class="btn btn-secondary" onclick="clearForm()">
                                <span>🗑️</span>
                                مسح النموذج
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Other sections (uploads, notifications) would go here -->
            <div id="uploads-section" class="content-section">
                <h3>الملفات المرفوعة</h3>
                <p>قيد التطوير...</p>
            </div>

            <div id="notifications-section" class="content-section">
                <div class="notifications-list" id="employee-notifications-list">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
                                <div class="notification-icon">
                                    <?php
                                    $icons = [
                                        'contract_created' => '📄',
                                        'contract_signed' => '✅',
                                        'contract_rejected' => '❌',
                                        'contract_returned' => '🔄'
                                    ];
                                    echo $icons[$notification['type']] ?? '📄';
                                    ?>
                                </div>
                                <div class="notification-content">
                                    <h4><?= htmlspecialchars($notification['title']) ?></h4>
                                    <p><?= htmlspecialchars($notification['message']) ?></p>
                                    <small><?= date('Y-m-d H:i', strtotime($notification['created_at'])) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-notifications">
                            <span class="no-notifications-icon">🔔</span>
                            <p>لا توجد إشعارات</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Include the same styles as manager dashboard with employee-specific modifications */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
        }

        .quick-actions {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 32px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
        }

        .action-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: 32px;
            opacity: 0.8;
        }

        .action-info h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
        }

        .action-info p {
            margin: 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        .form-sections {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .form-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
        }

        .form-group.half-width {
            flex: 0 0 calc(50% - 10px);
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: white;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 14px;
            backdrop-filter: blur(10px);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 32px;
        }

        /* Inherit other styles from manager dashboard... */
    </style>

    <script>
        // Employee dashboard JavaScript functions
        function showSection(sectionName) {
            // Same implementation as manager dashboard
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.getElementById(sectionName + '-section').classList.add('active');
            document.querySelector(`[onclick="showSection('${sectionName}')"]`).classList.add('active');
            
            const titles = {
                overview: { title: 'نظرة عامة', subtitle: 'مرحباً بك في لوحة تحكم الموظف' },
                'my-contracts': { title: 'عقودي', subtitle: 'إدارة ومتابعة عقودك الشخصية' },
                'create-contract': { title: 'إنشاء عقد جديد', subtitle: 'ابدأ بإنشاء عقد جديد' },
                uploads: { title: 'الملفات المرفوعة', subtitle: 'ملفات PDF والمستندات' },
                notifications: { title: 'الإشعارات', subtitle: 'رسائل النظام والتنبيهات' }
            };
            
            document.getElementById('section-title').textContent = titles[sectionName].title;
            document.getElementById('section-subtitle').textContent = titles[sectionName].subtitle;
        }
        
        function createContract(event) {
            event.preventDefault();
            // Implementation for creating contract
            console.log('Creating contract...');
        }
        
        function submitForReview() {
            console.log('Submitting for review...');
        }
        
        function clearForm() {
            document.getElementById('create-contract-form').reset();
        }
        
        function refreshData() {
            console.log('Refreshing data...');
        }
        
        function filterMyContracts() {
            console.log('Filtering contracts...');
        }
        
        function uploadFile() {
            console.log('Uploading file...');
        }
        
        function exportToPDF() {
            console.log('Exporting to PDF...');
        }
    </script>
</body>
</html>