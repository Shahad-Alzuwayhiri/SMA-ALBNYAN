<?php
/**
 * مولد ملفات PDF تجريبية - نظام سما البنيان
 * يساعد في إنشاء ملفات PDF تجريبية لاختبار النظام
 */

class SamplePdfGenerator {
    
    /**
     * إنشاء ملف PDF بسيط
     */
    public static function createSimplePdf($title = "ملف تجريبي", $content = "") {
        $defaultContent = "هذا ملف PDF تجريبي تم إنشاؤه لاختبار نظام سما البنيان";
        $content = $content ?: $defaultContent;
        
        $pdfContent = "%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
/Resources <<
/Font <<
/F1 5 0 R
>>
>>
>>
endobj

4 0 obj
<<
/Length " . (strlen($title) + strlen($content) + 100) . "
>>
stream
BT
/F1 16 Tf
50 700 Td
(" . $title . ") Tj
0 -30 Td
/F1 12 Tf
(" . $content . ") Tj
0 -30 Td
(تاريخ الإنشاء: " . date('Y-m-d H:i:s') . ") Tj
ET
endstream
endobj

5 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
endobj

xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000274 00000 n 
0000000500 00000 n 
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
569
%%EOF";

        return $pdfContent;
    }
    
    /**
     * إنشاء ملف PDF لعقد
     */
    public static function createContractPdf($contractNumber, $clientName, $amount) {
        $title = "عقد رقم: " . $contractNumber;
        $content = "العميل: " . $clientName . " - المبلغ: " . number_format($amount) . " ريال";
        
        return self::createSimplePdf($title, $content);
    }
    
    /**
     * حفظ ملف PDF في مجلد storage
     */
    public static function savePdfFile($filename, $pdfContent) {
        $storageDir = __DIR__ . '/../storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        $filePath = $storageDir . '/' . $filename;
        $result = file_put_contents($filePath, $pdfContent);
        
        return $result !== false ? $filePath : false;
    }
    
    /**
     * إنشاء مجموعة ملفات تجريبية
     */
    public static function createSampleFiles() {
        $files = [];
        
        // ملف عام
        $generalPdf = self::createSimplePdf("نظام سما البنيان", "نظام إدارة العقود المتطور");
        $files['sample.pdf'] = self::savePdfFile('sample.pdf', $generalPdf);
        
        // ملف عقد تجريبي
        $contractPdf = self::createContractPdf("SMA-2025001", "شركة الاختبار المحدودة", 50000);
        $files['contract_sample.pdf'] = self::savePdfFile('contract_sample.pdf', $contractPdf);
        
        // ملف تقرير
        $reportPdf = self::createSimplePdf("تقرير العقود الشهري", "تقرير شهر أكتوبر 2025");
        $files['report_sample.pdf'] = self::savePdfFile('report_sample.pdf', $reportPdf);
        
        return $files;
    }
}

// إذا تم استدعاء الملف مباشرة، ينشئ الملفات التجريبية
if (basename($_SERVER['PHP_SELF']) === 'SamplePdfGenerator.php') {
    echo "🔧 إنشاء ملفات PDF تجريبية...\n";
    
    $files = SamplePdfGenerator::createSampleFiles();
    
    echo "✅ تم إنشاء الملفات التجريبية:\n";
    foreach ($files as $name => $path) {
        if ($path) {
            echo "  📄 $name: $path\n";
        } else {
            echo "  ❌ فشل في إنشاء $name\n";
        }
    }
    
    echo "\n🎉 تم الانتهاء من إنشاء الملفات التجريبية!\n";
}
?>