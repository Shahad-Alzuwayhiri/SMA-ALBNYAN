<?php
/**
 * ملف إعدادات المشروع الأساسي
 * بديل بسيط لملف Laravel config
 */

return [
    /**
     * معلومات التطبيق الأساسية
     */
    'app' => [
        'name' => 'نظام إدارة العقود - سما البنيان التجارية',
        'version' => '1.0.0',
        'debug' => true,
        'timezone' => 'Asia/Riyadh',
        'locale' => 'ar',
        'fallback_locale' => 'en',
    ],

    /**
     * إعدادات قاعدة البيانات
     */
    'database' => [
        'default' => 'sqlite',
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => __DIR__ . '/../database/contracts.db',
                'prefix' => '',
            ],
            'mysql' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => '3306',
                'database' => 'contracts_sama',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ],
        ],
    ],

    /**
     * إعدادات الجلسة
     */
    'session' => [
        'lifetime' => 120, // minutes
        'cookie_name' => 'sama_contracts_session',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => true,
    ],

    /**
     * إعدادات التشفير
     */
    'encryption' => [
        'key' => 'sama-contracts-encryption-key-2025',
        'cipher' => 'AES-256-CBC',
    ],

    /**
     * إعدادات الإشعارات
     */
    'notifications' => [
        'enabled' => true,
        'email_notifications' => false,
        'sms_notifications' => false,
    ],

    /**
     * إعدادات PDF
     */
    'pdf' => [
        'default_font' => 'Amiri',
        'fallback_font' => 'Cairo',
        'margin_top' => 15,
        'margin_bottom' => 15,
        'margin_left' => 15,
        'margin_right' => 15,
    ],

    /**
     * إعدادات الملفات المرفوعة
     */
    'uploads' => [
        'max_file_size' => 10485760, // 10MB
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
        'upload_path' => __DIR__ . '/../static/uploads/',
    ],

    /**
     * إعدادات النظام
     */
    'system' => [
        'contracts_per_page' => 20,
        'auto_backup' => true,
        'backup_interval' => 24, // hours
        'contract_number_prefix' => 'B',
        'hijri_year' => 1447,
    ],
];