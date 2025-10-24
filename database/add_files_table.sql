-- إضافة جدول الملفات لحفظ ملفات PDF كـ base64
-- تاريخ الإنشاء: 2025-10-08

-- جدول الملفات
CREATE TABLE IF NOT EXISTS files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contract_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) DEFAULT 'pdf',
    file_size INT,
    encoded_string LONGTEXT NOT NULL,
    mime_type VARCHAR(100) DEFAULT 'application/pdf',
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- إضافة فهرس لتحسين الأداء
CREATE INDEX idx_files_contract_id ON files(contract_id);
CREATE INDEX idx_files_created_at ON files(created_at);