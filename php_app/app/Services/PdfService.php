<?php

namespace App\Services;

class PdfService
{
    private $templatePath;
    private $fontsPath;

    public function __construct()
    {
        $this->templatePath = storage_path('app/templates/');
        $this->fontsPath = public_path('static/fonts/');
    }

    /**
     * Generate contract PDF using TCPDF with full Arabic support
     */
    public function generateContractPdf(array $contractData, ?string $templatePath = null): string|false
    {
        try {
            // Check if TCPDF is available
            if (!class_exists('TCPDF')) {
                error_log('TCPDF not available, falling back to HTML');
                return false;
            }

            // Create new PDF document
            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('ContractSama');
            $pdf->SetAuthor('ContractSama System');
            $pdf->SetTitle('عقد استثمار - رقم ' . ($contractData['contract_number'] ?? 'غير محدد'));
            $pdf->SetSubject('عقد استثمار');

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set RTL support
            $pdf->setRTL(true);

            // Set margins
            $pdf->SetMargins(20, 20, 20);
            $pdf->SetAutoPageBreak(true, 20);

            // Set Arabic font
            $this->setArabicFont($pdf);

            // Add page
            $pdf->AddPage();

            // Generate content
            $this->generateContent($pdf, $contractData);

            // Return PDF content as string
            return $pdf->Output('', 'S');

        } catch (\Exception $e) {
            error_log('PDF Generation Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set Arabic font for the PDF
     */
    private function setArabicFont($pdf): void
    {
        try {
            // Try built-in Arabic fonts first
            $pdf->SetFont('aealarabiya', '', 12);
        } catch (\Exception $e) {
            try {
                // Fallback to DejaVu Sans (supports Arabic)
                $pdf->SetFont('dejavusans', '', 12);
            } catch (\Exception $e2) {
                // Ultimate fallback
                $pdf->SetFont('helvetica', '', 12);
                error_log('Using non-Arabic font as fallback');
            }
        }
    }

    /**
     * Generate the contract content
     */
    private function generateContent($pdf, array $data): void
    {
        // Title
        $pdf->SetY(30);
        $pdf->SetFont('', 'B', 18);
        $pdf->Cell(0, 15, 'عقد استثمار', 0, 1, 'C');
        
        $pdf->Ln(10);

        // Contract details
        $pdf->SetFont('', '', 12);
        
        $content = $this->buildContractText($data);
        
        // Use writeHTML for better RTL support
        $html = '<div dir="rtl" style="text-align: right; font-family: Arial;">';
        $html .= nl2br($content);
        $html .= '</div>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    /**
     * Build the contract text content
     */
    private function buildContractText(array $data): string
    {
        $text = "بسم الله الرحمن الرحيم\n\n";
        
        $text .= "رقم العقد: " . ($data['contract_number'] ?? 'غير محدد') . "\n";
        $text .= "التاريخ: " . ($data['start_date_h'] ?? date('Y-m-d')) . "\n\n";
        
        $text .= "الأطراف:\n";
        $text .= "الطرف الأول: شركة الاستثمار\n";
        $text .= "الطرف الثاني: " . ($data['partner2_name'] ?? $data['partner_name'] ?? 'غير محدد') . "\n";
        $text .= "رقم الهوية: " . ($data['partner_id'] ?? 'غير محدد') . "\n";
        $text .= "الهاتف: " . ($data['partner_phone'] ?? 'غير محدد') . "\n\n";
        
        $text .= "شروط العقد:\n";
        $text .= "• مبلغ الاستثمار: " . number_format($data['investment_amount'] ?? 0, 2) . " ريال\n";
        $text .= "• مبلغ رأس المال: " . number_format($data['capital_amount'] ?? 0, 2) . " ريال\n";
        $text .= "• نسبة الربح: " . ($data['profit_percent'] ?? 0) . "%\n";
        $text .= "• فترة الأرباح: " . ($data['profit_interval_months'] ?? 3) . " أشهر\n";
        $text .= "• إشعار الانسحاب: " . ($data['withdrawal_notice_days'] ?? 30) . " يوم\n";
        $text .= "• تاريخ البداية: " . ($data['start_date_h'] ?? date('Y-m-d')) . "\n";
        $text .= "• تاريخ الانتهاء: " . ($data['end_date_h'] ?? date('Y-m-d', strtotime('+1 year'))) . "\n";
        $text .= "• نسبة العمولة: " . ($data['commission_percent'] ?? 0) . "%\n";
        $text .= "• إشعار الخروج: " . ($data['exit_notice_days'] ?? 30) . " يوم\n";
        $text .= "• مبلغ الغرامة: " . number_format($data['penalty_amount'] ?? 0, 2) . " ريال\n\n";
        
        $text .= "التوقيعات:\n\n";
        $text .= "الطرف الأول: ________________\n\n";
        $text .= "الطرف الثاني: ________________\n\n";
        
        return $text;
    }

    /**
     * Generate HTML version of contract (fallback when TCPDF not available)
     */
    public function generateHtmlContract(array $data): string
    {
        $html = '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>عقد استثمار - ' . ($data['contract_number'] ?? 'غير محدد') . '</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            direction: rtl; 
            text-align: right; 
            margin: 40px;
            line-height: 1.6;
        }
        .header { 
            text-align: center; 
            font-size: 24px; 
            font-weight: bold; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .section { 
            margin: 20px 0; 
        }
        .label { 
            font-weight: bold; 
            color: #333;
        }
        .terms {
            background: #f8f9fa;
            padding: 20px;
            border-right: 4px solid #007bff;
            margin: 20px 0;
        }
        .signatures {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 200px;
            border-top: 1px solid #333;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">عقد استثمار</div>
    
    <div class="section">
        <span class="label">رقم العقد:</span> ' . ($data['contract_number'] ?? 'غير محدد') . '
    </div>
    
    <div class="section">
        <span class="label">التاريخ:</span> ' . ($data['start_date_h'] ?? date('Y-m-d')) . '
    </div>
    
    <div class="section">
        <h3>الأطراف:</h3>
        <p><span class="label">الطرف الأول:</span> شركة الاستثمار</p>
        <p><span class="label">الطرف الثاني:</span> ' . ($data['partner2_name'] ?? $data['partner_name'] ?? 'غير محدد') . '</p>
        <p><span class="label">رقم الهوية:</span> ' . ($data['partner_id'] ?? 'غير محدد') . '</p>
        <p><span class="label">الهاتف:</span> ' . ($data['partner_phone'] ?? 'غير محدد') . '</p>
    </div>
    
    <div class="terms">
        <h3>شروط العقد:</h3>
        <ul>
            <li>مبلغ الاستثمار: ' . number_format($data['investment_amount'] ?? 0, 2) . ' ريال</li>
            <li>مبلغ رأس المال: ' . number_format($data['capital_amount'] ?? 0, 2) . ' ريال</li>
            <li>نسبة الربح: ' . ($data['profit_percent'] ?? 0) . '%</li>
            <li>فترة الأرباح: ' . ($data['profit_interval_months'] ?? 3) . ' أشهر</li>
            <li>إشعار الانسحاب: ' . ($data['withdrawal_notice_days'] ?? 30) . ' يوم</li>
            <li>تاريخ البداية: ' . ($data['start_date_h'] ?? date('Y-m-d')) . '</li>
            <li>تاريخ الانتهاء: ' . ($data['end_date_h'] ?? date('Y-m-d', strtotime('+1 year'))) . '</li>
            <li>نسبة العمولة: ' . ($data['commission_percent'] ?? 0) . '%</li>
            <li>إشعار الخروج: ' . ($data['exit_notice_days'] ?? 30) . ' يوم</li>
            <li>مبلغ الغرامة: ' . number_format($data['penalty_amount'] ?? 0, 2) . ' ريال</li>
        </ul>
    </div>
    
    <div class="signatures">
        <div class="signature-box">
            <p>الطرف الأول</p>
        </div>
        <div class="signature-box">
            <p>الطرف الثاني</p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Generate detailed contract PDF with full Arabic content
     */
    public function generateDetailedContractPdf(array $contractData): string|false
    {
        try {
            // Check if TCPDF is available
            if (!class_exists('TCPDF')) {
                error_log('TCPDF not available, falling back to HTML');
                return false;
            }

            // Create new PDF document
            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('سما البنيان التجارية');
            $pdf->SetAuthor('نظام إدارة العقود');
            $pdf->SetTitle('عقد مفصل - رقم ' . ($contractData['contract_number'] ?? 'غير محدد'));
            $pdf->SetSubject('عقد ' . $this->getContractTypeName($contractData['contract_type'] ?? 'general'));

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set RTL support
            $pdf->setRTL(true);

            // Set margins
            $pdf->SetMargins(15, 20, 15);
            $pdf->SetAutoPageBreak(true, 20);

            // Set Arabic font
            $this->setArabicFont($pdf);

            // Add page
            $pdf->AddPage();

            // Generate detailed content
            $this->generateDetailedContent($pdf, $contractData);

            // Return PDF content as string
            return $pdf->Output('', 'S');

        } catch (\Exception $e) {
            error_log('Detailed PDF Generation Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate detailed contract content
     */
    private function generateDetailedContent($pdf, array $data): void
    {
        // Header
        $pdf->SetY(25);
        $pdf->SetFont('', 'B', 16);
        $pdf->Cell(0, 15, 'بسم الله الرحمن الرحيم', 0, 1, 'C');
        
        $pdf->Ln(5);
        $pdf->SetFont('', 'B', 14);
        $pdf->Cell(0, 10, 'رقم العقد: ' . ($data['contract_number'] ?? 'غير محدد'), 0, 1, 'C');
        
        $pdf->Ln(10);

        // Use full contract text if available
        if (!empty($data['full_contract_text'])) {
            $pdf->SetFont('', '', 11);
            $html = '<div dir="rtl" style="text-align: right; font-family: Arial; line-height: 1.8;">';
            $html .= nl2br(htmlspecialchars($data['full_contract_text']));
            $html .= '</div>';
            
            $pdf->writeHTML($html, true, false, true, false, '');
        } else {
            // Generate content from individual fields
            $this->generateContentFromFields($pdf, $data);
        }
    }

    /**
     * Generate content from individual contract fields
     */
    private function generateContentFromFields($pdf, array $data): void
    {
        $pdf->SetFont('', '', 11);
        
        $content = "تم الاتفاق بتاريخ " . ($data['hijri_date'] ?? date('d-m-Y') . 'هـ') . " في " . ($data['location'] ?? 'المملكة العربية السعودية') . " بين كل من:\n\n";
        
        $content .= "أولاً: (طرف أول) " . ($data['first_party_name'] ?? 'شركة سما البنيان التجارية') . "\n";
        $content .= "السجل التجاري: " . ($data['first_party_commercial_reg'] ?? '4030533070') . "\n";
        $content .= "المحافظة: " . ($data['first_party_city'] ?? 'جدة') . " - الحي: " . ($data['first_party_district'] ?? 'الحمدانية') . "\n";
        $content .= "الممثل القانوني: " . ($data['first_party_representative'] ?? 'احمد عبدالله سعيد الزهراني') . "\n\n";
        
        $content .= "ثانياً: (طرف ثان) " . ($data['second_party_name'] ?? 'الطرف الثاني') . "\n";
        if (!empty($data['second_party_id'])) {
            $content .= "رقم الهوية: " . $data['second_party_id'] . "\n";
        }
        if (!empty($data['second_party_mobile'])) {
            $content .= "رقم الجوال: " . $data['second_party_mobile'] . "\n";
        }
        $content .= "\n";
        
        $content .= "التفاصيل المالية والشروط:\n";
        $content .= "• مبلغ رأس المال: " . number_format($data['capital_amount'] ?? 0, 2) . " ريال سعودي\n";
        $content .= "• نسبة الأرباح: " . ($data['profit_percentage'] ?? 0) . "%\n";
        $content .= "• دورية تسليم الأرباح: كل " . ($data['profit_period_months'] ?? 6) . " أشهر\n";
        $content .= "• مدة الإخطار للانسحاب: " . ($data['withdrawal_notice_days'] ?? 60) . " يوماً\n";
        $content .= "• الشرط الجزائي: " . number_format($data['penalty_amount'] ?? 3000, 2) . " ريال لكل " . ($data['penalty_period_days'] ?? 30) . " يوم تأخير\n";
        $content .= "• نسبة العمولة: " . ($data['commission_percentage'] ?? 2.5) . "%\n";
        $content .= "• القوة القاهرة: " . ($data['force_majeure_days'] ?? 90) . " يوماً\n\n";
        
        if (!empty($data['start_date'])) {
            $content .= "• تاريخ البداية: " . $data['start_date'] . "\n";
        }
        if (!empty($data['end_date'])) {
            $content .= "• تاريخ النهاية: " . $data['end_date'] . "\n";
        }
        
        if (!empty($data['description'])) {
            $content .= "\nوصف العقد:\n" . $data['description'] . "\n\n";
        }
        
        if (!empty($data['terms_conditions'])) {
            $content .= "الشروط والأحكام الإضافية:\n" . $data['terms_conditions'] . "\n\n";
        }
        
        $content .= "\n\nالتوقيعات:\n\n";
        $content .= "الطرف الأول: ________________________                    الطرف الثاني: ________________________\n\n";
        $content .= ($data['first_party_name'] ?? 'شركة سما البنيان التجارية') . "                                           " . ($data['second_party_name'] ?? 'الطرف الثاني') . "\n\n";
        $content .= "التاريخ: _______________                                                      التاريخ: _______________\n";
        
        // Use writeHTML for better RTL support
        $html = '<div dir="rtl" style="text-align: right; font-family: Arial; line-height: 1.8;">';
        $html .= nl2br(htmlspecialchars($content));
        $html .= '</div>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    /**
     * Get contract type name in Arabic
     */
    private function getContractTypeName($type): string
    {
        $types = [
            'real_estate_speculation' => 'مضاربة عقارية',
            'partnership' => 'شراكة',
            'investment' => 'استثمار',
            'general' => 'عام'
        ];
        
        return $types[$type] ?? 'عام';
    }
}