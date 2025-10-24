<?php
/**
 * قالب عقد الاستثمار - شركة سما البنيان التجارية
 * نموذج عقد المضاربة في العقارات
 */

class InvestmentContractTemplate {
    
    public static function generateContractHTML($contractData) {
        $contractNumber = $contractData['contract_number'] ?? 'INV-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $hijriDate = $contractData['hijri_date'] ?? '15-4-1447هـ';
        $investorName = $contractData['investor_name'] ?? '***************';
        $investorId = $contractData['investor_id'] ?? '***********';
        $investorPhone = $contractData['investor_phone'] ?? '**************';
        $investorEmail = $contractData['investor_email'] ?? '............';
        $investorAddress = $contractData['investor_address'] ?? 'جدة حي الروضة شارع صاري';
        $investmentAmount = $contractData['investment_amount'] ?? '100,000';
        $profitPercentage = $contractData['profit_percentage'] ?? '40';
        $commissionRate = $contractData['commission_rate'] ?? '2.5';
        $penaltyAmount = $contractData['penalty_amount'] ?? '3,000';
        $contractDuration = $contractData['contract_duration'] ?? '6';
        $startDate = $contractData['start_date'] ?? '15-4-1447هـ';
        $endDate = $contractData['end_date'] ?? '15-10-1447هـ';
        
        return '
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عقد استثمار - ' . $contractNumber . '</title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        body {
            font-family: "Arial", "Tahoma", sans-serif;
            font-size: 12px;
            line-height: 1.6;
            direction: rtl;
            text-align: right;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #253355;
            padding-bottom: 20px;
        }
        .company-logo {
            font-size: 24px;
            font-weight: bold;
            color: #253355;
            margin-bottom: 10px;
        }
        .contract-title {
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0;
            color: #253355;
        }
        .contract-number {
            background: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
            margin: 15px 0;
            text-align: center;
        }
        .parties-section {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-right: 4px solid #253355;
        }
        .article {
            margin: 20px 0;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .article-title {
            font-weight: bold;
            color: #253355;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .article-content {
            text-align: justify;
            line-height: 1.8;
        }
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-party {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .signature-party:first-child {
            border-left: none;
        }
        .signature-party:last-child {
            border-right: none;
        }
        .signature-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
            color: #253355;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin: 10px 0;
            height: 50px;
        }
        .amount-highlight {
            background: #fff3cd;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
        }
        .important-text {
            color: #dc3545;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <!-- الترويسة -->
    <div class="header">
        <div class="company-logo">شركة سما البنيان التجارية</div>
        <div>للتطوير والاستثمار العقاري</div>
        <div class="contract-title">عقد استثمار ومضاربة في العقارات</div>
    </div>

    <div class="contract-number">
        <strong>رقم العقد: ' . $contractNumber . '</strong><br>
        <strong>التاريخ: ' . $hijriDate . '</strong>
    </div>

    <!-- البسملة -->
    <div style="text-align: center; font-size: 16px; font-weight: bold; margin: 20px 0;">
        بسم الله الرحمن الرحيم
    </div>

    <!-- المقدمة -->
    <div class="article">
        <div class="article-content">
            بعون الله وتوفيقه تم الاتفاق يوم الجمعة الموافق ' . $hijriDate . ' وفي محافظة جدة بين كل من:
        </div>
    </div>

    <!-- أطراف العقد -->
    <div class="parties-section">
        <div style="margin-bottom: 15px;">
            <strong>1- السادة / شركة سما البنيان التجارية</strong><br>
            سجل تجاري رقم: 4030533070<br>
            محافظة: جدة بحي: الحمدانية<br>
            ويمثلها في التوقيع السيد / احمد عبد الله سعيد الزهراني<br>
            سعودي الجنسية رقم الجوال: (0537295224)<br>
            وعنوان الشركة جدة - حي الحمدانية – شارع ياسر بن عامر<br>
            <strong>ويشار إليها في العقد بالطرف الأول.</strong>
        </div>
        
        <div>
            <strong>2- السيد/ة: ' . $investorName . '</strong><br>
            سعودي الجنسية بموجب السجل المدني رقم ' . $investorId . '<br>
            جوال رقم: (' . $investorPhone . ')، بريد الكتروني: (' . $investorEmail . ')<br>
            وعنوانه مدينة: ' . $investorAddress . '<br>
            <strong>ويشار إليه في العقد بالطرف الثاني.</strong>
        </div>
    </div>

    <!-- التمهيد -->
    <div class="article">
        <div class="article-title">تمهيــــــــد</div>
        <div class="article-content">
            ولما كان الطرف الأول شركة عقارية مؤهلة بترخيص من الهيئة العامة للعقار للبيع والتأجير على الخارطة وترخيص التأهيل للمطور العقاري وتعمل في مجال التطوير العقاري للفلل السكنية وشقق التمليك والمجمعات التجارية وتشغيل وإدارة محطات الوقود والمشاريع التجارية والسكنية ولها خبرة وممارسة في هذا المجال بالإنشاء والبيع والتأجير وادارة المحافظ العقارية.
        </div>
    </div>

    <!-- البنود -->
    <div class="article">
        <div class="article-title">البند الأول: التمهيد</div>
        <div class="article-content">
            يعتبر التمهيد والمقدمة أعلاه جزء لا يتجزأ من هذا العقد، وأن البيانات والعناوين الموضحة في المقدمة منتجة لآثارها النظامية.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند الثاني: حدود العقد</div>
        <div class="article-content">
            يُعد محل هذا العقد مشروعًا تجاريًا قابلًا للربح والخسارة وبموجب هذا العقد اتفق الطرفان على التزام الطرف الأول بصفته شركة متخصصة في مجال التطوير العقاري في فتح باب المشاركة في العقارات بطريق المضاربة وقد رغب الطرف الثاني الدخول والاستثمار والمضاربة على أن تكون المضاربة في العقارات التي يملكها الطرف الأول او التي يملك فيها حصصاً أياً كانت نسبة الطرف الأول في العقار.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند الثالث: مبلغ المضاربة</div>
        <div class="article-content">
            اتفق الطرفان على ان مبلغ المضاربة المقدم من الطرف الثاني بمبلغ وقدره <span class="amount-highlight">(' . $investmentAmount . ') ريال سعودي</span>، يلتزم الطرف الثاني ويقر الطرف الأول باستلامه للعقار.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند الرابع: استرداد رأس المال</div>
        <div class="article-content">
            اتفق الطرفان على استرداد مبلغ رأس المال يكون بعد ست أشهر من بداية العقد، وقبل انسحابه من المشروع بشرط الإخطار كتابةً بـ <span class="important-text">(60) يومًا على الأقل</span>، وفي حال طلب الانسحاب يعد الإشعار معتمدًا إذا تم تسليمه كتابةً أو على رقم الجوال المحددين في العقد.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند الخامس: الأرباح</div>
        <div class="article-content">
            اتفق الطرفان على أن نسبة متوسط الأرباح للطرف الثاني هي <span class="amount-highlight">' . $profitPercentage . '% من قيمة رأس المال</span>، ويلتزم الطرف الأول بسداد نصيب الطرف الثاني من الأرباح خلال <span class="important-text">15 يوماً من انتهاء المدة المتفق عليها</span> كحد أقصى.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند السادس: الخسائر</div>
        <div class="article-content">
            يقر الطرف الثاني بأنه في حال خسارة المشروع لأي سبب كان فإنه يتحمل نسبة من الخسارة مساوية لنسبته في المشروع مالم تكن الخسارة ناتجة عن تقصير او اهمال من الطرف الأول.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند السابع: إدارة المشروع</div>
        <div class="article-content">
            ادرك الطرف الثاني مدى قدرة الطرف الأول الإدارية وفريق عمله الذي يعمل لديه وتحت إدارته، ووافق الطرف الثاني على إدارة الطرف الأول، وأدرك ان تكون إدارة المشروع بالكامل من صلاحيات ومسؤوليات الطرف الأول، وذلك على سبيل المثال لا الحصر قيام الطرف الأول بتعيين وعزل العمال والمهندسين وبيع الوحدات السكنية وتحديد موقع المشروع وغيرها من صلاحيات الطرف الأول، وليس للطرف الثاني الحق او التدخل او الاعتراض على شيء من ذلك.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند الثامن: الوفاة</div>
        <div class="article-content">
            اتفق الطرفان إنه بموجب هذا العقد وفي حالة وفاة الطرف الثاني -لا سمح الله- يتم انتقال النسبة الخاصة به إلى ورثته، وتسري بنود هذا العقد على الورثة دون أي استثناء، ولا يحق لورثة الطرف الثاني الاعتراض على أي بند من بنود هذا العقد.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند التاسع: مدة العقد</div>
        <div class="article-content">
            مدة هذه الشراكة <span class="amount-highlight">(' . $contractDuration . ') أشهر</span> تبدأ من تاريخ ' . $startDate . ' وتنتهي ' . $endDate . '، واتفق الطرفان ان هذه المدة قابلة للتجديد لمدة أو مدد أخرى مماثلة.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند العاشر: العمولة</div>
        <div class="article-content">
            يحق للطرف الثاني الحصول على نسبة عمولة قدرها <span class="amount-highlight">' . $commissionRate . '%</span> عند التسويق للمحفظة ويكون ذلك بطلب من الطرف الثاني يبين فيه أسماء الأشخاص المشاركين عن طريقه.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند الحادي عشر: بطلان أحد البنود</div>
        <div class="article-content">
            في حال بطلان أي بند من بنود العقد، فإن ذلك لا يؤثر على صحة باقي البنود وتظل ملزمة للطرفين.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند الثاني عشر: القوة القاهرة</div>
        <div class="article-content">
            في حال حدوث كوارث طبيعية او جوائح كونية او أي قوة قاهرة فإنه يتم مراعاة ذلك من الطرفين، وفي حال نتج عن هذه القوة القاهرة توقف عن العمل في أي من المشاريع فإنه يتم احتساب ذلك ضمن مدة العقد، ولا يترتب على ذلك أي من مستحقات، ولا يحق لأي من الطرفين المطالبة بأي تعويضات مترتبة على ذلك.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند الثالث عشر: القانون والاختصاص</div>
        <div class="article-content">
            في حال نشوء أي نزاع بين الطرفين (لا قدر الله) حيال هذا العقد يحل بالطرق الودية وفي حال تعذر ذلك خلال أسبوعين، فينعقد الاختصاص للتحكيم وفقاً لأحكام نظام التحكيم في المملكة العربية السعودية ويكون التحكيم في مدينة جدة أو عبر الاتصال المرئي (عن بعد).
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند الرابع عشر: التعهد</div>
        <div class="article-content">
            يتعهد الطرفان بأنهما وقعا هذا العقد بكامل الأهلية الشرعية والنظامية وبعد الاطلاع والموافقة على جميع بنوده، ويعتبر التوقيع على هذا العقد من الطرفين بأنه مقروءً ومفهوماً ومعلوماً علماً نافياً للجهالة والغبن والغرر.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند الخامس عشر: الشرط الجزائي</div>
        <div class="article-content">
            في حال تأخير الطرف الأول في تسليم الأرباح في المواعيد المحددة في هذا العقد، يلتزم بدفع شرط جزائي قدره <span class="amount-highlight">(' . $penaltyAmount . ') ريال سعودي</span> عن كل (30) ثلاثين يوم تأخير.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند السادس عشر: تصديق العقد</div>
        <div class="article-content">
            تم إعداد هذا العقد والاطلاع عليه ومراجعته وتنقيحه من قبل مكتب المحامي بشير بن عبد الله صديق كنسارة.
        </div>
    </div>

    <div class="article">
        <div class="article-title">البند السابع عشر: نسخ العقد</div>
        <div class="article-content">
            حرر هذا العقد من نسختين أصليتين مكونه من (4) صفحات و (17) مادة وقع عليهما الطرفان بالرضا والإيجاب والقبول لما جاء فيهما من مواد، وتسلم كل طرف نسخة للعمل بموجبها.
        </div>
    </div>

    <!-- التوقيعات -->
    <div style="text-align: center; font-weight: bold; margin: 30px 0;">
        وعلى ما سبق جرى التعاقد والله خير الشاهدين
    </div>

    <div class="signature-section">
        <div class="signature-party">
            <div class="signature-title">الطرف الأول</div>
            <div><strong>شركة سما البنيان التجارية</strong></div>
            <div>للتطوير والاستثمار العقاري</div>
            <div>السجل التجاري: 4030533070</div>
            <div style="margin-top: 20px;">التوقيع:</div>
            <div class="signature-line"></div>
            <div style="margin-top: 10px;">الختم:</div>
            <div class="signature-line"></div>
        </div>
        
        <div class="signature-party">
            <div class="signature-title">الطرف الثاني</div>
            <div>الاسم: ' . $investorName . '</div>
            <div>الهوية الوطنية / الإقامة: ' . $investorId . '</div>
            <div>رقم الجوال: ' . $investorPhone . '</div>
            <div style="margin-top: 20px;">التوقيع:</div>
            <div class="signature-line"></div>
        </div>
    </div>

    <div class="footer">
        <div>© 2025 شركة سما البنيان التجارية - جميع الحقوق محفوظة</div>
        <div>رقم العقد: ' . $contractNumber . ' | تاريخ الإنشاء: ' . date('Y-m-d H:i:s') . '</div>
    </div>
</body>
</html>';
    }
}
?>