-- نظام إدارة العقود - سما البنيان التجارية
-- إنشاء قاعدة البيانات والجداول المطلوبة (بدون بيانات ثابتة)

CREATE DATABASE IF NOT EXISTS sama_contracts;
USE sama_contracts;

-- جدول المستخدمين (المديرين والموظفين)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('manager', 'employee') DEFAULT 'employee',
    permissions JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- جدول العقود
CREATE TABLE IF NOT EXISTS contracts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contract_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    second_party_name VARCHAR(255) NOT NULL,
    second_party_phone VARCHAR(20),
    second_party_email VARCHAR(255),
    contract_amount DECIMAL(15,2) NOT NULL,
    profit_percentage DECIMAL(5,2) DEFAULT 0.00,
    start_date DATE,
    end_date DATE,
    status ENUM('draft', 'pending_review', 'approved', 'signed', 'rejected', 'expired') DEFAULT 'draft',
    description TEXT,
    terms_conditions TEXT,
    created_by INT NOT NULL,
    reviewed_by INT DEFAULT NULL,
    signed_by INT DEFAULT NULL,
    pdf_path VARCHAR(500),
    signed_pdf_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (signed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- جدول التوقيعات الإلكترونية
CREATE TABLE IF NOT EXISTS digital_signatures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contract_id INT NOT NULL,
    signer_id INT NOT NULL,
    signature_type ENUM('manager_signature', 'company_seal', 'electronic_signature') DEFAULT 'electronic_signature',
    signature_data TEXT NOT NULL, -- Base64 encoded signature or seal image
    signature_metadata JSON DEFAULT NULL, -- Additional signature info (IP, timestamp, etc.)
    signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (signer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- جدول الإشعارات
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    contract_id INT DEFAULT NULL,
    type ENUM('contract_created', 'contract_pending', 'contract_signed', 'contract_rejected', 'contract_returned', 'general_notification', 'account_activated', 'account_deactivated') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
);

-- جدول تاريخ العقود (لتتبع التغييرات)
CREATE TABLE IF NOT EXISTS contract_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contract_id INT NOT NULL,
    action ENUM('created', 'edited', 'submitted', 'approved', 'rejected', 'signed', 'returned') NOT NULL,
    previous_status VARCHAR(50),
    new_status VARCHAR(50),
    comment TEXT,
    action_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (action_by) REFERENCES users(id) ON DELETE CASCADE
);

-- جدول الملفات المرفقة
CREATE TABLE IF NOT EXISTS contract_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contract_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- جدول التوقيعات المحفوظة للمستخدمين
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
);

-- إدراج حساب المدير الأساسي فقط (للدخول الأولي)
-- كلمة المرور: 123456
INSERT INTO users (name, email, phone, password, role, permissions) VALUES 
('مدير النظام', 'admin@sama.com', '0500000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 
 '{"view_all_contracts": true, "sign_contracts": true, "manage_employees": true, "view_reports": true, "approve_contracts": true, "reject_contracts": true, "edit_any_contract": true, "delete_contracts": true}')
ON DUPLICATE KEY UPDATE email = email;

-- إنشاء الفهارس لتحسين الأداء
CREATE INDEX idx_contracts_status ON contracts(status);
CREATE INDEX idx_contracts_created_by ON contracts(created_by);
CREATE INDEX idx_contracts_contract_number ON contracts(contract_number);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_contract_history_contract_id ON contract_history(contract_id);

-- إشعار ترحيبي للمدير
INSERT INTO notifications (user_id, type, title, message) VALUES 
(1, 'general_notification', 'مرحباً بك في نظام إدارة العقود', 'مرحباً بك في نظام إدارة العقود الخاص بمؤسسة سما البنيان التجارية. يمكنك الآن البدء بإضافة الموظفين وإدارة العقود.')
ON DUPLICATE KEY UPDATE title = title;