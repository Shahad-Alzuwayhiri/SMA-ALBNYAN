<?php

class SimplePdfService
{
    public function generateContractHtml($contract, $detailedContract = null)
    {
        // تحضير البيانات
        $data = $this->prepareData($contract, $detailedContract);
        
        // قالب HTML مع CSS للطباعة
        $html = $this->buildHtmlTemplate($data);
        
        return $html;
    }
    
    private function prepareData($contract, $detailedContract)
    {
        // إعداد البيانات الأساسية
        $data = [
            'contract_number' => $contract['contract_number'] ?? 'غير محدد',
            'client_name' => $contract['client_name'] ?? 'غير محدد',
            'client_id' => $contract['client_id'] ?? '________________',
            'client_phone' => $contract['client_phone'] ?? '________________',
            'amount' => number_format($contract['amount'] ?? 0, 2),
            'contract_date' => $contract['contract_date'] ?? date('Y-m-d'),
            'signature_method' => $this->getSignatureMethodText($contract['signature_method'] ?? ''),
            'contract_duration' => $contract['contract_duration'] ?? 12,
            'profit_interval' => $contract['profit_interval'] ?? 6,
            'notes' => $contract['notes'] ?? '',
            'hijri_date' => $this->getCurrentHijriDate($contract['contract_date'] ?? null),
        ];
        
        // إضافة بيانات مفصلة إن وجدت
        if ($detailedContract) {
            $data = array_merge($data, [
                'partner_name' => $detailedContract['partner_name'] ?? $data['client_name'],
                'partner_id' => $detailedContract['partner_id'] ?? $data['client_id'],
                'partner_phone' => $detailedContract['partner_phone'] ?? $data['client_phone'],
                'investment_amount' => number_format($detailedContract['investment_amount'] ?? $contract['amount'], 2),
                'profit_percent' => $detailedContract['profit_percent'] ?? 30,
                'profit_interval_months' => $detailedContract['profit_interval_months'] ?? $data['profit_interval'],
            ]);
        }
        
        return $data;
    }
    
    private function getSignatureMethodText($method)
    {
        $methods = [
            'handwritten' => 'توقيع يدوي',
            'digital' => 'توقيع رقمي',
            'witness' => 'بحضور شاهد'
        ];
        
        return $methods[$method] ?? 'غير محدد';
    }
    
    private function buildHtmlTemplate($data)
    {
        return <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عقد رقم {$data['contract_number']}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background: white;
            color: #253355;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .contract-document {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background: white;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #253355;
            padding-bottom: 25px;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #e8eaec 0%, #77bcc3 100%);
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 40px;
        }
        
        .company-logo {
            width: 120px;
            height: auto;
            margin: 0 auto 15px auto;
            display: block;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #253355;
            margin-bottom: 5px;
        }
        
        .company-subtitle {
            font-size: 16px;
            color: #9694ac;
            margin-bottom: 20px;
        }
        
        .contract-number {
            background: #253355;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            display: inline-block;
            font-size: 18px;
            font-weight: bold;
        }
        
        .contract-intro {
            background: #e8eaec;
            padding: 20px;
            border-right: 5px solid #77bcc3;
            margin: 25px 0;
            border-radius: 8px;
        }
        
        .parties-section {
            background: #f8f9fa;
            padding: 25px;
            margin: 25px 0;
            border-radius: 8px;
            border: 2px solid #e8eaec;
        }
        
        .contract-content {
            margin: 30px 0;
            text-align: justify;
            line-height: 1.8;
        }
        
        .contract-content p {
            margin: 15px 0;
        }
        
        .contract-content .clause {
            margin: 20px 0;
            padding: 15px 0;
        }
        
        .clause-title {
            font-weight: bold;
            color: #253355;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .signatures {
            margin-top: 60px;
            display: table;
            width: 100%;
        }
        
        .signature-container {
            display: table-row;
        }
        
        .signature-box {
            display: table-cell;
            text-align: center;
            width: 50%;
            padding: 20px;
            vertical-align: top;
        }
        
        .signature-line {
            border-bottom: 2px solid #253355;
            height: 80px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .signature-title {
            font-weight: bold;
            color: #253355;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .signature-details {
            color: #9694ac;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e8eaec;
            color: #9694ac;
            font-size: 12px;
        }
        
        @media print {
            body {
                margin: 0;
                font-size: 12px;
            }
            
            .contract-document {
                margin: 0;
                padding: 15mm;
            }
            
            @page {
                margin: 10mm;
                size: A4;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="contract-document">
        <div class="header">
            <div class="company-name">سما البنيان</div>
            <div class="company-name" style="font-size: 20px; color: #77bcc3;">SMA ALBNYAN</div>
            <div class="company-subtitle">للتطوير والاستثمار العقاري</div>
            <div class="contract-number">رقم العقد: {$data['contract_number']}</div>
        </div>
        
        <div class="contract-content">
            <p style="text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 30px;">
                <strong>بسم الله الرحمن الرحيم</strong>
            </p>
            
            <div class="contract-intro">
                <p style="font-weight: bold;">بموجب عون الله وتوفيقه تم الاتفاق يوم الأربعاء في محافظة جدة بتاريخ {$data['hijri_date']} بين كل من:</p>
            </div>
            
            <div class="parties-section">
                <p><strong>1- السادة / شركة سما البنيان التجارية</strong></p>
                <p>سجل تجاري رقم: 4030533070، محافظة جدة – حي الحمدانية – شارع ياسر بن عامر سعودي الجنسية رقم الجوال: 0555123456</p>
                <p>ويمثلها السيد / أحمد عبدالله سعيد الزهراني، ويشار إليها في هذا العقد بـ <strong>الطرف الأول</strong>.</p>
                
                <p><strong>2- السيد / {$data['client_name']}</strong> سعودي الجنسية بموجب السجل المدني رقم {$data['client_id']}</p>
                <p>وعنوانه: المملكة العربية السعودية، جوال رقم: ({$data['client_phone']})</p>
                <p>ويشار إليه في هذا العقد بـ <strong>الطرف الثاني</strong>.</p>
                <p>_____________________________________________________________________</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند الأول: التمهيد</div>
                <p>لما كان الطرف الأول شركة عقارية مؤهلة بترخيص من الهيئة العامة للعقار للبيع والتأجير على الخارطة، وتمتلك خبرة وممارسة في مجال التطوير العقاري للمطورين العقاريين، وبالإنشاء والبيع والتأجير وإدارة المحافظ العقارية والفلل السكنية وشقق التمليك والمجمعات التجارية.</p>
                <p>يُعتبر هذا التمهيد والمقدمة أعلاه جزءًا لا يتجزأ من هذا العقد، والبيانات والعناوين الواردة فيه منتجة لآثارها النظامية.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند الثاني: حدود العقد</div>
                <p>بموجب هذا العقد اتفق الطرفان على التزام الطرف الأول بصفته شركة متخصصة في مجال التطوير العقاري بفتح باب المشاركة في العقارات بطريق المضاربة، وقد رغب الطرف الثاني بالدخول في الاستثمار والمضاربة بمبلغ وقدره (<strong>{$data['amount']} ريال</strong>) في العقارات التي يملكها الطرف الأول أو يشارك فيها، وتكون نسبة الربح والخسارة بحسب الحصص المقررة.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند الثالث: مبلغ المضاربة</div>
                <p>اتفق الطرفان على أن مبلغ المضاربة المقدم من الطرف الثاني هو مبلغ وقدره (<strong>{$data['amount']} ريال</strong>).</p>
                <p>ويلتزم الطرف الثاني بسداد هذا المبلغ عند توقيع هذه الاتفاقية وذلك بإحدى الطرق التالية:</p>
                <p>• عن طريق شيك رقم ............ وتاريخ ............ مسحوب على البنك .................</p>
                <p>• أو عن طريق حوالة بنكية رقم مرجعي ................ بتاريخ ................. من البنك .................</p>
                <p>ويقر الطرف الأول باستلامه للمبلغ.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند الرابع: استرداد رأس المال</div>
                <p>اتفق الطرفان على أن استرداد مبلغ رأس المال يكون بعد ستة أشهر من بداية العقد، ويحق للطرف الثاني طلب الانسحاب بعد إخطار كتابي قبل (60) يومًا على الأقل، ويُعد الإشعار معتمدًا إذا تم تسليمه كتابةً على رقم الجوال المحدد في العقد.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند الخامس: الأرباح</div>
                <p>اتفق الطرفان على أن نسبة أرباح الطرف الثاني هي <strong>30٪</strong> من قيمة رأس المال كل <strong>6 أشهر</strong>، ويلتزم الطرف الأول بسداد نصيب الطرف الثاني من الأرباح خلال 15 يومًا من انتهاء المدة المتفق عليها.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند السادس: الخسائر</div>
                <p>يقر الطرف الثاني بأنه في حال تعرض المشروع لأي خسارة لأي سبب كان، فإنه يتحمل نسبة من الخسارة مساوية لنسبته في المشروع، مالم تكن الخسارة ناتجة عن تقصير أو إهمال من الطرف الأول.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند السابع: إدارة المشروع</div>
                <p>أدرك الطرف الثاني ووافق على أن إدارة المشروع بالكامل من صلاحيات ومسؤوليات الطرف الأول، وتحت إدارته المباشرة، وله صلاحية تعيين وعزل العمال والمهندسين وبيع الوحدات السكنية وتحديد موقع المشروع وغيرها من القرارات التنفيذية، دون أن يكون للطرف الثاني حق التدخل أو الاعتراض.</p>
                <p>وللطرف الأول كامل الصلاحية في توقيع العقود والتصرف برأس المال ضمن حدود المصروفات اللازمة لإنجاح المشروع.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند الثامن: الوفاة</div>
                <p>اتفق الطرفان أنه في حال وفاة الطرف الثاني – لا سمح الله – تنتقل نسبته إلى ورثته، وتسري جميع بنود هذا العقد على الورثة دون أي استثناء، ولا يحق لهم الاعتراض على أي بند.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند التاسع: مسؤولية الطرف الأول</div>
                <p>يلتزم الطرف الأول بإدارة المشاريع بالطريقة المهنية المناسبة، وبذل كافة الجهود لإنجاح المشروع وتحقيق أعلى الأرباح، ويلتزم بإطلاع الطرف الثاني على سير المشروع كل ثلاثة أشهر.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند العاشر: مسؤولية الطرف الثاني</div>
                <p>يلتزم الطرف الثاني بعدم التدخل في إدارة المشروع وترك كامل الصلاحية للطرف الأول، وعدم الاعتراض على القرارات المتخذة من الطرف الأول طالما أنها في مصلحة المشروع.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند الحادي عشر: فض النزاع</div>
                <p>في حالة نشوء أي نزاع بين الطرفين يتم حل النزاع وديًا أولاً، وفي حالة عدم الوصول لحل ودي يتم اللجوء للجهات القضائية المختصة في المملكة العربية السعودية.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند الثاني عشر: تعديل العقد</div>
                <p>لا يجوز تعديل أي بند من بنود هذا العقد إلا بموافقة كتابية من الطرفين وتوثيق التعديل رسميًا.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند الثالث عشر: إنهاء العقد</div>
                <p>ينتهي هذا العقد بإحدى الحالات التالية:</p>
                <p>• انتهاء مدة العقد المحددة</p>
                <p>• طلب أحد الطرفين إنهاء العقد مع الإخطار المسبق</p>
                <p>• انتهاء المشروع وتوزيع كامل الأرباح</p>
                <p>• وفاة أحد الطرفين</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند الرابع عشر: القانون الحاكم</div>
                <p>يخضع هذا العقد لأنظمة المملكة العربية السعودية، وتختص المحاكم السعودية بالنظر في أي نزاع قد ينشأ عن هذا العقد.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند الخامس عشر: سريان العقد</div>
                <p>يسري هذا العقد اعتبارًا من تاريخ توقيعه من الطرفين واستلام الطرف الأول لمبلغ المضاربة كاملاً.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند السادس عشر: إقرار واعتراف</div>
                <p>يقر الطرف الثاني بأنه قرأ هذا العقد قراءة واعية وفهم جميع بنوده وشروطه، ووافق عليها موافقة تامة، وأنه وقع عليه بكامل إرادته دون إكراه أو ضغط من أي نوع.</p>
            </div>
            
            <div class="clause">
                <div class="clause-title">البند السابع عشر: التوقيعات</div>
                <p>يعتبر التوقيع على هذا العقد بمثابة موافقة تامة ونهائية من الطرفين على جميع الشروط الواردة في هذا العقد، وقد وُقِّع هذا العقد من نسختين أصليتين، لكل طرف نسخة للعمل بموجبها عند الحاجة.</p>
            </div>
        </div>
        
        <div class="signatures">
            <div class="signature-container">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">توقيع الطرف الأول</div>
                    <div class="signature-details">
                        شركة سما البنيان التجارية<br>
                        أحمد عبدالله سعيد الزهراني
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">توقيع الطرف الثاني</div>
                    <div class="signature-details">
                        الاسم: {$data['client_name']}<br>
                        الهوية: {$data['client_id']}<br>
                        رقم جوال: {$data['client_phone']}<br>
                        طريقة التوقيع: {$data['signature_method']}<br>
                        التوقيع: ________________
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            هذا العقد مُولد إلكترونياً من نظام إدارة العقود - شركة سما البنيان التجارية<br>
            جدة - الحمدانية | سجل تجاري: 4030533070
        </div>
        
        <div class="no-print" style="text-align: center; margin: 30px 0;">
            <button onclick="downloadPDF()" style="background: #253355; color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;">
                📄 تنزيل PDF
            </button>
            <button onclick="window.print()" style="background: #77bcc3; color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; margin-right: 10px;">
                🖨️ طباعة
            </button>
        </div>
    </div>
    
    <script>
        // إضافة وظيفة تنزيل PDF تلقائياً
        function downloadPDF() {
            // إخفاء عناصر الطباعة
            document.querySelectorAll('.no-print').forEach(el => el.style.display = 'none');
            
            // طباعة الصفحة (حفظ كـ PDF)
            window.print();
            
            // إظهار عناصر الطباعة مرة أخرى
            setTimeout(() => {
                document.querySelectorAll('.no-print').forEach(el => el.style.display = 'block');
            }, 1000);
        }
        
        // تفعيل التنزيل عند الضغط على Ctrl+P
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                downloadPDF();
            });
        
        // إضافة تلميح للمستخدم
        window.addEventListener('load', function() {
            setTimeout(() => {
                const hint = document.createElement('div');
                hint.innerHTML = '💡 لتنزيل العقد كملف PDF: اضغط على "تنزيل PDF" أو Ctrl+P ثم اختر "حفظ كـ PDF"';
                hint.style.cssText = 'position: fixed; top: 10px; left: 50%; transform: translateX(-50%); background: #77bcc3; color: white; padding: 10px 20px; border-radius: 5px; z-index: 1000; font-size: 14px;';
                document.body.appendChild(hint);
                
                setTimeout(() => hint.remove(), 5000);
            }, 2000);
        });
    </script>
</body>
</html>
HTML;
    }
    
    private function getCurrentHijriDate($contractDate = null)
    {
        // استخدام تاريخ العقد أو التاريخ الحالي
        $date = $contractDate ? strtotime($contractDate) : time();
        
        // تحويل تقريبي للتاريخ الهجري
        $gregorianYear = date('Y', $date);
        $gregorianMonth = date('n', $date);
        $gregorianDay = date('j', $date);
        
        // تحويل تقريبي (الفرق حوالي 579 سنة)
        $hijriYear = $gregorianYear - 579 + (($gregorianMonth > 6) ? 1 : 0);
        
        // أشهر هجرية
        $hijriMonths = [
            1 => 'محرم', 2 => 'صفر', 3 => 'ربيع الأول', 
            4 => 'ربيع الثاني', 5 => 'جمادى الأولى', 6 => 'جمادى الثانية',
            7 => 'رجب', 8 => 'شعبان', 9 => 'رمضان',
            10 => 'شوال', 11 => 'ذو القعدة', 12 => 'ذو الحجة'
        ];
        
        // تحويل تقريبي للشهر
        $hijriMonth = (($gregorianMonth + 1) % 12) + 1;
        $hijriMonthName = $hijriMonths[$hijriMonth];
        
        return "$gregorianDay $hijriMonthName $hijriYear هـ";
    }
}