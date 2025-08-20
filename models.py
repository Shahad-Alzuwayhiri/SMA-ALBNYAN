# © 2025 ContractSama. All rights reserved.

import sqlite3, uuid
from werkzeug.security import generate_password_hash, check_password_hash

DB_NAME = "users.db"

def _conn():
    return sqlite3.connect(DB_NAME)

def init_db():
    conn = _conn(); cur = conn.cursor()

    # جدول المستخدمين — فقط (الاسم، الجوال، البريد، كلمة المرور)
    cur.execute("""
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name   TEXT NOT NULL,
            phone  TEXT NOT NULL UNIQUE,
            email  TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at TEXT DEFAULT (datetime('now'))
        )
    """)

    # جدول العقود
    cur.execute("""
        CREATE TABLE IF NOT EXISTS contracts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title   TEXT NOT NULL,
            content TEXT NOT NULL,
            serial  TEXT UNIQUE NOT NULL,
            user_id INTEGER NOT NULL,
            created_at TEXT DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    """)

    conn.commit(); conn.close()

# استدعاء أولي لضمان وجود الجداول
init_db()

# ---------- USERS ----------
def get_user_by_email(email: str):
    conn = _conn(); cur = conn.cursor()
    cur.execute("SELECT id, name, email, password FROM users WHERE email=?", (email,))
    row = cur.fetchone(); conn.close()
    return row

def get_user_by_id(uid: int):
    conn = _conn(); cur = conn.cursor()
    cur.execute("SELECT id, name, email, password FROM users WHERE id=?", (uid,))
    row = cur.fetchone(); conn.close()
    return row

def create_user(*, name, phone, email, password):
    try:
        pw_hash = generate_password_hash(password)
        conn = _conn(); cur = conn.cursor()
        cur.execute("""
            INSERT INTO users (name, phone, email, password)
            VALUES (?, ?, ?, ?)
        """, (name, phone, email, pw_hash))
        conn.commit(); conn.close()
        return True, "OK"
    except sqlite3.IntegrityError:
        return False, "⚠️ هذا البريد أو رقم الجوال مسجّل مسبقًا"
    except Exception as e:
        return False, f"خطأ غير متوقع: {e}"

def verify_login(email: str, password: str):
    row = get_user_by_email(email)
    if not row: return None
    uid, name, em, pw_hash = row
    if check_password_hash(pw_hash, password):
        return {"id": uid, "name": name, "email": em}
    return None

# ---------- CONTRACTS ----------
def _generate_serial():
    return f"CS-{str(uuid.uuid4())[:8].upper()}"

def create_contract(title, content, user_id: int):
    serial = _generate_serial()
    conn = _conn(); cur = conn.cursor()
    cur.execute("INSERT INTO contracts (title, content, serial, user_id) VALUES (?, ?, ?, ?)",
                (title, content, serial, user_id))
    conn.commit(); conn.close()
    return serial

def list_contracts(user_id: int):
    conn = _conn(); cur = conn.cursor()
    cur.execute("SELECT id, title, serial, created_at FROM contracts WHERE user_id=? ORDER BY id DESC", (user_id,))
    rows = cur.fetchall(); conn.close()
    return rows

def get_contract(contract_id: int, user_id: int):
    conn = _conn(); cur = conn.cursor()
    cur.execute("SELECT id, title, content, serial, created_at FROM contracts WHERE id=? AND user_id=?",
                (contract_id, user_id))
    row = cur.fetchone(); conn.close()
    return row

def update_contract(contract_id: int, title: str, content: str, user_id: int) -> bool:
    conn = _conn(); cur = conn.cursor()
    cur.execute("UPDATE contracts SET title=?, content=? WHERE id=? AND user_id=?",
                (title, content, contract_id, user_id))
    conn.commit(); changed = cur.rowcount; conn.close()
    return changed > 0

def delete_contract(contract_id: int, user_id: int) -> bool:
    conn = _conn(); cur = conn.cursor()
    cur.execute("DELETE FROM contracts WHERE id=? AND user_id=?", (contract_id, user_id))
    conn.commit(); changed = cur.rowcount; conn.close()
    return changed > 0
