<?php
$pdo = new PDO('sqlite:database/contracts.db');

echo "إضافة العمود المفقود contract_amount...\n";

try {
    // إضافة العمود الجديد
    $pdo->exec("ALTER TABLE contracts ADD COLUMN contract_amount DECIMAL(15,2) DEFAULT 0");
    echo "✅ تم إضافة العمود contract_amount\n";
    
    // نسخ القيم من amount إلى contract_amount
    $pdo->exec("UPDATE contracts SET contract_amount = amount");
    echo "✅ تم نسخ القيم من amount إلى contract_amount\n";
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "✅ العمود contract_amount موجود بالفعل\n";
        // تحديث القيم فقط
        $pdo->exec("UPDATE contracts SET contract_amount = amount WHERE contract_amount = 0");
        echo "✅ تم تحديث القيم في contract_amount\n";
    } else {
        echo "❌ خطأ: " . $e->getMessage() . "\n";
    }
}

// إضافة أعمدة أخرى مفقودة
$columnsToAdd = [
    'profit_percentage' => 'DECIMAL(5,2) DEFAULT 30',
    'start_date' => 'DATE DEFAULT CURRENT_DATE',
    'end_date' => 'DATE',
    'contract_number' => 'TEXT UNIQUE',
    'client_id' => 'TEXT',
    'client_phone' => 'TEXT',
    'contract_date' => 'DATE DEFAULT CURRENT_DATE',
    'signature_method' => 'TEXT DEFAULT "electronic"',
    'contract_duration' => 'INTEGER DEFAULT 12',
    'profit_interval' => 'TEXT DEFAULT "monthly"',
    'notes' => 'TEXT',
    'created_by' => 'INTEGER',
    'approved_by' => 'INTEGER',
    'approval_date' => 'DATETIME',
    'manager_notes' => 'TEXT'
];

foreach ($columnsToAdd as $columnName => $definition) {
    try {
        $pdo->exec("ALTER TABLE contracts ADD COLUMN {$columnName} {$definition}");
        echo "✅ تم إضافة العمود {$columnName}\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "⏭️ العمود {$columnName} موجود بالفعل\n";
        } else {
            echo "⚠️ خطأ في إضافة {$columnName}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== الأعمدة الحالية في جدول العقود ===\n";
$result = $pdo->query('PRAGMA table_info(contracts)');
while ($row = $result->fetch()) {
    echo "- {$row['name']} ({$row['type']})\n";
}

echo "\n🎉 تم الانتهاء من تحديث قاعدة البيانات!\n";