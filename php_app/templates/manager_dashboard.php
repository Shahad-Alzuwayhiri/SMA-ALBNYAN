<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المدير - نظام إدارة العقود سما</title>
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
                <p>لوحة تحكم المدير</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#overview" class="nav-item active" onclick="showSection('overview')">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">نظرة عامة</span>
                </a>
                <a href="#contracts" class="nav-item" onclick="showSection('contracts')">
                    <span class="nav-icon">📄</span>
                    <span class="nav-text">إدارة العقود</span>
                    <span class="nav-badge" id="pending-contracts-badge"><?= $pendingCount ?? 0 ?></span>
                </a>
                <a href="#employees" class="nav-item" onclick="showSection('employees')">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">إدارة الموظفين</span>
                </a>
                <a href="#signatures" class="nav-item" onclick="showSection('signatures')">
                    <span class="nav-icon">✍️</span>
                    <span class="nav-text">التوقيعات الإلكترونية</span>
                </a>
                <a href="#reports" class="nav-item" onclick="showSection('reports')">
                    <span class="nav-icon">📈</span>
                    <span class="nav-text">التقارير والإحصاءات</span>
                </a>
                <a href="#notifications" class="nav-item" onclick="showSection('notifications')">
                    <span class="nav-icon">🔔</span>
                    <span class="nav-text">الإشعارات</span>
                    <span class="nav-badge" id="notifications-badge"><?= $unreadNotifications ?? 0 ?></span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-avatar">👨‍💼</span>
                    <div class="user-details">
                        <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'المدير') ?></span>
                        <span class="user-role">مدير</span>
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
                    <p id="section-subtitle">مرحباً بك في لوحة تحكم المدير</p>
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
                            <h3 id="total-contracts">0</h3>
                            <p>إجمالي العقود</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <h3 id="pending-contracts">0</h3>
                            <p>عقود بانتظار التوقيع</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3 id="signed-contracts">0</h3>
                            <p>عقود موقعة</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3 id="total-amount">0 ر.س</h3>
                            <p>إجمالي رأس المال الموقع</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h3>النشاط الأخير</h3>
                    <div class="activity-list" id="recent-activity-list">
                        <div class="no-activity">
                            <span class="no-activity-icon">🏗️</span>
                            <p>مؤسسة سما البنيان التجارية</p>
                            <p>لا يوجد نشاط حتى الآن - ابدأ بإضافة الموظفين وإنشاء العقود</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contracts Section -->
            <div id="contracts-section" class="content-section">
                <div class="section-header">
                    <div class="filters">
                        <select id="contract-status-filter" onchange="filterContracts()">
                            <option value="">جميع الحالات</option>
                            <option value="draft">مسودة</option>
                            <option value="pending_review">بانتظار المراجعة</option>
                            <option value="signed">موقعة</option>
                            <option value="rejected">مرفوضة</option>
                            <option value="expired">منتهية</option>
                        </select>
                        
                        <select id="contract-employee-filter" onchange="filterContracts()">
                            <option value="">جميع الموظفين</option>
                            <!-- Will be populated by JavaScript -->
                        </select>
                    </div>
                </div>

                <div class="contracts-grid" id="contracts-grid">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>

            <!-- Employees Section -->
            <div id="employees-section" class="content-section">
                <div class="section-header">
                    <button class="btn btn-primary" onclick="showAddEmployeeModal()">
                        <span>➕</span>
                        إضافة موظف جديد
                    </button>
                </div>

                <div class="employees-table-container">
                    <table class="data-table" id="employees-table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الهاتف</th>
                                <th>عدد العقود</th>
                                <th>آخر نشاط</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="employees-table-body">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Signatures Section -->
            <div id="signatures-section" class="content-section">
                <div class="signature-manager">
                    <div class="signature-upload">
                        <h3>إدارة التوقيعات والأختام</h3>
                        <div class="upload-area" id="signature-upload-area">
                            <div class="upload-icon">✍️</div>
                            <p>اسحب وأفلت التوقيع أو الختم هنا</p>
                            <p class="upload-hint">أو انقر لاختيار ملف (PNG, JPG, SVG)</p>
                            <input type="file" id="signature-file" accept="image/*" style="display: none;">
                        </div>
                    </div>
                    
                    <div class="current-signatures">
                        <h4>التوقيعات المحفوظة</h4>
                        <div class="signatures-list" id="signatures-list">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <div class="pending-signatures">
                    <h3>العقود بانتظار التوقيع</h3>
                    <div class="pending-contracts-list" id="pending-signatures-list">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Reports Section -->
            <div id="reports-section" class="content-section">
                <div class="reports-grid">
                    <div class="report-card">
                        <h3>📊 تقرير العقود الشهري</h3>
                        <canvas id="monthly-contracts-chart"></canvas>
                    </div>
                    
                    <div class="report-card">
                        <h3>💰 تقرير الأرباح</h3>
                        <canvas id="profits-chart"></canvas>
                    </div>
                    
                    <div class="report-card">
                        <h3>👥 أداء الموظفين</h3>
                        <div id="employees-performance"></div>
                    </div>
                    
                    <div class="report-card">
                        <h3>📈 الإحصائيات العامة</h3>
                        <div id="general-stats"></div>
                    </div>
                </div>
            </div>

            <!-- Notifications Section -->
            <div id="notifications-section" class="content-section">
                <div class="notifications-header">
                    <button class="btn btn-secondary" onclick="markAllNotificationsRead()">
                        <span>✅</span>
                        تعيين الكل كمقروء
                    </button>
                </div>
                
                <div class="notifications-list" id="notifications-list">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Contract Details Modal -->
    <div id="contract-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="contract-modal-title">تفاصيل العقد</h3>
                <button class="modal-close" onclick="closeModal('contract-modal')">&times;</button>
            </div>
            <div class="modal-body" id="contract-modal-body">
                <!-- Will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" id="sign-contract-btn" onclick="signContract()">
                    <span>✍️</span>
                    توقيع العقد
                </button>
                <button class="btn btn-danger" id="reject-contract-btn" onclick="rejectContract()">
                    <span>❌</span>
                    رفض العقد
                </button>
                <button class="btn btn-secondary" onclick="closeModal('contract-modal')">إغلاق</button>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div id="add-employee-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>إضافة موظف جديد</h3>
                <button class="modal-close" onclick="closeModal('add-employee-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="add-employee-form" onsubmit="addEmployee(event)">
                    <div class="form-group">
                        <label for="employee-name">الاسم الكامل</label>
                        <input type="text" id="employee-name" required>
                    </div>
                    <div class="form-group">
                        <label for="employee-email">البريد الإلكتروني</label>
                        <input type="email" id="employee-email" required>
                    </div>
                    <div class="form-group">
                        <label for="employee-phone">رقم الهاتف</label>
                        <input type="tel" id="employee-phone">
                    </div>
                    <div class="form-group">
                        <label for="employee-password">كلمة المرور</label>
                        <input type="password" id="employee-password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="document.getElementById('add-employee-form').submit()">
                    <span>➕</span>
                    إضافة الموظف
                </button>
                <button class="btn btn-secondary" onclick="closeModal('add-employee-modal')">إلغاء</button>
            </div>
        </div>
    </div>

    <style>
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-icon {
            margin-left: 12px;
            font-size: 18px;
        }

        .nav-badge {
            margin-right: auto;
            background: #ff4757;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 12px;
        }

        .main-content {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .header-title h1 {
            margin: 0;
            color: white;
            font-size: 32px;
        }

        .header-title p {
            margin: 4px 0 0 0;
            color: rgba(255, 255, 255, 0.7);
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            font-size: 48px;
            opacity: 0.8;
        }

        .stat-info h3 {
            margin: 0;
            font-size: 32px;
            color: white;
        }

        .stat-info p {
            margin: 4px 0 0 0;
            color: rgba(255, 255, 255, 0.7);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            margin: 5% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        let currentContractId = null;
        
        // Show/Hide sections
        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionName + '-section').classList.add('active');
            document.querySelector(`[onclick="showSection('${sectionName}')"]`).classList.add('active');
            
            // Update header
            const titles = {
                overview: { title: 'نظرة عامة', subtitle: 'مرحباً بك في لوحة تحكم المدير' },
                contracts: { title: 'إدارة العقود', subtitle: 'مراجعة وتوقيع العقود' },
                employees: { title: 'إدارة الموظفين', subtitle: 'إضافة وإدارة حسابات الموظفين' },
                signatures: { title: 'التوقيعات الإلكترونية', subtitle: 'إدارة التوقيعات والأختام' },
                reports: { title: 'التقارير والإحصاءات', subtitle: 'تقارير مفصلة عن الأداء' },
                notifications: { title: 'الإشعارات', subtitle: 'رسائل النظام والتنبيهات' }
            };
            
            document.getElementById('section-title').textContent = titles[sectionName].title;
            document.getElementById('section-subtitle').textContent = titles[sectionName].subtitle;
            
            // Load section data
            loadSectionData(sectionName);
        }
        
        // Load data for specific section
        function loadSectionData(section) {
            switch(section) {
                case 'contracts':
                    loadContracts();
                    break;
                case 'employees':
                    loadEmployees();
                    break;
                case 'signatures':
                    loadPendingSignatures();
                    break;
                case 'reports':
                    loadReports();
                    break;
                case 'notifications':
                    loadNotifications();
                    break;
            }
        }
        
        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
        });
        
        // Load dashboard data
        function loadDashboardData() {
            // This will be implemented with AJAX calls to the backend
            console.log('Loading dashboard data...');
        }
        
        // Placeholder functions (to be implemented)
        function refreshData() { console.log('Refreshing data...'); }
        function filterContracts() { console.log('Filtering contracts...'); }
        function loadContracts() { console.log('Loading contracts...'); }
        function loadEmployees() { console.log('Loading employees...'); }
        function loadPendingSignatures() { console.log('Loading pending signatures...'); }
        function loadReports() { console.log('Loading reports...'); }
        function loadNotifications() { console.log('Loading notifications...'); }
        function showAddEmployeeModal() { showModal('add-employee-modal'); }
        function addEmployee(event) { event.preventDefault(); console.log('Adding employee...'); }
        function signContract() { console.log('Signing contract...'); }
        function rejectContract() { console.log('Rejecting contract...'); }
        function markAllNotificationsRead() { console.log('Marking all notifications as read...'); }
    </script>
</body>
</html>