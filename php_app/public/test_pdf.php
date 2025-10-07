<?php
// اختبار سريع لتصدير PDF
session_start();

// محاكاة بيانات المستخدم للاختبار
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'manager';

require_once '../models/Contract.php';
require_once '../models/DetailedContract.php';
require_once '../services/SimplePdfService.php';

try {
    // جلب أول عقد للاختبار
    $contractModel = new Contract();
    $contracts = $contractModel->getAllForManager();
    
    if (empty($contracts)) {
        die('لا توجد عقود للاختبار');
    }
    
    $contract = $contracts[0];
    $contractId = $contract['id'];
    
    // جلب البيانات المفصلة
    $detailedContractModel = new DetailedContract();
    $detailedContract = $detailedContractModel->getByContractId($contractId);
    
    // إنشاء خدمة PDF
    $pdfService = new SimplePdfService();
    $htmlContent = $pdfService->generateContractHtml($contract, $detailedContract);
    
    // إرسال HTML للعرض
    header('Content-Type: text/html; charset=UTF-8');
    echo $htmlContent;
    
} catch (Exception $e) {
    echo "خطأ في النظام: " . $e->getMessage();
    echo "<br><br>تفاصيل الخطأ:<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>