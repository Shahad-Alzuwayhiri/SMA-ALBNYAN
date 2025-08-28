import os

def get_db_url():
    """
    على Render نقرأ DATABASE_URL (Postgres).
    محليًا نرجع SQLite ملف users.db.
    """
    url = os.environ.get("DATABASE_URL")
    if url and url.startswith("postgres://"):
        # تعديل رابط Postgres ليتوافق مع SQLAlchemy
        url = url.replace("postgres://", "postgresql+psycopg2://", 1)
    return url or "sqlite:///users.db"
