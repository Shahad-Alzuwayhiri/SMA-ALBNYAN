<?php
// إنشاء عقود تجريبية لاختبار نظام الترقيم التلقائي

require_once 'config/database.php';
require_once 'models/Contract.php';

try {
    echo "🔨 إنشاء عقود تجريبية لاختبار الترقيم...\n";
    echo "===========================================\n\n";
    
    $contractModel = new Contract();
    
    // إنشاء 3 عقود تجريبية
    $sampleContracts = [
        [
            'title' => 'عقد مضاربة عقارية - مشروع الدمام',
            'second_party_name' => 'أحمد محمد العمري',
            'second_party_phone' => '0501234567',
            'second_party_email' => 'ahmed@example.com',
            'contract_amount' => 500000,
            'profit_percentage' => 30,
            'description' => 'عقد مضاربة عقارية لمشروع سكني في الدمام',
            'created_by' => 2, // مدير العقود
            'status' => 'draft'
        ],
        [
            'title' => 'عقد شراكة تجارية - مركز تسوق',
            'second_party_name' => 'سارة عبدالله الزهراني',
            'second_party_phone' => '0507654321',
            'second_party_email' => 'sara@example.com',
            'contract_amount' => 750000,
            'profit_percentage' => 25,
            'description' => 'عقد شراكة لإنشاء مركز تسوق في جدة',
            'created_by' => 3, // موظف العقود
            'status' => 'draft'
        ],
        [
            'title' => 'عقد استثمار عقاري - أبراج سكنية',
            'second_party_name' => 'خالد عبدالعزيز القحطاني',
            'second_party_phone' => '0509876543',
            'second_party_email' => 'khalid@example.com',
            'contract_amount' => 1200000,
            'profit_percentage' => 35,
            'description' => 'عقد استثمار لمشروع أبراج سكنية في الرياض',
            'created_by' => 2, // مدير العقود
            'status' => 'draft'
        ]
    ];
    
    foreach ($sampleContracts as $index => $contractData) {
        $contractId = $contractModel->create($contractData);
        
        if ($contractId) {
            $contract = $contractModel->findById($contractId);
            echo "✅ تم إنشاء العقد #{" . ($index + 1) . "}\n";
            echo "   📋 العنوان: " . $contract['title'] . "\n";
            echo "   🔢 رقم العقد: " . $contract['contract_number'] . "\n";
            echo "   👤 العميل: " . $contract['second_party_name'] . "\n";
            echo "   💰 المبلغ: " . number_format($contract['contract_amount']) . " ر.س\n";
            echo "   📊 نسبة الأرباح: " . $contract['profit_percentage'] . "%\n\n";
        } else {
            echo "❌ فشل في إنشاء العقد #{" . ($index + 1) . "}\n\n";
        }
    }
    
    echo "🎉 تم إنشاء العقود التجريبية بنجاح!\n";
    echo "💡 لاحظ أن كل عقد حصل على رقم فريد تلقائياً\n";
    echo "🔗 يمكنك الآن زيارة /contracts لرؤية العقود\n";
    
} catch (Exception $e) {
    echo "❌ خطأ في إنشاء العقود التجريبية: " . $e->getMessage() . "\n";
}
?>