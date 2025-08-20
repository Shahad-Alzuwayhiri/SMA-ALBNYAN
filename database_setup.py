import sqlite3

# إنشاء (أو فتح إذا موجود) قاعدة بيانات باسم users.db
conn = sqlite3.connect("users.db")
cursor = conn.cursor()

# إنشاء جدول المستخدمين
cursor.execute("""
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL
)
""")

# إدخال موظف تجريبي
cursor.execute("INSERT INTO users (email, password) VALUES (?, ?)", ("admin@company.com", "1234"))

# حفظ التغييرات وإغلاق الاتصال
conn.commit()
conn.close()

print("تم إنشاء قاعدة البيانات وإضافة مستخدم تجريبي ✅")
