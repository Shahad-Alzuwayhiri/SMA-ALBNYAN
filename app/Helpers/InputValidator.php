<?php

namespace App\Helpers;

/**
 * Input Validation and Sanitization Helper - مساعد التحقق من صحة البيانات وتنظيفها
 * Provides secure input validation and sanitization methods for user data
 * يوفر طرق آمنة للتحقق من صحة وتنظيف بيانات المستخدمين
 */
class InputValidator
{
    /**
     * Sanitize and validate email address - تنظيف والتحقق من صحة عنوان البريد الإلكتروني
     * @param string $email Email address to validate
     * @return string|false Clean email or false if invalid
     */
    public static function email($email)
    {
        $email = trim($email); // Remove whitespace - إزالة المسافات
        $email = filter_var($email, FILTER_SANITIZE_EMAIL); // Clean email - تنظيف البريد
        
        // Validate email format - التحقق من صيغة البريد
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        return $email;
    }
    
    /**
     * Sanitize string and remove harmful characters - تنظيف النص وإزالة الأحرف الضارة
     * @param string $input Input string to sanitize
     * @param int $maxLength Maximum allowed length
     * @return string Clean and safe string
     */
    public static function string($input, $maxLength = 255)
    {
        $input = trim($input); // Remove whitespace - إزالة المسافات
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); // Escape HTML - تشفير HTML
        $input = substr($input, 0, $maxLength); // Limit length - تحديد الطول
        
        return $input;
    }
    
    /**
     * Validate and sanitize password - التحقق من وتنظيف كلمة المرور
     * @param string $password Password to validate
     * @param int $minLength Minimum password length
     * @return string|false Clean password or false if invalid
     */
    public static function password($password, $minLength = 8)
    {
        if (strlen($password) < $minLength) {
            return false;
        }
        
        // Check for at least one letter and one number
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        return $password;
    }
    
    /**
     * Sanitize integer
     */
    public static function integer($input, $min = null, $max = null)
    {
        $input = filter_var($input, FILTER_VALIDATE_INT);
        
        if ($input === false) {
            return false;
        }
        
        if ($min !== null && $input < $min) {
            return false;
        }
        
        if ($max !== null && $input > $max) {
            return false;
        }
        
        return $input;
    }
    
    /**
     * Sanitize float/decimal
     */
    public static function float($input, $min = null, $max = null)
    {
        $input = filter_var($input, FILTER_VALIDATE_FLOAT);
        
        if ($input === false) {
            return false;
        }
        
        if ($min !== null && $input < $min) {
            return false;
        }
        
        if ($max !== null && $input > $max) {
            return false;
        }
        
        return $input;
    }
    
    /**
     * Validate date format
     */
    public static function date($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date ? $date : false;
    }
    
    /**
     * Sanitize phone number
     */
    public static function phone($phone)
    {
        $phone = preg_replace('/[^0-9+\-\s()]/', '', $phone);
        $phone = trim($phone);
        
        if (strlen($phone) < 10) {
            return false;
        }
        
        return $phone;
    }
    
    /**
     * Validate CSRF token
     */
    public static function csrfToken($token)
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate file upload
     */
    public static function fileUpload($file, $allowedTypes = [], $maxSize = 10485760)
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return false;
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($file['tmp_name']);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize HTML content (for rich text)
     */
    public static function html($input, $allowedTags = '')
    {
        return strip_tags($input, $allowedTags);
    }
}