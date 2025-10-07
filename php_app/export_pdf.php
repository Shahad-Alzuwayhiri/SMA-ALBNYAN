<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'models/Contract.php';
require_once 'models/DetailedContract.php';
require_once 'services/SimplePdfService.php';

// التحقق من معرف العقد
if (!isset($_GET['id'])) {
    die('معرف العقد مطلوب');
}

$contractId = $_GET['id'];

try {
    // جلب بيانات العقد
    $contractModel = new Contract();
    $contract = $contractModel->findById($contractId);
    
    if (!$contract) {
        die('العقد غير موجود');
    }
    
    // جلب البيانات المفصلة
    $detailedContractModel = new DetailedContract();
    $detailedContract = $detailedContractModel->getByContractId($contractId);
    
    // إنشاء خدمة PDF المبسطة
    $pdfService = new SimplePdfService();
    $htmlContent = $pdfService->generateContractHtml($contract, $detailedContract);
    
    // إرسال HTML للعرض
    header('Content-Type: text/html; charset=UTF-8');
    echo $htmlContent;
    
} catch (Exception $e) {
    die('خطأ في تصدير العقد: ' . $e->getMessage());
}
?>