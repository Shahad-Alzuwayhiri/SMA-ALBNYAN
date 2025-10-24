<?php
/**
 * تحديث شامل لقاعدة البيانات - العقود الرسمية
 * إضافة جميع الحقول المطلوبة من النموذج الرسمي
 */

require_once '../config/database.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث قاعدة البيانات - سما البنيان</title>
    <style>
        body { font-family: 'Cairo', Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #d4af37; padding-bottom: 10px; }
        .success { color: #27ae60; background: #d5f4e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; background: #ebf3fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; background: #fdeaea; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: right; }
        th { background: #d4af37; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔄 تحديث قاعدة البيانات - النموذج الرسمي</h1>
    
    <?php
    try {
        $pdo = new PDO("sqlite:" . __DIR__ . "/contracts.db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<div class='info'>📊 بدء عملية التحديث...</div>";
        
        // إضافة الحقول الجديدة للطرف الأول
        $first_party_updates = [
            'first_party_name' => "ALTER TABLE contracts ADD COLUMN first_party_name TEXT DEFAULT 'شركة سما البنيان التجارية'",
            'first_party_commercial_register' => "ALTER TABLE contracts ADD COLUMN first_party_commercial_register TEXT DEFAULT '4030533070'",
            'first_party_city' => "ALTER TABLE contracts ADD COLUMN first_party_city TEXT DEFAULT 'جدة'",
            'first_party_district' => "ALTER TABLE contracts ADD COLUMN first_party_district TEXT DEFAULT 'الحمدانية'",
            'first_party_representative' => "ALTER TABLE contracts ADD COLUMN first_party_representative TEXT DEFAULT 'احمد عبد الله سعيد الزهراني'",
            'first_party_phone' => "ALTER TABLE contracts ADD COLUMN first_party_phone TEXT DEFAULT '0537295224'",
            'first_party_address' => "ALTER TABLE contracts ADD COLUMN first_party_address TEXT DEFAULT 'جدة - حي الحمدانية – شارع ياسر بن عامر'",
        ];
        
        // إضافة الحقول الجديدة للطرف الثاني
        $second_party_updates = [
            'second_party_nationality' => "ALTER TABLE contracts ADD COLUMN second_party_nationality TEXT DEFAULT 'سعودي الجنسية'",
            'second_party_id' => "ALTER TABLE contracts ADD COLUMN second_party_id TEXT",
            'second_party_city' => "ALTER TABLE contracts ADD COLUMN second_party_city TEXT DEFAULT 'جدة'",
            'second_party_district' => "ALTER TABLE contracts ADD COLUMN second_party_district TEXT",
            'second_party_street' => "ALTER TABLE contracts ADD COLUMN second_party_street TEXT",
        ];
        
        // حقول تفاصيل العقد الجديدة
        $contract_details = [
            'hijri_date' => "ALTER TABLE contracts ADD COLUMN hijri_date TEXT",
            'location' => "ALTER TABLE contracts ADD COLUMN location TEXT DEFAULT 'محافظة جدة'",
            'investment_amount' => "ALTER TABLE contracts ADD COLUMN investment_amount DECIMAL(15,2)",
            'loss_responsibility' => "ALTER TABLE contracts ADD COLUMN loss_responsibility TEXT DEFAULT 'shared'",
            'contract_duration_months' => "ALTER TABLE contracts ADD COLUMN contract_duration_months INTEGER DEFAULT 6",
            'is_renewable' => "ALTER TABLE contracts ADD COLUMN is_renewable BOOLEAN DEFAULT 1",
            'withdrawal_notice_days' => "ALTER TABLE contracts ADD COLUMN withdrawal_notice_days INTEGER DEFAULT 60",
            'minimum_investment_period_months' => "ALTER TABLE contracts ADD COLUMN minimum_investment_period_months INTEGER DEFAULT 6",
            'profit_payment_deadline_days' => "ALTER TABLE contracts ADD COLUMN profit_payment_deadline_days INTEGER DEFAULT 15",
            'commission_percentage' => "ALTER TABLE contracts ADD COLUMN commission_percentage DECIMAL(5,2) DEFAULT 2.5",
            'commission_conditions' => "ALTER TABLE contracts ADD COLUMN commission_conditions TEXT",
            'penalty_amount' => "ALTER TABLE contracts ADD COLUMN penalty_amount DECIMAL(10,2) DEFAULT 3000.00",
            'penalty_period_days' => "ALTER TABLE contracts ADD COLUMN penalty_period_days INTEGER DEFAULT 30",
            'project_description' => "ALTER TABLE contracts ADD COLUMN project_description TEXT",
            'inheritance_clause' => "ALTER TABLE contracts ADD COLUMN inheritance_clause TEXT",
            'force_majeure_clause' => "ALTER TABLE contracts ADD COLUMN force_majeure_clause TEXT",
            'legal_counsel_info' => "ALTER TABLE contracts ADD COLUMN legal_counsel_info TEXT DEFAULT 'مكتب المحامي بشير بن عبد الله صديق كنسارة'",
            'special_conditions' => "ALTER TABLE contracts ADD COLUMN special_conditions TEXT",
            'attachments' => "ALTER TABLE contracts ADD COLUMN attachments TEXT",
            'signed_at' => "ALTER TABLE contracts ADD COLUMN signed_at DATETIME",
            'approved_by' => "ALTER TABLE contracts ADD COLUMN approved_by INTEGER",
        ];
        
        // دمج جميع التحديثات
        $all_updates = array_merge($first_party_updates, $second_party_updates, $contract_details);
        
        echo "<h3>🔧 إضافة الحقول الجديدة:</h3>";
        
        foreach ($all_updates as $column_name => $sql) {
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
        
        // نقل البيانات القديمة إلى الحقول الجديدة
        $data_migrations = [
            "UPDATE contracts SET investment_amount = amount WHERE investment_amount IS NULL AND amount IS NOT NULL" => "نقل المبالغ إلى investment_amount",
            "UPDATE contracts SET profit_percentage = 40.0 WHERE profit_percentage = 0 OR profit_percentage IS NULL" => "تحديث نسب الأرباح الافتراضية إلى 40%",
            "UPDATE contracts SET contract_duration_months = duration WHERE contract_duration_months IS NULL AND duration IS NOT NULL" => "نقل مدة العقد",
            "UPDATE contracts SET second_party_name = client_name WHERE second_party_name IS NULL AND client_name IS NOT NULL" => "نقل أسماء العملاء",
            "UPDATE contracts SET second_party_id = client_id WHERE second_party_id IS NULL AND client_id IS NOT NULL" => "نقل أرقام هوية العملاء",
            "UPDATE contracts SET second_party_phone = client_phone WHERE second_party_phone IS NULL AND client_phone IS NOT NULL" => "نقل أرقام هواتف العملاء",
            "UPDATE contracts SET second_party_email = client_email WHERE second_party_email IS NULL AND client_email IS NOT NULL" => "نقل بريد العملاء الإلكتروني",
        ];
        
        foreach ($data_migrations as $sql => $description) {
            try {
                $affected = $pdo->exec($sql);
                echo "<div class='success'>✅ {$description} - تم تحديث {$affected} سجل</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ خطأ في {$description}: " . $e->getMessage() . "</div>";
            }
        }
        
        echo "<h3>🗃️ إنشاء الجداول الإضافية:</h3>";
        
        // إنشاء جدول سجل التعديلات
        $revisions_table = "
        CREATE TABLE IF NOT EXISTS contract_revisions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_id INTEGER NOT NULL,
            revision_number INTEGER NOT NULL,
            changes_description TEXT,
            changed_by INTEGER NOT NULL,
            changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            old_values TEXT,
            new_values TEXT,
            FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($revisions_table);
        echo "<div class='success'>✅ تم إنشاء جدول سجل التعديلات</div>";
        
        // إنشاء جدول التوقيعات الرقمية
        $signatures_table = "
        CREATE TABLE IF NOT EXISTS digital_signatures (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_id INTEGER NOT NULL,
            signer_name TEXT NOT NULL,
            signer_role TEXT NOT NULL,
            signature_data TEXT,
            signature_image_path TEXT,
            ip_address TEXT,
            user_agent TEXT,
            signed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_verified BOOLEAN DEFAULT FALSE,
            verification_method TEXT,
            FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($signatures_table);
        echo "<div class='success'>✅ تم إنشاء جدول التوقيعات الرقمية</div>";
        
        // إنشاء جدول المرفقات
        $attachments_table = "
        CREATE TABLE IF NOT EXISTS contract_attachments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_id INTEGER NOT NULL,
            file_name TEXT NOT NULL,
            file_path TEXT NOT NULL,
            file_type TEXT,
            file_size INTEGER,
            description TEXT,
            uploaded_by INTEGER NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($attachments_table);
        echo "<div class='success'>✅ تم إنشاء جدول المرفقات</div>";
        
        echo "<div class='success'><h2>🎉 تم التحديث بنجاح!</h2><p>قاعدة البيانات أصبحت جاهزة للعمل مع النموذج الرسمي الجديد.</p></div>";
        
        // عرض ملخص الهيكل الجديد
        echo "<h3>📋 ملخص هيكل الجدول المحدث:</h3>";
        $columns = $pdo->query("PRAGMA table_info(contracts)")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>اسم العمود</th><th>النوع</th><th>القيمة الافتراضية</th><th>مطلوب</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td><strong>{$column['name']}</strong></td>";
            echo "<td>{$column['type']}</td>";
            echo "<td>" . ($column['dflt_value'] ? htmlspecialchars($column['dflt_value']) : '-') . "</td>";
            echo "<td>" . ($column['notnull'] ? 'نعم' : 'لا') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // إحصائيات سريعة
        $contract_count = $pdo->query("SELECT COUNT(*) FROM contracts")->fetchColumn();
        echo "<div class='info'>📊 إجمالي العقود في النظام: <strong>{$contract_count}</strong> عقد</div>";
        
    } catch (PDOException $e) {
        echo "<div class='error'><h3>❌ خطأ في التحديث</h3><p>" . $e->getMessage() . "</p></div>";
    }
    ?>
    
    <div style="margin-top: 30px; padding: 20px; background: #e8f4fd; border-radius: 10px;">
        <h3>📝 الخطوات التالية:</h3>
        <ol>
            <li>تحديث نماذج إنشاء العقود لتشمل الحقول الجديدة</li>
            <li>تحديث قالب PDF ليعكس النموذج الرسمي</li>
            <li>اختبار النظام مع البيانات الجديدة</li>
            <li>تدريب المستخدمين على الواجهة المحدثة</li>
        </ol>
    </div>
</div>

</body>
</html>