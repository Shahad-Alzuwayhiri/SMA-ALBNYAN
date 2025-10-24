-- نظام إدارة العقود - سما البنيان التجارية
-- إنشاء قاعدة البيانات والجداول المطلوبة

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
    type ENUM('contract_created', 'contract_pending', 'contract_signed', 'contract_rejected', 'contract_returned') NOT NULL,
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

-- إدراج بيانات تجريبية للمستخدمين
INSERT INTO users (name, email, phone, password, role, permissions) VALUES 
('أحمد المدير', 'manager@sama.com', '0501234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 
 '{"view_all_contracts": true, "sign_contracts": true, "manage_employees": true, "view_reports": true, "approve_contracts": true}'),
('سارة الموظفة', 'employee@sama.com', '0509876543', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 
 '{"create_contracts": true, "edit_own_contracts": true, "upload_files": true, "view_own_contracts": true}'),
('محمد الموظف', 'employee2@sama.com', '0505551234', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 
 '{"create_contracts": true, "edit_own_contracts": true, "upload_files": true, "view_own_contracts": true}');

-- إدراج عقود تجريبية
INSERT INTO contracts (contract_number, title, second_party_name, second_party_phone, second_party_email, contract_amount, profit_percentage, start_date, end_date, status, description, created_by) VALUES
('SB-2024-001', 'عقد توريد مواد بناء', 'شركة الخليج للمقاولات', '0501111111', 'gulf@example.com', 250000.00, 15.00, '2024-01-15', '2024-06-15', 'pending_review', 'عقد توريد مواد بناء أساسية للمشروع السكني الجديد', 2),
('SB-2024-002', 'عقد صيانة المباني', 'مؤسسة النخبة للصيانة', '0502222222', 'elite@example.com', 180000.00, 20.00, '2024-02-01', '2025-02-01', 'draft', 'عقد صيانة دورية للمباني التجارية', 2),
('SB-2024-003', 'عقد استشارات هندسية', 'مكتب الهندسي المتميز', '0503333333', 'engineering@example.com', 75000.00, 25.00, '2024-01-20', '2024-04-20', 'signed', 'استشارات هندسية للمشاريع الجديدة', 3);

-- إدراج إشعارات تجريبية
INSERT INTO notifications (user_id, contract_id, type, title, message) VALUES
(1, 1, 'contract_pending', 'عقد جديد بانتظار المراجعة', 'العقد رقم SB-2024-001 بانتظار موافقتك وتوقيعك'),
(1, 2, 'contract_created', 'عقد جديد تم إنشاؤه', 'تم إنشاء العقد رقم SB-2024-002 من قبل سارة الموظفة'),
(2, 3, 'contract_signed', 'تم توقيع العقد', 'تم توقيع العقد رقم SB-2024-003 بنجاح'),
(3, 3, 'contract_signed', 'تم توقيع عقدك', 'تم توقيع العقد رقم SB-2024-003 من قبل المدير');

-- إدراج تاريخ العقود
INSERT INTO contract_history (contract_id, action, previous_status, new_status, comment, action_by) VALUES
(1, 'created', NULL, 'draft', 'تم إنشاء العقد', 2),
(1, 'submitted', 'draft', 'pending_review', 'تم رفع العقد للمراجعة', 2),
(2, 'created', NULL, 'draft', 'تم إنشاء العقد', 2),
(3, 'created', NULL, 'draft', 'تم إنشاء العقد', 3),
(3, 'submitted', 'draft', 'pending_review', 'تم رفع العقد للمراجعة', 3),
(3, 'signed', 'pending_review', 'signed', 'تم توقيع العقد', 1);

-- إنشاء الفهارس لتحسين الأداء
CREATE INDEX idx_contracts_status ON contracts(status);
CREATE INDEX idx_contracts_created_by ON contracts(created_by);
CREATE INDEX idx_contracts_contract_number ON contracts(contract_number);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_contract_history_contract_id ON contract_history(contract_id);