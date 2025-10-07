<?php
/**
 * دوال مساعدة للإعدادات
 */

if (!function_exists('config')) {
    /**
     * الحصول على قيمة من ملف الإعدادات
     * 
     * @param string $key المفتاح (مثل: 'app.name' أو 'database.default')
     * @param mixed $default القيمة الافتراضية
     * @return mixed
     */
    function config($key, $default = null) {
        static $config = null;
        
        // تحميل الإعدادات لأول مرة
        if ($config === null) {
            $config = require __DIR__ . '/config.php';
        }
        
        // تقسيم المفتاح إلى أجزاء
        $keys = explode('.', $key);
        $value = $config;
        
        // البحث في الإعدادات
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
}

if (!function_exists('app_name')) {
    /**
     * الحصول على اسم التطبيق
     * 
     * @return string
     */
    function app_name() {
        return config('app.name', 'نظام إدارة العقود');
    }
}

if (!function_exists('app_version')) {
    /**
     * الحصول على إصدار التطبيق
     * 
     * @return string
     */
    function app_version() {
        return config('app.version', '1.0.0');
    }
}

if (!function_exists('is_debug_mode')) {
    /**
     * التحقق من وضع التطوير
     * 
     * @return bool
     */
    function is_debug_mode() {
        return config('app.debug', false);
    }
}

if (!function_exists('get_db_path')) {
    /**
     * الحصول على مسار قاعدة البيانات
     * 
     * @return string
     */
    function get_db_path() {
        return config('database.connections.sqlite.database');
    }
}

if (!function_exists('get_upload_path')) {
    /**
     * الحصول على مسار الملفات المرفوعة
     * 
     * @return string
     */
    function get_upload_path() {
        return config('uploads.upload_path');
    }
}

if (!function_exists('get_contract_prefix')) {
    /**
     * الحصول على بادئة أرقام العقود
     * 
     * @return string
     */
    function get_contract_prefix() {
        return config('system.contract_number_prefix', 'B');
    }
}

if (!function_exists('get_hijri_year')) {
    /**
     * الحصول على السنة الهجرية الحالية
     * 
     * @return int
     */
    function get_hijri_year() {
        return config('system.hijri_year', 1447);
    }
}