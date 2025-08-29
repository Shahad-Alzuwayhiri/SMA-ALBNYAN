import os

def get_db_url():
    """
    على Render نقرأ DATABASE_URL (Postgres).
    محليًا نرجع SQLite ملف users.db.
    """
    url = os.environ.get("DATABASE_URL")
    if url:
        if url.startswith("postgres://"):
            # تعديل رابط Postgres ليتوافق مع SQLAlchemy
            url = url.replace("postgres://", "postgresql+psycopg2://", 1)
        elif url.startswith("postgresql://"):
            # إذا كان الرابط بالفعل متوافق مع SQLAlchemy
            pass
        elif url.startswith("sqlite://"):
            # إذا كان الرابط SQLite
            pass
        else:
            # إذا كان الرابط غير مدعوم، استخدم SQLite محليًا
            url = None
    return url or "sqlite:///users.db"
