<?php
require_once '../includes/auth.php';

$auth->requireAuth();
$user = $auth->getCurrentUser();

// التحقق من وجود معرف العقد
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    die('معرف العقد غير صحيح');
}

$contract_id = (int)$_GET['id'];

try {
    // جلب بيانات العقد
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as creator_name 
        FROM contracts c 
        LEFT JOIN users u ON c.created_by = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$contract_id]);
    $contract = $stmt->fetch();
    
    if (!$contract) {
        header('HTTP/1.0 404 Not Found');
        die('العقد غير موجود');
    }
    
    // التحقق من صلاحية الوصول
    $canAccess = false;
    if ($user['role'] === 'admin') {
        $canAccess = true;
    } elseif ($user['role'] === 'manager') {
        $canAccess = true;
    } elseif ($user['role'] === 'employee' && $contract['created_by'] == $user['id']) {
        $canAccess = true;
    }
    
    if (!$canAccess) {
        header('HTTP/1.0 403 Forbidden');
        die('غير مسموح لك بتحميل هذا العقد');
    }
    
} catch (PDOException $e) {
    header('HTTP/1.0 500 Internal Server Error');
    die('خطأ في قاعدة البيانات: ' . $e->getMessage());
}

// إعداد محتوى PDF (نسخة بسيطة نصية حالياً)
$content = generateContractPDF($contract);

// إعداد Headers للتحميل
$filename = "contract_" . $contract['id'] . "_" . date('Y-m-d') . ".pdf";
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $content;

function generateContractPDF($contract) {
    // إنشاء محتوى PDF بتصميم الشركة
    // في التطبيق الحقيقي، يجب استخدام مكتبة PDF مثل TCPDF أو mPDF
    
    $isAmendment = $contract['is_amendment'] ?? false;
    $contractType = $isAmendment ? 'تعديل عقد' : 'عقد استثماري';
    $netProfit = $contract['net_profit'] ?? 0;
    $amendmentInfo = $isAmendment ? "\nالعقد الأصلي: {$contract['parent_contract_id']}\nمدة التعديل: {$contract['amendment_duration_months']} شهر" : '';
    
    $content = "
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║                        سما البنيان للتطوير والاستثمار العقاري                        ║
║                             SMA ALBNYAN                                      ║
║                    للتطوير والاستثمار العقاري                                   ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝

═══════════════════════════════════════════════════════════════════════════════
                            {$contractType} رقم: {$contract['contract_number']}
═══════════════════════════════════════════════════════════════════════════════

📋 معلومات العقد الأساسية:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
• اسم العميل: {$contract['client_name']}
• رقم الهوية: {$contract['client_id']}
• رقم الجوال: {$contract['client_phone']}
• تاريخ العقد: {$contract['contract_date']}
• طريقة التوقيع: {$contract['signature_method']}{$amendmentInfo}

💰 التفاصيل المالية:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
• مبلغ العقد: " . number_format($contract['amount'], 2) . " ريال سعودي
• نسبة الربح: {$contract['profit_percentage']}%
• صافي الربح المتوقع: " . number_format($netProfit, 2) . " ريال سعودي
• مدة العقد: {$contract['contract_duration']} شهر
• فترة توزيع الأرباح: كل {$contract['profit_interval']} أشهر

📝 الشروط والأحكام:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. يخضع هذا العقد لأنظمة وقوانين المملكة العربية السعودية
2. الحد الأدنى للاستثمار: 50,000 ريال سعودي (وفقاً لسياسة الشركة)
3. مدة تعديل العقود: 6 أشهر فقط (غير قابلة للتغيير)
4. يتم توزيع الأرباح حسب الفترات المتفق عليها
5. يحق للشركة مراجعة العقد دورياً لضمان الجودة

📋 ملاحظات إضافية:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{$contract['notes']}

👤 معلومات المنشئ:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
• منشئ العقد: {$contract['creator_name']}
• تاريخ الإنشاء: {$contract['created_at']}
• تاريخ التحديث: {$contract['updated_at']}
• حالة العقد: {$contract['status']}

═══════════════════════════════════════════════════════════════════════════════
                              🏢 WATERMARK: SMA ALBNYAN 🏢
                     للتطوير والاستثمار العقاري - جميع الحقوق محفوظة
═══════════════════════════════════════════════════════════════════════════════

📄 تفاصيل التصدير:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
• تاريخ التصدير: " . date('Y-m-d H:i:s') . "
• نظام إدارة العقود - سما البنيان
• هذا المستند محمي بحقوق الطبع والنشر

╔══════════════════════════════════════════════════════════════════════════════╗
║  هذا العقد صادر من نظام سما البنيان للتطوير والاستثمار العقاري الإلكتروني        ║
║                    للتحقق من صحة العقد يرجى التواصل مع الشركة                    ║
╚══════════════════════════════════════════════════════════════════════════════╝
";
    
    return $content;
}

// تسجيل عملية التحميل في السجل
try {
    $logStmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, details, created_at) 
        VALUES (?, 'contract_download', ?, datetime('now'))
    ");
    $logStmt->execute([
        $user['id'], 
        "تم تحميل العقد رقم: {$contract['id']} - {$contract['title']}"
    ]);
} catch (PDOException $e) {
    // تجاهل أخطاء السجل
}
?>