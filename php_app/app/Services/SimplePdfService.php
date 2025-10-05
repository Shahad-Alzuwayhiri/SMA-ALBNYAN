<?php

namespace App\Services;

class PurePdfService
{
    private $templatePath;
    private $fontsPath;

    public function __construct()
    {
        $this->templatePath = storage_path('app/templates/');
        $this->fontsPath = public_path('static/fonts/');
    }

    /**
     * Generate contract PDF using TCPDF
     * This method will work once TCPDF is properly installed via Composer
     */
    public function generateContractPdf(array $contractData, ?string $templatePath = null): string|false
    {
        try {
            // Check if TCPDF is available
            if (!class_exists('TCPDF')) {
                \Log::error('TCPDF class not found. Please install via composer: composer require tecnickcom/tcpdf');
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
            \Log::error('PDF Generation Error: ' . $e->getMessage());
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
                \Log::warning('Using non-Arabic font as fallback');
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
     * Simple fallback PDF generation using HTML
     * This can work without TCPDF using DomPDF or similar
     */
    public function generateSimplePdf(array $contractData): string
    {
        $html = $this->generateHtmlContract($contractData);
        
        // For now, return HTML - this would need DomPDF integration
        return $html;
    }

    /**
     * Generate HTML version of contract
     */
    private function generateHtmlContract(array $data): string
    {
        $html = '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; direction: rtl; text-align: right; }
        .header { text-align: center; font-size: 24px; font-weight: bold; margin-bottom: 30px; }
        .section { margin: 15px 0; }
        .label { font-weight: bold; }
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
    
    <div class="section">
        <h3>شروط العقد:</h3>
        <ul>
            <li>مبلغ الاستثمار: ' . number_format($data['investment_amount'] ?? 0, 2) . ' ريال</li>
            <li>نسبة الربح: ' . ($data['profit_percent'] ?? 0) . '%</li>
            <li>فترة الأرباح: ' . ($data['profit_interval_months'] ?? 3) . ' أشهر</li>
            <li>تاريخ البداية: ' . ($data['start_date_h'] ?? date('Y-m-d')) . '</li>
            <li>تاريخ الانتهاء: ' . ($data['end_date_h'] ?? date('Y-m-d', strtotime('+1 year'))) . '</li>
        </ul>
    </div>
    
    <div class="section" style="margin-top: 50px;">
        <p>الطرف الأول: ________________</p>
        <br>
        <p>الطرف الثاني: ________________</p>
    </div>
</body>
</html>';

        return $html;
    }
}