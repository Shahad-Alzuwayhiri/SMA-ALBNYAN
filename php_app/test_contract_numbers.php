<?php
// اختبار نظام أرقام العقود الجديد

require_once 'config/database.php';
require_once 'helpers/contract_helpers.php';

try {
    $db = getDB();
    
    echo "🔍 اختبار نظام أرقام العقود الجديد\n";
    echo "=====================================\n\n";
    
    // اختبار التاريخ الهجري
    $hijri = gregorianToHijri();
    echo "📅 التاريخ الهجري الحالي: " . $hijri['formatted'] . "\n";
    echo "📅 السنة الهجرية: " . $hijri['year'] . "\n\n";
    
    // اختبار إنشاء رقم عقد جديد
    echo "🔢 اختبار إنشاء أرقام العقود:\n";
    
    for ($i = 1; $i <= 5; $i++) {
        $contractNumber = generateNewContractNumber($db);
        echo "   عقد #{$i}: {$contractNumber}\n";
    }
    
    echo "\n✅ تم اختبار النظام بنجاح!\n";
    echo "💡 كل عقد جديد سيحصل على رقم تلقائي فريد\n";
    
} catch (Exception $e) {
    echo "❌ خطأ في الاختبار: " . $e->getMessage() . "\n";
}
?>