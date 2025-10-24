<?php
/**
 * إضافة أعمدة عقود الاستثمار بالعقار
 * تحديث جدول العقود لدعم بيانات العقارات
 */

try {
    // إنشاء اتصال قاعدة البيانات
    $pdo = new PDO(
        "sqlite:" . __DIR__ . "/contracts.db",
        null,
        null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "🔧 إضافة أعمدة عقود الاستثمار بالعقار...\n";
    
    // التحقق من الأعمدة الموجودة
    $columns = $pdo->query("PRAGMA table_info(contracts)")->fetchAll();
    $existingColumns = array_column($columns, 'name');
    
    // الأعمدة المطلوبة
    $requiredColumns = [
        'property_number' => 'VARCHAR(50)',
        'property_location' => 'TEXT',
        'profit_frequency' => 'INTEGER DEFAULT 2'
    ];
    
    foreach ($requiredColumns as $columnName => $columnType) {
        if (!in_array($columnName, $existingColumns)) {
            $pdo->exec("ALTER TABLE contracts ADD COLUMN $columnName $columnType");
            echo "✅ تم إضافة عمود $columnName بنجاح\n";
        } else {
            echo "✅ عمود $columnName موجود مسبقاً\n";
        }
    }
    
    echo "🎉 تم تحديث جدول العقود بنجاح!\n";
    
} catch (PDOException $e) {
    echo "❌ خطأ في تحديث قاعدة البيانات: " . $e->getMessage() . "\n";
    exit(1);
}
?>