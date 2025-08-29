import sqlite3
from werkzeug.security import generate_password_hash

try:
    # إنشاء (أو فتح إذا موجود) قاعدة بيانات باسم users.db
    conn = sqlite3.connect("users.db")
    cursor = conn.cursor()

    # إنشاء جدول المستخدمين
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        email TEXT UNIQUE NOT NULL,
        phone TEXT,
        password_hash TEXT NOT NULL,
        role TEXT CHECK(role IN ('employee','manager','admin')) DEFAULT 'employee'
    )
    """)

    # إدخال مدير تجريبي (يتفعل مرة وحدة)
    admin_email = "admin@company.com"
    admin_pass = generate_password_hash("12345678")  # كلمة مرور مشفرة
    cursor.execute("SELECT id FROM users WHERE email=?", (admin_email,))
    if not cursor.fetchone():
        cursor.execute(
            "INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)",
            ("مدير النظام", admin_email, "0500000000", admin_pass, "admin")
        )
        print("تم إنشاء مستخدم مدير تجريبي ✅")
    else:
        print("المستخدم موجود مسبقًا ✔")

    # حفظ التغييرات وإغلاق الاتصال
    conn.commit()
    print("تم إنشاء قاعدة البيانات بنجاح ✅")
except Exception as e:
    print(f"حدث خطأ أثناء إعداد قاعدة البيانات: {e}")
finally:
    if 'conn' in locals():
        conn.close()
