# models.py
# © 2025 ContractSama. All rights reserved.

import os
from datetime import datetime
from typing import Optional, Tuple, List

from sqlalchemy import (
    create_engine, Column, Integer, String, Text, Float, DateTime, ForeignKey,
    UniqueConstraint, select, or_, func
)
from sqlalchemy.orm import declarative_base, relationship, sessionmaker, Session
from werkzeug.security import generate_password_hash, check_password_hash

# -------------------------------------------------------------------
# إعداد قاعدة البيانات (نفس منطق app.py: إذا ما فيه DATABASE_URL استخدم SQLite)
# -------------------------------------------------------------------
DATABASE_URL = (os.getenv("DATABASE_URL") or "").strip()
if not DATABASE_URL:
    DATABASE_URL = "sqlite:///users.db"

# ملاحظة: إذا كنت تستخدم psycopg على Render أحيانًا تحتاج تصحيح البروتوكول
if DATABASE_URL.startswith("postgres://"):
    DATABASE_URL = DATABASE_URL.replace("postgres://", "postgresql+psycopg2://", 1)

engine = create_engine(DATABASE_URL, echo=False, future=True)
SessionLocal = sessionmaker(bind=engine, autoflush=False, autocommit=False, future=True)

Base = declarative_base()
def user_dashboard_metrics(user_id: int) -> dict:
    """مجاميع موظف واحد لصفحة الداش بورد."""
    session = get_session()
    try:
        total_invest = session.query(
            func.coalesce(func.sum(Contract.investment_amount), 0.0)
        ).filter(Contract.user_id == user_id).scalar() or 0.0

        total_contracts = session.query(
            func.count(Contract.id)
        ).filter(Contract.user_id == user_id).scalar() or 0

        last = session.query(
            Contract.client_contract_no, Contract.internal_serial
        ).filter(Contract.user_id == user_id)\
         .order_by(Contract.id.desc()).first()

        last_serial = (last[0] or last[1]) if last else "-"

        return {
            "total_invest": float(total_invest),
            "total_contracts": int(total_contracts),
            "last_serial": last_serial,
        }
    finally:
        session.close()

# -------------------------------------------------------------------
# نماذج الجداول
# -------------------------------------------------------------------
class User(Base):
    __tablename__ = "users"

    id            = Column(Integer, primary_key=True)
    name          = Column(String(120), nullable=False)
    email         = Column(String(120), unique=True, nullable=False)
    phone         = Column(String(20), nullable=True)
    password_hash = Column(String(256), nullable=False)
    role          = Column(String(20), nullable=False, default="employee")  # employee / manager
    stamp_path    = Column(String(255), nullable=True)  # ختم المدير (اختياري)

    created_at    = Column(DateTime, default=datetime.utcnow, nullable=False)

    contracts     = relationship("Contract", back_populates="user")

    # أدوات مساعدة داخلية (لا تُستدعى مباشرة من app.py)
    def set_password(self, password: str) -> None:
        self.password_hash = generate_password_hash(password)

    def check_password(self, password: str) -> bool:
        return check_password_hash(self.password_hash, password)


class Contract(Base):
    __tablename__ = "contracts"
    __table_args__ = (
        UniqueConstraint("client_contract_no", name="uq_contract_client_no"),
    )

    id                = Column(Integer, primary_key=True)
    title             = Column(String(255), nullable=False)
    content           = Column(Text, nullable=False, default="")

    # أرقام
    internal_serial   = Column(Integer, nullable=False)               # الرقم التسلسلي الداخلي
    client_contract_no= Column(String(50), nullable=False, unique=True)  # رقم العقد المعروض للعميل (PREFIX-0001 ...)

    created_at        = Column(DateTime, default=datetime.utcnow, nullable=False)

    # بيانات العميل
    client_name       = Column(String(120), nullable=True)
    client_id_number  = Column(String(20),  nullable=True)
    client_phone      = Column(String(20),  nullable=True)
    client_address    = Column(String(255), nullable=True)

    # مالية
    investment_amount       = Column(Float, nullable=True)
    capital_amount          = Column(Float, nullable=True)
    profit_percent          = Column(Float, nullable=True)
    profit_interval_months  = Column(Integer, nullable=True)
    withdrawal_notice_days  = Column(Integer, nullable=True)
    start_date_h            = Column(String(20), nullable=True)
    end_date_h              = Column(String(20), nullable=True)
    commission_percent      = Column(Float, nullable=True)
    exit_notice_days        = Column(Integer, nullable=True)
    jurisdiction            = Column(String(120), nullable=True)

    # توقيع
    signature_path     = Column(String(255), nullable=True)

    # حالة وإجراءات المدير
    status             = Column(String(20), nullable=False, default="pending")  # pending / approved / rejected
    manager_note       = Column(String(255), nullable=True)

    # معلومات عن القالب المستخدم
    template_text      = Column(String(50), nullable=True)

    # علاقة المستخدم المنشئ
    user_id            = Column(Integer, ForeignKey("users.id"), nullable=False)
    user               = relationship("User", back_populates="contracts")


class ContractNumberCounter(Base):
    """
    عداد لكل بادئة PREFIX لضمان عدم تضارب أرقام client_contract_no
    الحقول:
      - prefix (مثلاً B123-1447)
      - last_no (آخر رقم مُستخدم)
    """
    __tablename__ = "contract_counters"
    __table_args__ = (
        UniqueConstraint("prefix", name="uq_counter_prefix"),
    )

    id      = Column(Integer, primary_key=True)
    prefix  = Column(String(50), nullable=False, unique=True)
    last_no = Column(Integer, nullable=False, default=0)


# -------------------------------------------------------------------
# تهيئة القاعدة والـ Session
# -------------------------------------------------------------------
def init_db() -> None:
    """إنشاء الجداول إذا لم تكن موجودة."""
    Base.metadata.create_all(bind=engine)


def get_session() -> Session:
    """ارجع Session جديدة. لا تنسَ session.close() بعد الاستخدام."""
    return SessionLocal()


# -------------------------------------------------------------------
# دوال المستخدمين
# -------------------------------------------------------------------
def get_user_by_id(uid: int) -> Optional[User]:
    s = get_session()
    try:
        return s.get(User, uid)
    finally:
        s.close()


def get_user_by_email(email: str) -> Optional[User]:
    s = get_session()
    try:
        stmt = select(User).where(func.lower(User.email) == func.lower(email))
        return s.execute(stmt).scalars().first()
    finally:
        s.close()


def create_user(*, name: str, phone: Optional[str], email: str, password: str) -> Tuple[bool, str]:
    """
    إنشاء مستخدم جديد (موظف افتراضيًا). إذا أردتِ مدير: غيّري role بعد الإنشاء أو أضيفي دالة خاصة.
    يعيد (ok, msg)
    """
    s = get_session()
    try:
        exists = s.execute(select(User).where(func.lower(User.email) == func.lower(email))).scalar_one_or_none()
        if exists:
            return False, "البريد مستخدم من قبل."

        u = User(name=name.strip(), phone=(phone or None), email=email.strip().lower(), role="employee")
        u.set_password(password)
        s.add(u)
        s.commit()
        return True, "تم إنشاء المستخدم."
    except Exception as e:
        s.rollback()
        return False, f"خطأ: {e}"
    finally:
        s.close()


def verify_login(email: str, password: str) -> Optional[dict]:
    """
    تحقق دخول: يعيد dict بسيط {id, name, email, role} أو None.
    """
    s = get_session()
    try:
        stmt = select(User).where(func.lower(User.email) == func.lower(email.strip()))
        u = s.execute(stmt).scalars().first()
        if u and u.check_password(password):
            return {"id": u.id, "name": u.name, "email": u.email, "role": u.role}
        return None
    finally:
        s.close()


def update_user_password(*, email: str, new_password: str) -> Tuple[bool, str]:
    s = get_session()
    try:
        stmt = select(User).where(func.lower(User.email) == func.lower(email.strip()))
        u = s.execute(stmt).scalars().first()
        if not u:
            return False, "المستخدم غير موجود."
        u.set_password(new_password)
        s.commit()
        return True, "تم تحديث كلمة المرور."
    except Exception as e:
        s.rollback()
        return False, f"خطأ: {e}"
    finally:
        s.close()


# -------------------------------------------------------------------
# أدوات أرقام العقود
# -------------------------------------------------------------------
def _allocate_serial(prefix: str) -> Tuple[int, str]:
    """
    داخل معاملة: يجلب/ينشئ عدّاد البادئة ثم يزيده ويعيد:
      (internal_serial:int, client_contract_no:str)
    """
    s = get_session()
    try:
        # نحتاج قفل/تسلسل بدائي: في SQLite يكفي معاملة. في Postgres يُفضل SELECT FOR UPDATE.
        counter = s.execute(select(ContractNumberCounter).where(ContractNumberCounter.prefix == prefix)).scalars().first()
        if not counter:
            counter = ContractNumberCounter(prefix=prefix, last_no=0)
            s.add(counter)
            s.flush()  # للحصول على id

        counter.last_no += 1
        new_no = counter.last_no
        client_no = f"{prefix}-{new_no:04d}"

        s.commit()
        return new_no, client_no
    except Exception:
        s.rollback()
        raise
    finally:
        s.close()


# -------------------------------------------------------------------
# دوال العقود (CRUD + قوائم)
# -------------------------------------------------------------------
def create_contract(
    *,
    user_id: int,
    title: str,
    content_final: str,
    prefix: str,
    client_name: Optional[str],
    client_id_number: Optional[str],
    client_phone: Optional[str],
    client_address: Optional[str],
    investment_amount: Optional[float],
    signature_path: Optional[str],
    template_text: Optional[str] = None,
    # الحقول الإضافية (اختيارية):
    capital_amount: Optional[float] = None,
    profit_percent: Optional[float] = None,
    profit_interval_months: Optional[int] = None,
    withdrawal_notice_days: Optional[int] = None,
    start_date_h: Optional[str] = None,
    end_date_h: Optional[str] = None,
    commission_percent: Optional[float] = None,
    exit_notice_days: Optional[int] = None,
    jurisdiction: Optional[str] = None,
) -> Tuple[int, int, str]:
    """
    ينشئ عقدًا جديدًا مع حجز رقم حقيقي (client_contract_no) من العداد.
    يعيد: (new_id, internal_serial, real_no)
    """
    # أولاً: خصصي رقمًا حقيقيًا
    internal_serial, real_no = _allocate_serial(prefix)

    s = get_session()
    try:
        c = Contract(
            title=title.strip(),
            content=(content_final or ""),
            internal_serial=internal_serial,
            client_contract_no=real_no,
            user_id=user_id,

            client_name=(client_name or None),
            client_id_number=(client_id_number or None),
            client_phone=(client_phone or None),
            client_address=(client_address or None),
            investment_amount=investment_amount,

            signature_path=signature_path,
            template_text=(template_text or None),

            # الإضافية
            capital_amount=capital_amount,
            profit_percent=profit_percent,
            profit_interval_months=profit_interval_months,
            withdrawal_notice_days=withdrawal_notice_days,
            start_date_h=start_date_h,
            end_date_h=end_date_h,
            commission_percent=commission_percent,
            exit_notice_days=exit_notice_days,
            jurisdiction=jurisdiction,
        )
        s.add(c)
        s.commit()
        return c.id, internal_serial, real_no
    except Exception as e:
        s.rollback()
        # في حال فشل الإدراج بعد حجز الرقم، الرقم يبقى مستخدمًا — وهذا سلوك مقبول عادة.
        raise e
    finally:
        s.close()


def update_contract(
    cid: int,
    user_id: int,
    *,
    title: Optional[str] = None,
    content: Optional[str] = None,
    client_name: Optional[str] = None,
    client_id_number: Optional[str] = None,
    client_phone: Optional[str] = None,
    client_address: Optional[str] = None,
    investment_amount: Optional[float] = None,
    # الإضافية
    capital_amount: Optional[float] = None,
    profit_percent: Optional[float] = None,
    profit_interval_months: Optional[int] = None,
    withdrawal_notice_days: Optional[int] = None,
    start_date_h: Optional[str] = None,
    end_date_h: Optional[str] = None,
    commission_percent: Optional[float] = None,
    exit_notice_days: Optional[int] = None,
    jurisdiction: Optional[str] = None,
) -> bool:
    s = get_session()
    try:
        c = s.get(Contract, cid)
        if not c or c.user_id != user_id:
            return False

        if title is not None: c.title = title
        if content is not None: c.content = content
        if client_name is not None: c.client_name = client_name or None
        if client_id_number is not None: c.client_id_number = client_id_number or None
        if client_phone is not None: c.client_phone = client_phone or None
        if client_address is not None: c.client_address = client_address or None
        if investment_amount is not None: c.investment_amount = investment_amount

        if capital_amount is not None: c.capital_amount = capital_amount
        if profit_percent is not None: c.profit_percent = profit_percent
        if profit_interval_months is not None: c.profit_interval_months = profit_interval_months
        if withdrawal_notice_days is not None: c.withdrawal_notice_days = withdrawal_notice_days
        if start_date_h is not None: c.start_date_h = start_date_h
        if end_date_h is not None: c.end_date_h = end_date_h
        if commission_percent is not None: c.commission_percent = commission_percent
        if exit_notice_days is not None: c.exit_notice_days = exit_notice_days
        if jurisdiction is not None: c.jurisdiction = jurisdiction

        s.commit()
        return True
    except Exception:
        s.rollback()
        return False
    finally:
        s.close()


def delete_contract(cid: int, user_id: int) -> bool:
    s = get_session()
    try:
        c = s.get(Contract, cid)
        if not c or c.user_id != user_id:
            return False
        s.delete(c)
        s.commit()
        return True
    except Exception:
        s.rollback()
        return False
    finally:
        s.close()


def get_contract(cid: int, user_id: int) -> Optional[Contract]:
    s = get_session()
    try:
        c = s.get(Contract, cid)
        if not c or c.user_id != user_id:
            return None
        return c
    finally:
        s.close()


def list_contracts(user_id: int, q: Optional[str]) -> List[Tuple[int, str, str, str, Optional[str]]]:
    """
    يرجع قائمة مختصرة للعقود:
      (id, title, shown_no, created_at_iso, client_name)
    مع بحث اختياري على العنوان/العميل/رقم العقد.
    """
    s = get_session()
    try:
        stmt = select(
            Contract.id,
            Contract.title,
            Contract.client_contract_no,
            Contract.created_at,
            Contract.client_name
        ).where(Contract.user_id == user_id)

        if q:
            like = f"%{q.strip()}%"
            stmt = stmt.where(
                or_(
                    Contract.title.ilike(like),
                    Contract.client_name.ilike(like),
                    Contract.client_contract_no.ilike(like),
                )
            )
        stmt = stmt.order_by(Contract.created_at.desc())
        rows = s.execute(stmt).all()

        out = []
        for cid, title, shown_no, created_at, client_name in rows:
            out.append((
                cid,
                title,
                shown_no,
                created_at.strftime("%Y-%m-%d %H:%M:%S"),
                client_name
            ))
        return out
    finally:
        s.close()


# -------------------------------------------------------------------
# دوال المدير (اعتماد/رفض + قائمة)
# -------------------------------------------------------------------
def manager_set_status(cid: int, *, approve: bool, note: Optional[str]) -> bool:
    """
    تحديث حالة العقد من قِبل المدير.
    approve=True => approved
    approve=False => rejected
    """
    s = get_session()
    try:
        c = s.get(Contract, cid)
        if not c:
            return False
        c.status = "approved" if approve else "rejected"
        c.manager_note = (note or None)
        s.commit()
        return True
    except Exception:
        s.rollback()
        return False
    finally:
        s.close()


def manager_list_contracts(
    *, status: Optional[str] = None, q: Optional[str] = None
) -> List[Tuple[int, str, str, str, Optional[str], str, Optional[str]]]:
    """
    قائمة للمدير (كل العقود، مع فلترة اختيارية):
      يعيد صفوف بالشكل:
      (id, title, shown_no, created_at_iso, client_name, status, manager_note)
    """
    s = get_session()
    try:
        stmt = select(
            Contract.id,
            Contract.title,
            Contract.client_contract_no,
            Contract.created_at,
            Contract.client_name,
            Contract.status,
            Contract.manager_note
        )

        if status in ("pending", "approved", "rejected"):
            stmt = stmt.where(Contract.status == status)

        if q:
            like = f"%{q.strip()}%"
            stmt = stmt.where(
                or_(
                    Contract.title.ilike(like),
                    Contract.client_name.ilike(like),
                    Contract.client_contract_no.ilike(like),
                )
            )

        stmt = stmt.order_by(Contract.created_at.desc())
        rows = s.execute(stmt).all()

        return [
            (
                r[0],                 # id
                r[1],                 # title
                r[2],                 # shown_no
                r[3].strftime("%Y-%m-%d %H:%M:%S"),  # created_at_iso
                r[4],                 # client_name
                r[5],                 # status
                r[6],                 # manager_note
            )
            for r in rows
        ]
    finally:
        s.close()
