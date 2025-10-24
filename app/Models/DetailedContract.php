<?php

require_once __DIR__ . '/../config/database.php';

class DetailedContract
{
    private $db;
    
    public function __construct()
    {
        $this->db = getDB();
    }
    
    /**
     * إnشاء عقد مفصل جديد
     * @return bool
     */
    public function create($contractId, $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO detailed_contracts (
                contract_id, contract_type, hijri_date, location,
                first_party_name, first_party_commercial_reg, first_party_city, 
                first_party_district, first_party_representative,
                second_party_name, second_party_id, second_party_mobile,
                capital_amount, profit_percentage, profit_period_months,
                withdrawal_notice_days, penalty_amount, penalty_period_days,
                commission_percentage, force_majeure_days,
                full_contract_text, contract_clauses
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $contractId,
            $data['contract_type'] ?? 'real_estate_speculation',
            $data['hijri_date'] ?? null,
            $data['location'] ?? null,
            $data['first_party_name'] ?? 'شركة سما البنيان التجارية',
            $data['first_party_commercial_reg'] ?? '4030533070',
            $data['first_party_city'] ?? 'جدة',
            $data['first_party_district'] ?? 'الحمدانية',
            $data['first_party_representative'] ?? 'احمد عبدالله سعيد الزهراني',
            $data['second_party_name'] ?? null,
            $data['second_party_id'] ?? null,
            $data['second_party_mobile'] ?? null,
            $data['capital_amount'] ?? 0,
            $data['profit_percentage'] ?? 30,
            $data['profit_period_months'] ?? 6,
            $data['withdrawal_notice_days'] ?? 60,
            $data['penalty_amount'] ?? 3000,
            $data['penalty_period_days'] ?? 30,
            $data['commission_percentage'] ?? 2.5,
            $data['force_majeure_days'] ?? 90,
            $data['full_contract_text'] ?? null,
            $data['contract_clauses'] ?? null
        ]);
    }
    
    /**
     * الحصول على تفاصيل العقد
     */
    public function getByContractId($contractId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM detailed_contracts 
            WHERE contract_id = ?
        ");
        $stmt->execute([$contractId]);
        return $stmt->fetch();
    }
    
    /**
     * تحديث العقد المفصل
     */
    public function update($contractId, $data)
    {
        $fields = [];
        $values = [];
        
        $allowedFields = [
            'contract_type', 'hijri_date', 'location',
            'first_party_name', 'first_party_commercial_reg', 'first_party_city',
            'first_party_district', 'first_party_representative',
            'second_party_name', 'second_party_id', 'second_party_mobile',
            'capital_amount', 'profit_percentage', 'profit_period_months',
            'withdrawal_notice_days', 'penalty_amount', 'penalty_period_days',
            'commission_percentage', 'force_majeure_days',
            'full_contract_text', 'contract_clauses'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $values[] = $contractId;
        
        $sql = "UPDATE detailed_contracts SET " . implode(', ', $fields) . " WHERE contract_id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($values);
    }
    
    /**
     * حذف العقد المفصل
     */
    public function deleteByContractId($contractId)
    {
        $stmt = $this->db->prepare("DELETE FROM detailed_contracts WHERE contract_id = ?");
        return $stmt->execute([$contractId]);
    }
    
    /**
     * إنشاء نص العقد الكامل
     */
    public function generateFullContractText($data)
    {
        $template = $this->getContractTemplate($data['contract_type'] ?? 'real_estate_speculation');
        
        // استبدال المتغيرات في القالب
        $replacements = [
            '{CONTRACT_NUMBER}' => $data['contract_number'] ?? '',
            '{HIJRI_DATE}' => $data['hijri_date'] ?? '',
            '{LOCATION}' => $data['location'] ?? '',
            '{FIRST_PARTY_NAME}' => $data['first_party_name'] ?? 'شركة سما البنيان التجارية',
            '{FIRST_PARTY_COMMERCIAL_REG}' => $data['first_party_commercial_reg'] ?? '4030533070',
            '{FIRST_PARTY_CITY}' => $data['first_party_city'] ?? 'جدة',
            '{FIRST_PARTY_DISTRICT}' => $data['first_party_district'] ?? 'الحمدانية',
            '{FIRST_PARTY_REPRESENTATIVE}' => $data['first_party_representative'] ?? 'احمد عبدالله سعيد الزهراني',
            '{SECOND_PARTY_NAME}' => $data['second_party_name'] ?? '[يُملأ لاحقاً]',
            '{SECOND_PARTY_ID}' => $data['second_party_id'] ?? '[يُملأ لاحقاً]',
            '{SECOND_PARTY_MOBILE}' => $data['second_party_mobile'] ?? '[يُملأ لاحقاً]',
            '{CAPITAL_AMOUNT}' => number_format($data['capital_amount'] ?? 0) . ' ريال سعودي',
            '{PROFIT_PERCENTAGE}' => $data['profit_percentage'] ?? '30',
            '{PROFIT_PERIOD_MONTHS}' => $data['profit_period_months'] ?? '6',
            '{WITHDRAWAL_NOTICE_DAYS}' => $data['withdrawal_notice_days'] ?? '60',
            '{PENALTY_AMOUNT}' => number_format($data['penalty_amount'] ?? 3000),
            '{PENALTY_PERIOD_DAYS}' => $data['penalty_period_days'] ?? '30',
            '{COMMISSION_PERCENTAGE}' => $data['commission_percentage'] ?? '2.5',
            '{FORCE_MAJEURE_DAYS}' => $data['force_majeure_days'] ?? '90',
            '{START_DATE}' => $data['start_date'] ?? '[يُحدد لاحقاً]',
            '{END_DATE}' => $data['end_date'] ?? '[يُحدد لاحقاً]'
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * الحصول على قالب العقد حسب النوع
     */
    private function getContractTemplate($contractType)
    {
        switch ($contractType) {
            case 'real_estate_speculation':
                return $this->getRealEstateSpeculationTemplate();
            case 'partnership':
                return $this->getPartnershipTemplate();
            case 'investment':
                return $this->getInvestmentTemplate();
            default:
                return $this->getRealEstateSpeculationTemplate();
        }
    }
    
    /**
     * قالب عقد المضاربة العقارية
     */
    private function getRealEstateSpeculationTemplate()
    {
        return "بسم الله الرحمن الرحيم

رقم العقد {CONTRACT_NUMBER}

بعون الله وتوفيقه تم الاتفاق يوم {HIJRI_DATE} وفي {LOCATION} بين كل من:

أولاً: (طرف أول) السادة / {FIRST_PARTY_NAME} سجل تجاري رقم: {FIRST_PARTY_COMMERCIAL_REG} محافظة: {FIRST_PARTY_CITY} بحي: {FIRST_PARTY_DISTRICT} ويمثلها في التوقيع السيد / {FIRST_PARTY_REPRESENTATIVE}

ثانياً: (طرف ثان) السيد {SECOND_PARTY_NAME}
الهوية: {SECOND_PARTY_ID}
رقم الجوال: {SECOND_PARTY_MOBILE}

تمهيد
ولما كان الطرف الأول شركة عقارية مؤهلة بترخيص من الهيئة العامة للعقار للبيع والتأجير على الخارطة وترخيص للمساهمات العقارية وترخيص التأهيل للمطور العقاري وتعمل في مجال التطوير العقاري للفلل السكنية وشقق التمليك والمجمعات التجارية ولها خبرة وممارسة في هذا المجال بالإنشاء والبيع والتأجير وادارة المحافظ العقارية وكون الطرف الثاني يرغب المشاركة في العقارات والمضاربة مع الطرف الأول والدخول برأس ماله قدره ({CAPITAL_AMOUNT}) وقد اتفق الطرفان وهما بكامل الأهلية الشرعية والنظامية:

البند الأول – التمهيد:
يعتبر التمهيد والمقدمة أعلاه جزء لا يتجزأ من هذا العقد، وأن البيانات والعناوين الموضحة في المقدمة منتجة لآثارها النظامية.

البند الثاني – نطاق العقد:
بموجب هذا العقد اتفق الطرفان على التزام الطرف الأول بصفته شركة متخصصة في مجال التطوير العقاري في فتح باب المشاركة في العقارات بطريق المضاربة وقد رغب الطرف الثاني الدخول والاستثمار بالمبلغ المتفق عليه أعلاه في هذا العقد على أن تكون المضاربة في العقارات التي يملكها الطرف الأول والتي يملك فيها حصصاً وتسليم الأرباح للطرف الثاني في مواعيدها المحددة حسب هذا العقد.

البند الثالث – استرداد رأس المال:
اتفق الطرفان على أن استرداد رأس المال يكون بعد استكمال المشروع أو بناءً على طلب الطرف الثاني بشرط الإخطار كتابةً قبل موعد السحب بـ ({WITHDRAWAL_NOTICE_DAYS}) يومًا على الأقل، ويعد الإشعار معتمدًا إذا تم تسليمه كتابةً أو عبر البريد الإلكتروني أو رقم الجوال المحددين في العقد.

البند الرابع – الأرباح:
اتفق الطرفان على أن النسبة التقريبية لأرباح الطرف الثاني هي {PROFIT_PERCENTAGE}% من قيمة رأس المال، وتُسلم كل ({PROFIT_PERIOD_MONTHS}) أشهر.

البند الخامس – الخسائر:
يُعد المشروع محل هذا العقد مشروعًا تجاريًا قابلًا للربح والخسارة. وفي حال الخسارة، يتم بيع جميع حصص الطرف الأول، وتوزع المبالغ على المشاركين بنسبة وتناسب مع حصصهم مع الاتفاق وديًا على آلية إعادة المبالغ للطرف الثاني.

البند السادس – إدارة المشروع:
اتفق الطرفان بإجماع على قيام الطرف الأول وحده بإدارة المشاريع وله في ذلك كافة السلطات اللازمة للإدارة والتوقيع على العقود وتعيين وعزل الموظفين والعمال والمهندسين وبيع الوحدات السكنية وتحديد موقع المشروع بما ترتضيه المصلحة العامة وذلك على سبيل المثال وليس الحصر.

البند السابع – الوفاة:
اتفق الطرفان إنه بموجب هذا العقد وفي حالة وفاة أحد الشركاء -لا سمح الله- يتم انتقال النسبة الخاصة بالشريك المتوفى إلى ورثته.

البند الثامن – مدة العقد:
تبدأ هذه الشراكة من تاريخ: {START_DATE} وتنتهي {END_DATE} قابلة للتجديد لمدة أخرى مماثلة.

البند التاسع – العمولة:
يحق للطرف الثاني الحصول على نسبة عمولة قدرها {COMMISSION_PERCENTAGE}% عند التسويق للمحفظة ويكون ذلك بطلب من الطرف الثاني يبين فيه أسماء الأشخاص المشاركين عن طريقه.

البند العاشر – الانسحاب:
اتفق الطرفان أنه في حال رغبة أحدهما بالانسحاب من الشراكة، عليه إشعار الطرف الآخر بذلك بشكل رسمي قبل مدة لا تقل عن ({WITHDRAWAL_NOTICE_DAYS}) يومًا، ويعد الإشعار معتمدًا إذا تم تسليمه كتابةً أو عبر البريد الإلكتروني أو رقم الجوال المحددين في العقد.

البند الحادي عشر – بطلان أحد البنود:
في حال بطلان أي بند من بنود العقد، فإن ذلك لا يؤثر على صحة باقي البنود وتظل ملزمة للطرفين.

البند الثاني عشر – القوة القاهرة:
في حال حدوث قوة قاهرة – لا قدر الله – تستمر لمدة تزيد عن ({FORCE_MAJEURE_DAYS}) يومًا، يلتزم الطرف الأول بإعادة رأس المال للطرف الثاني.

البند الثالث عشر – القانون والاختصاص:
يخضع هذا العقد للأنظمة القضائية في المملكة العربية السعودية.

البند الرابع عشر – التعهد:
يتعهد الطرفان بأنهما وقعا هذا العقد بكامل قواهما العقلية وبعد الاطلاع والموافقة على جميع بنوده.

البند الخامس عشر – التعويض عن الإهمال:
اتفق الطرفان أن الخسائر التي تنتج من الشراكة نتيجة إهمال الطرف الأول في الشركة وعمله والطرف الثاني في التأخير بتحويل المبلغ المشارك به، ينتج عنها إلزام هذا الشريك بتسديد تعويض للشريك الآخر.

البند السادس عشر – الشرط الجزائي:
في حال تأخير الطرف الأول في تسليم الأرباح في المواعيد المحددة في هذا العقد، يلتزم بدفع شرط جزائي قدره ({PENALTY_AMOUNT}) ريال سعودي عن كل ({PENALTY_PERIOD_DAYS}) يوم تأخير.


توقيع الطرف الأول                                                                                                 توقيع الطرف الثاني
{FIRST_PARTY_NAME}                                                       الاسم/ {SECOND_PARTY_NAME}
                                                                                                    الهوية/ {SECOND_PARTY_ID}
                                                                                 رقم جوال/ {SECOND_PARTY_MOBILE}
                                                                                       التوقيع/";
    }
    
    /**
     * قالب عقد الشراكة
     */
    private function getPartnershipTemplate()
    {
        return "بسم الله الرحمن الرحيم

عقد شراكة رقم {CONTRACT_NUMBER}

تم الاتفاق بتاريخ {HIJRI_DATE} في {LOCATION} بين:
الطرف الأول: {FIRST_PARTY_NAME}
الطرف الثاني: {SECOND_PARTY_NAME}

[يُكمل القالب حسب الحاجة]";
    }
    
    /**
     * قالب عقد الاستثمار
     */
    private function getInvestmentTemplate()
    {
        return "بسم الله الرحمن الرحيم

عقد استثمار رقم {CONTRACT_NUMBER}

تم الاتفاق بتاريخ {HIJRI_DATE} في {LOCATION} بين:
الطرف الأول: {FIRST_PARTY_NAME}
الطرف الثاني: {SECOND_PARTY_NAME}

[يُكمل القالب حسب الحاجة]";
    }
}

?>