<?php
/**
 * Contract Helper Functions
 * Common functions used across contract management system
 */

/**
 * Format contract status in Arabic
 */
function getContractStatusText($status) {
    $statuses = [
        'draft' => 'مسودة',
        'pending_review' => 'قيد المراجعة',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'signed' => 'موقع'
    ];
    
    return $statuses[$status] ?? 'غير محدد';
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'draft' => 'status-draft',
        'pending_review' => 'status-pending_review',
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        'signed' => 'status-signed'
    ];
    
    return $classes[$status] ?? 'bg-secondary';
}

/**
 * Format contract type in Arabic
 */
function getContractTypeText($type) {
    $types = [
        'real_estate' => 'استثمار عقاري',
        'company' => 'استثمار شركة'
    ];
    
    return $types[$type] ?? 'غير محدد';
}

/**
 * Format profit interval in Arabic
 */
function getProfitIntervalText($interval) {
    $intervals = [
        '3' => 'كل 3 أشهر',
        '6' => 'كل 6 أشهر',
        '12' => 'سنوياً'
    ];
    
    return $intervals[$interval] ?? 'غير محدد';
}

/**
 * Calculate remaining time
 */
function getTimeRemaining($date) {
    $now = new DateTime();
    $target = new DateTime($date);
    $interval = $now->diff($target);
    
    if ($target < $now) {
        return 'منتهية';
    }
    
    $days = $interval->days;
    
    if ($days > 30) {
        $months = floor($days / 30);
        return $months . ' شهر';
    } elseif ($days > 0) {
        return $days . ' يوم';
    } else {
        return 'اليوم';
    }
}

/**
 * Format currency amount
 */
function formatCurrency($amount) {
    return number_format($amount, 2, '.', ',') . ' ريال';
}

/**
 * Get user role text in Arabic
 */
function getRoleText($role) {
    $roles = [
        'admin' => 'مدير النظام',
        'manager' => 'مدير',
        'employee' => 'موظف'
    ];
    
    return $roles[$role] ?? 'غير محدد';
}

/**
 * Check if user can perform action on contract
 */
function canUserPerformAction($user, $contract, $action) {
    $userRole = $user['role'];
    $contractStatus = $contract['status'];
    $isOwner = $contract['created_by'] == $user['id'];
    
    switch ($action) {
        case 'edit':
            return ($contractStatus === 'draft' || $contractStatus === 'rejected') && 
                   ($isOwner || in_array($userRole, ['manager', 'admin']));
            
        case 'delete':
            return $contractStatus === 'draft' && 
                   ($isOwner || in_array($userRole, ['manager', 'admin']));
            
        case 'approve':
            return $contractStatus === 'pending_review' && 
                   in_array($userRole, ['manager', 'admin']);
            
        case 'reject':
            return $contractStatus === 'pending_review' && 
                   in_array($userRole, ['manager', 'admin']);
            
        case 'submit_for_review':
            return $contractStatus === 'draft' && 
                   ($isOwner || in_array($userRole, ['manager', 'admin']));
            
        case 'sign':
            return $contractStatus === 'approved' && 
                   in_array($userRole, ['manager', 'admin']);
            
        default:
            return false;
    }
}

/**
 * Generate contract activity description
 */
function getActivityDescription($action, $details = '') {
    $descriptions = [
        'create_contract' => 'تم إنشاء العقد',
        'update_contract' => 'تم تحديث العقد',
        'submit_for_review' => 'تم إرسال العقد للمراجعة',
        'approve_contract' => 'تم اعتماد العقد',
        'reject_contract' => 'تم رفض العقد',
        'sign_contract' => 'تم توقيع العقد',
        'upload_file' => 'تم رفع ملف',
        'delete_file' => 'تم حذف ملف'
    ];
    
    $description = $descriptions[$action] ?? $action;
    
    if ($details) {
        $description .= ': ' . $details;
    }
    
    return $description;
}

/**
 * Get contract progress percentage
 */
function getContractProgress($status) {
    $progress = [
        'draft' => 20,
        'pending_review' => 40,
        'approved' => 80,
        'rejected' => 0,
        'signed' => 100
    ];
    
    return $progress[$status] ?? 0;
}

/**
 * Validate contract data
 */
function validateContractData($data) {
    $errors = [];
    
    // Required fields
    if (empty($data['client_name']) || strlen($data['client_name']) < 3) {
        $errors[] = 'اسم العميل مطلوب ويجب أن يكون 3 أحرف على الأقل';
    }
    
    if (empty($data['client_id']) || !preg_match('/^[0-9]{10}$/', $data['client_id'])) {
        $errors[] = 'رقم الهوية يجب أن يكون 10 أرقام بالضبط';
    }
    
    if (empty($data['client_phone']) || !preg_match('/^05[0-9]{8}$/', $data['client_phone'])) {
        $errors[] = 'رقم الجوال غير صحيح (يجب أن يبدأ بـ 05 ويكون 10 أرقام)';
    }
    
    if (!empty($data['client_email']) && !filter_var($data['client_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }
    
    if ($data['amount'] < 50000) {
        $errors[] = 'مبلغ العقد يجب أن يكون 50,000 ريال على الأقل';
    }
    
    if ($data['profit_percentage'] <= 0 || $data['profit_percentage'] > 50) {
        $errors[] = 'نسبة الربح يجب أن تكون بين 1% و 50%';
    }
    
    return $errors;
}

/**
 * Log contract activity
 */
function logContractActivity($pdo, $userId, $contractId, $action, $description = '') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, related_contract_id, action, description, created_at) 
            VALUES (?, ?, ?, ?, datetime('now'))
        ");
        
        $fullDescription = getActivityDescription($action, $description);
        
        return $stmt->execute([$userId, $contractId, $action, $fullDescription]);
    } catch (PDOException $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get contract statistics
 */
function getContractStatistics($pdo) {
    $stats = [];
    
    try {
        // Total contracts
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM contracts");
        $stats['total'] = $stmt->fetchColumn();
        
        // By status
        $stmt = $pdo->query("
            SELECT status, COUNT(*) as count 
            FROM contracts 
            GROUP BY status
        ");
        
        while ($row = $stmt->fetch()) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        // Total investment amount
        $stmt = $pdo->query("SELECT SUM(amount) as total_investment FROM contracts WHERE status != 'rejected'");
        $stats['total_investment'] = $stmt->fetchColumn() ?: 0;
        
        // Total profit
        $stmt = $pdo->query("SELECT SUM(net_profit) as total_profit FROM contracts WHERE status = 'signed'");
        $stats['total_profit'] = $stmt->fetchColumn() ?: 0;
        
    } catch (PDOException $e) {
        error_log("Failed to get statistics: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Send notification (placeholder for future implementation)
 */
function sendNotification($userId, $title, $message, $type = 'info') {
    // TODO: Implement notification system
    // This could send email, SMS, or push notifications
    return true;
}

/**
 * Generate PDF-safe HTML
 */
function getPdfSafeHtml($content) {
    // Remove problematic elements for PDF generation
    $content = str_replace(['<script>', '</script>'], '', $content);
    $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
    
    return $content;
}

/**
 * Format date in Arabic
 */
function formatArabicDate($date, $format = 'Y-m-d') {
    $arabicMonths = [
        '01' => 'يناير', '02' => 'فبراير', '03' => 'مارس', '04' => 'أبريل',
        '05' => 'مايو', '06' => 'يونيو', '07' => 'يوليو', '08' => 'أغسطس',
        '09' => 'سبتمبر', '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر'
    ];
    
    $datetime = new DateTime($date);
    $day = $datetime->format('d');
    $month = $arabicMonths[$datetime->format('m')];
    $year = $datetime->format('Y');
    
    return "$day $month $year";
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Check file upload security
 */
function isSecureFileUpload($file) {
    $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'نوع الملف غير مسموح'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً (الحد الأقصى 10 ميجابايت)'];
    }
    
    return ['success' => true];
}
?>