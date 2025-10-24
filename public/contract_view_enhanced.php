<?php
require_once '../includes/auth.php';

$auth->requireAuth();
$user = $auth->getCurrentUser();

$contract_id = $_GET['id'] ?? null;
if (!$contract_id) {
    die('معرف العقد مطلوب');
}

try {
    // جلب بيانات العقد
    $stmt = $pdo->prepare("SELECT * FROM contracts WHERE id = ?");
    $stmt->execute([$contract_id]);
    $contract = $stmt->fetch();

    if (!$contract) {
        die('العقد غير موجود');
    }

    // التحقق من الصلاحية
    if ($user['role'] === 'employee' && $contract['created_by'] != $user['id']) {
        die('غير مسموح لك بعرض هذا العقد');
    }

} catch (PDOException $e) {
    die('خطأ في جلب بيانات العقد: ' . $e->getMessage());
}

// تحديد نوع العقد
$contractType = $contract['contract_type'] ?? 'investment_cash';
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عقد الاستثمار - <?= htmlspecialchars($contract['contract_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', Arial, sans-serif;
            background: #f8f9fa;
            padding: 20px;
            line-height: 1.8;
            color: #333;
        }
        .contract-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .contract-header {
            text-align: center;
            border-bottom: 3px solid #1a365d;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }
        .company-info {
            background: linear-gradient(135deg, #1a365d, #2d5aa0);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .contract-title {
            font-size: 24px;
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 20px;
        }
        .contract-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-right: 4px solid #4299e1;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
        }
        .detail-label {
            font-weight: 600;
            color: #1a365d;
            min-width: 150px;
        }
        .detail-value {
            flex: 1;
            text-align: left;
        }
        .article-section {
            margin: 30px 0;
            padding: 20px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .article-title {
            font-size: 18px;
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 15px;
            padding: 10px;
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border-right: 4px solid #4299e1;
        }
        .article-content {
            font-size: 16px;
            line-height: 2;
            text-align: justify;
            padding: 10px;
            background: #fefefe;
        }
        .signature-section {
            margin-top: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .signature-party {
            text-align: center;
            border: 2px dashed #cbd5e0;
            padding: 30px;
            border-radius: 8px;
            background: white;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
        
        @media print {
            .print-btn { display: none; }
            body { background: white; padding: 0; }
            .contract-container { 
                box-shadow: none; 
                max-width: none;
                margin: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <button class="btn btn-primary print-btn" onclick="window.print()">
        <i class="fas fa-print me-2"></i> طباعة
    </button>

    <div class="contract-container">
        <!-- رأس العقد -->
        <div class="contract-header">
            <div class="company-info">
                <h1><i class="fas fa-building me-2"></i>شركة سما البنيان للتطوير والاستثمار</h1>
                <p class="mb-0">شركة ذات مسؤولية محدودة - سجل تجاري رقم: xxxxxxxxx</p>
            </div>
            
            <div class="contract-title">
                <?php if ($contractType === 'investment_property'): ?>
                    عقد استثمار عقاري (مساهمة عقارية)
                <?php else: ?>
                    عقد استثمار نقدي
                <?php endif; ?>
            </div>
            
            <div class="contract-details">
                <div class="detail-row">
                    <span class="detail-label">رقم العقد:</span>
                    <span class="detail-value"><?= htmlspecialchars($contract['contract_number']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">تاريخ العقد:</span>
                    <span class="detail-value"><?= date('Y/m/d', strtotime($contract['contract_date'] ?? 'now')) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">اسم المستثمر:</span>
                    <span class="detail-value"><?= htmlspecialchars($contract['client_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">رقم الهوية:</span>
                    <span class="detail-value"><?= htmlspecialchars($contract['client_id']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">رقم الجوال:</span>
                    <span class="detail-value"><?= htmlspecialchars($contract['client_phone']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">البريد الإلكتروني:</span>
                    <span class="detail-value"><?= htmlspecialchars($contract['client_email']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">مبلغ الاستثمار:</span>
                    <span class="detail-value"><?= number_format($contract['amount']) ?> ريال سعودي</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">نسبة الربح:</span>
                    <span class="detail-value"><?= $contract['profit_percentage'] ?>% سنوياً</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">مدة العقد:</span>
                    <span class="detail-value"><?= $contract['contract_duration'] ?> شهر</span>
                </div>
                <?php if ($contractType === 'investment_property'): ?>
                <div class="detail-row">
                    <span class="detail-label">رقم العقار:</span>
                    <span class="detail-value"><?= htmlspecialchars($contract['property_number'] ?? 'غير محدد') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">موقع العقار:</span>
                    <span class="detail-value"><?= htmlspecialchars($contract['property_location'] ?? 'غير محدد') ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- نص العقد والبنود -->
        <div class="contract-body">
            <!-- الأطراف -->
            <div class="article-section">
                <div class="article-title">أطراف العقد</div>
                <div class="article-content">
                    <strong>الطرف الأول (الشركة):</strong><br>
                    شركة سما البنيان للتطوير والاستثمار<br>
                    شركة ذات مسؤولية محدودة سعودية<br>
                    السجل التجاري رقم: xxxxxxxxx<br>
                    العنوان: المملكة العربية السعودية<br><br>
                    
                    <strong>الطرف الثاني (المستثمر):</strong><br>
                    <?= htmlspecialchars($contract['client_name']) ?><br>
                    سعودي الجنسية بموجب السجل المدني رقم <?= htmlspecialchars($contract['client_id']) ?><br>
                    جوال رقم: (<?= htmlspecialchars($contract['client_phone']) ?>)<br>
                    بريد إلكتروني: (<?= htmlspecialchars($contract['client_email']) ?>)<br>
                    <strong>ويشار إليه في العقد بالطرف الثاني.</strong>
                </div>
            </div>

            <!-- التمهيد -->
            <div class="article-section">
                <div class="article-title">تمهيــــــــد</div>
                <div class="article-content">
                    ولما كان الطرف الأول شركة عقارية مؤهلة بترخيص من الهيئة العامة للعقار للبيع والتأجير على الخارطة وترخيص التأهيل للمطور العقاري وتعمل في مجال التطوير العقاري للفلل السكنية وشقق التمليك والمجمعات التجارية وتشغيل وإدارة محطات الوقود والمشاريع التجارية والسكنية ولها خبرة وممارسة في هذا المجال بالإنشاء والبيع والتأجير وادارة المحافظ العقارية.
                </div>
            </div>

            <!-- البنود -->
            <div class="article-section">
                <div class="article-title">البند الأول: التمهيد</div>
                <div class="article-content">
                    يعتبر التمهيد والمقدمة أعلاه جزء لا يتجزأ من هذا العقد، وأن البيانات والعناوين الموضحة في المقدمة منتجة لآثارها النظامية.
                </div>
            </div>

            <div class="article-section">
                <div class="article-title">البند الثاني: موضوع العقد</div>
                <div class="article-content">
                    <?php if ($contractType === 'investment_property'): ?>
                        يتعهد الطرف الثاني بالمساهمة في العقار رقم <?= htmlspecialchars($contract['property_number'] ?? 'XXX') ?> الواقع في <?= htmlspecialchars($contract['property_location'] ?? 'الموقع المحدد') ?> بمبلغ قدره <?= number_format($contract['amount']) ?> ريال سعودي، مقابل الحصول على نسبة ربح قدرها <?= $contract['profit_percentage'] ?>% سنوياً من قيمة العقار أو من عائد تأجيره.
                    <?php else: ?>
                        يتعهد الطرف الثاني بدفع مبلغ قدره <?= number_format($contract['amount']) ?> ريال سعودي للطرف الأول كاستثمار نقدي، مقابل الحصول على عائد استثماري قدره <?= $contract['profit_percentage'] ?>% سنوياً من قيمة المبلغ المستثمر.
                    <?php endif; ?>
                </div>
            </div>

            <div class="article-section">
                <div class="article-title">البند الثالث: مدة العقد</div>
                <div class="article-content">
                    مدة هذا العقد <?= $contract['contract_duration'] ?> شهراً، تبدأ من تاريخ استلام المبلغ وتنتهي في <?= date('Y/m/d', strtotime('+' . $contract['contract_duration'] . ' months', strtotime($contract['contract_date'] ?? 'now'))) ?>.
                </div>
            </div>

            <div class="article-section">
                <div class="article-title">البند الرابع: دفع الأرباح</div>
                <div class="article-content">
                    يتم دفع الأرباح للطرف الثاني حسب الاتفاق الشهري أو حسب الاتفاق المبرم، ويكون موعد الدفع في نهاية كل فترة محاسبية.
                </div>
            </div>

            <div class="article-section">
                <div class="article-title">البند الخامس: ضمان رأس المال</div>
                <div class="article-content">
                    <?php if ($contractType === 'investment_property'): ?>
                        يضمن الطرف الأول للطرف الثاني رأس المال المستثمر في العقار، ويتحمل الطرف الأول أي خسائر قد تطرأ على العقار أو انخفاض في قيمته.
                    <?php else: ?>
                        يضمن الطرف الأول للطرف الثاني رأس المال المستثمر كاملاً، ويتحمل الطرف الأول أي خسائر قد تطرأ على الاستثمار.
                    <?php endif; ?>
                </div>
            </div>

            <div class="article-section">
                <div class="article-title">البند السادس: الالتزامات</div>
                <div class="article-content">
                    <strong>التزامات الطرف الأول:</strong><br>
                    • إدارة الاستثمار بالشكل الأمثل<br>
                    • دفع الأرباح في المواعيد المحددة<br>
                    • تقديم تقارير دورية عن حالة الاستثمار<br>
                    • ضمان رأس المال المستثمر<br><br>
                    
                    <strong>التزامات الطرف الثاني:</strong><br>
                    • دفع مبلغ الاستثمار في الموعد المحدد<br>
                    • عدم المطالبة برأس المال قبل انتهاء مدة العقد<br>
                    • إخطار الطرف الأول بأي تغيير في بياناته
                </div>
            </div>

            <div class="article-section">
                <div class="article-title">البند السابع: فسخ العقد</div>
                <div class="article-content">
                    يحق لأي من الطرفين فسخ العقد في الحالات التالية:<br>
                    • إخلال أحد الطرفين بالتزاماته العقدية<br>
                    • عدم دفع الأرباح لمدة تزيد عن 3 أشهر<br>
                    • حدوث ظروف قاهرة تمنع تنفيذ العقد<br>
                    • اتفاق الطرفين على الفسخ
                </div>
            </div>

            <div class="article-section">
                <div class="article-title">البند الثامن: فض النزاعات</div>
                <div class="article-content">
                    في حالة نشوء أي نزاع حول تفسير أو تنفيذ أحكام هذا العقد، يتم حل النزاع ودياً أولاً، وفي حالة عدم التوصل لحل ودي خلال 30 يوماً، يحال النزاع إلى المحاكم المختصة في المملكة العربية السعودية.
                </div>
            </div>

            <div class="article-section">
                <div class="article-title">البند التاسع: أحكام عامة</div>
                <div class="article-content">
                    • يسري هذا العقد على الطرفين وورثتهما وخلفائهما<br>
                    • أي تعديل على هذا العقد يجب أن يكون مكتوباً وموقعاً من الطرفين<br>
                    • يخضع هذا العقد لأنظمة المملكة العربية السعودية<br>
                    • تم تحرير هذا العقد من نسختين، لكل طرف نسخة للعمل بموجبها
                </div>
            </div>
        </div>

        <!-- التواقيع -->
        <div class="signature-section">
            <div class="signature-party">
                <h5>الطرف الأول</h5>
                <p><strong>شركة سما البنيان للتطوير والاستثمار</strong></p>
                <div style="height: 80px; border-bottom: 1px solid #000; margin: 20px 0;"></div>
                <p>التوقيع: ___________________</p>
                <p>التاريخ: <?= date('Y/m/d') ?></p>
            </div>
            
            <div class="signature-party">
                <h5>الطرف الثاني</h5>
                <p><strong><?= htmlspecialchars($contract['client_name']) ?></strong></p>
                <div style="height: 80px; border-bottom: 1px solid #000; margin: 20px 0;"></div>
                <p>التوقيع: ___________________</p>
                <p>التاريخ: <?= date('Y/m/d') ?></p>
            </div>
        </div>
    </div>

    <script>
        // Auto print functionality for PDF generation
        if (window.location.search.includes('print=1')) {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        }
    </script>
</body>
</html>