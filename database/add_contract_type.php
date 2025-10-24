<?php
/**
 * إضافة عمود نوع العقد - نظام سما البنيان
 * تحديث جدول العقود لدعم أنواع العقود المختلفة
 */

try {
    // إنشاء اتصال قاعدة البيانات
    $pdo = new PDO(
        "sqlite:" . __DIR__ . "/contracts.db",
        null,
        null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "🔧 إضافة عمود نوع العقد...\n";
    
    // التحقق من وجود العمود
    $columns = $pdo->query("PRAGMA table_info(contracts)")->fetchAll();
    $hasContractType = false;
    
    foreach ($columns as $column) {
        if ($column['name'] === 'contract_type') {
            $hasContractType = true;
            break;
        }
    }
    
    if ($hasContractType) {
        echo "✅ عمود contract_type موجود مسبقاً\n";
    } else {
        // إضافة عمود نوع العقد
        $pdo->exec("ALTER TABLE contracts ADD COLUMN contract_type VARCHAR(50) DEFAULT 'general'");
        echo "✅ تم إضافة عمود contract_type بنجاح\n";
    }
    
    // التحقق من وجود عمود مدة العقد
    $hasContractDuration = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'contract_duration') {
            $hasContractDuration = true;
            break;
        }
    }
    
    if (!$hasContractDuration) {
        $pdo->exec("ALTER TABLE contracts ADD COLUMN contract_duration INTEGER DEFAULT 6");
        echo "✅ تم إضافة عمود contract_duration بنجاح\n";
    } else {
        echo "✅ عمود contract_duration موجود مسبقاً\n";
    }
    
    echo "🎉 تم تحديث جدول العقود بنجاح!\n";
    
} catch (PDOException $e) {
    echo "❌ خطأ في تحديث قاعدة البيانات: " . $e->getMessage() . "\n";
    exit(1);
}
?>