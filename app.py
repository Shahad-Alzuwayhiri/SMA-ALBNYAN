# © 2025 ContractSama. All rights reserved.

import os, uuid, base64, io, zipfile, shutil, json
from io import BytesIO
from functools import wraps
from jinja2 import Template
from werkzeug.utils import secure_filename
from flask import Flask, render_template, request, redirect, url_for, flash, send_file, abort
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
from flask_wtf import CSRFProtect
from flask_mail import Mail, Message
from itsdangerous import URLSafeTimedSerializer, BadSignature, SignatureExpired
from dotenv import load_dotenv
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
from flask_wtf.csrf import generate_csrf  # اختياري إن احتجته في الفورم
import models
from models import init_db, get_session, User, Contract
from pdf_utils import generate_contract_pdf
from flask import session, request, redirect, url_for
MANAGER_INVITE_CODE = os.getenv("MANAGER_INVITE_CODE", "").strip()

# ---------------- إعداد .env ----------------
load_dotenv()
DEV_SHOW_LINK = os.getenv("DEV_SHOW_LINK", "False") == "True"

# ---------------- تطبيق Flask ----------------
app = Flask(__name__)
app.config["SECRET_KEY"] = os.environ.get("SECRET_KEY", "CHANGE_ME_SECRET")

# ---------------- قاعدة البيانات ----------------
DATABASE_URL = (os.getenv("DATABASE_URL") or "").strip()
if not DATABASE_URL:
    DATABASE_URL = "sqlite:///users.db"

engine = create_engine(DATABASE_URL, echo=False, future=True)
SessionLocal = sessionmaker(bind=engine, autoflush=False, autocommit=False)

# ---------------- إعداد البريد ----------------
app.config.update(
    MAIL_SERVER=os.environ.get("MAIL_SERVER"),
    MAIL_PORT=int(os.environ.get("MAIL_PORT", "587")),
    MAIL_USE_TLS=os.environ.get("MAIL_USE_TLS", "True") == "True",
    MAIL_USERNAME=os.environ.get("MAIL_USERNAME"),
    MAIL_PASSWORD=os.environ.get("MAIL_PASSWORD"),
    MAIL_DEFAULT_SENDER=os.environ.get("MAIL_DEFAULT_SENDER") or os.environ.get("MAIL_USERNAME")
)
mail = Mail(app)

# ---------------- ملفات التوقيع ----------------
UPLOAD_REL = os.path.join("static", "uploads", "signatures")
os.makedirs(os.path.join(app.root_path, UPLOAD_REL), exist_ok=True)

# ---------------- هوية العلامة ----------------
BRAND = {"name": "شركة سما البنيان التجارية", "primary": "#1F3C88", "accent": "#22B8CF"}
CONTRACT_PREFIX = os.environ.get("CONTRACT_PREFIX", "B123-1447")

# ---------------- CSRF & Serializer ----------------
csrf = CSRFProtect(app)
serializer = URLSafeTimedSerializer(app.config["SECRET_KEY"])

# ---------------- تسجيل الدخول ----------------
login_manager = LoginManager(app)
login_manager.login_view = "login"

class Current(UserMixin):
    def __init__(self, u: User):
        self.id = str(u.id)
        self.name = u.name
        self.email = u.email
        self.role = getattr(u, "role", "user")

@login_manager.user_loader
def load_user(user_id):
    u = models.get_user_by_id(int(user_id))
    return Current(u) if u else None

def role_required(role_name):
    def _decorator(fn):
        @wraps(fn)
        def _wrapped(*args, **kwargs):
            if not current_user.is_authenticated:
                return login_manager.unauthorized()
            if getattr(current_user, "role", "user") != role_name:
                abort(403)
            return fn(*args, **kwargs)
        return _wrapped
    return _decorator
    # لا تسمح بالدخول لأي صفحة بدون تسجيل (ما عدا صفحات السماح)
OPEN_ENDPOINTS = {
    "login", "signup", "forgot_password", "reset_password", "static"
}

@app.before_request
def _force_auth_everywhere():
    # اسم الـ endpoint الحالي
    ep = request.endpoint or ""
    # اسم endpoint للملفات الثابتة = 'static'
    base_ep = ep.split(".")[0]

    if base_ep not in OPEN_ENDPOINTS and not getattr(current_user, "is_authenticated", False):
        # رجّع المستخدم لصفحة الدخول مع next=
        return redirect(url_for("login", next=request.path))

# ---------------- مساعدة لحفظ توقيع اللوحة (Base64) ----------------
def _save_signature_base64(data_url: str):
    """
    يستقبل data:image/png;base64,.... ويعيد المسار النسبي داخل static/uploads/signatures
    (تم الإبقاء عليه لو رجعنا التوقيع لاحقًا؛ حاليًا التوقيع مُلغى حسب طلبك)
    """
    if not data_url or not data_url.startswith("data:image"):
        return None
    try:
        header, b64 = data_url.split(",", 1)
        raw = base64.b64decode(b64)
        fname = f"sig_{uuid.uuid4().hex[:12]}.png"
        rel_path = os.path.join(UPLOAD_REL, fname)
        abs_path = os.path.join(app.root_path, rel_path)
        with open(abs_path, "wb") as f:
            f.write(raw)
        return rel_path
    except Exception:
        return None

# ---------------- Auth ----------------
@app.route("/", methods=["GET","POST"])
def login():
    if request.method == "POST":
        email = request.form.get("email","").strip().lower()
        password = request.form.get("password","").strip()
        u = models.verify_login(email, password)
        if u:
            cu = Current(models.get_user_by_id(u["id"]))
            login_user(cu)
            flash("تم تسجيل الدخول بنجاح ✅", "success")
            if cu.role == "manager":
                return redirect(url_for("manager_dashboard"))
            return redirect(url_for("dashboard"))
        flash("❌ بيانات الدخول غير صحيحة", "danger")
    return render_template("login.html", BRAND=BRAND)

@app.route("/signup", methods=["GET","POST"])
def signup():
    if request.method == "POST":
        name  = request.form.get("name","").strip()
        email = request.form.get("email","").strip().lower()
        pw    = request.form.get("password","").strip()
        cpw   = request.form.get("confirm_password","").strip()
        phone = request.form.get("phone","").strip() or None

        if not name or not email or not pw:
            flash("رجاءً املأ جميع الحقول.", "danger")
            return render_template("signup.html", BRAND=BRAND)
        if len(pw) < 8:
            flash("كلمة المرور يجب ألا تقل عن 8 أحرف.", "danger")
            return render_template("signup.html", BRAND=BRAND)
        if pw != cpw:
            flash("❌ كلمتا المرور غير متطابقتين.", "danger")
            return render_template("signup.html", BRAND=BRAND)

        ok, msg = models.create_user(name=name, phone=phone, email=email, password=pw)
        if ok:
            flash("تم إنشاء الحساب ✅ سجل الدخول الآن.", "success")
            return redirect(url_for("login"))
        else:
            flash(msg, "danger")
    return render_template("signup.html", BRAND=BRAND)

@app.route("/forgot", methods=["GET","POST"])
def forgot_password():
    if request.method == "POST":
        email = request.form.get("email","").strip().lower()
        u = models.get_user_by_email(email)
        if not u:
            flash("لم نجد بريدًا بهذا العنوان.", "danger")
            return render_template("forgot_password.html", BRAND=BRAND)
        token = serializer.dumps(email, salt="reset-password")
        reset_url = url_for("reset_password", token=token, _external=True)
        try:
            if app.config.get("MAIL_SERVER") and app.config.get("MAIL_USERNAME"):
                msg = Message(subject="إعادة تعيين كلمة المرور - ContractSama",
                              recipients=[email],
                              body=f"مرحبًا،\n\nاستخدم الرابط التالي لإعادة تعيين كلمة المرور (صالح لـ 30 دقيقة):\n{reset_url}\n\nإن لم تطلب ذلك فتجاهل الرسالة.")
                mail.send(msg)
                flash("تم إرسال رابط إعادة التعيين على بريدك الإلكتروني.", "success")
            else:
                if DEV_SHOW_LINK:
                    flash("وضع التطوير: تم إنشاء الرابط (أدناه).", "info")
                    flash(reset_url, "secondary")
                else:
                    flash("لم يتم ضبط إعدادات البريد. ضع DEV_SHOW_LINK=True في .env لعرض الرابط مباشرة.", "warning")
        except Exception:
            if DEV_SHOW_LINK:
                flash("تعذر إرسال البريد. تم عرض الرابط أدناه:", "warning")
                flash(reset_url, "secondary")
            else:
                flash("تعذر إرسال البريد، وDEV_SHOW_LINK غير مفعّل.", "danger")
    return render_template("forgot_password.html", BRAND=BRAND)

@app.route("/reset/<token>", methods=["GET","POST"])
def reset_password(token):
    try:
        email = serializer.loads(token, salt="reset-password", max_age=1800)
    except SignatureExpired:
        flash("انتهت صلاحية الرابط. أعد المحاولة.", "danger")
        return redirect(url_for("forgot_password"))
    except BadSignature:
        flash("رابط غير صالح.", "danger")
        return redirect(url_for("forgot_password"))

    if request.method == "POST":
        pw  = request.form.get("password","").strip()
        cpw = request.form.get("confirm_password","").strip()
        if len(pw) < 8:
            flash("كلمة المرور يجب ألا تقل عن 8.", "danger")
            return render_template("reset_password.html", BRAND=BRAND)
        if pw != cpw:
            flash("❌ كلمتا المرور غير متطابقتين", "danger")
            return render_template("reset_password.html", BRAND=BRAND)
        ok, msg = models.update_user_password(email=email, new_password=pw)
        if ok:
            flash("تم تحديث كلمة المرور. سجل الدخول.", "success")
            return redirect(url_for("login"))
        else:
            flash(msg, "danger")
    return render_template("reset_password.html", BRAND=BRAND)

@app.route("/logout")
@login_required
def logout():
    logout_user()
    session.clear()  # مهم
    flash("تم تسجيل الخروج.", "info")
    return redirect(url_for("login"))


# ---------------- صفحات التطبيق ----------------
@app.route("/dashboard")
@login_required
def dashboard():
    if getattr(current_user, "role", "user") == "manager":
        return redirect(url_for("manager_dashboard"))
    return render_template("dashboard.html", BRAND=BRAND)

@app.route("/contracts")
@login_required
def contracts_list():
    q = request.args.get("q","").strip() or None
    rows = models.list_contracts(int(current_user.id), q)
    return render_template("contracts_list.html", items=rows, q=q, BRAND=BRAND)

@app.route("/contracts/create", methods=["GET","POST"])
@login_required
def contracts_create():
    # رقم متوقع للعرض فقط (قد يتغير عند الحفظ)
    session = get_session()
    try:
        from models import ContractNumberCounter
        cnt = session.query(ContractNumberCounter).filter_by(prefix=CONTRACT_PREFIX).first()
        next_expected = (cnt.last_no + 1) if cnt else 1
        tentative_no = f"{CONTRACT_PREFIX}-{next_expected:04d}"
    finally:
        session.close()

    if request.method == "GET":
        return render_template("contracts_create.html", BRAND=BRAND,
                               formvals={}, generated_no=tentative_no)

    f = request.form
    vals = {
        "meeting_day_name":   f.get("meeting_day_name","").strip(),
        "meeting_date_h":     f.get("meeting_date_h","").strip(),
        "city":               f.get("city","").strip(),
        "partner2_name":      f.get("partner2_name","").strip(),
        "sign2_name":         (f.get("sign2_name","").strip() or f.get("partner2_name","").strip()),
        "sign2_id":           f.get("sign2_id","").strip(),
        "sign2_phone":        f.get("sign2_phone","").strip(),
        "client_address":     f.get("client_address","").strip(),
        "investment_amount":  f.get("investment_amount","").strip(),
        "capital_amount":     f.get("capital_amount","").strip(),
        "profit_percent":     f.get("profit_percent","").strip(),
        "profit_interval_months": f.get("profit_interval_months","").strip(),
        "withdrawal_notice_days": f.get("withdrawal_notice_days","").strip(),
        "start_date_h":       f.get("start_date_h","").strip(),
        "end_date_h":         f.get("end_date_h","").strip(),
        "commission_percent": f.get("commission_percent","").strip(),
        "exit_notice_days":   f.get("exit_notice_days","").strip(),
        "jurisdiction":       f.get("jurisdiction","").strip(),
        "penalty_amount":     (f.get("penalty_amount","").strip() or "3000"),
    }
    action = f.get("action","save")

    # التحقق من الحقول المطلوبة
    required = list(vals.keys())
    missing = [k for k in required if not vals.get(k)]
    if missing:
        flash("❌ رجاءً املأ جميع الحقول المطلوبة.", "danger")
        return render_template("contracts_create.html", BRAND=BRAND,
                               formvals=vals, generated_no=tentative_no)

    # قراءة القالب الثابت
    tpl_path = os.path.join(app.root_path, "templates", "contract_fixed_v1.txt")
    if not os.path.exists(tpl_path):
        flash("قالب العقد غير موجود! (contract_fixed_v1.txt)", "danger")
        return render_template("contracts_create.html", BRAND=BRAND,
                               formvals=vals, generated_no=tentative_no)
    with open(tpl_path, "r", encoding="utf-8") as fobj:
        tpl_text = fobj.read()

    # سياق التعبئة للمعاينة (يستخدم الرقم المتوقع مؤقتًا)
    ctx = vals.copy()
    ctx["BRAND"] = BRAND
    ctx["serial"] = tentative_no

    # معاينة فقط: ما نحفظ
    if action == "preview":
        try:
            content_preview = Template(tpl_text).render(**ctx)
        except Exception as e:
            flash(f"خطأ في تعبئة القالب: {e}", "danger")
            return render_template("contracts_create.html", BRAND=BRAND,
                                   formvals=vals, generated_no=tentative_no)
        return render_template("contracts_create.html", BRAND=BRAND,
                               formvals=vals, preview_content=content_preview,
                               generated_no=tentative_no)

    # (التوقيع مُلغى حالياً حسب طلبك) — لن نتحقق من التوقيع

    # تحويل مبلغ الاستثمار إلى رقم إن أمكن
    try:
        inv_amt = float(vals["investment_amount"])
    except:
        inv_amt = None

    title = f"عقد مشاركة - {vals['partner2_name']}"

    # نحفظ مبدئيًا للحصول على الرقم الحقيقي ثم نعيد تعبئة القالب به
    new_id, internal_serial, real_no = models.create_contract(
        user_id=int(current_user.id),
        title=title,
        content_final="",  # مبدئيًا
        prefix=CONTRACT_PREFIX,
        client_name=vals["partner2_name"],
        client_id_number=vals["sign2_id"],
        client_phone=vals["sign2_phone"],
        client_address=vals["client_address"],
        investment_amount=inv_amt,
        signature_path=None,
        template_text="FIXED_v1",
    )

    # إعادة بناء السياق برقم العقد الحقيقي
    ctx_final = vals.copy()
    ctx_final["BRAND"] = BRAND
    ctx_final["serial"] = real_no

    try:
        final_text = Template(tpl_text).render(**ctx_final)
    except Exception as e:
        flash(f"تم إنشاء السجل لكن فشلت تعبئة النص: {e}", "warning")
        final_text = ""

    # تحديث نص العقد بعد معرفة الرقم الحقيقي
    models.update_contract(new_id, int(current_user.id), title=title, content=final_text)

    flash(f"تم إنشاء العقد ✅ (رقمه: {real_no})", "success")

    if action == "save_back":
        return redirect(url_for("contracts_list"))
    if action == "save_pdf":
        return redirect(url_for("contracts_pdf", cid=new_id))
    return redirect(url_for("contracts_detail", cid=new_id))

@app.route("/contracts/<int:cid>")
@login_required
def contracts_detail(cid: int):
    # إن كان مديرًا، يرى أي عقد؛ غير ذلك يرى فقط عقوده
    if getattr(current_user, "role", "user") == "manager":
        s = get_session()
        try:
            c = s.get(Contract, cid)
        finally:
            s.close()
    else:
        c = models.get_contract(cid, int(current_user.id))
    if not c:
        flash("العقد غير موجود.", "danger")
        return redirect(url_for("contracts_list"))
    return render_template("contracts_detail.html", item=c, BRAND=BRAND)

@app.route("/contracts/<int:cid>/edit", methods=["GET","POST"])
@login_required
def contracts_edit(cid: int):
    c = models.get_contract(cid, int(current_user.id))
    if not c:
        flash("العقد غير موجود.", "danger")
        return redirect(url_for("contracts_list"))

    if request.method == "POST":
        title = request.form.get("title","").strip()
        content = request.form.get("content","").strip()
        client_name      = request.form.get("client_name","").strip() or None
        client_id_number = request.form.get("client_id_number","").strip() or None
        client_phone     = request.form.get("client_phone","").strip() or None
        client_address   = request.form.get("client_address","").strip() or None
        investment_amount = request.form.get("investment_amount","").strip()
        try:
            inv_amt = float(investment_amount) if investment_amount else None
        except:
            inv_amt = None

        ok = models.update_contract(
            cid, int(current_user.id),
            title=title, content=content,
            client_name=client_name, client_id_number=client_id_number, client_phone=client_phone,
            client_address=client_address, investment_amount=inv_amt
        )
        flash("تم تحديث العقد." if ok else "لم يتم أي تعديل.", "info")
        return redirect(url_for("contracts_detail", cid=cid))

    return render_template("contracts_edit.html", item=c, BRAND=BRAND)

@app.route("/contracts/<int:cid>/delete", methods=["POST"])
@login_required
def contracts_delete(cid: int):
    ok = models.delete_contract(cid, int(current_user.id))
    flash("تم حذف العقد." if ok else "تعذر حذف العقد.", "info")
    return redirect(url_for("contracts_list"))

@app.route("/contracts/<int:cid>/pdf")
@login_required
def contracts_pdf(cid: int):
    try:
        if getattr(current_user, "role", "user") == "manager":
            c = models.get_contract_for_manager(cid)
        else:
            c = models.get_contract(cid, int(current_user.id))
        if not c:
            flash("العقد غير موجود.", "danger")
            return redirect(url_for("contracts_list"))

        logo_path = os.path.join(app.root_path, "static", "img", "logo.png")
        font_path = os.path.join(app.root_path, "static", "fonts", "Amiri-Regular.ttf")
        signature_abs = os.path.join(app.root_path, c.signature_path) if c.signature_path else None

        pdf_bytes = generate_contract_pdf(
            title=c.title,
            content=c.content,
            serial=c.client_contract_no or c.internal_serial,
            created_at=str(c.created_at).split(".")[0],
            brand=BRAND,
            logo_path=logo_path,
            font_path=font_path,
            client_name=c.client_name,
            client_id_number=c.client_id_number,
            client_phone=c.client_phone,
            client_address=c.client_address,
            investment_amount=c.investment_amount,
            signature_path=signature_abs,
            prepared_by=getattr(current_user, "name", "الموظف"),
        )
        return send_file(BytesIO(pdf_bytes), mimetype="application/pdf",
                         as_attachment=False, download_name=f"contract_{c.id}.pdf")
    except Exception as e:
        # يطبع في الكونسول ويظهر لك رسالة مفيدة
        import traceback; traceback.print_exc()
        flash(f"تعذر توليد الـ PDF: {e}", "danger")
        return redirect(url_for("contracts_detail", cid=cid))


# ---------------- النسخ الاحتياطي/الاستعادة ----------------
@app.route("/admin/backup")
@login_required
def admin_backup():
    """
    ZIP: data.json (users+contracts) + signatures/
    تنبيه: كلمات المرور محفوظة مُشفّرة (hash) — لا يمكن استعادتها كنص.
    """
    session = get_session()
    try:
        users = session.query(User).all()
        contracts = session.query(Contract).all()
        data = {
            "version": 1,
            "users": [
                dict(id=u.id, name=u.name, phone=u.phone, email=u.email,
                     password_hash=u.password_hash, stamp_path=u.stamp_path,
                     created_at=str(u.created_at), role=u.role)
                for u in users
            ],
            "contracts": [
                dict(id=c.id, title=c.title, content=c.content, internal_serial=c.internal_serial,
                     created_at=str(c.created_at), user_id=c.user_id,
                     client_name=c.client_name, client_id_number=c.client_id_number,
                     client_phone=c.client_phone, client_address=c.client_address,
                     investment_amount=c.investment_amount,
                     client_contract_no=c.client_contract_no,
                     signature_path=c.signature_path, template_text=c.template_text,
                     status=c.status, manager_note=c.manager_note,
                     approved_at=str(getattr(c,"approved_at",None)) if getattr(c,"approved_at",None) else None,
                     rejected_at=str(getattr(c,"rejected_at",None)) if getattr(c,"rejected_at",None) else None)
                for c in contracts
            ],
        }
    finally:
        session.close()

    mem = io.BytesIO()
    with zipfile.ZipFile(mem, mode="w", compression=zipfile.ZIP_DEFLATED) as z:
        z.writestr("data.json", json.dumps(data, ensure_ascii=False, indent=2))
        sig_dir = os.path.join(app.root_path, UPLOAD_REL)
        if os.path.isdir(sig_dir):
            for root, _, files in os.walk(sig_dir):
                for f in files:
                    full = os.path.join(root, f)
                    arc = os.path.relpath(full, app.root_path)
                    z.write(full, arcname=arc)
    mem.seek(0)
    return send_file(mem, mimetype="application/zip", as_attachment=True,
                     download_name="contracts_backup.zip")

@app.route("/admin/restore", methods=["GET","POST"])
@login_required
def admin_restore():
    if request.method == "POST":
        file = request.files.get("backup_zip")
        if not file or not file.filename.lower().endswith(".zip"):
            flash("حمّل ملف ZIP صالح.", "danger")
            return redirect(url_for("admin_restore"))

        mem = io.BytesIO(file.read())
        with zipfile.ZipFile(mem, 'r') as z:
            if "data.json" not in z.namelist():
                flash("النسخة الاحتياطية لا تحتوي data.json", "danger")
                return redirect(url_for("admin_restore"))

            data = json.loads(z.read("data.json").decode("utf-8"))

            # استعادة صور التوقيع
            for name in z.namelist():
                if name.startswith(f"{UPLOAD_REL}/"):
                    target_path = os.path.join(app.root_path, name)
                    os.makedirs(os.path.dirname(target_path), exist_ok=True)
                    with z.open(name) as src, open(target_path, "wb") as dst:
                        shutil.copyfileobj(src, dst)

            # استعادة البيانات (حذف ثم إدراج)
            session = get_session()
            try:
                session.query(Contract).delete()
                session.query(User).delete()
                session.commit()

                # users
                for u in data.get("users", []):
                    nu = User(
                        id=u["id"],
                        name=u["name"], phone=u["phone"], email=u["email"],
                        password_hash=u["password_hash"], stamp_path=u.get("stamp_path"),
                        role=u.get("role","user")
                    )
                    session.add(nu)
                session.commit()

                # contracts
                for c in data.get("contracts", []):
                    nc = Contract(
                        id=c["id"], title=c["title"], content=c["content"],
                        internal_serial=c["internal_serial"], user_id=c["user_id"],
                        client_name=c.get("client_name"), client_id_number=c.get("client_id_number"),
                        client_phone=c.get("client_phone"), client_address=c.get("client_address"),
                        investment_amount=c.get("investment_amount"),
                        client_contract_no=c.get("client_contract_no"),
                        signature_path=c.get("signature_path"),
                        template_text=c.get("template_text"),
                        status=c.get("status","pending"),
                        manager_note=c.get("manager_note"),
                    )
                    session.add(nc)
                session.commit()
                flash("تمت الاستعادة بنجاح.", "success")
            except Exception as e:
                session.rollback()
                flash(f"فشل الاستعادة: {e}", "danger")
            finally:
                session.close()

        return redirect(url_for("dashboard"))

    return render_template("admin_restore.html", BRAND=BRAND)

# ---------------- لوحة المدير ----------------
@app.route("/manager/dashboard")
@login_required
@role_required("manager")
def manager_dashboard():
    status = request.args.get("status", "pending")
    q = request.args.get("q", "").strip() or None
    items = models.manager_list_contracts(status=status, q=q)
    return render_template("manager_dashboard.html", BRAND=BRAND, items=items, status=status, q=q)

@app.route("/manager/contracts/<int:cid>/approve", methods=["POST"])
@login_required
@role_required("manager")
def manager_approve_contract(cid: int):
    note = request.form.get("note","").strip() or None
    ok = models.manager_set_status(cid=cid, approve=True, note=note)
    flash("تم اعتماد العقد." if ok else "تعذر اعتماد العقد.", "success" if ok else "danger")
    return redirect(request.referrer or url_for("manager_dashboard"))

@app.route("/manager/contracts/<int:cid>/reject", methods=["POST"])
@login_required
@role_required("manager")
def manager_reject_contract(cid: int):
    note = request.form.get("note","").strip() or None
    ok = models.manager_set_status(cid=cid, approve=False, note=note)
    flash("تم رفض العقد." if ok else "تعذر رفض العقد.", "warning" if ok else "danger")
    return redirect(request.referrer or url_for("manager_dashboard"))

# ---------- إنشاء الجداول أول مرة ----------
with app.app_context():
    init_db()

if __name__ == "__main__":
    app.run(debug=True)
