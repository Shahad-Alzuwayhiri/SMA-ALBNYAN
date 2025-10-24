<?php
/**
 * Contract PDF Generation - Sama Al-Bunyan Contracts Platform
 * Generate formatted printable PDF copy of the contract
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Authentication check
$auth->requireAuth();
$user = $auth->getCurrentUser();

$contract_id = $_GET['id'] ?? null;
if (!$contract_id || !is_numeric($contract_id)) {
    header('Location: contracts_list.php?error=' . urlencode('معرف العقد مطلوب'));
    exit;
}

$contract = null;
$error = '';

try {
    // Get contract details
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u1.name as created_by_name,
            u2.name as reviewed_by_name
        FROM contracts c
        LEFT JOIN users u1 ON c.created_by = u1.id
        LEFT JOIN users u2 ON c.reviewed_by = u2.id
        WHERE c.id = ?
    ");
    $stmt->execute([$contract_id]);
    $contract = $stmt->fetch();

    if (!$contract) {
        header('Location: contracts_list.php?error=' . urlencode('العقد غير موجود'));
        exit;
    }
    
    // Role-based access control
    if ($user['role'] === 'employee' && $contract['created_by'] != $user['id']) {
        header('Location: contracts_list.php?error=' . urlencode('غير مسموح لك بعرض هذا العقد'));
        exit;
    }
    
    // Only approved or signed contracts can be printed as PDF
    if (!in_array($contract['status'], ['approved', 'signed'])) {
        header('Location: contract_view.php?id=' . $contract_id . '&error=' . urlencode('يمكن طباعة العقود المعتمدة والموقعة فقط'));
        exit;
    }

} catch (PDOException $e) {
    $error = 'خطأ في جلب بيانات العقد: ' . $e->getMessage();
}

// Helper functions
function formatCurrency($amount) {
    return number_format($amount, 2) . ' ريال سعودي';
}

function formatDate($date) {
    if (!$date) return 'غير محدد';
    return date('Y/m/d', strtotime($date));
}

function getContractTypeName($type) {
    $types = [
        'investment' => 'استثمار نقدي',
        'property_investment' => 'استثمار بعقار',
        'real_estate' => 'عقاري'
    ];
    return $types[$type] ?? 'غير محدد';
}

function getProfitIntervalName($interval) {
    $intervals = [
        'monthly' => 'شهري',
        'quarterly' => 'ربع سنوي',
        'semi_annual' => 'نصف سنوي',
        'annual' => 'سنوي',
        'end_of_contract' => 'نهاية العقد'
    ];
    return $intervals[$interval] ?? 'غير محدد';
}

function getContractStatusName($status) {
    $statuses = [
        'active' => 'نشط',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
        'pending' => 'قيد المراجعة',
        'suspended' => 'معلق'
    ];
    return $statuses[$status] ?? 'غير محدد';
}

function getArabicDayName($date) {
    $dayNumber = date('N', strtotime($date));
    $arabicDays = [
        1 => 'الاثنين',
        2 => 'الثلاثاء', 
        3 => 'الأربعاء',
        4 => 'الخميس',
        5 => 'الجمعة',
        6 => 'السبت',
        7 => 'الأحد'
    ];
    return $arabicDays[$dayNumber] ?? 'غير محدد';
}

function convertNumberToArabicText($number) {
    // تحويل مبسط للأرقام إلى نص عربي
    $number = (int)$number;
    if ($number >= 100000) {
        return 'مائة ألف';
    } elseif ($number >= 50000) {
        return 'خمسون ألف';
    } elseif ($number >= 10000) {
        return 'عشرة آلاف';
    } else {
        return 'ألف';
    }
}

// Set headers for PDF download if requested
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="contract_' . $contract['contract_number'] . '.pdf"');
    // In a real implementation, you would use a PDF library like TCPDF or mPDF here
    // For now, we'll redirect to the HTML version
    header('Location: contract_pdf.php?id=' . $contract_id);
    exit;
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عقد استثمار رقم: <?= htmlspecialchars($contract['contract_number']) ?> - سما البنيان</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sma-gold: #D4AF37;
            --sma-dark-gold: #B8941F;
            --sma-gray: #2C3E50;
            --sma-light-gray: #F8F9FA;
            --sma-border: #E8E8E8;
        }
        
        * {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: white;
            color: #333;
            line-height: 1.8;
            font-size: 13px;
            direction: rtl;
        }

        @media screen {
            body {
                background: #f8f9fa;
                padding: 2rem 0;
            }
            
            .contract-wrapper {
                background: white;
                margin: 0 auto;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
                border-radius: 10px;
                overflow: hidden;
                max-width: 210mm;
            }
            
            .print-controls {
                background: var(--sma-gray);
                color: white;
                padding: 1.5rem;
                text-align: center;
                border-bottom: 3px solid var(--sma-gold);
            }
            
            .btn-print {
                background: var(--sma-gold);
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                margin: 0 10px;
                transition: all 0.3s ease;
            }
            
            .btn-print:hover {
                background: var(--sma-dark-gold);
                transform: translateY(-2px);
            }
        }
        
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
                font-size: 12px;
            }
            
            .print-controls {
                display: none !important;
            }
            
            .contract-wrapper {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                max-width: none !important;
            }
            
            .page-break {
                page-break-before: always;
            }
        }
        
        .contract-document {
            padding: 40px;
            font-family: 'Cairo', sans-serif;
            line-height: 1.8;
            color: #333;
            position: relative;
        }

        /* Official Header Design Based on Sama Al-Bunyan Template */
        .official-header {
            position: relative;
            margin-bottom: 40px;
            padding: 30px 0;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .logo-section {
            flex: 1;
            text-align: right;
        }

        .company-logo {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--sma-gold), var(--sma-dark-gold));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
            border: 3px solid var(--sma-gold);
        }

        .company-info {
            flex: 2;
            text-align: center;
            padding: 0 30px;
        }

        .company-name-arabic {
            font-size: 28px;
            font-weight: 700;
            color: var(--sma-gray);
            margin-bottom: 8px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .company-name-english {
            font-size: 18px;
            color: var(--sma-gold);
            font-weight: 600;
            margin-bottom: 15px;
            font-family: 'Arial', sans-serif;
        }

        .company-subtitle {
            font-size: 16px;
            color: var(--sma-gold);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .registration-info {
            font-size: 12px;
            color: var(--sma-gray);
            margin-top: 10px;
        }

        .contact-info {
            flex: 1;
            text-align: left;
            font-size: 11px;
            color: var(--sma-gray);
            line-height: 1.6;
        }

        .header-border {
            height: 4px;
            background: linear-gradient(90deg, var(--sma-gold) 0%, var(--sma-dark-gold) 100%);
            border-radius: 2px;
            margin-top: 20px;
        }

        .contract-intro {
            margin: 30px 0;
        }
        
        .bismillah {
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            color: var(--sma-gray);
            margin-bottom: 20px;
            font-family: 'Amiri', 'Cairo', serif;
        }
        
        .contract-title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            color: var(--sma-gray);
            margin: 30px 0;
            padding: 20px;
            border: 3px solid var(--sma-gold);
            border-radius: 15px;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.1) 0%, rgba(212, 175, 55, 0.05) 100%);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.2);
        }
        
        
        .contract-number {
            text-align: center;
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--sma-dark-gold);
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--sma-gold);
        }
        
        .parties-section {
            margin: 40px 0;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .party-box {
            flex: 1;
            min-width: 300px;
            background: #fafbfc;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .party-header {
            background: linear-gradient(135deg, var(--sma-gray), var(--sma-dark-gray));
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .party-header h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 700;
        }
        
        .party-label {
            font-size: 12px;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .party-details {
            padding: 25px;
        }
        
        .party-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .party-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }
        
        .party-table td.label {
            font-weight: 600;
            color: var(--sma-gray);
            width: 35%;
            font-size: 13px;
        }
        
        .party-table td.value {
            color: #333;
            font-weight: 500;
        }
        
        .first-party .party-header {
            background: linear-gradient(135deg, var(--sma-gold), var(--sma-dark-gold));
            color: white;
        }
        
        .second-party .party-header {
            background: linear-gradient(135deg, var(--sma-gray), var(--sma-dark-gray));
            color: white;
        }
        
        .info-table {
            width: 100%;
            margin: 1rem 0;
            border-collapse: collapse;
        }
        
        .info-table th,
        .info-table td {
            padding: 0.75rem;
            border: 1px solid #ddd;
            text-align: right;
        }
        
        .info-table th {
            background: var(--sma-gold);
            color: white;
            font-weight: bold;
        }
        
        .info-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .amount-highlight {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--sma-dark-gold);
            background: rgba(212, 175, 55, 0.1);
            padding: 0.5rem;
            border-radius: 5px;
            text-align: center;
        }
        
        .contract-details-section {
            margin: 40px 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            border: 2px solid var(--sma-gold);
        }
        
        .section-header h2 {
            margin: 0 0 8px 0;
            font-size: 24px;
            color: var(--sma-gray);
            font-weight: 700;
        }
        
        .section-subtitle {
            color: var(--sma-gold);
            font-size: 14px;
            font-weight: 500;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .detail-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
        }
        
        .detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        
        .primary-card {
            border-color: var(--sma-gold);
            background: linear-gradient(135deg, #fff9e6, #ffffff);
        }
        
        .contract-details-header {
            margin: 25px 0;
        }
        
        .contract-agreement {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-right: 4px solid var(--sma-gold);
        }
        
        .contract-agreement p {
            margin: 0;
            font-size: 16px;
            line-height: 1.8;
            color: var(--sma-gray);
        }
        
        .card-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .card-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--sma-gray);
            margin-bottom: 8px;
        }
        
        .card-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--sma-dark-gray);
            margin-bottom: 5px;
        }
        
        .card-sublabel {
            font-size: 11px;
            color: #6c757d;
            font-weight: 400;
        }
        
        .profit-highlight {
            color: #28a745 !important;
        }
        
        .contract-preamble {
            margin: 40px 0;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            border: 2px solid var(--sma-gold);
        }
        
        .preamble-content p {
            text-align: justify;
            line-height: 1.8;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .official-terms-section {
            margin: 40px 0;
        }
        
        .terms-list-official {
            margin: 30px 0;
        }
        
        .term-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            page-break-inside: avoid;
        }
        
        .term-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f3f4;
        }
        
        .term-number {
            background: linear-gradient(135deg, var(--sma-gold), var(--sma-dark-gold));
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .term-header h4 {
            margin: 0;
            color: var(--sma-gray);
            font-size: 16px;
            font-weight: 700;
        }
        
        .term-item p {
            margin: 0;
            text-align: justify;
            line-height: 1.8;
            font-size: 13px;
            color: #333;
        }
        
        .terms-section {
            margin: 40px 0;
        }
        
        .terms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .term-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .term-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        
        .term-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--sma-gold), var(--sma-dark-gold));
        }
        
        .term-number {
            position: absolute;
            top: -10px;
            right: 20px;
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, var(--sma-gold), var(--sma-dark-gold));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(212, 175, 55, 0.3);
        }
        
        .term-content {
            margin-top: 15px;
        }
        
        .term-content h4 {
            margin: 0 0 12px 0;
            color: var(--sma-gray);
            font-size: 16px;
            font-weight: 700;
        }
        
        .term-content p {
            margin: 0;
            line-height: 1.7;
            color: #444;
        }
        
        .terms-list {
            list-style: none;
            padding: 0;
        }
        
        .terms-list li {
            margin: 1rem 0;
            padding: 0.5rem;
            background: #f8f9fa;
            border-right: 3px solid var(--sma-gold);
            border-radius: 5px;
        }
        
        .signatures-section {
            margin: 50px 0;
        }
        
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
        }
        
        .signature-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .first-party-sig {
            border-left: 4px solid var(--sma-gold);
        }
        
        .second-party-sig {
            border-left: 4px solid var(--sma-gray);
        }
        
        .signature-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f3f4;
        }
        
        .signature-header h3 {
            margin: 0 0 5px 0;
            color: var(--sma-gray);
            font-size: 18px;
        }
        
        .signature-area {
            margin: 30px 0;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 2px solid #333;
            margin: 20px auto;
            width: 200px;
            height: 50px;
        }
        
        .signature-label-area {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }
        
        .signatory-info {
            text-align: center;
            margin: 20px 0;
        }
        
        .signatory-info p {
            margin: 5px 0;
            color: #444;
        }
        
        .signature-date {
            font-weight: 600;
            color: var(--sma-gold) !important;
        }
        
        .stamp-area, .witness-area {
            margin-top: 25px;
            text-align: center;
        }
        
        .stamp-placeholder {
            width: 80px;
            height: 80px;
            border: 2px dashed var(--sma-gold);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 10px;
            color: var(--sma-gold);
        }
        
        .stamp-english {
            font-size: 8px;
        }
        
        .witness-line {
            border-bottom: 1px solid #ccc;
            width: 150px;
            height: 30px;
            margin: 0 auto 10px;
        }
        
        .witness-label {
            font-size: 12px;
            color: #666;
        }
        
        .contract-validation {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 40px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            border: 2px solid var(--sma-gold);
        }
        
        .validation-stamp {
            flex-shrink: 0;
        }
        
        .validation-circle {
            width: 100px;
            height: 100px;
            border: 3px solid var(--sma-gold);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: white;
            box-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);
        }
        
        .validation-text {
            font-size: 16px;
            font-weight: 700;
            color: var(--sma-gold);
        }
        
        .validation-english {
            font-size: 10px;
            color: var(--sma-gray);
        }
        
        .validation-date {
            font-size: 8px;
            color: #666;
            margin-top: 5px;
        }
        
        .validation-info {
            flex: 1;
            text-align: center;
        }
        
        .signature-section {
            margin-top: 3rem;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
            padding: 2rem 1rem;
            border: 2px dashed #ccc;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 2rem;
            color: var(--sma-dark-gold);
        }
        
        .contract-footer {
            margin-top: 50px;
            background: linear-gradient(135deg, var(--sma-gray), var(--sma-dark-gray));
            color: white;
            padding: 30px;
            border-radius: 15px;
        }
        
        .footer-content {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .footer-logo-circle {
            width: 50px;
            height: 50px;
            background: var(--sma-gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 16px;
        }
        
        .footer-company-name {
            display: flex;
            flex-direction: column;
        }
        
        .footer-company-name span:first-child {
            font-size: 14px;
            font-weight: 600;
        }
        
        .footer-english {
            font-size: 11px;
            opacity: 0.8;
        }
        
        .footer-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .footer-row {
            display: flex;
            gap: 10px;
            font-size: 12px;
        }
        
        .footer-label {
            font-weight: 600;
            color: var(--sma-gold);
        }
        
        .footer-contact {
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 20px;
            font-size: 12px;
        }
        
        .footer-contact p {
            margin: 5px 0;
        }
        
        @media (max-width: 768px) {
            .contract-document {
                padding: 1rem;
            }
            
            .signature-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php if ($error): ?>
    <div class="container mt-5">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <div class="text-center">
            <a href="contracts_list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
    </div>
    <?php else: ?>
    
    <div class="contract-wrapper">
        <!-- Print Controls (hidden in print) -->
        <div class="print-controls">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> طباعة العقد
            </button>
            <a href="contract_view.php?id=<?= $contract['id'] ?>" class="btn-print" style="text-decoration: none;">
                <i class="fas fa-eye"></i> عرض العقد
            </a>
            <a href="contracts_list.php" class="btn-print" style="text-decoration: none;">
                <i class="fas fa-arrow-right"></i> العودة للقائمة
            </a>
        </div>
        
        <!-- Contract Document -->
        <div class="contract-document">
            <!-- Official Header Based on Sama Al-Bunyan Template -->
            <div class="official-header">
                <div class="header-content">
                    <!-- Logo Section -->
                    <div class="logo-section">
                        <div class="company-logo">
                            SMA
                        </div>
                    </div>
                    
                    <!-- Company Information -->
                    <div class="company-info">
                        <div class="company-name-arabic">شركة سما البنيان للتطوير والاستثمار العقاري</div>
                        <div class="company-name-english">SAMA AL-BUNYAN DEVELOPMENT & REAL ESTATE INVESTMENT</div>
                        <div class="company-subtitle">للتطوير والاستثمار العقاري</div>
                        <div class="registration-info">
                            س.ت: 4030533070 | المملكة العربية السعودية
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="contact-info">
                        <div><strong>الهاتف:</strong> +966 12 234 5678</div>
                        <div><strong>الجوال:</strong> +966 50 123 4567</div>
                        <div><strong>البريد الإلكتروني:</strong></div>
                        <div>info@sma-albnyan.com</div>
                        <div><strong>الموقع الإلكتروني:</strong></div>
                        <div>www.sma-albnyan.com</div>
                    </div>
                </div>
                
                <div class="header-border"></div>
            </div>
            
            <!-- Contract Introduction -->
            <div class="contract-intro">
                <div class="bismillah">بسم الله الرحمن الرحيم</div>
                
                <div class="contract-title">
                    عقد استثمار عقاري - مضاربة شرعية
                    <br>
                    <span style="font-size: 16px; font-weight: 500;">Real Estate Investment Contract - Sharia Compliant Partnership</span>
                </div>
                
                <div class="contract-details-header">
                    <div class="contract-agreement">
                        <p><strong>بعون الله وتوفيقه تم الاتفاق يوم <?= getArabicDayName($contract['contract_date']) ?> الموافق 
                        <?= formatDate($contract['contract_date']) ?><?= !empty($contract['hijri_date']) ? ' - ' . htmlspecialchars($contract['hijri_date']) : '' ?>هـ 
                        وفي <?= htmlspecialchars($contract['location'] ?? 'محافظة جدة') ?> بين كل من:</strong></p>
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0; padding: 15px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 10px; border: 2px solid var(--sma-gold);">
                        <strong style="color: var(--sma-gold); font-size: 18px;">رقم العقد: <?= htmlspecialchars($contract['contract_number']) ?></strong>
                        <br>
                        <span style="font-size: 14px; color: var(--sma-gray);">تاريخ العقد: <?= formatDate($contract['contract_date']) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Introduction -->
            <div class="section-title">تمهيد</div>
            <p>
                بعون الله وتوفيقه تم الاتفاق في تاريخ <strong><?= formatDate($contract['contract_date']) ?></strong> 
                الموافق <strong><?= formatDate($contract['contract_date']) ?></strong> 
                في مدينة جدة بالمملكة العربية السعودية بين كل من:
            </p>
            
            <!-- Contract Parties -->
            <div class="parties-section">
                <div class="party-box first-party">
                    <div class="party-header">
                        <h3>الطرف الأول (الشركة)</h3>
                        <span class="party-label">First Party (Company)</span>
                    </div>
                    <div class="party-details">
                        <table class="party-table">
                            <tr>
                                <td class="label">اسم الشركة:</td>
                                <td class="value"><?= htmlspecialchars($contract['first_party_name'] ?? 'شركة سما البنيان التجارية') ?></td>
                            </tr>
                            <tr>
                                <td class="label">Company Name:</td>
                                <td class="value">Sama Al-Bunyan Commercial Real Estate Development & Investment Company</td>
                            </tr>
                            <tr>
                                <td class="label">السجل التجاري:</td>
                                <td class="value"><?= htmlspecialchars($contract['first_party_commercial_register'] ?? '4030533070') ?></td>
                            </tr>
                            <tr>
                                <td class="label">المحافظة:</td>
                                <td class="value"><?= htmlspecialchars($contract['first_party_city'] ?? 'جدة') ?></td>
                            </tr>
                            <tr>
                                <td class="label">الحي:</td>
                                <td class="value"><?= htmlspecialchars($contract['first_party_district'] ?? 'الحمدانية') ?></td>
                            </tr>
                            <tr>
                                <td class="label">العنوان الكامل:</td>
                                <td class="value"><?= htmlspecialchars($contract['first_party_address'] ?? 'جدة - حي الحمدانية – شارع ياسر بن عامر') ?></td>
                            </tr>
                            <tr>
                                <td class="label">ممثل الشركة:</td>
                                <td class="value"><?= htmlspecialchars($contract['first_party_representative'] ?? 'أحمد عبد الله سعيد الزهراني') ?></td>
                            </tr>
                            <tr>
                                <td class="label">الجنسية:</td>
                                <td class="value">سعودي الجنسية</td>
                            </tr>
                            <tr>
                                <td class="label">رقم الجوال:</td>
                                <td class="value"><?= htmlspecialchars($contract['first_party_phone'] ?? '0537295224') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="party-box second-party">
                    <div class="party-header">
                        <h3>الطرف الثاني (العميل)</h3>
                        <span class="party-label">Second Party (Client)</span>
                    </div>
                    <div class="party-details">
                        <table class="party-table">
                            <tr>
                                <td class="label">الاسم الكامل:</td>
                                <td class="value"><?= htmlspecialchars($contract['second_party_name'] ?? $contract['client_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="label">الجنسية:</td>
                                <td class="value"><?= htmlspecialchars($contract['second_party_nationality'] ?? 'سعودي الجنسية') ?></td>
                            </tr>
                            <tr>
                                <td class="label">رقم الهوية:</td>
                                <td class="value"><?= htmlspecialchars($contract['second_party_id'] ?? $contract['client_id']) ?></td>
                            </tr>
                            <tr>
                                <td class="label">رقم الجوال:</td>
                                <td class="value"><?= htmlspecialchars($contract['second_party_phone'] ?? $contract['client_phone']) ?></td>
                            </tr>
                            <?php if (!empty($contract['second_party_email'] ?? $contract['client_email'])): ?>
                            <tr>
                                <td class="label">البريد الإلكتروني:</td>
                                <td class="value"><?= htmlspecialchars($contract['second_party_email'] ?? $contract['client_email']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="label">المدينة:</td>
                                <td class="value"><?= htmlspecialchars($contract['second_party_city'] ?? 'جدة') ?></td>
                            </tr>
                            <?php if (!empty($contract['second_party_district'])): ?>
                            <tr>
                                <td class="label">الحي:</td>
                                <td class="value"><?= htmlspecialchars($contract['second_party_district']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($contract['second_party_street'])): ?>
                            <tr>
                                <td class="label">الشارع:</td>
                                <td class="value"><?= htmlspecialchars($contract['second_party_street']) ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Contract Preamble -->
            <div class="contract-preamble">
                <div class="section-header">
                    <h2>تمهيــــــــد</h2>
                    <span class="section-subtitle">Preamble</span>
                </div>
                
                <div class="preamble-content">
                    <p>ولما كان الطرف الأول شركة عقارية مؤهلة بترخيص من الهيئة العامة للعقار للبيع والتأجير على الخارطة وترخيص التأهيل للمطور العقاري وتعمل في مجال التطوير العقاري للفلل السكنية وشقق التمليك والمجمعات التجارية وتشغيل وإدارة محطات الوقود والمشاريع التجارية والسكنية ولها خبرة وممارسة في هذا المجال بالإنشاء والبيع والتأجير وادارة المحافظ العقارية.</p>
                    <?php if (!empty($contract['project_description'])): ?>
                    <p><strong>وصف المشروع:</strong> <?= nl2br(htmlspecialchars($contract['project_description'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Contract Details -->
            <div class="contract-details-section">
                <div class="section-header">
                    <h2>تفاصيل العقد والاستثمار</h2>
                    <span class="section-subtitle">Contract & Investment Details</span>
                </div>
                
                <div class="details-grid">
                    <div class="detail-card primary-card">
                        <div class="card-icon">💰</div>
                        <div class="card-content">
                            <div class="card-label">مبلغ الاستثمار</div>
                            <div class="card-value amount-highlight"><?= formatCurrency($contract['amount']) ?></div>
                            <div class="card-sublabel">Investment Amount</div>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="card-icon">📊</div>
                        <div class="card-content">
                            <div class="card-label">نسبة الربح</div>
                            <div class="card-value"><?= number_format($contract['profit_percentage'], 1) ?>%</div>
                            <div class="card-sublabel">Profit Percentage</div>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="card-icon">📈</div>
                        <div class="card-content">
                            <div class="card-label">صافي الربح المتوقع</div>
                            <div class="card-value profit-highlight"><?= formatCurrency($contract['amount'] * $contract['profit_percentage'] / 100) ?></div>
                            <div class="card-sublabel">Expected Net Profit</div>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="card-icon">⚖️</div>
                        <div class="card-content">
                            <div class="card-label">نوع العقد</div>
                            <div class="card-value"><?= getContractTypeName($contract['contract_type']) ?></div>
                            <div class="card-sublabel">Contract Type</div>
                        </div>
                    </div>
                </div>
                
                <table class="info-table">
                <tr>
                    <th style="background: var(--sma-gold); color: white;">البيان</th>
                    <th style="background: var(--sma-gold); color: white;">التفاصيل</th>
                </tr>
                <tr>
                    <td><strong>مدة العقد</strong></td>
                    <td><?= $contract['contract_duration'] ?> شهر</td>
                </tr>
                <tr>
                    <td><strong>فترة دفع الأرباح</strong></td>
                    <td><?= getProfitIntervalName($contract['profit_interval']) ?></td>
                </tr>
                <tr>
                    <td><strong>تاريخ انتهاء العقد</strong></td>
                    <td><?= formatDate(date('Y-m-d', strtotime($contract['contract_date'] . ' + ' . $contract['contract_duration'] . ' months'))) ?></td>
                </tr>
                <tr>
                    <td><strong>إجمالي العائد المتوقع</strong></td>
                    <td class="profit-highlight"><?= formatCurrency($contract['amount'] + ($contract['amount'] * $contract['profit_percentage'] / 100)) ?></td>
                </tr>
                <tr>
                    <td><strong>حالة العقد</strong></td>
                    <td>
                        <span style="color: <?= $contract['status'] === 'active' ? '#28a745' : '#6c757d' ?>; font-weight: 600;">
                            <?= getContractStatusName($contract['status']) ?>
                        </span>
                    </td>
                </tr>
            </table>
            
            <!-- Property Details (if applicable) -->
            <?php if ($contract['contract_type'] === 'property_investment' && 
                     (!empty($contract['property_description']) || $contract['property_value'] > 0)): ?>
            <div class="section-title">تفاصيل العقار</div>
            <div class="contract-parties">
                <?php if (!empty($contract['property_description'])): ?>
                <p><strong>وصف العقار:</strong><br><?= nl2br(htmlspecialchars($contract['property_description'])) ?></p>
                <?php endif; ?>
                <?php if ($contract['property_value'] > 0): ?>
                <p><strong>قيمة العقار:</strong> <?= formatCurrency($contract['property_value']) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Contract Terms -->
            <div class="terms-section">
                <div class="section-header">
                    <h2>شروط وأحكام العقد</h2>
                    <span class="section-subtitle">Terms & Conditions</span>
                </div>
                
                <div class="terms-grid">
                    <div class="term-card">
                        <div class="term-number">1</div>
                        <div class="term-content">
                            <h4>أساس الاستثمار الشرعي</h4>
                            <p>يلتزم الطرف الأول بإدارة واستثمار المبلغ المتفق عليه وفقاً لأحكام الشريعة الإسلامية ومبادئ الاستثمار الحلال.</p>
                        </div>
                    </div>
                    
                    <div class="term-card">
                        <div class="term-number">2</div>
                        <div class="term-content">
                            <h4>توزيع الأرباح</h4>
                            <p>يتم توزيع الأرباح بنسبة <strong style="color: var(--sma-gold);"><?= number_format($contract['profit_percentage'], 1) ?>%</strong> للطرف الثاني من صافي الأرباح المحققة.</p>
                        </div>
                    </div>
                    
                    <div class="term-card">
                        <div class="term-number">3</div>
                        <div class="term-content">
                            <h4>مدة العقد</h4>
                            <p>مدة العقد <strong><?= $contract['contract_duration'] ?> شهر</strong> تبدأ من تاريخ <strong><?= formatDate($contract['contract_date']) ?></strong> وتنتهي في <strong><?= formatDate(date('Y-m-d', strtotime($contract['contract_date'] . ' + ' . $contract['contract_duration'] . ' months'))) ?></strong>.</p>
                        </div>
                    </div>
                    
                    <div class="term-card">
                        <div class="term-number">4</div>
                        <div class="term-content">
                            <h4>آلية دفع الأرباح</h4>
                            <p>يتم دفع الأرباح بشكل <strong><?= getProfitIntervalName($contract['profit_interval']) ?></strong> حسب الاتفاق وظروف السوق.</p>
                        </div>
                    </div>
                    
                    <div class="term-card">
                        <div class="term-number">5</div>
                        <div class="term-content">
                            <h4>ضمان رأس المال</h4>
                            <p>في حالة الخسارة، يتحمل الطرف الأول الخسارة كاملة ولا يتحمل الطرف الثاني أي خسارة في رأس المال المستثمر.</p>
                        </div>
                    </div>
                    
                    <div class="term-card">
                        <div class="term-number">6</div>
                        <div class="term-content">
                            <h4>استرداد رأس المال</h4>
                            <p>يحق للطرف الثاني استرداد رأس ماله كاملاً في نهاية مدة العقد أو حسب الاتفاق المسبق مع إدارة الشركة.</p>
                        </div>
                    </div>
                </div>
                <li><strong>البند السابع:</strong> تطبق أحكام الشريعة الإسلامية والأنظمة المعمول بها في المملكة العربية السعودية على هذا العقد.</li>
            </ul>
            
            <!-- Notes -->
            <?php if (!empty($contract['notes'])): ?>
            <div class="section-title">ملاحظات إضافية</div>
            <div class="contract-parties">
                <?= nl2br(htmlspecialchars($contract['notes'])) ?>
            </div>
            <?php endif; ?>
            
            <!-- Signatures Section -->
            <div class="signatures-section">
                <div class="section-header">
                    <h2>التوقيعات والاعتماد</h2>
                    <span class="section-subtitle">Signatures & Approval</span>
                </div>
                
                <div class="signatures-grid">
                    <div class="signature-card first-party-sig">
                        <div class="signature-header">
                            <h3>الطرف الأول - الشركة</h3>
                            <span class="signature-label">First Party - Company</span>
                        </div>
                        <div class="signature-area">
                            <div class="signature-line"></div>
                            <div class="signature-label-area">التوقيع / Signature</div>
                        </div>
                        <div class="signatory-info">
                            <p><strong>السيد/ أحمد عبدالله سعيد الزهراني</strong></p>
                            <p>مفوض بالتوقيع عن شركة سما البنيان</p>
                            <p class="signature-date">التاريخ: <?= formatDate(date('Y-m-d')) ?></p>
                        </div>
                        <div class="stamp-area">
                            <div class="stamp-placeholder">
                                <span>ختم الشركة</span>
                                <span class="stamp-english">Company Seal</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="signature-card second-party-sig">
                        <div class="signature-header">
                            <h3>الطرف الثاني - العميل</h3>
                            <span class="signature-label">Second Party - Client</span>
                        </div>
                        <div class="signature-area">
                            <div class="signature-line"></div>
                            <div class="signature-label-area">التوقيع / Signature</div>
                        </div>
                        <div class="signatory-info">
                            <p><strong><?= htmlspecialchars($contract['client_name']) ?></strong></p>
                            <p>رقم الهوية: <?= htmlspecialchars($contract['client_id']) ?></p>
                            <p class="signature-date">التاريخ: <?= formatDate(date('Y-m-d')) ?></p>
                        </div>
                        <div class="witness-area">
                            <div class="witness-line"></div>
                            <div class="witness-label">الشاهد / Witness</div>
                        </div>
                    </div>
                </div>
                
                <div class="contract-validation">
                    <div class="validation-stamp">
                        <div class="validation-circle">
                            <span class="validation-text">معتمد</span>
                            <span class="validation-english">APPROVED</span>
                            <span class="validation-date"><?= date('Y-m-d') ?></span>
                        </div>
                    </div>
                    <div class="validation-info">
                        <p><strong>هذا العقد معتمد ومصدق وفقاً للأنظمة واللوائح المعمول بها</strong></p>
                        <p style="font-size: 12px; color: #666;">This contract is approved and certified according to applicable laws and regulations</p>
                    </div>
                </div>
            </div>
            
            <!-- Contract Footer -->
            <div class="contract-footer">
                <div class="footer-content">
                    <div class="footer-logo">
                        <div class="footer-logo-circle">سما</div>
                        <div class="footer-company-name">
                            <span>شركة سما البنيان للتطوير والاستثمار العقاري</span>
                            <span class="footer-english">Sama Al-Bunyan Real Estate Development & Investment Company</span>
                        </div>
                    </div>
                    <div class="footer-details">
                        <div class="footer-row">
                            <span class="footer-label">تاريخ الإنشاء:</span>
                            <span><?= formatDate($contract['created_at']) ?></span>
                        </div>
                        <div class="footer-row">
                            <span class="footer-label">رقم العقد:</span>
                            <span><?= htmlspecialchars($contract['contract_number']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="footer-contact">
                    <p>📞 هاتف: +966 12 234 5678 | 📧 البريد الإلكتروني: contracts@sma-albnyan.com</p>
                    <p>📍 العنوان: جدة - المملكة العربية السعودية</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Official 17 Contract Terms from the provided document -->
            <div class="official-terms-section">
                <div class="section-header">
                    <h2>بنود العقد الرسمية</h2>
                    <span class="section-subtitle">Official Contract Terms</span>
                </div>
                
                <div class="terms-list-official">
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند الأول</span>
                            <h4>التمهيد</h4>
                        </div>
                        <p>يعتبر التمهيد والمقدمة أعلاه جزء لا يتجزأ من هذا العقد، وأن البيانات والعناوين الموضحة في المقدمة منتجة لآثارها النظامية.</p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند الثاني</span>
                            <h4>حدود العقد</h4>
                        </div>
                        <p>يُعد محل هذا العقد مشروعًا تجاريًا قابلًا للربح والخسارة وبموجب هذا العقد اتفق الطرفان على التزام الطرف الأول بصفته شركة متخصصة في مجال التطوير العقاري في فتح باب المشاركة في العقارات بطريق المضاربة وقد رغب الطرف الثاني الدخول والاستثمار والمضاربة على أن تكون المضاربة في العقارات التي يملكها الطرف الأول او التي يملك فيها حصصاً أياً كانت نسبة الطرف الأول في العقار.</p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند الثالث</span>
                            <h4><?= ($contract['investment_type'] ?? 'cash') === 'cash' ? 'مبلغ المضاربة' : 'العقار محل المضاربة' ?></h4>
                        </div>
                        <?php if (($contract['investment_type'] ?? 'cash') === 'cash'): ?>
                            <p>اتفق الطرفان على ان مبلغ المضاربة المقدم من الطرف الثاني بمبلغ وقدره <strong style="color: var(--sma-gold);">(<?= formatCurrency($contract['investment_amount'] ?? $contract['amount']) ?>)</strong> <?= convertNumberToArabicText($contract['investment_amount'] ?? $contract['amount']) ?> ريال، يلتزم الطرف الثاني ويقر الطرف الأول باستلامه.</p>
                        <?php else: ?>
                            <p>اتفق الطرفان على ان محل المضاربة هو العقار المقدم من الطرف الثاني والذي تبلغ قيمته السوقية <strong style="color: var(--sma-gold);">(<?= formatCurrency($contract['property_market_value'] ?? 0) ?>)</strong> <?= convertNumberToArabicText($contract['property_market_value'] ?? 0) ?> ريال، وتفاصيله كالتالي:</p>
                            
                            <div class="property-details" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0; border-right: 4px solid var(--sma-gold);">
                                <div class="row">
                                    <?php if (!empty($contract['property_number'])): ?>
                                    <div class="col-md-6">
                                        <strong>رقم العقار:</strong> <?= htmlspecialchars($contract['property_number']) ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($contract['property_location_city'])): ?>
                                    <div class="col-md-6">
                                        <strong>المدينة:</strong> <?= htmlspecialchars($contract['property_location_city']) ?>
                                        <?php if (!empty($contract['property_location_district'])): ?>
                                            - <?= htmlspecialchars($contract['property_location_district']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($contract['property_plan_number'])): ?>
                                    <div class="col-md-6">
                                        <strong>رقم المخطط:</strong> <?= htmlspecialchars($contract['property_plan_number']) ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($contract['property_area'])): ?>
                                    <div class="col-md-6">
                                        <strong>المساحة:</strong> <?= number_format($contract['property_area'], 2) ?> متر مربع
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($contract['property_deed_number'])): ?>
                                    <div class="col-md-12" style="margin-top: 10px;">
                                        <strong>رقم الصك:</strong> <?= htmlspecialchars($contract['property_deed_number']) ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($contract['property_description_detailed'])): ?>
                                    <div class="col-md-12" style="margin-top: 10px;">
                                        <strong>وصف العقار:</strong> <?= htmlspecialchars($contract['property_description_detailed']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <p>يلتزم الطرف الثاني ويقر الطرف الأول باستلامه للعقار المذكور أعلاه.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند الرابع</span>
                            <h4>استرداد رأس المال</h4>
                        </div>
                        <p>اتفق الطرفان على استرداد مبلغ رأس المال يكون بعد <strong><?= $contract['minimum_investment_period_months'] ?? 6 ?> أشهر</strong> من بداية العقد، وقبل انسحابه من المشروع بشرط الإخطار كتابةً بـ <strong>(<?= $contract['withdrawal_notice_days'] ?? 60 ?>) يومًا</strong> على الأقل، وفي حال طلب الانسحاب يعد الإشعار معتمدًا إذا تم تسليمه كتابةً أو على رقم الجوال المحددين في العقد.</p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند الخامس</span>
                            <h4>الأرباح</h4>
                        </div>
                        <p>اتفق الطرفان على أن نسبة متوسط الأرباح للطرف الثاني هي <strong style="color: var(--sma-gold);"><?= number_format($contract['profit_percentage'], 1) ?>%</strong> من قيمة رأس المال<?php if (($contract['investment_type'] ?? 'cash') === 'property'): ?> (قيمة العقار)<?php endif; ?>، ويلتزم الطرف الأول بسداد نصيب الطرف الثاني من الأرباح <?php 
                        if (($contract['investment_type'] ?? 'cash') === 'property' && ($contract['profit_distribution_frequency'] ?? '') === 'bimonthly'): 
                            echo 'كل شهرين';
                        else:
                            echo 'خلال <strong>' . ($contract['profit_payment_deadline_days'] ?? 15) . ' يوماً</strong> من انتهاء المدة المتفق عليها كحد أقصى';
                        endif;
                        ?>.</p>
                        
                        <?php if (($contract['investment_type'] ?? 'cash') === 'property'): ?>
                        <div class="alert alert-info" style="background: #e8f4fd; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px; margin: 10px 0;">
                            <strong>📋 توزيع الأرباح للعقود العقارية:</strong><br>
                            في العقود العقارية، يتم توزيع الأرباح كل شهرين بدلاً من نهاية العقد، مما يوفر دخلاً دورياً للطرف الثاني.
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند السادس</span>
                            <h4>الخسائر</h4>
                        </div>
                        <p><?php
                        if (($contract['loss_responsibility'] ?? 'first_party') === 'first_party') {
                            echo 'يقر الطرف الثاني بأنه في حال خسارة المشروع لأي سبب كان فإن الطرف الأول يتحمل كامل الخسارة ولا يتحمل الطرف الثاني أي خسارة في رأس المال المستثمر.';
                        } else {
                            echo 'يقر الطرف الثاني بأنه في حال خسارة المشروع لأي سبب كان فإنه يتحمل نسبة من الخسارة مساوية لنسبته في المشروع مالم تكن الخسارة ناتجة عن تقصير او اهمال من الطرف الأول.';
                        }
                        ?></p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند السابع</span>
                            <h4>إدارة المشروع</h4>
                        </div>
                        <p>ادرك الطرف الثاني مدى قدرة الطرف الأول الإدارية وفريق عمله الذي يعمل لديه وتحت إدارته، ووافق الطرف الثاني على إدارة الطرف الأول، وأدرك ان تكون إدارة المشروع بالكامل من صلاحيات ومسؤوليات الطرف الأول، وذلك على سبيل المثال لا الحصر قيام الطرف الأول بتعيين وعزل العمال والمهندسين وبيع الوحدات السكنية وتحديد موقع المشروع وغيرها من صلاحيات الطرف الأول، وليس للطرف الثاني الحق او التدخل او الاعتراض على شيء من ذلك، وللطرف الأول توقيع العقود اللازمة لتسيير المشروع وإنجازه وإنجاحه بالطريقة التي يراها مناسبة، وكذلك له كامل الحق في التصرف برأس المال، وذلك في حدود المصروفات اللازمة لإنجاح المشروع وما يتعلق به.</p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند الثامن</span>
                            <h4>الوفاة</h4>
                        </div>
                        <p><?= htmlspecialchars($contract['inheritance_clause'] ?? 'اتفق الطرفان إنه بموجب هذا العقد وفي حالة وفاة الطرف الثاني -لا سمح الله- يتم انتقال النسبة الخاصة به إلى ورثته، وتسري بنود هذا العقد على الورثة دون أي استثناء، ولا يحق لورثة الطرف الثاني الاعتراض على أي بند من بنود هذا العقد.') ?></p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند التاسع</span>
                            <h4>مدة العقد</h4>
                        </div>
                        <p>مدة هذه الشراكة <strong>(<?= $contract['contract_duration_months'] ?? 6 ?>) أشهر</strong> تبدأ من تاريخ <?= formatDate($contract['contract_date']) ?> وتنتهي <?= formatDate(date('Y-m-d', strtotime($contract['contract_date'] . ' + ' . ($contract['contract_duration_months'] ?? 6) . ' months'))) ?>، <?= ($contract['is_renewable'] ?? 1) ? 'واتفق الطرفان ان هذه المدة قابلة للتجديد لمدة أو مدد أخرى مماثلة، ويقر الطرف الثاني بعمله بأنه في حال عدم رغبة الطرف الأول بتجديد العقد فإنه يحق له إخطار الطرف الثاني في أي وقت بعدم رغبته بتجديد العقد، ويعد الاخطار رسمياً ومنهياً للعقد بانتهاء مدة العقد الأصلية او المجددة.' : 'والعقد غير قابل للتجديد وينتهي بانتهاء المدة المحددة.' ?></p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند العاشر</span>
                            <h4>العمولة</h4>
                        </div>
                        <p>يحق للطرف الثاني الحصول على نسبة عمولة قدرها <strong><?= number_format($contract['commission_percentage'] ?? 2.5, 1) ?>%</strong> عند التسويق للمحفظة ويكون ذلك بطلب من الطرف الثاني يبين فيه أسماء الأشخاص المشاركين عن طريقه.
                        <?php if (!empty($contract['commission_conditions'])): ?>
                        <br><strong>شروط العمولة:</strong> <?= nl2br(htmlspecialchars($contract['commission_conditions'])) ?>
                        <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند الحادي عشر</span>
                            <h4>بطلان أحد البنود</h4>
                        </div>
                        <p>في حال بطلان أي بند من بنود العقد، فإن ذلك لا يؤثر على صحة باقي البنود وتظل ملزمة للطرفين.</p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند الثاني عشر</span>
                            <h4>القوة القاهرة</h4>
                        </div>
                        <p><?= htmlspecialchars($contract['force_majeure_clause'] ?? 'في حال حدوث كوارث طبيعية او جوائح كونية او أي قوة قاهرة فإنه يتم مراعاة ذلك من الطرفين، وفي حال نتج عن هذه القوة القاهرة توقف عن العمل في أي من المشاريع فإنه يتم احتساب ذلك ضمن مدة العقد، ولا يترتب على ذلك أي من مستحقات، ولا يحق لأي من الطرفين المطالبة بأي تعويضات مترتبة على ذلك.') ?></p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند الثالث عشر</span>
                            <h4>القانون والاختصاص</h4>
                        </div>
                        <p>في حال نشوء أي نزاع بين الطرفين (لا قدر الله) حيال هذا العقد يحل بالطرق الودية وفي حال تعذر ذلك خلال أسبوعين، فينعقد الاختصاص للتحكيم وفقاً لأحكام نظام التحكيم في المملكة العربية السعودية ويكون التحكيم في مدينة جدة أو عبر الاتصال المرئي (عن بعد)، ويلتزم الطرف الثاني بسداد اتعاب التحكيم كاملة ابتداءً، وفي حال انتهاء الدعوى التحكيمية بحكم لصالح الطرف الثاني فإن الطرف الأول ملتزم بسداد اتعاب التحكيم التي قام الطرف الثاني بسدادها.</p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند الرابع عشر</span>
                            <h4>التعهد</h4>
                        </div>
                        <p>يتعهد الطرفان بأنهما وقعا هذا العقد بكامل الأهلية الشرعية والنظامية وبعد الاطلاع والموافقة على جميع بنوده، ويعتبر التوقيع على هذا العقد من الطرفين بأنه مقروءً ومفهوماً ومعلوماً علماً نافياً للجهالة والغبن والغرر وقد صادق الطرفان على جميع بنوده وأحكامه، ولا يحق لأي طرف التعديل على العقد او أي بند من بنوده الا بمصادقة الطرف الثاني وتوقيعه على هذا التعديل.</p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند الخامس عشر</span>
                            <h4>الشرط الجزائي</h4>
                        </div>
                        <p>في حال تأخير الطرف الأول في تسليم الأرباح في المواعيد المحددة في هذا العقد، يلتزم بدفع شرط جزائي قدره <strong style="color: var(--sma-gold);"><?= formatCurrency($contract['penalty_amount'] ?? 3000) ?></strong> عن كل <strong>(<?= $contract['penalty_period_days'] ?? 30 ?>) يوم تأخير</strong>.</p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند السادس عشر</span>
                            <h4>تصديق العقد</h4>
                        </div>
                        <p>تم إعداد هذا العقد والاطلاع عليه ومراجعته وتنقيحه من قبل <?= htmlspecialchars($contract['legal_counsel_info'] ?? 'مكتب المحامي بشير بن عبد الله صديق كنسارة') ?>.</p>
                    </div>
                    
                    <div class="term-item">
                        <div class="term-header">
                            <span class="term-number">البند السابع عشر</span>
                            <h4>نسخ العقد</h4>
                        </div>
                        <p>حرر هذا العقد من نسختين أصليتين مكونه من <strong>(4) صفحات و (17) مادة</strong> وقع عليهما الطرفان بالرضا والإيجاب والقبول لما جاء فيهما من مواد، وتسلم كل طرف نسخة للعمل بموجبها.</p>
                        <p style="text-align: center; margin-top: 20px; font-weight: 600; color: var(--sma-gold);">وعلى ما سبق جرى التعاقد والله خير الشاهدين</p>
                    </div>
                </div>
            </div>    <?php endif; ?>
    
    <script>
        // Auto print if print parameter is set
        if (window.location.search.includes('print=1')) {
            window.onload = function() {
                window.print();
            };
        }
    </script>
</body>
</html>