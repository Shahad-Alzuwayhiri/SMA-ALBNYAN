<?php

require_once __DIR__ . '/../Models/Contract.php';
require_once __DIR__ . '/../Models/DetailedContract.php';

class DetailedContractController
{
    private $contractModel;
    private $detailedContractModel;
    
    public function __construct()
    {
        $this->contractModel = new Contract();
        $this->detailedContractModel = new DetailedContract();
    }
    
    /**
     * عرض نموذج إنشاء عقد مفصل
     */
    public function create()
    {
        // التحقق من المصادقة
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // تحميل قالب إنشاء العقد المفصل
        require_once __DIR__ . '/../templates/layout_helpers.php';
        
        ob_start();
        include __DIR__ . '/../templates/create_detailed_contract.php';
        $content = ob_get_clean();
        
        // استخدام تخطيط الصفحة الرئيسي
        return renderMasterLayout(
            $content,
            [],
            'إنشاء عقد مفصل - سما البنيان التجارية',
            false, // is_auth_page
            true, // show_sidebar
            '', // additional_head
            '' // additional_scripts
        );
    }
    
    /**
     * حفظ العقد المفصل
     */
    public function store()
    {
        // التحقق من المصادقة
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // التحقق من طريقة الطلب
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /contracts/create-detailed');
            exit;
        }
        
        try {
            // التحقق من البيانات المطلوبة
            $requiredFields = ['title', 'second_party_name', 'capital_amount'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("الحقل '{$field}' مطلوب");
                }
            }
            
            // إنشاء العقد الأساسي أولاً
            $contractData = [
                'title' => $_POST['title'],
                'second_party_name' => $_POST['second_party_name'],
                'second_party_phone' => $_POST['second_party_mobile'] ?? null,
                'second_party_email' => $_POST['second_party_email'] ?? null,
                'contract_amount' => $_POST['capital_amount'],
                'profit_percentage' => $_POST['profit_percentage'] ?? 30,
                'start_date' => $_POST['start_date'] ?? null,
                'end_date' => $_POST['end_date'] ?? null,
                'description' => $_POST['description'] ?? null,
                'terms_conditions' => $_POST['terms_conditions'] ?? null,
                'created_by' => $_SESSION['user_id'],
                'status' => 'draft',
                'contract_type' => $_POST['contract_type'] ?? 'real_estate_speculation',
                'hijri_date' => $_POST['hijri_date'] ?? null,
                'location' => $_POST['location'] ?? null,
                'is_detailed' => 1
            ];
            
            $contractId = $this->contractModel->create($contractData);
            
            if (!$contractId) {
                throw new Exception('فشل في إنشاء العقد الأساسي');
            }
            
            // إنشاء العقد المفصل
            $detailedData = [
                'contract_type' => $_POST['contract_type'] ?? 'real_estate_speculation',
                'hijri_date' => $_POST['hijri_date'] ?? null,
                'location' => $_POST['location'] ?? null,
                'first_party_name' => $_POST['first_party_name'] ?? 'شركة سما البنيان التجارية',
                'first_party_commercial_reg' => $_POST['first_party_commercial_reg'] ?? '4030533070',
                'first_party_city' => $_POST['first_party_city'] ?? 'جدة',
                'first_party_district' => $_POST['first_party_district'] ?? 'الحمدانية',
                'first_party_representative' => $_POST['first_party_representative'] ?? 'احمد عبدالله سعيد الزهراني',
                'second_party_name' => $_POST['second_party_name'],
                'second_party_id' => $_POST['second_party_id'] ?? null,
                'second_party_mobile' => $_POST['second_party_mobile'] ?? null,
                'capital_amount' => $_POST['capital_amount'],
                'profit_percentage' => $_POST['profit_percentage'] ?? 30,
                'profit_period_months' => $_POST['profit_period_months'] ?? 6,
                'withdrawal_notice_days' => $_POST['withdrawal_notice_days'] ?? 60,
                'penalty_amount' => $_POST['penalty_amount'] ?? 3000,
                'penalty_period_days' => $_POST['penalty_period_days'] ?? 30,
                'commission_percentage' => $_POST['commission_percentage'] ?? 2.5,
                'force_majeure_days' => $_POST['force_majeure_days'] ?? 90,
                'contract_clauses' => $_POST['contract_clauses'] ?? null,
                // سيتم إنشاء رقم العقد تلقائياً في نموذج العقد الأساسي
                'start_date' => $_POST['start_date'] ?? null,
                'end_date' => $_POST['end_date'] ?? null
            ];
            
            // إنشاء النص الكامل للعقد
            $detailedData['full_contract_text'] = $this->detailedContractModel->generateFullContractText($detailedData);
            
            $result = $this->detailedContractModel->create($contractId, $detailedData);
            
            if (!$result) {
                throw new Exception('فشل في إنشاء التفاصيل المتقدمة للعقد');
            }
            
            $_SESSION['success'] = 'تم إنشاء العقد المفصل بنجاح';
            header("Location: /contracts/view-detailed/{$contractId}");
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'حدث خطأ في إنشاء العقد: ' . $e->getMessage();
            header('Location: /contracts/create-detailed');
            exit;
        }
    }
    
    /**
     * عرض العقد المفصل
     */
    public function view($contractId)
    {
        // التحقق من المصادقة
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        try {
            // الحصول على العقد الأساسي
            $contract = $this->contractModel->findById($contractId);
            if (!$contract) {
                throw new Exception('العقد غير موجود');
            }
            
            // التحقق من الصلاحيات
            if ($_SESSION['user_role'] !== 'manager' && $contract['created_by'] != $_SESSION['user_id']) {
                throw new Exception('غير مصرح لك بعرض هذا العقد');
            }
            
            // الحصول على تفاصيل العقد المفصل
            $detailedContract = $this->detailedContractModel->getByContractId($contractId);
            
            // تحميل قالب عرض العقد المفصل
            require_once __DIR__ . '/../templates/layout_helpers.php';
            
            ob_start();
            include __DIR__ . '/../templates/detailed_contract_view.php';
            $content = ob_get_clean();
            
            // استخدام تخطيط الصفحة الرئيسي
            return renderMasterLayout(
                $content,
                [
                    'contract' => $contract,
                    'detailedContract' => $detailedContract
                ],
                'عرض العقد المفصل - سما البنيان التجارية',
                false, // is_auth_page
                true, // show_sidebar
                '', // additional_head
                '' // additional_scripts
            );
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /contracts');
            exit;
        }
    }
    
    /**
     * تحديث العقد المفصل
     */
    public function update($contractId)
    {
        // التحقق من المصادقة
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // التحقق من طريقة الطلب
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /contracts/view-detailed/{$contractId}");
            exit;
        }
        
        try {
            // الحصول على العقد للتحقق من الصلاحيات
            $contract = $this->contractModel->findById($contractId);
            if (!$contract) {
                throw new Exception('العقد غير موجود');
            }
            
            // التحقق من الصلاحيات
            if ($_SESSION['user_role'] !== 'manager' && $contract['created_by'] != $_SESSION['user_id']) {
                throw new Exception('غير مصرح لك بتعديل هذا العقد');
            }
            
            // تحديث العقد الأساسي
            $contractData = [
                'title' => $_POST['title'] ?? $contract['title'],
                'second_party_name' => $_POST['second_party_name'] ?? $contract['second_party_name'],
                'second_party_phone' => $_POST['second_party_mobile'] ?? $contract['second_party_phone'],
                'second_party_email' => $_POST['second_party_email'] ?? $contract['second_party_email'],
                'contract_amount' => $_POST['capital_amount'] ?? $contract['contract_amount'],
                'profit_percentage' => $_POST['profit_percentage'] ?? $contract['profit_percentage'],
                'start_date' => $_POST['start_date'] ?? $contract['start_date'],
                'end_date' => $_POST['end_date'] ?? $contract['end_date'],
                'description' => $_POST['description'] ?? $contract['description'],
                'terms_conditions' => $_POST['terms_conditions'] ?? $contract['terms_conditions']
            ];
            
            $this->contractModel->update($contractId, $contractData);
            
            // تحديث التفاصيل المتقدمة
            $detailedData = [];
            
            $detailedFields = [
                'contract_type', 'hijri_date', 'location',
                'first_party_name', 'first_party_commercial_reg', 'first_party_city',
                'first_party_district', 'first_party_representative',
                'second_party_name', 'second_party_id', 'second_party_mobile',
                'capital_amount', 'profit_percentage', 'profit_period_months',
                'withdrawal_notice_days', 'penalty_amount', 'penalty_period_days',
                'commission_percentage', 'force_majeure_days', 'contract_clauses'
            ];
            
            foreach ($detailedFields as $field) {
                if (isset($_POST[$field])) {
                    $detailedData[$field] = $_POST[$field];
                }
            }
            
            // إعادة إنشاء النص الكامل للعقد
            if (!empty($detailedData)) {
                $currentDetails = $this->detailedContractModel->getByContractId($contractId);
                $mergedData = array_merge($currentDetails ?: [], $detailedData);
                $detailedData['full_contract_text'] = $this->detailedContractModel->generateFullContractText($mergedData);
                
                $this->detailedContractModel->update($contractId, $detailedData);
            }
            
            $_SESSION['success'] = 'تم تحديث العقد بنجاح';
            header("Location: /contracts/view-detailed/{$contractId}");
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'حدث خطأ في تحديث العقد: ' . $e->getMessage();
            header("Location: /contracts/view-detailed/{$contractId}");
            exit;
        }
    }
    
    /**
     * حذف العقد المفصل
     */
    public function delete($contractId)
    {
        // التحقق من المصادقة
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        try {
            // الحصول على العقد للتحقق من الصلاحيات
            $contract = $this->contractModel->findById($contractId);
            if (!$contract) {
                throw new Exception('العقد غير موجود');
            }
            
            // التحقق من الصلاحيات (المدير فقط يستطيع الحذف)
            if ($_SESSION['user_role'] !== 'manager') {
                throw new Exception('غير مصرح لك بحذف العقود');
            }
            
            // حذف التفاصيل المتقدمة أولاً
            $this->detailedContractModel->deleteByContractId($contractId);
            
            // حذف العقد الأساسي
            $this->contractModel->delete($contractId);
            
            $_SESSION['success'] = 'تم حذف العقد بنجاح';
            header('Location: /contracts');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'حدث خطأ في حذف العقد: ' . $e->getMessage();
            header('Location: /contracts');
            exit;
        }
    }
    
    /**
     * تصدير العقد كـ PDF محسّن
     */
    public function exportPdf($contractId)
    {
        // التحقق من المصادقة
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        try {
            // الحصول على العقد وتفاصيله
            $contract = $this->contractModel->findById($contractId);
            $detailedContract = $this->detailedContractModel->getByContractId($contractId);
            
            if (!$contract) {
                throw new Exception('العقد غير موجود');
            }
            
            // التحقق من الصلاحيات
            if ($_SESSION['user_role'] !== 'manager' && $contract['created_by'] != $_SESSION['user_id']) {
                throw new Exception('غير مصرح لك بتصدير هذا العقد');
            }
            
            // إعداد بيانات العقد للـ PDF
            $contractData = $this->prepareContractDataForPdf($contract, $detailedContract);
            
            // إنشاء PDF باستخدام خدمة PDF المبسطة  
            require_once __DIR__ . '/../services/SimplePdfService.php';
            $pdfService = new SimplePdfService();
            
            // إنشاء HTML قابل للطباعة كـ PDF
            $htmlContent = $pdfService->generateContractHtml($contract, $detailedContract);
            
            // إعداد رؤوس HTML للطباعة
            $filename = 'contract_' . $contract['contract_number'] . '_' . date('Y-m-d') . '.html';
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            
            echo $htmlContent;
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'حدث خطأ في تصدير العقد: ' . $e->getMessage();
            header("Location: /contracts/view-detailed/{$contractId}");
            exit;
        }
    }
    
    /**
     * إعداد بيانات العقد للـ PDF
     */
    private function prepareContractDataForPdf($contract, $detailedContract)
    {
        return [
            'contract_number' => $contract['contract_number'],
            'contract_type' => $detailedContract['contract_type'] ?? 'real_estate_speculation',
            'hijri_date' => $detailedContract['hijri_date'] ?? date('d-m-Y') . 'هـ',
            'location' => $detailedContract['location'] ?? 'المملكة العربية السعودية',
            
            // الطرف الأول
            'first_party_name' => $detailedContract['first_party_name'] ?? 'شركة سما البنيان التجارية',
            'first_party_commercial_reg' => $detailedContract['first_party_commercial_reg'] ?? '4030533070',
            'first_party_city' => $detailedContract['first_party_city'] ?? 'جدة',
            'first_party_district' => $detailedContract['first_party_district'] ?? 'الحمدانية',
            'first_party_representative' => $detailedContract['first_party_representative'] ?? 'احمد عبدالله سعيد الزهراني',
            
            // الطرف الثاني
            'second_party_name' => $detailedContract['second_party_name'] ?? $contract['second_party_name'],
            'second_party_id' => $detailedContract['second_party_id'] ?? 'غير محدد',
            'second_party_mobile' => $detailedContract['second_party_mobile'] ?? $contract['second_party_phone'],
            
            // التفاصيل المالية
            'capital_amount' => $detailedContract['capital_amount'] ?? $contract['contract_amount'],
            'profit_percentage' => $detailedContract['profit_percentage'] ?? $contract['profit_percentage'],
            'profit_period_months' => $detailedContract['profit_period_months'] ?? 6,
            'withdrawal_notice_days' => $detailedContract['withdrawal_notice_days'] ?? 60,
            'penalty_amount' => $detailedContract['penalty_amount'] ?? 3000,
            'penalty_period_days' => $detailedContract['penalty_period_days'] ?? 30,
            'commission_percentage' => $detailedContract['commission_percentage'] ?? 2.5,
            'force_majeure_days' => $detailedContract['force_majeure_days'] ?? 90,
            
            // التواريخ
            'start_date' => $contract['start_date'],
            'end_date' => $contract['end_date'],
            
            // النص الكامل
            'full_contract_text' => $detailedContract['full_contract_text'] ?? '',
            'description' => $contract['description'] ?? '',
            'terms_conditions' => $contract['terms_conditions'] ?? ''
        ];
    }
    
    /**
     * تصدير نسخة HTML (احتياطية)
     */
    private function exportHtmlVersion($contract, $detailedContract)
    {
        $filename = 'contract_' . $contract['contract_number'] . '_' . date('Y-m-d') . '.html';
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // إنشاء محتوى HTML للعقد
        ob_start();
        include __DIR__ . '/../templates/detailed_contract_view.php';
        $htmlContent = ob_get_clean();
        
        echo $htmlContent;
        exit;
    }
    
    /**
     * استيراد عقد من النص
     */
    public function importFromText()
    {
        // التحقق من المصادقة
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['contract_text'])) {
            try {
                // تحليل النص واستخراج البيانات
                $extractedData = $this->extractDataFromText($_POST['contract_text']);
                
                // إنشاء العقد بناءً على البيانات المستخرجة
                $contractData = [
                    'title' => $extractedData['title'] ?? 'عقد مستورد',
                    'second_party_name' => $extractedData['second_party_name'] ?? '',
                    'contract_amount' => $extractedData['capital_amount'] ?? 0,
                    'description' => 'عقد مستورد من النص',
                    'created_by' => $_SESSION['user_id'],
                    'status' => 'draft',
                    'is_detailed' => 1
                ];
                
                $contractId = $this->contractModel->create($contractData);

                if (!$contractId) {
                    throw new Exception('فشل في إنشاء العقد الأساسي');
                }

                // إنشاء التفاصيل المتقدمة
                $extractedData['full_contract_text'] = $_POST['contract_text'];
                $this->detailedContractModel->create($contractId, $extractedData);

                $_SESSION['success'] = 'تم استيراد العقد بنجاح';
                header("Location: /contracts/view-detailed/{$contractId}");
                exit;
                
            } catch (Exception $e) {
                $_SESSION['error'] = 'حدث خطأ في استيراد العقد: ' . $e->getMessage();
            }
        }
        
        // عرض نموذج الاستيراد
        require_once __DIR__ . '/../templates/layout_helpers.php';
        
        ob_start();
        include __DIR__ . '/../templates/import_contract.php';
        $content = ob_get_clean();
        
        return renderMasterLayout(
            $content,
            [],
            'استيراد عقد من النص - سما البنيان التجارية'
        );
    }
    
    /**
     * استخراج البيانات من النص
     */
    private function extractDataFromText($text)
    {
        $data = [];
        
        // استخراج رقم العقد
        if (preg_match('/رقم العقد[:\s]*([\w\-\d]+)/u', $text, $matches)) {
            $data['contract_number'] = trim($matches[1]);
        }
        
        // استخراج التاريخ الهجري
        if (preg_match('/(\d{1,2}-\d{1,2}-\d{4}هـ)/u', $text, $matches)) {
            $data['hijri_date'] = $matches[1];
        }
        
        // استخراج المكان
        if (preg_match('/في\s+([^\s]+(?:\s+[^\s]+)*?)(?:\s+بين)/u', $text, $matches)) {
            $data['location'] = trim($matches[1]);
        }
        
        // استخراج اسم الطرف الثاني (البحث عن الأسطر التي تحتوي على خط تحت)
        if (preg_match('/ثانياً[:\s]*\([^)]*\)[^_]*_{5,}/u', $text, $matches)) {
            // يحتاج لتحسين أكثر
        }
        
        // استخراج المبلغ
        if (preg_match('/(\d+(?:,\d{3})*)\s*(?:ألف\s+)?ريال/u', $text, $matches)) {
            $amount = str_replace(',', '', $matches[1]);
            $data['capital_amount'] = floatval($amount);
        }
        
        // استخراج نسبة الأرباح
        if (preg_match('/(\d+(?:\.\d+)?)%/u', $text, $matches)) {
            $data['profit_percentage'] = floatval($matches[1]);
        }
        
        return $data;
    }
}

?>