<?php
/**
 * Top Navigation Component
 * الشريط العلوي للتنقل
 */

$userName = $_SESSION['user_name'] ?? 'مستخدم';
$userRole = $_SESSION['user_role'] ?? 'employee';
$currentTime = date('Y-m-d H:i');
?>

<div class="topnav">
    <div class="topnav-content">
        <!-- Left Side - Breadcrumb & Actions -->
        <div class="topnav-left">
            <div class="breadcrumb">
                <?php
                $path = trim($_SERVER['REQUEST_URI'], '/');
                $segments = explode('/', $path);
                $breadcrumbs = [];
                
                // Generate breadcrumbs based on current path
                switch($path) {
                    case 'manager-dashboard':
                        $breadcrumbs = [['title' => 'لوحة المدير', 'icon' => '📊']];
                        break;
                    case 'employee-dashboard':
                        $breadcrumbs = [['title' => 'لوحة الموظف', 'icon' => '📋']];
                        break;
                    case 'contracts':
                        $breadcrumbs = [['title' => 'العقود', 'icon' => '📄']];
                        break;
                    case 'profile':
                        $breadcrumbs = [['title' => 'الملف الشخصي', 'icon' => '👤']];
                        break;
                    case 'notifications':
                        $breadcrumbs = [['title' => 'الإشعارات', 'icon' => '🔔']];
                        break;
                    default:
                        $breadcrumbs = [['title' => 'الرئيسية', 'icon' => '🏠']];
                }
                ?>
                
                <?php foreach($breadcrumbs as $index => $breadcrumb): ?>
                    <span class="breadcrumb-item">
                        <span class="breadcrumb-icon"><?= $breadcrumb['icon'] ?></span>
                        <span class="breadcrumb-text"><?= $breadcrumb['title'] ?></span>
                    </span>
                    <?php if ($index < count($breadcrumbs) - 1): ?>
                        <span class="breadcrumb-separator">←</span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Right Side - User Info & Actions -->
        <div class="topnav-right">
            <div class="topnav-actions">
                <!-- Notifications -->
                <div class="action-item notifications">
                    <a href="/notifications" class="action-link" title="الإشعارات">
                        <span class="action-icon">🔔</span>
                        <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                            <span class="action-badge"><?= $_SESSION['unread_notifications'] ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Quick Settings -->
                <div class="action-item settings">
                    <button class="action-link" title="الإعدادات السريعة" onclick="toggleQuickSettings()">
                        <span class="action-icon">⚙️</span>
                    </button>
                </div>
                
                <!-- User Menu -->
                <div class="action-item user-menu">
                    <button class="user-menu-toggle" onclick="toggleUserMenu()">
                        <div class="user-avatar-small">
                            <span><?= mb_substr($userName, 0, 1) ?></span>
                        </div>
                        <div class="user-info-small">
                            <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                            <span class="user-role"><?= $userRole === 'manager' ? 'مدير' : 'موظف' ?></span>
                        </div>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    
                    <!-- User Dropdown Menu -->
                    <div class="user-dropdown" id="userDropdown">
                        <a href="/profile" class="dropdown-item">
                            <span class="dropdown-icon">👤</span>
                            <span>الملف الشخصي</span>
                        </a>
                        <a href="/settings" class="dropdown-item">
                            <span class="dropdown-icon">⚙️</span>
                            <span>الإعدادات</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="/logout" class="dropdown-item logout">
                            <span class="dropdown-icon">🚪</span>
                            <span>تسجيل الخروج</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Current Time -->
            <div class="current-time">
                <span class="time-icon">🕐</span>
                <span class="time-text" id="currentTime"><?= $currentTime ?></span>
            </div>
        </div>
    </div>
    
    <!-- Quick Settings Panel -->
    <div class="quick-settings" id="quickSettings">
        <div class="settings-header">
            <h4>الإعدادات السريعة</h4>
            <button onclick="toggleQuickSettings()" class="close-btn">&times;</button>
        </div>
        <div class="settings-content">
            <div class="setting-item">
                <label>الوضع الليلي</label>
                <button class="toggle-btn" onclick="toggleDarkMode()">
                    <span class="toggle-slider"></span>
                </button>
            </div>
            <div class="setting-item">
                <label>الإشعارات</label>
                <button class="toggle-btn active" onclick="toggleNotifications()">
                    <span class="toggle-slider"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.topnav {
    height: var(--topnav-height);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    box-shadow: 0 2px 20px rgba(0,0,0,0.1);
    border-bottom: 1px solid rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.topnav-content {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 2rem;
}

/* Breadcrumb */
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #333;
    font-weight: 500;
}

.breadcrumb-icon {
    font-size: 1.25rem;
}

.breadcrumb-separator {
    color: #666;
    margin: 0 0.5rem;
}

/* Right Side */
.topnav-right {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.topnav-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.action-item {
    position: relative;
}

.action-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: none;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 50%;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
}

.action-link:hover {
    background: rgba(102, 126, 234, 0.2);
    transform: scale(1.05);
}

.action-icon {
    font-size: 1.2rem;
}

.action-badge {
    position: absolute;
    top: -5px;
    left: -5px;
    background: #ef4444;
    color: white;
    font-size: 0.75rem;
    padding: 0.2rem 0.4rem;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

/* User Menu */
.user-menu {
    position: relative;
}

.user-menu-toggle {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    background: rgba(102, 126, 234, 0.1);
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.user-menu-toggle:hover {
    background: rgba(102, 126, 234, 0.2);
}

.user-avatar-small {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.9rem;
}

.user-info-small {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.user-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #333;
}

.user-role {
    font-size: 0.75rem;
    color: #666;
}

.dropdown-arrow {
    font-size: 0.7rem;
    color: #666;
    transition: transform 0.3s ease;
}

.user-menu.active .dropdown-arrow {
    transform: rotate(180deg);
}

/* User Dropdown */
.user-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    min-width: 200px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    border: 1px solid rgba(0,0,0,0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
}

.user-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #333;
    text-decoration: none;
    transition: background 0.3s ease;
    border-radius: 8px;
    margin: 0.25rem;
}

.dropdown-item:hover {
    background: rgba(102, 126, 234, 0.1);
}

.dropdown-item.logout {
    color: #dc2626;
}

.dropdown-item.logout:hover {
    background: rgba(220, 38, 38, 0.1);
}

.dropdown-divider {
    height: 1px;
    background: rgba(0,0,0,0.1);
    margin: 0.5rem 1rem;
}

/* Current Time */
.current-time {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #666;
    background: rgba(0,0,0,0.05);
    padding: 0.5rem 1rem;
    border-radius: 20px;
}

/* Quick Settings */
.quick-settings {
    position: fixed;
    top: 0;
    left: -300px;
    width: 300px;
    height: 100vh;
    background: white;
    box-shadow: 5px 0 20px rgba(0,0,0,0.1);
    transition: left 0.3s ease;
    z-index: 1001;
}

.quick-settings.show {
    left: 0;
}

.settings-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.settings-header h4 {
    margin: 0;
    color: #333;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
}

.settings-content {
    padding: 1.5rem;
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.toggle-btn {
    width: 50px;
    height: 25px;
    background: #ccc;
    border: none;
    border-radius: 25px;
    position: relative;
    cursor: pointer;
    transition: background 0.3s ease;
}

.toggle-btn.active {
    background: #667eea;
}

.toggle-slider {
    width: 21px;
    height: 21px;
    background: white;
    border-radius: 50%;
    position: absolute;
    top: 2px;
    right: 2px;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.toggle-btn.active .toggle-slider {
    transform: translateX(-25px);
}

/* Responsive */
@media (max-width: 768px) {
    .topnav-content {
        padding: 0 1rem;
    }
    
    .user-info-small {
        display: none;
    }
    
    .current-time .time-text {
        display: none;
    }
    
    .breadcrumb-text {
        display: none;
    }
}
</style>

<script>
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    const menu = dropdown.parentElement;
    dropdown.classList.toggle('show');
    menu.classList.toggle('active');
}

function toggleQuickSettings() {
    const settings = document.getElementById('quickSettings');
    settings.classList.toggle('show');
}

function toggleDarkMode() {
    // Implementation for dark mode
    console.log('Dark mode toggled');
}

function toggleNotifications() {
    // Implementation for notifications toggle
    console.log('Notifications toggled');
}

// Update time every minute
setInterval(() => {
    const now = new Date();
    const timeString = now.getFullYear() + '-' + 
                      String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                      String(now.getDate()).padStart(2, '0') + ' ' +
                      String(now.getHours()).padStart(2, '0') + ':' + 
                      String(now.getMinutes()).padStart(2, '0');
    
    const timeElement = document.getElementById('currentTime');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}, 60000);

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.user-menu')) {
        document.getElementById('userDropdown').classList.remove('show');
        document.querySelector('.user-menu').classList.remove('active');
    }
    
    if (!e.target.closest('.quick-settings') && !e.target.closest('.settings .action-link')) {
        document.getElementById('quickSettings').classList.remove('show');
    }
});
</script>