<?php
/**
 * Manager Dashboard Content
 * محتوى لوحة تحكم المدير بدون layout
 */

$userName = $_SESSION['user_name'] ?? 'المدير';
?>

<div class="dashboard-header">
    <div class="welcome-section">
        <h1>🏢 مرحباً بك، <?= htmlspecialchars($userName) ?>!</h1>
        <p class="dashboard-subtitle">لوحة تحكم المدير - نظام إدارة العقود</p>
    </div>
    
    <div class="quick-actions">
        <a href="/contracts/create" class="quick-action-btn primary">
            <span class="btn-icon">➕</span>
            <span>عقد جديد</span>
        </a>
        <a href="/reports" class="quick-action-btn secondary">
            <span class="btn-icon">📊</span>
            <span>التقارير</span>
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Statistics Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon">📄</div>
            <div class="stat-content">
                <h3>إجمالي العقود</h3>
                <p class="stat-number"><?= $total_contracts ?? 142 ?></p>
                <span class="stat-change positive">+12% هذا الشهر</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3>العقود المكتملة</h3>
                <p class="stat-number">98</p>
                <span class="stat-change positive">+8% هذا الشهر</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <h3>قيد المراجعة</h3>
                <p class="stat-number"><?= $pending_approvals ?? 24 ?></p>
                <span class="stat-change neutral">بدون تغيير</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3>الموظفين النشطين</h3>
                <p class="stat-number">18</p>
                <span class="stat-change positive">+2 موظف جديد</span>
            </div>
        </div>
    </div>
    
    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Recent Contracts -->
        <div class="dashboard-card recent-contracts">
            <div class="card-header">
                <h3>العقود الحديثة</h3>
                <a href="/contracts" class="view-all-link">عرض الكل</a>
            </div>
            <div class="card-content">
                <div class="contract-item">
                    <div class="contract-info">
                        <h4>عقد توريد مواد البناء</h4>
                        <p>شركة البناء المتطورة</p>
                        <span class="contract-status pending">قيد المراجعة</span>
                    </div>
                    <div class="contract-meta">
                        <span class="contract-date">اليوم</span>
                        <span class="contract-value">250,000 ريال</span>
                    </div>
                </div>
                
                <div class="contract-item">
                    <div class="contract-info">
                        <h4>عقد صيانة المعدات</h4>
                        <p>مؤسسة التقنية الحديثة</p>
                        <span class="contract-status approved">مُعتمد</span>
                    </div>
                    <div class="contract-meta">
                        <span class="contract-date">أمس</span>
                        <span class="contract-value">85,000 ريال</span>
                    </div>
                </div>
                
                <div class="contract-item">
                    <div class="contract-info">
                        <h4>عقد خدمات استشارية</h4>
                        <p>شركة الاستشارات الذكية</p>
                        <span class="contract-status completed">مكتمل</span>
                    </div>
                    <div class="contract-meta">
                        <span class="contract-date">منذ يومين</span>
                        <span class="contract-value">120,000 ريال</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Performance Chart -->
        <div class="dashboard-card performance-chart">
            <div class="card-header">
                <h3>أداء العقود الشهري</h3>
                <select class="chart-filter">
                    <option>آخر 6 أشهر</option>
                    <option>السنة الحالية</option>
                </select>
            </div>
            <div class="card-content">
                <div class="chart-placeholder">
                    <div class="chart-bar" style="height: 60%">
                        <span class="bar-label">يناير</span>
                        <span class="bar-value">42</span>
                    </div>
                    <div class="chart-bar" style="height: 80%">
                        <span class="bar-label">فبراير</span>
                        <span class="bar-value">56</span>
                    </div>
                    <div class="chart-bar" style="height: 45%">
                        <span class="bar-label">مارس</span>
                        <span class="bar-value">32</span>
                    </div>
                    <div class="chart-bar" style="height: 90%">
                        <span class="bar-label">أبريل</span>
                        <span class="bar-value">63</span>
                    </div>
                    <div class="chart-bar" style="height: 70%">
                        <span class="bar-label">مايو</span>
                        <span class="bar-value">49</span>
                    </div>
                    <div class="chart-bar" style="height: 95%">
                        <span class="bar-label">يونيو</span>
                        <span class="bar-value">67</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Employee Activity -->
        <div class="dashboard-card employee-activity">
            <div class="card-header">
                <h3>نشاط الموظفين</h3>
                <span class="activity-badge">5 متصلين الآن</span>
            </div>
            <div class="card-content">
                <?php if (isset($recent_activities) && is_array($recent_activities)): ?>
                    <?php foreach($recent_activities as $activity): ?>
                        <div class="employee-item">
                            <div class="employee-avatar"><?= mb_substr($activity['user'] ?? 'م', 0, 1) ?></div>
                            <div class="employee-info">
                                <h4><?= htmlspecialchars($activity['user'] ?? 'مستخدم') ?></h4>
                                <p><?= htmlspecialchars($activity['action'] ?? '') ?> - <?= htmlspecialchars($activity['contract'] ?? '') ?></p>
                                <small><?= htmlspecialchars($activity['time'] ?? '') ?></small>
                            </div>
                            <div class="activity-status online"></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="employee-item">
                        <div class="employee-avatar">أ</div>
                        <div class="employee-info">
                            <h4>أحمد محمد</h4>
                            <p>أنشأ عقد جديد منذ 15 دقيقة</p>
                        </div>
                        <div class="activity-status online"></div>
                    </div>
                    
                    <div class="employee-item">
                        <div class="employee-avatar">س</div>
                        <div class="employee-info">
                            <h4>سارة أحمد</h4>
                            <p>راجعت عقد التوريد منذ ساعة</p>
                        </div>
                        <div class="activity-status online"></div>
                    </div>
                    
                    <div class="employee-item">
                        <div class="employee-avatar">م</div>
                        <div class="employee-info">
                            <h4>محمد علي</h4>
                            <p>أضاف تعليق على العقد #142</p>
                        </div>
                        <div class="activity-status away"></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="dashboard-card quick-stats">
            <div class="card-header">
                <h3>إحصائيات سريعة</h3>
            </div>
            <div class="card-content">
                <div class="quick-stat-item">
                    <span class="quick-stat-label">متوسط قيمة العقد</span>
                    <span class="quick-stat-value"><?= number_format($monthly_achievement ?? 185000) ?> ريال</span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label">وقت المعالجة</span>
                    <span class="quick-stat-value">3.2 أيام</span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label">معدل الإنجاز</span>
                    <span class="quick-stat-value"><?= $team_performance ?? 94 ?>.5%</span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label">التقييم العام</span>
                    <span class="quick-stat-value">⭐⭐⭐⭐⭐</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem 0;
    border-bottom: 2px solid rgba(102, 126, 234, 0.1);
}

.welcome-section h1 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 2rem;
}

.dashboard-subtitle {
    margin: 0;
    color: #666;
    font-size: 1.1rem;
}

.quick-actions {
    display: flex;
    gap: 1rem;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.quick-action-btn.primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.quick-action-btn.secondary {
    background: rgba(102, 126, 234, 0.1);
    color: #333;
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Statistics Cards */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.3s ease;
    backdrop-filter: blur(10px);
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 2.5rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    border-radius: 12px;
}

.stat-content h3 {
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
}

.stat-number {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
    color: #333;
}

.stat-change {
    font-size: 0.8rem;
    font-weight: 500;
}

.stat-change.positive {
    color: #10b981;
}

.stat-change.negative {
    color: #ef4444;
}

.stat-change.neutral {
    color: #666;
}

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    grid-template-rows: auto auto;
}

.dashboard-card {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.05);
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(102, 126, 234, 0.02);
}

.card-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.2rem;
}

.view-all-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
}

.card-content {
    padding: 1.5rem;
}

/* Recent Contracts */
.recent-contracts {
    grid-row: span 1;
}

.contract-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    background: rgba(0,0,0,0.02);
    transition: background 0.3s ease;
}

.contract-item:hover {
    background: rgba(102, 126, 234, 0.05);
}

.contract-info h4 {
    margin: 0 0 0.25rem 0;
    color: #333;
    font-size: 1rem;
}

.contract-info p {
    margin: 0 0 0.5rem 0;
    color: #666;
    font-size: 0.9rem;
}

.contract-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.contract-status.pending {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.contract-status.approved {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
}

.contract-status.completed {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.contract-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.contract-date {
    font-size: 0.8rem;
    color: #666;
}

.contract-value {
    font-weight: 600;
    color: #333;
}

/* Performance Chart */
.performance-chart {
    grid-row: span 2;
}

.chart-filter {
    padding: 0.5rem;
    border: 1px solid rgba(0,0,0,0.2);
    border-radius: 8px;
    font-size: 0.9rem;
}

.chart-placeholder {
    display: flex;
    align-items: end;
    gap: 1rem;
    height: 200px;
    padding: 2rem 0;
}

.chart-bar {
    flex: 1;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 8px 8px 0 0;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    min-height: 40px;
    color: white;
    font-weight: 600;
}

.bar-label {
    position: absolute;
    bottom: -25px;
    font-size: 0.8rem;
    color: #666;
}

.bar-value {
    padding: 0.5rem;
}

/* Employee Activity */
.employee-activity {
    grid-column: 2;
}

.activity-badge {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.employee-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    background: rgba(0,0,0,0.02);
}

.employee-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.employee-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 0.9rem;
    color: #333;
}

.employee-info p {
    margin: 0;
    font-size: 0.8rem;
    color: #666;
}

.employee-info small {
    font-size: 0.75rem;
    color: #999;
}

.activity-status {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: auto;
}

.activity-status.online {
    background: #10b981;
}

.activity-status.away {
    background: #f59e0b;
}

/* Quick Stats */
.quick-stats {
    grid-column: 2;
}

.quick-stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.quick-stat-item:last-child {
    border-bottom: none;
}

.quick-stat-label {
    color: #666;
    font-size: 0.9rem;
}

.quick-stat-value {
    font-weight: 600;
    color: #333;
}

/* Responsive */
@media (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        width: 100%;
    }
    
    .quick-action-btn {
        flex: 1;
        justify-content: center;
    }
}
</style>