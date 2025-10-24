<?php

namespace App\Services;

/**
 * Enhanced PDF Service with Company Watermark
 * خدمة PDF محسنة مع علامة مائية للشركة
 */

class EnhancedPdfService
{
    private $companyName = 'شركة سما البنيان التجارية';
    private $companyNameEn = 'SMA ALBNYAN COMPANY';
    private $companySubtitle = 'للتطوير والاستثمار العقاري';
    
    public function generateContractPdf($contract, $type = 'investment')
    {
        $contractData = $this->prepareContractData($contract, $type);
        return $this->generateHtmlWithWatermark($contractData, $type);
    }
    
    private function prepareContractData($contract, $type)
    {
        return [
            'contract_number' => $contract['contract_number'] ?? 'SBC-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'contract_date' => $contract['contract_date'] ?? date('Y-m-d'),
            'hijri_date' => $this->getHijriDate(),
            'client_name' => $contract['client_name'] ?? $contract['second_party_name'] ?? '',
            'client_id' => $contract['client_id'] ?? $contract['client_national_id'] ?? '',
            'client_phone' => $contract['client_phone'] ?? $contract['phone'] ?? '',
            'client_email' => $contract['client_email'] ?? $contract['email'] ?? '',
            'amount' => floatval($contract['amount'] ?? $contract['contract_amount'] ?? 0),
            'profit_percentage' => floatval($contract['profit_percentage'] ?? 30),
            'contract_duration' => intval($contract['contract_duration'] ?? 6),
            'notes' => $contract['notes'] ?? '',
            'property_number' => $contract['property_number'] ?? '',
            'property_location' => $contract['property_location'] ?? '',
            'type' => $type
        ];
    }
    
    private function generateHtmlWithWatermark($contractData, $type)
    {
        $contractNumber = $contractData['contract_number'];
        $date = $contractData['contract_date'];
        $hijriDate = $contractData['hijri_date'];
        $clientName = $contractData['client_name'];
        $clientId = $contractData['client_id'];
        $clientPhone = $contractData['client_phone'];
        $clientEmail = $contractData['client_email'];
        $amount = number_format($contractData['amount'], 2);
        $profitPercentage = $contractData['profit_percentage'];
        $contractDuration = $contractData['contract_duration'];
        $notes = $contractData['notes'];
        
        // Calculate profit
        $totalProfit = ($contractData['amount'] * $profitPercentage / 100);
        $totalAmount = $contractData['amount'] + $totalProfit;
        
        $contractTitle = $type === 'property' ? 'عقد استثمار بعقار' : 'عقد استثمار ومضاربة';
        
        $html = '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $contractTitle . ' - ' . $contractNumber . '</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: "Traditional Arabic", "Tahoma", "Arial", sans-serif;
            font-size: 14px;
            line-height: 1.8;
            direction: rtl;
            text-align: right;
            color: #000;
            margin: 0;
            padding: 0;
            position: relative;
        }
        
        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 48px;
            color: rgba(37, 51, 85, 0.05);
            z-index: -1;
            pointer-events: none;
            font-weight: bold;
        }
        
        .watermark-logo {
            position: fixed;
            top: 20%;
            left: 20%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.03;
            z-index: -1;
            pointer-events: none;
        }
        
        .document-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px double #253355;
            padding-bottom: 20px;
            position: relative;
            background: linear-gradient(135deg, #f8f9fa 0%, #e2e8f0 100%);
            padding: 30px;
            border-radius: 10px;
        }
        
        .company-logo-header {
            position: absolute;
            top: 10px;
            left: 20px;
            width: 80px;
            height: 40px;
            opacity: 0.8;
        }
        
        .kingdom-header {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #253355;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #253355;
            margin: 15px 0;
            background: linear-gradient(135deg, #253355 0%, #2d5aa0 50%, #4299e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .contract-title {
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
            color: #2d5aa0;
        }
        
        .contract-meta {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            font-weight: bold;
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            border: 2px solid #ffc107;
        }
        
        .contract-body {
            margin: 30px 0;
            text-align: justify;
        }
        
        .article {
            margin: 25px 0;
            padding: 15px;
            background: #f8f9fa;
            border-right: 4px solid #4299e1;
            border-radius: 5px;
        }
        
        .article-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
            color: #253355;
            text-decoration: underline;
        }
        
        .parties-section {
            background: #e8f5e8;
            border: 2px solid #253355;
            padding: 25px;
            margin: 30px 0;
            border-radius: 10px;
        }
        
        .party {
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .party-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 15px;
            color: #2d5aa0;
            border-bottom: 2px solid #4299e1;
            padding-bottom: 5px;
        }
        
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 14px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .financial-table th,
        .financial-table td {
            border: 2px solid #253355;
            padding: 15px;
            text-align: center;
        }
        
        .financial-table th {
            background: linear-gradient(135deg, #253355, #2d5aa0);
            color: white;
            font-weight: bold;
        }
        
        .amount-highlight {
            background: #fff3cd;
            font-weight: bold;
            color: #856404;
            padding: 5px;
            border-radius: 3px;
        }
        
        .signatures-section {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        
        .signature-box {
            width: 45%;
            border: 3px solid #253355;
            padding: 25px;
            text-align: center;
            min-height: 150px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom: 2px solid #2d5aa0;
            padding-bottom: 10px;
            color: #253355;
            font-size: 16px;
        }
        
        .signature-line {
            border-bottom: 2px solid #253355;
            margin: 20px 0;
            height: 50px;
        }
        
        .important-note {
            background: #ffe6e6;
            border: 3px solid #dc3545;
            padding: 20px;
            margin: 30px 0;
            font-weight: bold;
            text-align: center;
            border-radius: 10px;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 2px solid #ccc;
            padding-top: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
        }
        
        .basmala {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 30px 0;
            color: #253355;
            background: #e8f5e8;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #38a169;
        }
        
        @media print {
            body { 
                font-size: 12px; 
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none; }
            .watermark { opacity: 0.03; }
        }
    </style>
</head>
<body>';

        // Add watermarks
        $html .= '
    <!-- العلامة المائية -->
    <div class="watermark">' . $this->companyName . '</div>
    <div class="watermark-logo">
        <svg width="200" height="100" viewBox="0 0 200 100" xmlns="http://www.w3.org/2000/svg">
            <text x="100" y="50" font-family="Arial" font-size="16" font-weight="bold" fill="#253355" text-anchor="middle" opacity="0.1">
                ' . $this->companyNameEn . '
            </text>
        </svg>
    </div>';

        // Document header
        $html .= '
    <!-- رأس المستند -->
    <div class="document-header">
        <div class="kingdom-header">المملكة العربية السعودية</div>
        <div class="company-name">' . $this->companyName . '</div>
        <div class="company-name" style="font-size: 16px; opacity: 0.8;">' . $this->companyNameEn . '</div>
        <div style="color: #2d5aa0; font-weight: 500;">' . $this->companySubtitle . '</div>
        <div class="contract-title">' . $contractTitle . '</div>
    </div>';

        // Contract metadata
        $html .= '
    <!-- معلومات العقد -->
    <div class="contract-meta">
        <div>رقم العقد: <strong>' . $contractNumber . '</strong></div>
        <div>التاريخ الميلادي: <strong>' . date('d/m/Y', strtotime($date)) . '</strong></div>
        <div>التاريخ الهجري: <strong>' . $hijriDate . '</strong></div>
    </div>';

        // Basmala
        $html .= '
    <div class="basmala">
        بسم الله الرحمن الرحيم
    </div>';

        // Parties section
        $html .= '
    <!-- أطراف العقد -->
    <div class="parties-section">
        <div class="party">
            <div class="party-title">الطرف الأول (المضارب):</div>
            <p><strong>' . $this->companyName . '</strong><br>
            <strong>' . $this->companyNameEn . '</strong><br>
            سجل تجاري رقم: 4030533070<br>
            العنوان: المملكة العربية السعودية، مكة المكرمة، جدة<br>
            ' . $this->companySubtitle . '<br>
            هاتف: +966 12 234 5678<br>
            البريد الإلكتروني: info@sama-albonyan.com</p>
        </div>
        
        <div class="party">
            <div class="party-title">الطرف الثاني (رب المال):</div>
            <p><strong>' . $clientName . '</strong><br>
            رقم الهوية/الإقامة: ' . $clientId . '<br>
            رقم الجوال: ' . $clientPhone . '<br>
            البريد الإلكتروني: ' . $clientEmail . '</p>
        </div>
    </div>';

        // Contract body
        $html .= '
    <!-- نص العقد -->
    <div class="contract-body">
        <div class="article">
            <div class="article-title">المادة الأولى: موضوع العقد</div>
            <p>يقوم الطرف الثاني (رب المال) بتسليم مبلغ <span class="amount-highlight">' . $amount . ' ريال سعودي</span> للطرف الأول (المضارب) للاستثمار في مجال العقارات وفقاً لأحكام الشريعة الإسلامية والأنظمة المعمول بها في المملكة العربية السعودية.</p>
        </div>
        
        <div class="article">
            <div class="article-title">المادة الثانية: نسبة الأرباح</div>
            <p>يتم توزيع الأرباح المحققة من الاستثمار بنسبة <strong>' . $profitPercentage . '%</strong> للطرف الثاني (رب المال) و <strong>' . (100 - $profitPercentage) . '%</strong> للطرف الأول (المضارب).</p>
        </div>
        
        <div class="article">
            <div class="article-title">المادة الثالثة: مدة العقد</div>
            <p>مدة هذا العقد <strong>' . $contractDuration . ' أشهر</strong> تبدأ من تاريخ توقيع هذا العقد، ويتم صرف الأرباح بشكل دوري حسب الاتفاق.</p>
        </div>
        
        <div class="article">
            <div class="article-title">المادة الرابعة: التزامات الأطراف</div>
            <p><strong>التزامات الطرف الأول (المضارب):</strong><br>
            - العمل على استثمار المبلغ بأفضل الطرق الممكنة<br>
            - تقديم تقارير دورية عن سير الاستثمار<br>
            - المحافظة على رأس المال وعدم التفريط فيه<br>
            - الالتزام بأحكام الشريعة الإسلامية في جميع المعاملات</p>
            
            <p><strong>التزامات الطرف الثاني (رب المال):</strong><br>
            - تسليم رأس المال في الموعد المحدد<br>
            - عدم التدخل في إدارة الاستثمار<br>
            - تحمل نصيبه من الخسائر (إن وجدت) وفقاً للشريعة الإسلامية</p>
        </div>
        
        <div class="article">
            <div class="article-title">المادة الخامسة: الشروط العامة</div>
            <p>- هذا العقد خاضع لأحكام الشريعة الإسلامية والأنظمة السعودية<br>
            - في حالة النزاع، يتم الرجوع للجهات المختصة في المملكة العربية السعودية<br>
            - يحق لأي من الطرفين إنهاء العقد بإشعار مسبق مدته 30 يوماً<br>
            - تطبق أحكام هذا العقد على الورثة والخلف العام لكلا الطرفين</p>
        </div>
    </div>';

        // Financial table
        $html .= '
    <!-- الجدول المالي -->
    <table class="financial-table">
        <thead>
            <tr>
                <th>البيان</th>
                <th>المبلغ (ريال سعودي)</th>
                <th>النسبة</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>رأس المال</strong></td>
                <td class="amount-highlight">' . $amount . '</td>
                <td>100%</td>
            </tr>
            <tr>
                <td><strong>نسبة الربح المتوقع</strong></td>
                <td>' . number_format($totalProfit, 2) . '</td>
                <td>' . $profitPercentage . '%</td>
            </tr>
            <tr style="background: #e8f5e8;">
                <td><strong>إجمالي المبلغ المتوقع</strong></td>
                <td class="amount-highlight"><strong>' . number_format($totalAmount, 2) . '</strong></td>
                <td><strong>' . (100 + $profitPercentage) . '%</strong></td>
            </tr>
        </tbody>
    </table>';

        // Add notes if available
        if (!empty($notes)) {
            $html .= '
    <div class="important-note">
        <strong>ملاحظات خاصة:</strong><br>
        ' . nl2br(htmlspecialchars($notes)) . '
    </div>';
        }

        // Signatures
        $html .= '
    <!-- التوقيعات -->
    <div class="signatures-section">
        <div class="signature-box">
            <div class="signature-title">الطرف الأول (المضارب)</div>
            <div class="signature-line"></div>
            <div><strong>' . $this->companyName . '</strong></div>
            <div style="font-size: 14px; color: #666;">' . $this->companyNameEn . '</div>
            <div>الاسم: ................................</div>
            <div>التوقيع: ................................</div>
            <div>الختم: ................................</div>
            <div>التاريخ: ................................</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-title">الطرف الثاني (رب المال)</div>
            <div class="signature-line"></div>
            <div><strong>' . $clientName . '</strong></div>
            <div>الهوية: ' . $clientId . '</div>
            <div>الجوال: ' . $clientPhone . '</div>
            <div>التوقيع: ................................</div>
            <div>التاريخ: ................................</div>
        </div>
    </div>';

        // Footer
        $html .= '
    <!-- الختام -->
    <div class="footer">
        <p><strong>تم تحرير هذا العقد في نسختين أصليتين، بيد كل طرف نسخة للعمل بموجبها</strong></p>
        <hr style="margin: 15px 0;">
        <p><strong>' . $this->companyName . '</strong></p>
        <p><strong>' . $this->companyNameEn . '</strong></p>
        <p>' . $this->companySubtitle . '</p>
        <p>المملكة العربية السعودية</p>
        <p>تليفون: +966 12 234 5678 | البريد الإلكتروني: info@sama-albonyan.com</p>
        <p style="margin-top: 10px; font-size: 11px; color: #888;">
            رقم العقد: ' . $contractNumber . ' | تاريخ الإنشاء: ' . date('Y-m-d H:i:s') . '
        </p>
    </div>
</body>
</html>';

        return $html;
    }
    
    public function outputPdf($contract, $type = 'investment')
    {
        $html = $this->generateContractPdf($contract, $type);
        $filename = 'contract_' . ($contract['contract_number'] ?? 'unknown') . '_' . date('Y-m-d') . '.html';
        
        // Set headers for HTML display with print functionality
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $html;
        exit();
    }
    
    private function getHijriDate()
    {
        // Simple Hijri date conversion (can be improved later)
        $year = date('Y') - 579; // Simple approximation
        $month = date('n');
        $day = date('j');
        
        $hijriMonths = [
            1 => 'محرم', 2 => 'صفر', 3 => 'ربيع الأول', 4 => 'ربيع الثاني',
            5 => 'جمادى الأولى', 6 => 'جمادى الثانية', 7 => 'رجب', 8 => 'شعبان',
            9 => 'رمضان', 10 => 'شوال', 11 => 'ذو القعدة', 12 => 'ذو الحجة'
        ];
        
        return $day . ' ' . $hijriMonths[$month] . ' ' . $year . 'هـ';
    }
}
?>