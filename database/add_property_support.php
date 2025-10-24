<?php
/**
 * تحديث قاعدة البيانات - دعم العقود العقارية
 * إضافة الحقول المطلوبة لدعم نوعي العقود: النقدي والعقاري
 */

require_once '../config/database.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث قاعدة البيانات - دعم العقود العقارية</title>
    <style>
        body { font-family: 'Cairo', Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #d4af37; padding-bottom: 10px; }
        .success { color: #27ae60; background: #d5f4e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; background: #ebf3fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; background: #fdeaea; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #f39c12; background: #fef9e7; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: right; }
        th { background: #d4af37; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h1>🏢 تحديث قاعدة البيانات - دعم العقود العقارية</h1>
    
    <?php
    try {
        $pdo = new PDO("sqlite:" . __DIR__ . "/contracts.db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<div class='info'>📊 بدء عملية التحديث لدعم العقود العقارية...</div>";
        
        // إضافة الحقول الجديدة لدعم العقود العقارية
        $property_fields = [
            'investment_type' => "ALTER TABLE contracts ADD COLUMN investment_type TEXT DEFAULT 'cash' CHECK(investment_type IN ('cash', 'property'))",
            'property_number' => "ALTER TABLE contracts ADD COLUMN property_number TEXT", // رقم العقار
            'property_location_city' => "ALTER TABLE contracts ADD COLUMN property_location_city TEXT", // مدينة العقار
            'property_location_district' => "ALTER TABLE contracts ADD COLUMN property_location_district TEXT", // حي العقار
            'property_plan_number' => "ALTER TABLE contracts ADD COLUMN property_plan_number TEXT", // رقم المخطط
            'property_description_detailed' => "ALTER TABLE contracts ADD COLUMN property_description_detailed TEXT", // وصف تفصيلي للعقار
            'property_market_value' => "ALTER TABLE contracts ADD COLUMN property_market_value DECIMAL(15,2)", // قيمة العقار حسب السوق
            'property_exchange_date' => "ALTER TABLE contracts ADD COLUMN property_exchange_date DATE", // تاريخ نقل العقار
            'property_deed_number' => "ALTER TABLE contracts ADD COLUMN property_deed_number TEXT", // رقم صك العقار
            'property_area' => "ALTER TABLE contracts ADD COLUMN property_area DECIMAL(10,2)", // مساحة العقار
            'property_type' => "ALTER TABLE contracts ADD COLUMN property_type TEXT", // نوع العقار (أرض، شقة، فيلا، إلخ)
            'profit_distribution_frequency' => "ALTER TABLE contracts ADD COLUMN profit_distribution_frequency TEXT DEFAULT 'end_of_contract'", // تكرار توزيع الأرباح
            'profit_distribution_months' => "ALTER TABLE contracts ADD COLUMN profit_distribution_months INTEGER DEFAULT 6", // كل كم شهر
        ];
        
        echo "<h3>🔧 إضافة الحقول الجديدة للعقود العقارية:</h3>";
        
        foreach ($property_fields as $column_name => $sql) {
            try {
                // التحقق من وجود العمود
                $columns = $pdo->query("PRAGMA table_info(contracts)")->fetchAll(PDO::FETCH_ASSOC);
                $column_exists = false;
                
                foreach ($columns as $column) {
                    if ($column['name'] === $column_name) {
                        $column_exists = true;
                        break;
                    }
                }
                
                if (!$column_exists) {
                    $pdo->exec($sql);
                    echo "<div class='success'>✅ تم إضافة العمود: <strong>{$column_name}</strong></div>";
                } else {
                    echo "<div class='info'>ℹ️ العمود موجود مسبقاً: <strong>{$column_name}</strong></div>";
                }
            } catch (PDOException $e) {
                echo "<div class='error'>❌ خطأ في إضافة العمود {$column_name}: " . $e->getMessage() . "</div>";
            }
        }
        
        echo "<h3>📊 تحديث البيانات الموجودة:</h3>";
        
        // تحديث العقود الموجودة لتكون نقدية بشكل افتراضي
        $update_existing = "UPDATE contracts SET investment_type = 'cash' WHERE investment_type IS NULL";
        $affected = $pdo->exec($update_existing);
        echo "<div class='success'>✅ تم تحديث العقود الموجودة لتكون نقدية - {$affected} سجل</div>";
        
        // إنشاء جدول تفاصيل العقارات (إضافي للتفاصيل المعقدة)
        $property_details_table = "
        CREATE TABLE IF NOT EXISTS property_details (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_id INTEGER NOT NULL,
            property_register_number TEXT,
            property_coordinates TEXT,
            property_boundaries TEXT,
            property_restrictions TEXT,
            property_utilities TEXT,
            property_zoning TEXT,
            property_documents TEXT, -- JSON للوثائق المرفقة
            market_evaluation_date DATE,
            market_evaluation_source TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($property_details_table);
        echo "<div class='success'>✅ تم إنشاء جدول تفاصيل العقارات</div>";
        
        // إنشاء جدول سجل تقييم العقارات
        $property_valuations_table = "
        CREATE TABLE IF NOT EXISTS property_valuations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_id INTEGER NOT NULL,
            valuation_date DATE NOT NULL,
            valuation_amount DECIMAL(15,2) NOT NULL,
            valuation_source TEXT,
            evaluator_name TEXT,
            evaluation_method TEXT,
            market_conditions TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($property_valuations_table);
        echo "<div class='success'>✅ تم إنشاء جدول تقييمات العقارات</div>";
        
        // إضافة بيانات اختبارية للعقد العقاري
        echo "<h3>📝 إضافة بيانات تجريبية:</h3>";
        
        // البحث عن عقد موجود لتحويله لعقاري كمثال
        $test_contract = $pdo->query("SELECT id FROM contracts LIMIT 1")->fetch();
        
        if ($test_contract) {
            $contract_id = $test_contract['id'];
            
            // إضافة تفاصيل العقار التجريبي
            $insert_property = $pdo->prepare("
                INSERT INTO property_details (
                    contract_id, property_register_number, property_coordinates, 
                    property_boundaries, property_utilities, property_zoning,
                    market_evaluation_date, market_evaluation_source, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $insert_property->execute([
                $contract_id,
                '220204019361',
                'إحداثيات: 21.234567, 39.123456',
                'الحدود: شمالاً الشارع العام، جنوباً أرض فضاء، شرقاً وغرباً قطع أخرى',
                'كهرباء، ماء، صرف صحي، اتصالات',
                'سكني تجاري',
                date('Y-m-d'),
                'البورصة العقارية السعودية',
                'عقار في موقع متميز بمدينة ثول'
            ]);
            
            echo "<div class='success'>✅ تم إضافة تفاصيل العقار التجريبي للعقد رقم {$contract_id}</div>";
            
            // إضافة تقييم العقار
            $insert_valuation = $pdo->prepare("
                INSERT INTO property_valuations (
                    contract_id, valuation_date, valuation_amount, 
                    valuation_source, evaluator_name, evaluation_method
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $insert_valuation->execute([
                $contract_id,
                date('Y-m-d'),
                400000.00,
                'البورصة العقارية',
                'مكتب التقييم المعتمد',
                'المقارنة السوقية'
            ]);
            
            echo "<div class='success'>✅ تم إضافة تقييم العقار التجريبي</div>";
        }
        
        echo "<div class='success'><h2>🎉 تم التحديث بنجاح!</h2><p>النظام الآن يدعم نوعي العقود: النقدية والعقارية.</p></div>";
        
        // عرض ملخص الهيكل الجديد
        echo "<h3>📋 ملخص التحديثات:</h3>";
        
        echo "<div class='warning'>";
        echo "<h4>🔍 الحقول الجديدة المضافة:</h4>";
        echo "<ul>";
        foreach (array_keys($property_fields) as $field) {
            echo "<li><strong>{$field}</strong></li>";
        }
        echo "</ul>";
        echo "</div>";
        
        // إحصائيات سريعة
        $contract_count = $pdo->query("SELECT COUNT(*) FROM contracts")->fetchColumn();
        $property_details_count = $pdo->query("SELECT COUNT(*) FROM property_details")->fetchColumn();
        $valuations_count = $pdo->query("SELECT COUNT(*) FROM property_valuations")->fetchColumn();
        
        echo "<div class='info'>";
        echo "<h4>📊 إحصائيات النظام:</h4>";
        echo "<p>📑 إجمالي العقود: <strong>{$contract_count}</strong></p>";
        echo "<p>🏠 تفاصيل العقارات: <strong>{$property_details_count}</strong></p>";
        echo "<p>💰 تقييمات العقارات: <strong>{$valuations_count}</strong></p>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div class='error'><h3>❌ خطأ في التحديث</h3><p>" . $e->getMessage() . "</p></div>";
    }
    ?>
    
    <div style="margin-top: 30px; padding: 20px; background: #e8f4fd; border-radius: 10px;">
        <h3>📝 الخطوات التالية:</h3>
        <ol>
            <li><strong>تحديث نموذج إنشاء العقد</strong> - إضافة خيار العقد العقاري</li>
            <li><strong>تحديث قالب PDF</strong> - عرض تفاصيل العقار في البند الثالث</li>
            <li><strong>اختبار النظام</strong> - إنشاء عقود عقارية ونقدية</li>
            <li><strong>التدريب</strong> - تدريب المستخدمين على النوعين</li>
        </ol>
        
        <div class="warning">
            <h4>🔄 الفروقات بين النوعين:</h4>
            <table>
                <tr>
                    <th>الخاصية</th>
                    <th>العقد النقدي</th>
                    <th>العقد العقاري</th>
                </tr>
                <tr>
                    <td>البند الثالث</td>
                    <td>مبلغ نقدي (مثال: 100,000 ريال)</td>
                    <td>عقار برقم ومكان محدد</td>
                </tr>
                <tr>
                    <td>نسبة الربح</td>
                    <td>40% (العقد الأول)</td>
                    <td>30% (العقد الثاني)</td>
                </tr>
                <tr>
                    <td>دفع الأرباح</td>
                    <td>نهاية العقد</td>
                    <td>كل شهرين</td>
                </tr>
                <tr>
                    <td>التفاصيل الإضافية</td>
                    <td>لا توجد</td>
                    <td>رقم العقار، المكان، المخطط</td>
                </tr>
            </table>
        </div>
    </div>
</div>

</body>
</html>