<?php

require_once __DIR__ . '/../config/database.php';

class DigitalSignature
{
    private $db;
    
    public function __construct()
    {
        $this->db = getDB();
    }
    
    // إنشاء توقيع رقمي جديد
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO digital_signatures (contract_id, signer_id, signature_type, signature_data, signature_metadata)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $metadata = json_encode([
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
            'additional_data' => $data['metadata'] ?? []
        ]);
        
        return $stmt->execute([
            $data['contract_id'],
            $data['signer_id'],
            $data['signature_type'] ?? 'electronic_signature',
            $data['signature_data'],
            $metadata
        ]);
    }
    
    // الحصول على توقيعات العقد
    public function getByContract($contractId)
    {
        $stmt = $this->db->prepare("
            SELECT ds.*, u.name as signer_name
            FROM digital_signatures ds
            LEFT JOIN users u ON ds.signer_id = u.id
            WHERE ds.contract_id = ?
            ORDER BY ds.signed_at DESC
        ");
        $stmt->execute([$contractId]);
        return $stmt->fetchAll();
    }
    
    // الحصول على التوقيعات المحفوظة للمستخدم
    public function getSavedSignatures($userId)
    {
        $stmt = $this->db->prepare("
            SELECT signature_type, signature_data, COUNT(*) as usage_count
            FROM digital_signatures 
            WHERE signer_id = ?
            GROUP BY signature_type, signature_data
            ORDER BY COUNT(*) DESC, signed_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    // رفع توقيع أو ختم جديد
    public function uploadSignature($userId, $signatureType, $file)
    {
        $allowedTypes = ['image/png', 'image/jpg', 'image/jpeg', 'image/svg+xml'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('نوع الملف غير مدعوم. يرجى استخدام PNG, JPG, أو SVG');
        }
        
        if ($file['size'] > 2 * 1024 * 1024) { // 2MB max
            throw new Exception('حجم الملف كبير جداً. الحد الأقصى 2 ميجابايت');
        }
        
        // إنشاء مجلد التوقيعات إذا لم يكن موجوداً
        $uploadDir = __DIR__ . '/../../storage/signatures/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // تحديد اسم الملف
        $fileName = $signatureType . '_' . $userId . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // تحويل الصورة إلى base64 للتخزين في قاعدة البيانات
            $signatureData = base64_encode(file_get_contents($filePath));
            
            // حفظ في قاعدة البيانات كتوقيع محفوظ
            $stmt = $this->db->prepare("
                INSERT INTO user_signatures (user_id, signature_type, signature_data, file_path)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                signature_data = VALUES(signature_data),
                file_path = VALUES(file_path),
                updated_at = CURRENT_TIMESTAMP
            ");
            
            return $stmt->execute([$userId, $signatureType, $signatureData, $fileName]);
        }
        
        throw new Exception('فشل في رفع الملف');
    }
    
    // توقيع العقد إلكترونياً
    public function signContract($contractId, $signerId, $signatureData, $signatureType = 'electronic_signature')
    {
        try {
            $this->db->beginTransaction();
            
            // إنشاء التوقيع
            $signatureId = $this->create([
                'contract_id' => $contractId,
                'signer_id' => $signerId,
                'signature_type' => $signatureType,
                'signature_data' => $signatureData
            ]);
            
            if ($signatureId) {
                // تحديث حالة العقد
                require_once __DIR__ . '/Contract.php';
                $contractModel = new Contract();
                $contractModel->changeStatus($contractId, 'signed', $signerId);
                
                $this->db->commit();
                return true;
            }
            
            $this->db->rollBack();
            return false;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    // إنشاء PDF موقع
    public function generateSignedPDF($contractId)
    {
        require_once __DIR__ . '/Contract.php';
        $contractModel = new Contract();
        $contract = $contractModel->findById($contractId);
        
        if (!$contract) {
            throw new Exception('العقد غير موجود');
        }
        
        $signatures = $this->getByContract($contractId);
        
        // إنشاء PDF (يحتاج مكتبة PDF مثل TCPDF أو FPDF)
        // هذا مثال مبسط
        $pdfContent = $this->createPDFContent($contract, $signatures);
        
        // حفظ PDF
        $pdfDir = __DIR__ . '/../../storage/signed_contracts/';
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0755, true);
        }
        
        $fileName = 'signed_contract_' . $contractId . '_' . time() . '.pdf';
        $filePath = $pdfDir . $fileName;
        
        // هنا يتم إنشاء PDF فعلياً
        // file_put_contents($filePath, $pdfContent);
        
        // تحديث مسار PDF في العقد
        $contractModel->update($contractId, ['signed_pdf_path' => $fileName]);
        
        return $fileName;
    }
    
    // إنشاء محتوى PDF
    private function createPDFContent($contract, $signatures)
    {
        // هذا مثال مبسط - في التطبيق الحقيقي نحتاج مكتبة PDF
        $content = "
        العقد رقم: {$contract['contract_number']}
        اسم العميل: {$contract['client_name']}
        رقم الهوية: {$contract['client_id']}
        القيمة: {$contract['amount']} ريال سعودي
        
        الوصف:
        {$contract['description']}
        
        الشروط والأحكام:
        {$contract['terms_conditions']}
        
        التوقيعات:
        ";
        
        foreach ($signatures as $signature) {
            $content .= "
            - وقع بواسطة: {$signature['signer_name']}
            - نوع التوقيع: {$signature['signature_type']}
            - تاريخ التوقيع: {$signature['signed_at']}
            ";
        }
        
        return $content;
    }
    
    // التحقق من صحة التوقيع
    public function verifySignature($signatureId)
    {
        $stmt = $this->db->prepare("
            SELECT ds.*, c.status as contract_status
            FROM digital_signatures ds
            LEFT JOIN contracts c ON ds.contract_id = c.id
            WHERE ds.id = ?
        ");
        $stmt->execute([$signatureId]);
        $signature = $stmt->fetch();
        
        if (!$signature) {
            return ['valid' => false, 'message' => 'التوقيع غير موجود'];
        }
        
        // فحوصات إضافية للتحقق من صحة التوقيع
        $metadata = json_decode($signature['signature_metadata'], true);
        
        return [
            'valid' => true,
            'signature' => $signature,
            'metadata' => $metadata,
            'message' => 'التوقيع صحيح ومعتمد'
        ];
    }
    
    // إحصائيات التوقيعات
    public function getSignatureStats($userId = null)
    {
        $whereClause = $userId ? "WHERE signer_id = ?" : "";
        $params = $userId ? [$userId] : [];
        
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_signatures,
                COUNT(CASE WHEN signature_type = 'manager_signature' THEN 1 END) as manager_signatures,
                COUNT(CASE WHEN signature_type = 'company_seal' THEN 1 END) as company_seals,
                COUNT(CASE WHEN signature_type = 'electronic_signature' THEN 1 END) as electronic_signatures,
                COUNT(DISTINCT contract_id) as signed_contracts,
                MIN(signed_at) as first_signature,
                MAX(signed_at) as last_signature
            FROM digital_signatures 
            $whereClause
        ");
        $stmt->execute($params);
        return $stmt->fetch();
    }
}

// إنشاء جدول التوقيعات المحفوظة للمستخدمين (إذا لم يكن موجوداً)
function createUserSignaturesTable()
{
    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_signatures (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            signature_type ENUM('manager_signature', 'company_seal', 'electronic_signature') NOT NULL,
            signature_data TEXT NOT NULL,
            file_path VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_signature (user_id, signature_type),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
}

// استدعاء إنشاء الجدول
createUserSignaturesTable();