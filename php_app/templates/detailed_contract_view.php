<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض العقد المفصل - نظام إدارة العقود سما</title>
    <link rel="stylesheet" href="/static/css/glassmorphism.css">
    <style>
        .contract-document {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 40px;
            margin: 20px auto;
            max-width: 900px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            line-height: 1.8;
            font-family: 'Amiri', 'Cairo', serif;
        }

        .contract-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2c5530;
        }

        .contract-title {
            font-size: 28px;
            font-weight: bold;
            color: #2c5530;
            margin-bottom: 10px;
        }

        .contract-number {
            font-size: 20px;
            color: #666;
            margin-bottom: 15px;
        }

        .contract-content {
            font-size: 16px;
            line-height: 2;
            color: #333;
            text-align: justify;
        }

        .contract-content h3 {
            color: #2c5530;
            font-size: 18px;
            margin: 25px 0 15px 0;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .contract-content p {
            margin: 15px 0;
            text-align: justify;
            text-justify: inter-word;
        }

        .contract-parties {
            background: rgba(44, 85, 48, 0.05);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .party-info {
            margin: 15px 0;
            padding: 10px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 8px;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding-top: 30px;
            border-top: 1px solid #ddd;
        }

        .signature-box {
            width: 45%;
            text-align: center;
        }

        .signature-line {
            border-bottom: 2px solid #333;
            margin: 20px 0;
            height: 40px;
        }

        .print-controls {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .btn-print {
            background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            margin: 0 10px;
            transition: all 0.3s ease;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 85, 48, 0.4);
        }

        .btn-back {
            background: linear-gradient(135deg, #666 0%, #888 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            margin: 0 10px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 102, 102, 0.4);
        }

        .contract-clause {
            margin: 20px 0;
            padding: 15px;
            background: rgba(44, 85, 48, 0.02);
            border-right: 4px solid #2c5530;
            border-radius: 5px;
        }

        .clause-title {
            font-weight: bold;
            color: #2c5530;
            margin-bottom: 10px;
        }

        @media print {
            .print-controls {
                display: none;
            }
            
            .contract-document {
                box-shadow: none;
                border: none;
                background: white;
                margin: 0;
                padding: 20px;
            }
            
            body {
                background: white;
            }
        }

        @media (max-width: 768px) {
            .contract-document {
                padding: 20px;
                margin: 10px;
            }
            
            .signature-section {
                flex-direction: column;
            }
            
            .signature-box {
                width: 100%;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <?php if (!isset($_GET['print'])): ?>
        <div class="print-controls">
            <button onclick="window.print()" class="btn-print">طباعة العقد</button>
            <button onclick="window.open('?print=1', '_blank')" class="btn-print">معاينة الطباعة</button>
            <a href="/contracts" class="btn-back">العودة للقائمة</a>
        </div>
        <?php endif; ?>

        <div class="contract-document">
            <div class="contract-header">
                <div class="contract-title">بسم الله الرحمن الرحيم</div>
                <div class="contract-number">رقم العقد: <?= htmlspecialchars($contract['contract_number'] ?? 'غير محدد') ?></div>
                <div class="contract-number"><?= htmlspecialchars($detailedContract['contract_type'] ?? 'عقد عام') ?></div>
            </div>

            <div class="contract-content">
                <?php if ($detailedContract && $detailedContract['full_contract_text']): ?>
                    <div class="full-contract-text">
                        <?= nl2br(htmlspecialchars($detailedContract['full_contract_text'])) ?>
                    </div>
                <?php else: ?>
                    <!-- عرض العقد الأساسي إذا لم يتوفر النص المفصل -->
                    <h3>معلومات العقد الأساسية</h3>
                    
                    <div class="contract-parties">
                        <div class="party-info">
                            <strong>الطرف الأول:</strong> 
                            <?= htmlspecialchars($detailedContract['first_party_name'] ?? 'شركة سما البنيان التجارية') ?>
                            <br>
                            <strong>السجل التجاري:</strong> 
                            <?= htmlspecialchars($detailedContract['first_party_commercial_reg'] ?? '4030533070') ?>
                            <br>
                            <strong>المحافظة:</strong> 
                            <?= htmlspecialchars($detailedContract['first_party_city'] ?? 'جدة') ?>
                            <br>
                            <strong>الحي:</strong> 
                            <?= htmlspecialchars($detailedContract['first_party_district'] ?? 'الحمدانية') ?>
                            <br>
                            <strong>الممثل القانوني:</strong> 
                            <?= htmlspecialchars($detailedContract['first_party_representative'] ?? 'احمد عبدالله سعيد الزهراني') ?>
                        </div>

                        <div class="party-info">
                            <strong>الطرف الثاني:</strong> 
                            <?= htmlspecialchars($detailedContract['second_party_name'] ?? $contract['second_party_name'] ?? 'غير محدد') ?>
                            <br>
                            <strong>رقم الهوية:</strong> 
                            <?= htmlspecialchars($detailedContract['second_party_id'] ?? 'غير محدد') ?>
                            <br>
                            <strong>رقم الجوال:</strong> 
                            <?= htmlspecialchars($detailedContract['second_party_mobile'] ?? $contract['second_party_phone'] ?? 'غير محدد') ?>
                        </div>
                    </div>

                    <div class="contract-clause">
                        <div class="clause-title">البند الأول - نطاق العقد:</div>
                        <p><?= htmlspecialchars($contract['description'] ?? 'وصف العقد غير متوفر') ?></p>
                    </div>

                    <?php if ($detailedContract): ?>
                    <div class="contract-clause">
                        <div class="clause-title">البند الثاني - المبلغ والأرباح:</div>
                        <p>
                            مبلغ رأس المال: <?= number_format($detailedContract['capital_amount'] ?? 0) ?> ريال سعودي<br>
                            نسبة الأرباح: <?= htmlspecialchars($detailedContract['profit_percentage'] ?? '0') ?>%<br>
                            دورية تسليم الأرباح: كل <?= htmlspecialchars($detailedContract['profit_period_months'] ?? '6') ?> أشهر
                        </p>
                    </div>

                    <div class="contract-clause">
                        <div class="clause-title">البند الثالث - الانسحاب:</div>
                        <p>
                            مدة الإخطار المطلوبة: <?= htmlspecialchars($detailedContract['withdrawal_notice_days'] ?? '60') ?> يوماً
                        </p>
                    </div>

                    <div class="contract-clause">
                        <div class="clause-title">البند الرابع - الشرط الجزائي:</div>
                        <p>
                            قيمة الشرط الجزائي: <?= number_format($detailedContract['penalty_amount'] ?? 3000) ?> ريال سعودي<br>
                            عن كل <?= htmlspecialchars($detailedContract['penalty_period_days'] ?? '30') ?> يوم تأخير
                        </p>
                    </div>

                    <div class="contract-clause">
                        <div class="clause-title">البند الخامس - العمولة:</div>
                        <p>
                            نسبة العمولة عند التسويق: <?= htmlspecialchars($detailedContract['commission_percentage'] ?? '2.5') ?>%
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="contract-clause">
                        <div class="clause-title">البند الأخير - التواريخ:</div>
                        <p>
                            تاريخ البداية: <?= htmlspecialchars($contract['start_date'] ?? 'غير محدد') ?><br>
                            تاريخ النهاية: <?= htmlspecialchars($contract['end_date'] ?? 'غير محدد') ?><br>
                            <?php if ($detailedContract['hijri_date']): ?>
                            التاريخ الهجري: <?= htmlspecialchars($detailedContract['hijri_date']) ?><br>
                            <?php endif; ?>
                            <?php if ($detailedContract['location']): ?>
                            مكان التوقيع: <?= htmlspecialchars($detailedContract['location']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="signature-section">
                    <div class="signature-box">
                        <h4>توقيع الطرف الأول</h4>
                        <div class="signature-line"></div>
                        <p><strong><?= htmlspecialchars($detailedContract['first_party_name'] ?? 'شركة سما البنيان التجارية') ?></strong></p>
                        <p>التاريخ: _______________</p>
                    </div>

                    <div class="signature-box">
                        <h4>توقيع الطرف الثاني</h4>
                        <div class="signature-line"></div>
                        <p><strong>الاسم:</strong> <?= htmlspecialchars($detailedContract['second_party_name'] ?? $contract['second_party_name'] ?? '_______________') ?></p>
                        <p><strong>الهوية:</strong> <?= htmlspecialchars($detailedContract['second_party_id'] ?? '_______________') ?></p>
                        <p><strong>رقم الجوال:</strong> <?= htmlspecialchars($detailedContract['second_party_mobile'] ?? '_______________') ?></p>
                        <p>التاريخ: _______________</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // إضافة تاريخ اليوم للطباعة
        window.addEventListener('beforeprint', function() {
            const today = new Date().toLocaleDateString('ar-SA');
            const dateFields = document.querySelectorAll('.auto-date');
            dateFields.forEach(field => {
                if (field.textContent.includes('_______________')) {
                    field.textContent = field.textContent.replace('_______________', today);
                }
            });
        });

        // إضافة رقم الصفحة للطباعة
        window.addEventListener('beforeprint', function() {
            const style = document.createElement('style');
            style.textContent = `
                @page {
                    margin: 2cm;
                    @bottom-center {
                        content: "صفحة " counter(page) " من " counter(pages);
                        font-family: 'Cairo', sans-serif;
                        font-size: 12px;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>