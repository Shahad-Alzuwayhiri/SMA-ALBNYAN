<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استيراد عقد من النص - نظام إدارة العقود سما</title>
    <link rel="stylesheet" href="/assets/css/unified-theme.css">
    <style>
        .import-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .import-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c5530;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2c5530;
            display: flex;
            align-items: center;
        }

        .section-icon {
            width: 24px;
            height: 24px;
            margin-left: 10px;
            background: #2c5530;
            border-radius: 50%;
        }

        .contract-text-area {
            width: 100%;
            min-height: 500px;
            padding: 20px;
            border: 2px solid rgba(44, 85, 48, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            font-family: 'Amiri', 'Cairo', serif;
            font-size: 16px;
            line-height: 1.8;
            resize: vertical;
            transition: all 0.3s ease;
        }

        .contract-text-area:focus {
            outline: none;
            border-color: #2c5530;
            box-shadow: 0 0 15px rgba(44, 85, 48, 0.3);
            background: rgba(255, 255, 255, 0.95);
        }

        .extraction-preview {
            background: rgba(44, 85, 48, 0.05);
            border: 1px solid rgba(44, 85, 48, 0.2);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        .extracted-field {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            margin: 5px 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 5px;
        }

        .field-label {
            font-weight: bold;
            color: #2c5530;
            min-width: 150px;
        }

        .field-value {
            flex: 1;
            text-align: left;
            color: #333;
        }

        .btn-extract {
            background: linear-gradient(135deg, #4a7c59 0%, #2c5530 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 20px;
            cursor: pointer;
            margin: 10px 5px;
            transition: all 0.3s ease;
        }

        .btn-extract:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 85, 48, 0.3);
        }

        .btn-sample {
            background: linear-gradient(135deg, #666 0%, #888 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 15px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-sample:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(102, 102, 102, 0.3);
        }

        .submit-section {
            text-align: center;
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            margin-top: 30px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin: 0 10px;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(44, 85, 48, 0.4);
        }

        .btn-cancel {
            background: linear-gradient(135deg, #666 0%, #888 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-size: 18px;
            cursor: pointer;
            margin: 0 10px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102, 102, 102, 0.4);
        }

        .help-text {
            background: rgba(33, 150, 243, 0.1);
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
            line-height: 1.6;
        }

        .warning-text {
            background: rgba(255, 152, 0, 0.1);
            border-left: 4px solid #FF9800;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .import-container {
                padding: 10px;
            }
            
            .import-section {
                padding: 20px;
            }
            
            .contract-text-area {
                min-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="import-container">
            <div class="page-header">
                <h1>استيراد عقد من النص</h1>
                <p>الصق نص العقد الكامل وسيتم استخراج البيانات تلقائياً</p>
            </div>

            <form method="POST" action="/contracts/import-text" id="importForm">
                <!-- منطقة إدخال النص -->
                <div class="import-section">
                    <h3 class="section-title">
                        <span class="section-icon"></span>
                        نص العقد الكامل
                    </h3>
                    
                    <div class="help-text">
                        <strong>تعليمات:</strong>
                        <ul>
                            <li>الصق النص الكامل للعقد في المربع أدناه</li>
                            <li>تأكد من أن النص يحتوي على رقم العقد، أسماء الأطراف، والمبالغ</li>
                            <li>النظام سيحاول استخراج البيانات تلقائياً</li>
                            <li>يمكنك مراجعة البيانات المستخرجة قبل الحفظ</li>
                        </ul>
                    </div>
                    
                    <textarea 
                        id="contract_text" 
                        name="contract_text" 
                        class="contract-text-area"
                        placeholder="الصق نص العقد الكامل هنا..."
                        required
                    ></textarea>
                    
                    <div style="margin-top: 15px;">
                        <button type="button" class="btn-sample" onclick="loadSampleContract()">
                            تحميل عقد عينة للتجربة
                        </button>
                        <button type="button" class="btn-extract" onclick="extractData()">
                            استخراج البيانات من النص
                        </button>
                    </div>
                </div>

                <!-- معاينة البيانات المستخرجة -->
                <div class="import-section" id="extractionSection" style="display: none;">
                    <h3 class="section-title">
                        <span class="section-icon"></span>
                        البيانات المستخرجة
                    </h3>
                    
                    <div class="warning-text">
                        <strong>تنبيه:</strong> يرجى مراجعة البيانات المستخرجة والتأكد من صحتها قبل الحفظ. 
                        يمكنك تعديل أي بيانات مطلوبة بعد إنشاء العقد.
                    </div>
                    
                    <div id="extraction-preview" class="extraction-preview">
                        <!-- سيتم ملء البيانات المستخرجة هنا -->
                    </div>
                </div>

                <!-- أزرار الحفظ -->
                <div class="submit-section">
                    <button type="submit" class="btn-submit" id="submitBtn" disabled>
                        إنشاء عقد من النص المستورد
                    </button>
                    <a href="/contracts" class="btn-cancel">إلغاء</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // تحميل عقد عينة للتجربة
        function loadSampleContract() {
            const sampleText = `بسم الله الرحمن الرحيم
رقم العقد B123-1447

بعون الله وتوفيقه تم الاتفاق يوم الاربعاء الموافق 12-2-1447هـ وفي محافظة جدة بين كل من:

أولاً: (طرف أول) السادة / شركة سما البنيان التجارية سجل تجاري رقم :4030533070 محافظة : جدة بحي: الحمدانية ويمثلها في التوقيع السيد / احمد عبدالله سعيد الزهراني

ثانياً: (طرف ثان) السيد محمد أحمد العمري
الهوية: 1234567890
رقم الجوال: 0501234567

تمهيد
ولما كان الطرف الأول شركة عقارية مؤهلة بترخيص من الهيئة العامة للعقار للبيع والتأجير على الخارطة وترخيص للمساهمات العقارية وترخيص التأهيل للمطور العقاري و تعمل في مجال التطوير العقاري للفلل السكنية وشقق التمليك والمجمعات التجارية ولها خبرة وممارسة في هذا المجال بالإنشاء والبيع والتأجير وادارة المحافظ العقارية وكون الطرف الثاني يرغب المشاركة في العقارات والمضاربة مع الطرف الأول والدخول برأس ماله قدره (500,000) ريال سعودي وقد اتفق الطرفان وهما بكامل الأهلية الشرعية والنظامية:

البند الأول – التمهيد:
يعتبر التمهيد والمقدمة أعلاه جزء لا يتجزأ من هذا العقد، وأن البيانات والعناوين الموضحة في المقدمة منتجة لآثارها النظامية.

البند الثاني – نطاق العقد:
بموجب هذا العقد اتفق الطرفان على التزام الطرف الأول بصفته شركة متخصصة في مجال التطوير العقاري في فتح باب المشاركة في العقارات بطريق المضاربة وقد رغب الطرف الثاني الدخول والاستثمار بالمبلغ المتفق عليه أعلاه في هذا العقد على أن تكون المضاربة في العقارات التي يملكها الطرف الأول والتي يملك فيها حصصاً وتسليم الأرباح للطرف الثاني في مواعيدها المحددة حسب هذا العقد.

البند الثالث – استرداد رأس المال:
اتفق الطرفان على أن استرداد رأس المال يكون بعد استكمال المشروع أو بناءً على طلب الطرف الثاني بشرط الإخطار كتابةً قبل موعد السحب بـ (60) يومًا على الأقل.

البند الرابع – الأرباح:
اتفق الطرفان على أن النسبة التقريبية لأرباح الطرف الثاني هي 30% من قيمة رأس المال ، وتُسلم كل (6) أشهر.

البند الخامس – الشرط الجزائي:
في حال تأخير الطرف الأول في تسليم الأرباح في المواعيد المحددة في هذا العقد، يلتزم بدفع شرط جزائي قدره (3,000) ثلاثة آلاف ريال سعودي عن كل (30) ثلاثين يوم تأخير.`;

            document.getElementById('contract_text').value = sampleText;
            extractData();
        }

        // استخراج البيانات من النص
        function extractData() {
            const text = document.getElementById('contract_text').value;
            if (!text.trim()) {
                alert('يرجى إدخال نص العقد أولاً');
                return;
            }

            const extractedData = {
                contract_number: extractContractNumber(text),
                hijri_date: extractHijriDate(text),
                location: extractLocation(text),
                second_party_name: extractSecondPartyName(text),
                second_party_id: extractSecondPartyId(text),
                second_party_mobile: extractSecondPartyMobile(text),
                capital_amount: extractCapitalAmount(text),
                profit_percentage: extractProfitPercentage(text),
                withdrawal_notice_days: extractWithdrawalDays(text),
                penalty_amount: extractPenaltyAmount(text),
                profit_period_months: extractProfitPeriod(text)
            };

            displayExtractedData(extractedData);
            document.getElementById('extractionSection').style.display = 'block';
            document.getElementById('submitBtn').disabled = false;
        }

        // دوال استخراج البيانات
        function extractContractNumber(text) {
            const match = text.match(/رقم العقد[:\s]*([\w\-\d]+)/);
            return match ? match[1].trim() : 'غير محدد';
        }

        function extractHijriDate(text) {
            const match = text.match(/(\d{1,2}-\d{1,2}-\d{4}هـ)/);
            return match ? match[1] : 'غير محدد';
        }

        function extractLocation(text) {
            const match = text.match(/في\s+([^\s]+(?:\s+[^\s]+)*?)(?:\s+بين)/);
            return match ? match[1].trim() : 'غير محدد';
        }

        function extractSecondPartyName(text) {
            const match = text.match(/ثانياً[:\s]*\([^)]*\)[^/]*السيد\s+([^\n\r]+)/);
            return match ? match[1].trim() : 'غير محدد';
        }

        function extractSecondPartyId(text) {
            const match = text.match(/الهوية[:\s]*(\d+)/);
            return match ? match[1] : 'غير محدد';
        }

        function extractSecondPartyMobile(text) {
            const match = text.match(/(?:رقم الجوال|الجوال)[:\s]*([\d\+\-\s]+)/);
            return match ? match[1].trim() : 'غير محدد';
        }

        function extractCapitalAmount(text) {
            const match = text.match(/(\d{1,3}(?:,\d{3})*)\s*(?:ألف\s+)?ريال/);
            if (match) {
                return parseFloat(match[1].replace(/,/g, ''));
            }
            return 0;
        }

        function extractProfitPercentage(text) {
            const match = text.match(/(\d+(?:\.\d+)?)%/);
            return match ? parseFloat(match[1]) : 0;
        }

        function extractWithdrawalDays(text) {
            const match = text.match(/\((\d+)\)\s*يوم/);
            return match ? parseInt(match[1]) : 60;
        }

        function extractPenaltyAmount(text) {
            const match = text.match(/جزائي قدره[^(]*\(([^)]*)\)/);
            if (match) {
                const numberMatch = match[1].match(/(\d{1,3}(?:,\d{3})*)/);
                return numberMatch ? parseFloat(numberMatch[1].replace(/,/g, '')) : 0;
            }
            return 0;
        }

        function extractProfitPeriod(text) {
            const match = text.match(/كل\s*\((\d+)\)\s*أشهر/);
            return match ? parseInt(match[1]) : 6;
        }

        // عرض البيانات المستخرجة
        function displayExtractedData(data) {
            const previewDiv = document.getElementById('extraction-preview');
            let html = '';

            for (const [key, value] of Object.entries(data)) {
                const label = getFieldLabel(key);
                const formattedValue = formatFieldValue(key, value);
                html += `
                    <div class="extracted-field">
                        <span class="field-label">${label}:</span>
                        <span class="field-value">${formattedValue}</span>
                    </div>
                `;
            }

            previewDiv.innerHTML = html;
        }

        function getFieldLabel(key) {
            const labels = {
                contract_number: 'رقم العقد',
                hijri_date: 'التاريخ الهجري',
                location: 'مكان التوقيع',
                second_party_name: 'اسم الطرف الثاني',
                second_party_id: 'رقم الهوية',
                second_party_mobile: 'رقم الجوال',
                capital_amount: 'مبلغ رأس المال',
                profit_percentage: 'نسبة الأرباح',
                withdrawal_notice_days: 'مدة إشعار الانسحاب',
                penalty_amount: 'مبلغ الشرط الجزائي',
                profit_period_months: 'دورة الأرباح'
            };
            return labels[key] || key;
        }

        function formatFieldValue(key, value) {
            if (key === 'capital_amount' || key === 'penalty_amount') {
                return value ? `${value.toLocaleString()} ريال سعودي` : 'غير محدد';
            }
            if (key === 'profit_percentage') {
                return value ? `${value}%` : 'غير محدد';
            }
            if (key === 'withdrawal_notice_days') {
                return value ? `${value} يوماً` : 'غير محدد';
            }
            if (key === 'profit_period_months') {
                return value ? `${value} أشهر` : 'غير محدد';
            }
            return value || 'غير محدد';
        }

        // التحقق من صحة النموذج
        document.getElementById('importForm').addEventListener('submit', function(e) {
            const contractText = document.getElementById('contract_text').value;
            if (!contractText.trim()) {
                e.preventDefault();
                alert('يرجى إدخال نص العقد');
                return;
            }

            if (!confirm('هل أنت متأكد من إنشاء عقد جديد بناءً على النص المدخل؟')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>