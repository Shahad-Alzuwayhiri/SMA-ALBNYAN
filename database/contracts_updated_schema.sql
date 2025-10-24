-- نظام إدارة العقود - سما البنيان التجارية
-- مخطط قاعدة البيانات المحدث وفقاً للنموذج الرسمي للعقد

CREATE DATABASE IF NOT EXISTS sama_contracts_updated;
USE sama_contracts_updated;

-- جدول المستخدمين (المديرين والموظفين)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('manager', 'employee', 'admin') DEFAULT 'employee',
    permissions JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- جدول العقود المحدث وفقاً للنموذج الرسمي
CREATE TABLE IF NOT EXISTS contracts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contract_number VARCHAR(50) UNIQUE NOT NULL,
    
    -- بيانات الطرف الأول (الشركة) - ثابتة
    first_party_name VARCHAR(255) DEFAULT 'شركة سما البنيان التجارية',
    first_party_commercial_register VARCHAR(50) DEFAULT '4030533070',
    first_party_city VARCHAR(100) DEFAULT 'جدة',
    first_party_district VARCHAR(100) DEFAULT 'الحمدانية',
    first_party_representative VARCHAR(255) DEFAULT 'احمد عبد الله سعيد الزهراني',
    first_party_phone VARCHAR(20) DEFAULT '0537295224',
    first_party_address TEXT DEFAULT 'جدة - حي الحمدانية – شارع ياسر بن عامر',
    
    -- بيانات الطرف الثاني (العميل) - متغيرة
    second_party_name VARCHAR(255) NOT NULL,
    second_party_nationality VARCHAR(50) DEFAULT 'سعودي الجنسية',
    second_party_id VARCHAR(20) NOT NULL,
    second_party_phone VARCHAR(20) NOT NULL,
    second_party_email VARCHAR(255),
    second_party_city VARCHAR(100) DEFAULT 'جدة',
    second_party_district VARCHAR(100),
    second_party_street VARCHAR(255),
    
    -- تفاصيل العقد الأساسية
    contract_date DATE NOT NULL,
    hijri_date VARCHAR(50), -- التاريخ الهجري
    location VARCHAR(100) DEFAULT 'محافظة جدة',
    
    -- تفاصيل المضاربة والاستثمار
    investment_amount DECIMAL(15,2) NOT NULL, -- مبلغ المضاربة
    profit_percentage DECIMAL(5,2) NOT NULL DEFAULT 40.00, -- نسبة الأرباح (افتراضياً 40%)
    loss_responsibility ENUM('first_party', 'shared', 'second_party') DEFAULT 'shared', -- مسؤولية الخسائر
    
    -- مدة العقد
    contract_duration_months INT DEFAULT 6, -- مدة العقد بالأشهر
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_renewable BOOLEAN DEFAULT TRUE, -- قابل للتجديد
    
    -- شروط الاسترداد
    withdrawal_notice_days INT DEFAULT 60, -- فترة الإخطار للانسحاب بالأيام
    minimum_investment_period_months INT DEFAULT 6, -- الحد الأدنى لفترة الاستثمار
    profit_payment_deadline_days INT DEFAULT 15, -- مهلة دفع الأرباح بالأيام
    
    -- العمولة والمكافآت
    commission_percentage DECIMAL(5,2) DEFAULT 2.5, -- نسبة العمولة
    commission_conditions TEXT, -- شروط العمولة
    
    -- الشرط الجزائي
    penalty_amount DECIMAL(10,2) DEFAULT 3000.00, -- مبلغ الشرط الجزائي
    penalty_period_days INT DEFAULT 30, -- فترة الشرط الجزائي بالأيام
    
    -- تفاصيل إضافية
    project_description TEXT, -- وصف المشروع
    inheritance_clause TEXT, -- بند الوفاة والوراثة
    force_majeure_clause TEXT, -- بند القوة القاهرة
    legal_counsel_info VARCHAR(255) DEFAULT 'مكتب المحامي بشير بن عبد الله صديق كنسارة', -- معلومات المستشار القانوني
    
    -- حالة العقد
    status ENUM('draft', 'pending_review', 'approved', 'active', 'completed', 'cancelled', 'expired') DEFAULT 'draft',
    
    -- ملاحظات ومرفقات
    notes TEXT,
    special_conditions TEXT, -- شروط خاصة
    
    -- معلومات التوثيق
    created_by INT NOT NULL,
    reviewed_by INT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    
    -- ملفات العقد
    pdf_path VARCHAR(500),
    signed_pdf_path VARCHAR(500),
    attachments JSON DEFAULT NULL, -- مرفقات العقد
    
    -- طوابع زمنية
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    signed_at TIMESTAMP NULL,
    
    -- مفاتيح خارجية
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- فهارس للبحث السريع
    INDEX idx_contract_number (contract_number),
    INDEX idx_second_party_name (second_party_name),
    INDEX idx_second_party_id (second_party_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_created_at (created_at)
);

-- جدول سجل تعديلات العقود
CREATE TABLE IF NOT EXISTS contract_revisions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contract_id INT NOT NULL,
    revision_number INT NOT NULL,
    changes_description TEXT,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    old_values JSON,
    new_values JSON,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_contract_revisions (contract_id, revision_number)
);

-- جدول التوقيعات الإلكترونية
CREATE TABLE IF NOT EXISTS digital_signatures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contract_id INT NOT NULL,
    signer_name VARCHAR(255) NOT NULL,
    signer_role ENUM('first_party', 'second_party', 'witness', 'legal_counsel') NOT NULL,
    signature_data TEXT, -- بيانات التوقيع المشفرة
    signature_image_path VARCHAR(500),
    ip_address VARCHAR(45),
    user_agent TEXT,
    signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_method VARCHAR(100),
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    INDEX idx_contract_signatures (contract_id)
);

-- جدول مرفقات العقود
CREATE TABLE IF NOT EXISTS contract_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contract_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size BIGINT,
    description TEXT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_contract_attachments (contract_id)
);

-- جدول سجل الأنشطة
CREATE TABLE IF NOT EXISTS activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contract_id INT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_activity_log (contract_id, created_at),
    INDEX idx_user_activity (user_id, created_at)
);

-- جدول الإشعارات
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    contract_id INT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, is_read, created_at)
);

-- إدراج المستخدم الافتراضي (Admin)
INSERT INTO users (name, email, phone, password, role) VALUES 
('إدارة النظام', 'admin@sma-albnyan.com', '0537295224', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('أحمد عبدالله الزهراني', 'ahmed@sma-albnyan.com', '0537295224', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager');

-- Views مفيدة للتقارير
CREATE VIEW contract_summary AS
SELECT 
    c.id,
    c.contract_number,
    c.second_party_name,
    c.investment_amount,
    c.profit_percentage,
    c.start_date,
    c.end_date,
    c.status,
    DATEDIFF(c.end_date, c.start_date) as duration_days,
    (c.investment_amount * c.profit_percentage / 100) as expected_profit,
    u1.name as created_by_name,
    u2.name as reviewed_by_name,
    c.created_at
FROM contracts c
LEFT JOIN users u1 ON c.created_by = u1.id
LEFT JOIN users u2 ON c.reviewed_by = u2.id;

-- إجراءات مخزنة لحساب الأرباح
DELIMITER //
CREATE PROCEDURE CalculateContractProfits(IN contract_id INT)
BEGIN
    DECLARE investment DECIMAL(15,2);
    DECLARE profit_rate DECIMAL(5,2);
    DECLARE expected_profit DECIMAL(15,2);
    
    SELECT investment_amount, profit_percentage 
    INTO investment, profit_rate
    FROM contracts 
    WHERE id = contract_id;
    
    SET expected_profit = investment * profit_rate / 100;
    
    SELECT 
        investment as investment_amount,
        profit_rate as profit_percentage,
        expected_profit,
        (investment + expected_profit) as total_return;
END //
DELIMITER ;