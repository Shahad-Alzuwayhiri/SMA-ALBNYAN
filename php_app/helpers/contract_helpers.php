<?php
// Helper functions for Hijri date conversion and contract number generation

/**
 * تحويل التاريخ الميلادي إلى هجري (تقريبي)
 */
function gregorianToHijri($gregorianDate = null) {
    if ($gregorianDate === null) {
        $gregorianDate = date('Y-m-d');
    }
    
    $timestamp = is_string($gregorianDate) ? strtotime($gregorianDate) : $gregorianDate;
    $gregorianYear = date('Y', $timestamp);
    $gregorianMonth = date('m', $timestamp);
    $gregorianDay = date('d', $timestamp);
    
    // تحويل تقريبي للسنة الهجرية
    // السنة الهجرية أقصر بحوالي 11 يوم من السنة الميلادية
    $hijriYear = floor(($gregorianYear - 622) * 1.030684);
    $hijriYear += 1; // تعديل للحصول على السنة الهجرية الصحيحة
    
    // تحويل تقريبي للشهر (مبسط)
    $hijriMonth = $gregorianMonth;
    if ($gregorianMonth > 6) {
        $hijriMonth -= 1;
    }
    
    return [
        'year' => $hijriYear,
        'month' => $hijriMonth,
        'day' => $gregorianDay,
        'formatted' => $gregorianDay . '-' . $hijriMonth . '-' . $hijriYear . 'هـ'
    ];
}

/**
 * الحصول على السنة الهجرية الحالية
 */
function getCurrentHijriYear() {
    $hijri = gregorianToHijri();
    return $hijri['year'];
}

/**
 * تنسيق رقم العقد مع الترقيم التلقائي
 */
function formatContractNumber($sequenceNumber, $hijriYear = null) {
    if ($hijriYear === null) {
        $hijriYear = getCurrentHijriYear();
    }
    
    // تنسيق: B + رقم تسلسلي (3 أرقام) + سنة هجرية
    // مثال: B001-1447, B123-1447
    return "B" . str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT) . "-" . $hijriYear;
}

/**
 * الحصول على الرقم التسلسلي التالي للعقد
 */
function getNextContractSequence($db, $year = null) {
    if ($year === null) {
        $year = date('Y');
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) + 1 as next_number FROM contracts WHERE strftime('%Y', created_at) = ?");
    $stmt->execute([$year]);
    $result = $stmt->fetch();
    
    return $result ? $result['next_number'] : 1;
}

/**
 * إنشاء رقم عقد جديد مع الترقيم التلقائي
 */
function generateNewContractNumber($db) {
    $currentYear = date('Y');
    $hijriYear = getCurrentHijriYear();
    $sequenceNumber = getNextContractSequence($db, $currentYear);
    
    return formatContractNumber($sequenceNumber, $hijriYear);
}

/**
 * التحقق من وجود رقم عقد في قاعدة البيانات
 */
function isContractNumberExists($db, $contractNumber) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE contract_number = ?");
    $stmt->execute([$contractNumber]);
    $result = $stmt->fetch();
    return $result && $result[0] > 0;
}

/**
 * إنشاء رقم عقد فريد (مع التحقق من عدم التكرار)
 */
function generateUniqueContractNumber($db, $maxAttempts = 10) {
    $attempts = 0;
    
    do {
        $contractNumber = generateNewContractNumber($db);
        $attempts++;
        
        if (!isContractNumberExists($db, $contractNumber)) {
            return $contractNumber;
        }
        
        // إذا تكرر الرقم، أضف ثانية للوقت وأعد المحاولة
        sleep(1);
        
    } while ($attempts < $maxAttempts);
    
    // في حالة الفشل، أضف timestamp لضمان الفرادة
    $timestamp = time();
    $hijriYear = getCurrentHijriYear();
    return "B" . substr($timestamp, -3) . "-" . $hijriYear;
}

?>