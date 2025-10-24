<?php
/**
 * Contract System Test - Both Types
 * اختبار نظام العقود - النوعين
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار نظام العقود - سما البنيان</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 20px; background: #f8f9fa; color: #2c3e50; 
        }
        .container { 
            max-width: 1000px; margin: 0 auto; 
            background: white; padding: 30px; 
            border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header { 
            text-align: center; margin-bottom: 30px; 
            color: #d4af37; border-bottom: 2px solid #d4af37; 
            padding-bottom: 15px; 
        }
        .test-section { 
            margin: 20px 0; padding: 20px; 
            border: 1px solid #ddd; border-radius: 8px; 
        }
        .success { background: #d5f4e6; border-color: #27ae60; }
        .error { background: #fdeaea; border-color: #e74c3c; }
        .info { background: #ebf3fd; border-color: #3498db; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: right; }
        th { background: #f8f9fa; }
        .status-ok { color: #27ae60; font-weight: bold; }
        .status-error { color: #e74c3c; font-weight: bold; }
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 12px; }
        .badge-cash { background: #27ae60; color: white; }
        .badge-property { background: #f39c12; color: white; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .quick-links { 
            display: flex; gap: 10px; margin: 20px 0; 
            flex-wrap: wrap; justify-content: center; 
        }
        .quick-links a { 
            padding: 10px 15px; background: #3498db; 
            color: white; border-radius: 5px; 
        }
        .quick-links a:hover { background: #2980b9; text-decoration: none; }
        .form-section {
            background: #f8f9fa; padding: 15px; margin: 10px 0;
            border-radius: 5px; border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 اختبار نظام العقود المطور</h1>
            <p>فحص شامل للعقود النقدية والعقارية</p>
        </div>

        <?php
        try {
            // Test database connection
            if (!$pdo) {
                throw new Exception("قاعدة البيانات غير متصلة");
            }
            
            echo "<div class='test-section success'>";
            echo "<h3>✅ قاعدة البيانات متصلة بنجاح</h3>";
            echo "</div>";
            
            // Check if new columns exist
            echo "<div class='test-section info'>";
            echo "<h3>🔍 فحص الحقول الجديدة</h3>";
            
            $columns = $pdo->query("PRAGMA table_info(contracts)")->fetchAll();
            $newColumns = [
                'investment_type' => 'نوع الاستثمار',
                'property_number' => 'رقم العقار',
                'property_location_city' => 'مدينة العقار',
                'property_market_value' => 'قيمة العقار',
                'profit_distribution_frequency' => 'تكرار توزيع الأرباح'
            ];
            
            $foundColumns = [];
            foreach ($columns as $column) {
                if (array_key_exists($column['name'], $newColumns)) {
                    $foundColumns[$column['name']] = $newColumns[$column['name']];
                }
            }
            
            echo "<p><strong>الحقول الجديدة الموجودة:</strong></p>";
            echo "<ul>";
            foreach ($foundColumns as $colName => $colDesc) {
                echo "<li class='status-ok'>✅ {$colDesc} ({$colName})</li>";
            }
            echo "</ul>";
            
            $missingColumns = array_diff($newColumns, $foundColumns);
            if (!empty($missingColumns)) {
                echo "<p><strong>الحقول المفقودة:</strong></p>";
                echo "<ul>";
                foreach ($missingColumns as $colName => $colDesc) {
                    echo "<li class='status-error'>❌ {$colDesc} ({$colName})</li>";
                }
                echo "</ul>";
            }
            echo "</div>";
            
            // Show existing contracts with their types
            echo "<div class='test-section info'>";
            echo "<h3>📊 العقود الموجودة</h3>";
            
            $contracts = $pdo->query("
                SELECT 
                    id, contract_number, client_name, 
                    investment_type, amount, property_market_value,
                    profit_percentage, created_at
                FROM contracts 
                ORDER BY created_at DESC 
                LIMIT 10
            ")->fetchAll();
            
            if (empty($contracts)) {
                echo "<p>لا توجد عقود في النظام حالياً.</p>";
            } else {
                echo "<table>";
                echo "<tr>";
                echo "<th>رقم العقد</th>";
                echo "<th>العميل</th>";
                echo "<th>نوع الاستثمار</th>";
                echo "<th>المبلغ/القيمة</th>";
                echo "<th>نسبة الربح</th>";
                echo "<th>التاريخ</th>";
                echo "</tr>";
                
                foreach ($contracts as $contract) {
                    $investmentType = $contract['investment_type'] ?? 'cash';
                    $typeText = $investmentType === 'cash' ? 'نقدي' : 'عقاري';
                    $badgeClass = $investmentType === 'cash' ? 'badge-cash' : 'badge-property';
                    
                    $amount = $investmentType === 'cash' 
                        ? number_format($contract['amount'] ?? 0) . ' ريال'
                        : number_format($contract['property_market_value'] ?? 0) . ' ريال (عقار)';
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($contract['contract_number']) . "</td>";
                    echo "<td>" . htmlspecialchars($contract['client_name']) . "</td>";
                    echo "<td><span class='badge {$badgeClass}'>{$typeText}</span></td>";
                    echo "<td>{$amount}</td>";
                    echo "<td>" . ($contract['profit_percentage'] ?? 0) . "%</td>";
                    echo "<td>" . date('Y/m/d', strtotime($contract['created_at'])) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            echo "</div>";
            
            // Test contract creation forms
            echo "<div class='test-section info'>";
            echo "<h3>📝 اختبار نماذج إنشاء العقود</h3>";
            echo "<div class='quick-links'>";
            echo "<a href='public/create_contract.php'>إنشاء عقد جديد</a>";
            echo "<a href='public/contracts_list.php'>قائمة العقود</a>";
            echo "</div>";
            echo "</div>";
            
            // Sample data for testing
            echo "<div class='test-section info'>";
            echo "<h3>📋 بيانات تجريبية للاختبار</h3>";
            
            echo "<div class='form-section'>";
            echo "<h4>💰 بيانات العقد النقدي التجريبي:</h4>";
            echo "<ul>";
            echo "<li><strong>اسم العميل:</strong> أحمد محمد العمري</li>";
            echo "<li><strong>رقم الهوية:</strong> 1234567890</li>";
            echo "<li><strong>رقم الجوال:</strong> 0551234567</li>";
            echo "<li><strong>مبلغ المضاربة:</strong> 100,000 ريال</li>";
            echo "<li><strong>نسبة الربح:</strong> 40%</li>";
            echo "<li><strong>الربح المتوقع:</strong> 20,000 ريال (6 أشهر)</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "<div class='form-section'>";
            echo "<h4>🏠 بيانات العقد العقاري التجريبي:</h4>";
            echo "<ul>";
            echo "<li><strong>اسم العميل:</strong> فاطمة سالم الزهراني</li>";
            echo "<li><strong>رقم الهوية:</strong> 9876543210</li>";
            echo "<li><strong>رقم الجوال:</strong> 0559876543</li>";
            echo "<li><strong>رقم العقار:</strong> 220204019361</li>";
            echo "<li><strong>مدينة العقار:</strong> ثول</li>";
            echo "<li><strong>رقم المخطط:</strong> 2202040</li>";
            echo "<li><strong>قيمة العقار:</strong> 400,000 ريال</li>";
            echo "<li><strong>نسبة الربح:</strong> 30%</li>";
            echo "<li><strong>الربح المتوقع:</strong> 60,000 ريال (6 أشهر)</li>";
            echo "<li><strong>توزيع الأرباح:</strong> كل شهرين</li>";
            echo "</ul>";
            echo "</div>";
            echo "</div>";
            
            // Summary stats
            $stats = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN investment_type = 'cash' OR investment_type IS NULL THEN 1 ELSE 0 END) as cash_contracts,
                    SUM(CASE WHEN investment_type = 'property' THEN 1 ELSE 0 END) as property_contracts,
                    SUM(CASE WHEN investment_type = 'cash' OR investment_type IS NULL THEN amount ELSE 0 END) as total_cash_amount,
                    SUM(CASE WHEN investment_type = 'property' THEN property_market_value ELSE 0 END) as total_property_value
                FROM contracts
            ")->fetch();
            
            echo "<div class='test-section success'>";
            echo "<h3>📈 إحصائيات النظام</h3>";
            echo "<table>";
            echo "<tr><th>الإحصائية</th><th>القيمة</th></tr>";
            echo "<tr><td>إجمالي العقود</td><td>" . ($stats['total'] ?? 0) . "</td></tr>";
            echo "<tr><td>العقود النقدية</td><td>" . ($stats['cash_contracts'] ?? 0) . "</td></tr>";
            echo "<tr><td>العقود العقارية</td><td>" . ($stats['property_contracts'] ?? 0) . "</td></tr>";
            echo "<tr><td>إجمالي المبالغ النقدية</td><td>" . number_format($stats['total_cash_amount'] ?? 0) . " ريال</td></tr>";
            echo "<tr><td>إجمالي قيم العقارات</td><td>" . number_format($stats['total_property_value'] ?? 0) . " ريال</td></tr>";
            echo "</table>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='test-section error'>";
            echo "<h3>❌ خطأ في الاختبار</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>

        <div class="test-section info">
            <h3>🔗 روابط مفيدة</h3>
            <div class="quick-links">
                <a href="diagnostic.php">أداة التشخيص</a>
                <a href="<?php echo asset(''); ?>">النظام الرئيسي</a>
                <a href="USER_GUIDE.md" target="_blank">دليل المستخدم</a>
            </div>
        </div>

        <hr style="margin: 30px 0;">
        <p style="text-align: center; color: #7f8c8d; font-size: 14px;">
            © 2025 شركة سما البنيان للتطوير العقاري - اختبار النظام المطور
        </p>
    </div>
</body>
</html>