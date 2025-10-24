<?php
/**
 * إعدادات التطبيق العامة
 * General Application Configuration
 */

return [
    'name' => 'سما البنيان لإدارة العقود',
    'env' => 'development', // production, development, testing
    'debug' => true,
    'timezone' => 'Asia/Riyadh',
    'locale' => 'ar',
    'fallback_locale' => 'en',
    
    // URLs and Paths
    'base_url' => 'http://localhost:8084',
    'asset_url' => 'http://localhost:8084/assets',
    
    // Company Information
    'company' => [
        'name_ar' => 'شركة سما البنيان التجارية',
        'name_en' => 'SMA ALBNYAN COMPANY',
        'phone' => '+966 12 234 5678',
        'email' => 'info@sama-albonyan.com',
        'address' => 'جدة، المملكة العربية السعودية',
        'logo' => '/assets/images/sma-logo.svg'
    ],
    
    // Security Settings
    'security' => [
        'session_lifetime' => 120, // minutes
        'csrf_token_length' => 32,
        'password_min_length' => 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 15 // minutes
    ],
    
    // File Upload Settings
    'upload' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
        'path' => 'uploads/',
        'contracts_path' => 'uploads/contracts/',
        'avatars_path' => 'uploads/avatars/'
    ],
    
    // Pagination Settings
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100
    ],
    
    // PDF Settings
    'pdf' => [
        'watermark' => true,
        'company_logo' => true,
        'default_font' => 'Amiri',
        'fallback_font' => 'Cairo',
        'font_size' => 12,
        'margin' => [
            'top' => 15,
            'right' => 15,
            'bottom' => 15,
            'left' => 15
        ]
    ],
    
    // Encryption Settings
    'encryption' => [
        'key' => 'sama-contracts-encryption-key-2025',
        'cipher' => 'AES-256-CBC',
    ],
    
    // Notification Settings
    'notifications' => [
        'enabled' => true,
        'email_notifications' => false,
        'sms_notifications' => false,
    ],
    
    // System Settings
    'system' => [
        'contracts_per_page' => 20,
        'auto_backup' => true,
        'backup_interval' => 24, // hours
        'contract_number_prefix' => 'B',
        'hijri_year' => 1447,
    ]
];