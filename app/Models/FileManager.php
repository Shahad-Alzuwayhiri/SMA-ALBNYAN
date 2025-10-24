<?php
/**
 * فئة إدارة الملفات - نظام سما البنيان
 * وظائف لرفع وحفظ وتحويل ملفات PDF إلى base64
 */

class FileManager {
    
    private $pdo;
    private $allowedTypes = ['pdf'];
    private $maxFileSize = 10485760; // 10MB
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * رفع وحفظ ملف PDF في قاعدة البيانات
     * @param string $filePath مسار ملف PDF
     * @param int $contractId معرف العقد
     * @param int $uploadedBy معرف المستخدم الذي رفع الملف
     * @return array نتيجة العملية
     */
    public function uploadPdfFile($filePath, $contractId, $uploadedBy) {
        try {
            // التحقق من وجود الملف
            if (!file_exists($filePath)) {
                return ['success' => false, 'message' => 'الملف غير موجود'];
            }
            
            // التحقق من نوع الملف
            $fileInfo = pathinfo($filePath);
            if (!isset($fileInfo['extension']) || strtolower($fileInfo['extension']) !== 'pdf') {
                return ['success' => false, 'message' => 'نوع الملف غير مدعوم. يجب أن يكون PDF فقط'];
            }
            
            // التحقق من حجم الملف
            $fileSize = filesize($filePath);
            if ($fileSize > $this->maxFileSize) {
                return ['success' => false, 'message' => 'حجم الملف كبير جداً. الحد الأقصى 10MB'];
            }
            
            // قراءة محتوى الملف
            $pdfContent = file_get_contents($filePath);
            if ($pdfContent === false) {
                return ['success' => false, 'message' => 'فشل في قراءة الملف'];
            }
            
            // تحويل إلى base64
            $base64String = base64_encode($pdfContent);
            
            // حفظ في قاعدة البيانات
            $result = $this->saveToDatabase($base64String, $fileInfo['basename'], $fileSize, $contractId, $uploadedBy);
            
            return $result;
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'خطأ في النظام: ' . $e->getMessage()];
        }
    }
    
    /**
     * حفظ الملف المحول إلى base64 في قاعدة البيانات
     */
    private function saveToDatabase($base64String, $fileName, $fileSize, $contractId, $uploadedBy) {
        try {
            $sql = "INSERT INTO files (contract_id, file_name, file_size, encoded_string, uploaded_by) 
                    VALUES (:contract_id, :file_name, :file_size, :encoded_string, :uploaded_by)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':contract_id' => $contractId,
                ':file_name' => $fileName,
                ':file_size' => $fileSize,
                ':encoded_string' => $base64String,
                ':uploaded_by' => $uploadedBy
            ]);
            
            if ($result) {
                $fileId = $this->pdo->lastInsertId();
                return [
                    'success' => true, 
                    'message' => 'تم رفع الملف بنجاح',
                    'file_id' => $fileId
                ];
            } else {
                return ['success' => false, 'message' => 'فشل في حفظ الملف في قاعدة البيانات'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
        }
    }
    
    /**
     * استرداد ملف PDF من قاعدة البيانات
     * @param int $fileId معرف الملف
     * @return array|false بيانات الملف أو false
     */
    public function getFile($fileId) {
        try {
            $sql = "SELECT * FROM files WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $fileId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * استرداد جميع ملفات عقد معين
     * @param int $contractId معرف العقد
     * @return array قائمة الملفات
     */
    public function getContractFiles($contractId) {
        try {
            $sql = "SELECT f.*, u.name as uploaded_by_name 
                    FROM files f 
                    LEFT JOIN users u ON f.uploaded_by = u.id 
                    WHERE f.contract_id = :contract_id 
                    ORDER BY f.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':contract_id' => $contractId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * تنزيل ملف PDF
     * @param int $fileId معرف الملف
     */
    public function downloadFile($fileId) {
        $file = $this->getFile($fileId);
        
        if (!$file) {
            header('HTTP/1.0 404 Not Found');
            die('الملف غير موجود');
        }
        
        // فك تشفير base64
        $pdfContent = base64_decode($file['encoded_string']);
        
        // إعداد headers للتنزيل
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $pdfContent;
        exit;
    }
    
    /**
     * عرض ملف PDF في المتصفح
     * @param int $fileId معرف الملف
     */
    public function viewFile($fileId) {
        $file = $this->getFile($fileId);
        
        if (!$file) {
            header('HTTP/1.0 404 Not Found');
            die('الملف غير موجود');
        }
        
        // فك تشفير base64
        $pdfContent = base64_decode($file['encoded_string']);
        
        // إعداد headers للعرض
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $file['file_name'] . '"');
        header('Content-Length: ' . strlen($pdfContent));
        
        echo $pdfContent;
        exit;
    }
    
    /**
     * حذف ملف
     * @param int $fileId معرف الملف
     * @return bool نتيجة الحذف
     */
    public function deleteFile($fileId) {
        try {
            $sql = "DELETE FROM files WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $fileId]);
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * الحصول على إحصائيات الملفات
     * @return array الإحصائيات
     */
    public function getFilesStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_files,
                        SUM(file_size) as total_size,
                        AVG(file_size) as avg_size
                    FROM files";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'avg_size' => 0
            ];
        }
    }
}
?>