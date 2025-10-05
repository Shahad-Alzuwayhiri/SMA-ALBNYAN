<?php

namespace App\Services;

use TCPDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Log;

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
     * @param array $contractData
     * @param string|null $templatePath
     * @return string|false
     */
    public function generateContractPdf(array $contractData, ?string $templatePath = null): string|false
    {
        try {
            // Create new PDF document
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            
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

            // Add Arabic font
            $this->registerArabicFonts($pdf);

            // Add page
            $pdf->AddPage();

            // Generate content
            $this->generateContractContent($pdf, $contractData);

            // Return PDF content as string
            return $pdf->Output('', 'S');

        } catch (\Exception $e) {
            Log::error('PDF Generation Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Register Arabic fonts with TCPDF
     */
    private function registerArabicFonts(TCPDF $pdf): void
    {
        try {
            // Try to use Cairo font if available
            $cairoPath = $this->fontsPath . 'Cairo-Regular.ttf';
            if (file_exists($cairoPath)) {
                $fontname = \TCPDF_FONTS::addTTFfont($cairoPath, 'TrueTypeUnicode', '', 96);
                $pdf->SetFont($fontname, '', 14);
                return;
            }

            // Try Amiri font
            $amiriPath = $this->fontsPath . 'Amiri-Regular.ttf';
            if (file_exists($amiriPath)) {
                $fontname = \TCPDF_FONTS::addTTFfont($amiriPath, 'TrueTypeUnicode', '', 96);
                $pdf->SetFont($fontname, '', 14);
                return;
            }

            // Fallback to built-in Arabic font
            $pdf->SetFont('aealarabiya', '', 14);

        } catch (\Exception $e) {
            Log::warning('Font registration failed: ' . $e->getMessage());
            // Ultimate fallback
            $pdf->SetFont('dejavusans', '', 12);
        }
    }

    /**
     * Generate the actual contract content
     */
    private function generateContractContent(TCPDF $pdf, array $data): void
    {
        // Header - Company Logo and Title
        $pdf->SetY(30);
        $pdf->SetFont('', 'B', 18);
        $pdf->Cell(0, 15, 'عقد استثمار', 0, 1, 'C');
        
        $pdf->Ln(10);

        // Contract Number
        $pdf->SetFont('', 'B', 14);
        $pdf->Cell(50, 10, 'رقم العقد:', 0, 0, 'R');
        $pdf->SetFont('', '', 14);
        $pdf->Cell(0, 10, $data['contract_number'] ?? 'غير محدد', 0, 1, 'R');

        $pdf->Ln(5);

        // Date
        $pdf->SetFont('', 'B', 14);
        $pdf->Cell(50, 10, 'تاريخ العقد:', 0, 0, 'R');
        $pdf->SetFont('', '', 14);
        $pdf->Cell(0, 10, $data['start_date_h'] ?? date('Y-m-d'), 0, 1, 'R');

        $pdf->Ln(10);

        // Main content
        $this->generateContractBody($pdf, $data);
    }

    /**
     * Generate the main contract body
     */
    private function generateContractBody(TCPDF $pdf, array $data): void
    {
        $pdf->SetFont('', '', 12);

        // Introduction
        $intro = "بسم الله الرحمن الرحيم\n\n";
        $intro .= "إنه في يوم " . ($data['start_date_h'] ?? date('Y-m-d')) . " الموافق " . date('Y-m-d') . "\n";
        $intro .= "تم إبرام هذا العقد بين:\n\n";

        $pdf->writeHTML('<div dir="rtl">' . nl2br($intro) . '</div>', true, false, true, false, '');

        // First Party
        $firstParty = "الطرف الأول: شركة الاستثمار\n";
        $firstParty .= "ويمثلها في هذا العقد: المدير العام\n\n";

        // Second Party
        $secondParty = "الطرف الثاني: " . ($data['partner2_name'] ?? $data['partner_name'] ?? 'غير محدد') . "\n";
        $secondParty .= "رقم الهوية: " . ($data['partner_id'] ?? 'غير محدد') . "\n";
        $secondParty .= "رقم الهاتف: " . ($data['partner_phone'] ?? 'غير محدد') . "\n";
        $secondParty .= "العنوان: " . ($data['client_address'] ?? 'غير محدد') . "\n\n";

        $pdf->writeHTML('<div dir="rtl">' . nl2br($firstParty . $secondParty) . '</div>', true, false, true, false, '');

        // Contract Terms
        $terms = "شروط العقد:\n\n";
        $terms .= "1. مبلغ الاستثمار: " . number_format($data['investment_amount'] ?? 0, 2) . " ريال سعودي\n";
        $terms .= "2. مبلغ رأس المال: " . number_format($data['capital_amount'] ?? 0, 2) . " ريال سعودي\n";
        $terms .= "3. نسبة الربح: " . ($data['profit_percent'] ?? 0) . "%\n";
        $terms .= "4. فترة توزيع الأرباح: كل " . ($data['profit_interval_months'] ?? 3) . " أشهر\n";
        $terms .= "5. فترة الإشعار للانسحاب: " . ($data['withdrawal_notice_days'] ?? 30) . " يوم\n";
        $terms .= "6. تاريخ البداية: " . ($data['start_date_h'] ?? date('Y-m-d')) . "\n";
        $terms .= "7. تاريخ الانتهاء: " . ($data['end_date_h'] ?? date('Y-m-d', strtotime('+1 year'))) . "\n";
        $terms .= "8. نسبة العمولة: " . ($data['commission_percent'] ?? 0) . "%\n";
        $terms .= "9. فترة إشعار الخروج: " . ($data['exit_notice_days'] ?? 30) . " يوم\n";
        $terms .= "10. مبلغ الغرامة: " . number_format($data['penalty_amount'] ?? 0, 2) . " ريال سعودي\n\n";

        $pdf->writeHTML('<div dir="rtl">' . nl2br($terms) . '</div>', true, false, true, false, '');

        // Signatures
        $pdf->Ln(20);
        $signatures = "التوقيعات:\n\n";
        $signatures .= "الطرف الأول: ________________    التاريخ: ________________\n\n";
        $signatures .= "الطرف الثاني: ________________   التاريخ: ________________\n\n";

        $pdf->writeHTML('<div dir="rtl">' . nl2br($signatures) . '</div>', true, false, true, false, '');
    }

    /**
     * Extract text positions from existing PDF (if needed for template overlay)
     * This is a simplified version - for complex template overlay, consider using FPDI
     */
    public function extractPositions(string $pdfPath): ?array
    {
        try {
            // For now, return null - this would require FPDI integration
            // or a different approach for template-based PDF generation
            Log::info('PDF text extraction not implemented in PHP service');
            return null;
        } catch (\Exception $e) {
            Log::error('PDF extraction error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Simple template-based PDF generation (alternative to overlay method)
     */
    public function generateFromTemplate(array $contractData, string $templatePath): string|false
    {
        try {
            // This would implement FPDI-based template overlay
            // For now, use the standard generation method
            return $this->generateContractPdf($contractData, $templatePath);
        } catch (\Exception $e) {
            Log::error('Template PDF generation error: ' . $e->getMessage());
            return false;
        }
    }
}