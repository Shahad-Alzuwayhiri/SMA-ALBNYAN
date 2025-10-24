<?php
class OTPService {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
        $this->createOTPTable();
    }
    
    /**
     * إنشاء جدول OTP إذا لم يكن موجوداً
     */
    private function createOTPTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS otp_codes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                code TEXT NOT NULL,
                purpose TEXT NOT NULL DEFAULT 'login',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                is_used INTEGER DEFAULT 0
            )
        ";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * توليد كود OTP جديد
     */
    public function generateOTP($email, $purpose = 'login', $length = 6) {
        // توليد الكود
        $code = str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
        
        // تحديد وقت انتهاء الصلاحية (10 دقائق)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // حذف الأكواد القديمة غير المستخدمة لنفس البريد والغرض
        $this->cleanupOldCodes($email, $purpose);
        
        // حفظ الكود الجديد
        $stmt = $this->pdo->prepare("
            INSERT INTO otp_codes (email, code, purpose, expires_at)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$email, $code, $purpose, $expiresAt]);
        
        // محاولة إرسال الكود (في بيئة الإنتاج سيكون عبر SMS أو Email)
        $this->sendOTP($email, $code, $purpose);
        
        return $code;
    }
    
    /**
     * التحقق من صحة كود OTP
     */
    public function verifyOTP($email, $code, $purpose = 'login') {
        $stmt = $this->pdo->prepare("
            SELECT * FROM otp_codes 
            WHERE email = ? AND code = ? AND purpose = ? 
            AND is_used = 0 AND expires_at > CURRENT_TIMESTAMP
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        
        $stmt->execute([$email, $code, $purpose]);
        $otpRecord = $stmt->fetch();
        
        if ($otpRecord) {
            // تحديد الكود كمستخدم
            $updateStmt = $this->pdo->prepare("
                UPDATE otp_codes 
                SET is_used = 1, used_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $updateStmt->execute([$otpRecord['id']]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * حذف الأكواد القديمة
     */
    private function cleanupOldCodes($email, $purpose) {
        $stmt = $this->pdo->prepare("
            DELETE FROM otp_codes 
            WHERE email = ? AND purpose = ? AND (is_used = 1 OR expires_at < CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$email, $purpose]);
    }
    
    /**
     * إرسال كود OTP (محاكاة - في الإنتاج سيكون عبر SMS أو Email)
     */
    private function sendOTP($email, $code, $purpose) {
        // في بيئة التطوير، سنعرض الكود في ملف log
        $logMessage = date('Y-m-d H:i:s') . " - OTP for $email ($purpose): $code\n";
        file_put_contents('../storage/logs/otp.log', $logMessage, FILE_APPEND | LOCK_EX);
        
        // في الإنتاج، يمكن استخدام خدمة مثل Twilio للـ SMS أو PHPMailer للبريد الإلكتروني
        // مثال:
        // $this->sendSMS($phone, "كود التحقق الخاص بك: $code");
        // $this->sendEmail($email, "كود التحقق", "كود التحقق الخاص بك: $code");
        
        return true;
    }
    
    /**
     * التحقق من وجود كود OTP صالح
     */
    public function hasValidOTP($email, $purpose = 'login') {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM otp_codes 
            WHERE email = ? AND purpose = ? 
            AND is_used = 0 AND expires_at > CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([$email, $purpose]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * الحصول على آخر كود OTP تم إرساله (للاختبار فقط)
     */
    public function getLastOTP($email, $purpose = 'login') {
        $stmt = $this->pdo->prepare("
            SELECT code FROM otp_codes 
            WHERE email = ? AND purpose = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        
        $stmt->execute([$email, $purpose]);
        $result = $stmt->fetch();
        
        return $result ? $result['code'] : null;
    }
    
    /**
     * إحصائيات الأكواد
     */
    public function getOTPStats($email = null) {
        $whereClause = $email ? "WHERE email = ?" : "";
        $params = $email ? [$email] : [];
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_codes,
                COUNT(CASE WHEN is_used = 1 THEN 1 END) as used_codes,
                COUNT(CASE WHEN expires_at < CURRENT_TIMESTAMP AND is_used = 0 THEN 1 END) as expired_codes,
                COUNT(CASE WHEN expires_at > CURRENT_TIMESTAMP AND is_used = 0 THEN 1 END) as active_codes
            FROM otp_codes 
            $whereClause
        ");
        
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
?>