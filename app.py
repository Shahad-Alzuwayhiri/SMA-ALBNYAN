# ...existing code...
# © 2025 ContractSama. All rights reserved.

import os, uuid, base64, io, zipfile, shutil, json
from io import BytesIO
from functools import wraps
from jinja2 import Template
from flask import Flask, render_template, render_template_string, request, redirect, url_for, flash, send_file, abort, session
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
from flask_wtf import CSRFProtect
from flask_mail import Mail, Message
from itsdangerous import URLSafeTimedSerializer, BadSignature, SignatureExpired
from dotenv import load_dotenv
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
from flask_wtf.csrf import generate_csrf
import models
from models import init_db, get_session, User, Contract
from pdf_utils import generate_contract_pdf
from datetime import datetime
from werkzeug.utils import secure_filename
from urllib.parse import urlparse, urljoin
# ...existing code... (duplicate imports removed)

load_dotenv()

DEV_SHOW_LINK = os.getenv("DEV_SHOW_LINK", "False") == "True"
MANAGER_INVITE_CODE = os.getenv("MANAGER_INVITE_CODE", "").strip()

app = Flask(__name__)
app.config["SECRET_KEY"] = os.environ.get("SECRET_KEY", "CHANGE_ME_SECRET")

csrf = CSRFProtect(app)
app.jinja_env.globals["csrf_token"] = generate_csrf

DATABASE_URL = (os.getenv("DATABASE_URL") or "").strip() or "sqlite:///users.db"
engine = create_engine(DATABASE_URL, echo=False, future=True)
SessionLocal = sessionmaker(bind=engine, autoflush=False, autocommit=False)

app.config.update(
    MAIL_SERVER=os.environ.get("MAIL_SERVER"),
    MAIL_PORT=int(os.environ.get("MAIL_PORT", "587")),
    MAIL_USE_TLS=os.environ.get("MAIL_USE_TLS", "True") == "True",
    MAIL_USERNAME=os.environ.get("MAIL_USERNAME"),
    MAIL_PASSWORD=os.environ.get("MAIL_PASSWORD"),
    MAIL_DEFAULT_SENDER=os.environ.get("MAIL_DEFAULT_SENDER") or os.environ.get("MAIL_USERNAME"),
)
mail = Mail(app)

UPLOAD_REL = os.path.join("static", "uploads", "signatures")
os.makedirs(os.path.join(app.root_path, UPLOAD_REL), exist_ok=True)

BRAND = {"name": "شركة سما البنيان التجارية", "primary": "#1F3C88", "accent": "#22B8CF"}
CONTRACT_PREFIX = os.environ.get("CONTRACT_PREFIX", "B123-1447")

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
    try:
        u = models.get_user_by_id(int(user_id))
        return Current(u) if u else None
    except Exception:
        return None

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

OPEN_ENDPOINTS = {
    "login", "signup", "forgot_password", "reset_password", "static", "favicon"
}

@app.before_request
def force_auth_everywhere():
    # allow favicon and OPTIONS through without redirect (some clients request favicon before cookies)
    if request.path == "/favicon.ico" or request.method == "OPTIONS":
        return None
    ep = request.endpoint or ""
    base_ep = ep.split(".")[0]
    if base_ep not in OPEN_ENDPOINTS and not getattr(current_user, "is_authenticated", False):
        return redirect(url_for("login", next=request.path))

def save_signature_base64(data_url: str):
    if not data_url or not data_url.startswith("data:image"):
        return None
    try:
        _, b64 = data_url.split(",", 1)
        raw = base64.b64decode(b64)
        fname = f"sig_{uuid.uuid4().hex[:12]}.png"
        rel_path = os.path.join(UPLOAD_REL, fname)
        abs_path = os.path.join(app.root_path, rel_path)
        with open(abs_path, "wb") as f:
            f.write(raw)
        return rel_path
    except Exception:
        return None
def is_safe_url(target):
    host_url = request.host_url
    ref_url = urlparse(host_url)
    test_url = urlparse(urljoin(host_url, target))
    return (test_url.scheme in ("http", "https")) and (ref_url.netloc == test_url.netloc)
# login route (accepts / and /login)
# ...existing code...
@app.route("/", methods=["GET","POST"])
@app.route("/login", methods=["GET","POST"])
def login():
    if request.method == "POST":
        identity = (request.form.get("identity") or "").strip()
        password = request.form.get("password", "")
        app.logger.debug("Login attempt for identity=%s", identity)

        user = None
        # if identity looks like email, use existing helper
        if "@" in identity:
            user = models.verify_login(identity, password)
            app.logger.debug("verify_login returned: %s", user)
        else:
            # try phone lookup
            try:
                s = get_session()
                uobj = s.query(User).filter_by(phone=identity).first()
            finally:
                s.close()
            if uobj and uobj.check_password(password):
                user = {"id": uobj.id, "name": uobj.name, "email": uobj.email, "role": uobj.role}
            app.logger.debug("phone lookup returned: %s", bool(user))
        if not user:
            flash("❌ بيانات الدخول غير صحيحة.", "danger")
            return render_template("login.html", BRAND=BRAND, auth_page=True)

        # حفظ بيانات الجلسة (لا تحفظ كلمات المرور)
        session.clear()
        session["user_id"] = user["id"]
        session["user_name"] = user["name"]
        session["user_email"] = user["email"]
        session["user_role"] = user.get("role", "employee")
        # also tell flask-login that the user is authenticated
        try:
            uobj = models.get_user_by_id(int(user["id"]))
            if uobj:
                login_user(Current(uobj))
        except Exception:
            app.logger.debug("login: failed to call login_user()")

        # تحقق من next الآمن ثم توجيه حسب الدور
        next_url = request.args.get("next") or request.form.get("next")
        if next_url and is_safe_url(next_url):
            return redirect(next_url)

        if session["user_role"] == "manager":
            return redirect(url_for("manager_dashboard"))  # عدل اسم الوجهة إن كان مختلفاً
        return redirect(url_for("dashboard"))  # داشبورد الموظف

    # GET: render login page
    return render_template("login.html", BRAND=BRAND, auth_page=True)
# ...existing code...
# signup route
# ...existing code...
@app.route("/signup", methods=["GET","POST"])
def signup():
    if request.method == "POST":
        name  = request.form.get("name","").strip()
        email = request.form.get("email","").strip().lower()

        # DEBUG-SAFE: لا تطبع كلمات السر نفسها، فقط الأطوال والمساواة بعد trim
        pw_raw  = request.form.get("password", "")
        cpw_raw = request.form.get("confirm_password", "")

        # fallback: try common alternate confirm field names if confirm is empty
        if not cpw_raw:
            for alt in ("password_confirm", "confirm", "password2", "confirmPassword", "confirm-password"):
                v = request.form.get(alt)
                if v:
                    cpw_raw = v
                    app.logger.debug("signup: using alternate confirm field '%s'", alt)
                    break

        app.logger.debug("signup pwd debug: len_before_strip=%d,%d ; equal_after_strip=%s",
                         len(pw_raw), len(cpw_raw), pw_raw.strip() == cpw_raw.strip())

        pw  = pw_raw.strip()
        cpw = cpw_raw.strip()

        phone = request.form.get("phone","").strip() or None
        invite_code = request.form.get("invite_code","").strip()

        if not name or not email or not pw:
            flash("رجاءً املأ جميع الحقول.", "danger")
        return render_template("signup.html", BRAND=BRAND, auth_page=True)
        if len(pw) < 8:
            flash("كلمة المرور يجب ألا تقل عن 8 أحرف.", "danger")
            return render_template("signup.html", BRAND=BRAND)
        if pw != cpw:
            flash("❌ كلمتا المرور غير متطابقتين.", "danger")
            return render_template("signup.html", BRAND=BRAND)
        role = "user"
        if invite_code and MANAGER_INVITE_CODE and invite_code == MANAGER_INVITE_CODE:
            role = "manager"

        ok, msg = models.create_user(name=name, phone=phone, email=email, password=pw)
        if ok:
            if role != "user":
                s = get_session()
                try:
                    u = s.query(User).filter_by(email=email).first()
                    if u:
                        u.role = role
                        s.commit()
                except Exception:
                    s.rollback()
                finally:
                    s.close()
            flash("تم إنشاء الحساب ✅ سجل الدخول الآن.", "success")
            return redirect(url_for("login"))
        else:
            flash(msg, "danger")
    return render_template("signup.html", BRAND=BRAND, auth_page=True)
# ...existing code...
@app.route("/forgot", methods=["GET","POST"])
def forgot_password():
    if request.method == "POST":
        email = request.form.get("email","").strip().lower()
        u = models.get_user_by_email(email)
        if not u:
            flash("لم نجد بريدًا بهذا العنوان.", "danger")
        return render_template("forgot_password.html", BRAND=BRAND, auth_page=True)
        token = URLSafeTimedSerializer(app.config["SECRET_KEY"]).dumps(email, salt="reset-password")
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
    return render_template("forgot_password.html", BRAND=BRAND, auth_page=True)

@app.route("/reset/<token>", methods=["GET","POST"])
def reset_password(token):
    s = URLSafeTimedSerializer(app.config["SECRET_KEY"])
    try:
        email = s.loads(token, salt="reset-password", max_age=1800)
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
        return render_template("reset_password.html", BRAND=BRAND, auth_page=True)
        if pw != cpw:
            flash("❌ كلمتا المرور غير متطابقتين", "danger")
            return render_template("reset_password.html", BRAND=BRAND)
        ok, msg = models.update_user_password(email=email, new_password=pw)
        if ok:
            flash("تم تحديث كلمة المرور. سجل الدخول.", "success")
            return redirect(url_for("login"))
        else:
            flash(msg, "danger")
    return render_template("reset_password.html", BRAND=BRAND, auth_page=True)

@app.route("/logout")
@login_required
def logout():
    logout_user()
    session.clear()
    flash("تم تسجيل الخروج.", "info")
    return redirect(url_for("login"))

@app.route("/dashboard")
@login_required
def dashboard():
    if getattr(current_user, "role", "user") == "manager":
        return redirect(url_for("manager_dashboard"))

    s = get_session()
    try:
        u = s.get(User, int(current_user.id))
        if not u:
            flash("لم يتم العثور على المستخدم.", "danger")
            return redirect(url_for("login"))

        status_counts = models.user_contract_status_counts(int(current_user.id)) if hasattr(models, "user_contract_status_counts") else {}
        metrics = {
            "created_count": sum(status_counts.values()) if status_counts else 0,
            "pending_count": status_counts.get("pending", 0) if status_counts else 0,
            "closed_count": (status_counts.get("approved", 0) + status_counts.get("rejected", 0)) if status_counts else 0,
        }
        raw_contracts = models.list_contracts(int(current_user.id), None)[:5] if hasattr(models, "list_contracts") else []
        recent_contracts = []
        for row in raw_contracts:
            try:
                c_id, _, serial, created_at, client_name = row
            except Exception:
                continue
            s2 = get_session()
            try:
                contract_obj = s2.get(Contract, c_id)
                status = getattr(contract_obj, "status", "pending") if contract_obj else "pending"
            finally:
                s2.close()
            status_display = "بانتظار الاعتماد" if status == "pending" else ("معتمدة" if status == "approved" else "مرفوضة")
            if isinstance(created_at, str):
                try:
                    created_at_dt = datetime.strptime(created_at, "%Y-%m-%d %H:%M:%S")
                except Exception:
                    created_at_dt = datetime.now()
            else:
                created_at_dt = created_at
            recent_contracts.append({
                "id": c_id,
                "serial": serial,
                "client_name": client_name,
                "status": status,
                "status_display": status_display,
                "created_at": created_at_dt,
            })

        return render_template("dashboard.html", BRAND=BRAND, metrics=metrics, recent_contracts=recent_contracts, user=u)
    finally:
        s.close()

@app.route("/contracts")
@login_required
def contracts_list():
    q = request.args.get("q","").strip() or None
    rows = models.list_contracts(int(current_user.id), q) if hasattr(models, "list_contracts") else []
    return render_template("contracts_list.html", items=rows, q=q, BRAND=BRAND)


# Backwards-compatibility alias: some templates use 'contracts_list_view'
@app.route("/contracts/view")
@login_required
def contracts_list_view():
    # keep behavior identical: redirect to canonical contracts_list URL
    return redirect(url_for("contracts_list"))


@app.route("/contracts/list")
@login_required
def contracts_list_alias():
    # backwards-compatible alias for callers expecting /contracts/list
    return redirect(url_for("contracts_list"))

@app.route("/contracts/create", methods=["GET","POST"])
@login_required
def contracts_create():
    db_session = get_session()
    try:
        from models import ContractNumberCounter
        cnt = db_session.query(ContractNumberCounter).filter_by(prefix=CONTRACT_PREFIX).first()
        next_expected = (cnt.last_no + 1) if cnt else 1
        tentative_no = f"{CONTRACT_PREFIX}-{next_expected:04d}"
    finally:
        db_session.close()

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

    required = list(vals.keys())
    missing = [k for k in required if not vals.get(k)]
    if missing:
        flash("❌ رجاءً املأ جميع الحقول المطلوبة.", "danger")
        return render_template("contracts_create.html", BRAND=BRAND,
                               formvals=vals, generated_no=tentative_no)

    # Prefer an HTML template if present so templates can use a `data` dict.
    tpl_path_txt = os.path.join(app.root_path, "templates", "contract_fixed_v1.txt")
    tpl_path_html = os.path.join(app.root_path, "templates", "contract_fixed_v1.html")
    if os.path.exists(tpl_path_html):
        tpl_path = tpl_path_html
    else:
        tpl_path = tpl_path_txt

    if not os.path.exists(tpl_path):
        flash("قالب العقد غير موجود! (contract_fixed_v1.txt or .html)", "danger")
        return render_template("contracts_create.html", BRAND=BRAND,
                               formvals=vals, generated_no=tentative_no)
    with open(tpl_path, "r", encoding="utf-8") as fobj:
        tpl_text = fobj.read()

    # Prepare rendering context. If using an HTML template, expose form
    # values under a `data` dict (so templates can use `data.partner_name`, etc.).
    if tpl_path.lower().endswith('.html'):
        render_ctx = {'data': vals.copy(), 'BRAND': BRAND, 'serial': tentative_no}
    else:
        render_ctx = vals.copy()
        render_ctx['BRAND'] = BRAND
        render_ctx['serial'] = tentative_no

    if action == "preview":
        try:
            content_preview = Template(tpl_text).render(**render_ctx)
        except Exception as e:
            flash(f"خطأ في تعبئة القالب: {e}", "danger")
            return render_template("contracts_create.html", BRAND=BRAND,
                                   formvals=vals, generated_no=tentative_no)
        return render_template("contracts_create.html", BRAND=BRAND,
                               formvals=vals, preview_content=content_preview,
                               generated_no=tentative_no)

    try:
        inv_amt = float(vals["investment_amount"])
    except:
        inv_amt = None

    title = f"عقد مشاركة - {vals['partner2_name']}"

    new_id, _, real_no = models.create_contract(
        user_id=int(current_user.id),
        title=title,
        content_final="",
        prefix=CONTRACT_PREFIX,
        client_name=vals["partner2_name"],
        client_id_number=vals["sign2_id"],
        client_phone=vals["sign2_phone"],
        client_address=vals["client_address"],
        investment_amount=inv_amt,
        signature_path=None,
        template_text="FIXED_v1",
    )

    if tpl_path.lower().endswith('.html'):
        ctx_final = {'data': vals.copy(), 'BRAND': BRAND, 'serial': real_no}
    else:
        ctx_final = vals.copy()
        ctx_final['BRAND'] = BRAND
        ctx_final['serial'] = real_no

    try:
        final_text = Template(tpl_text).render(**ctx_final)
    except Exception as e:
        flash(f"تم إنشاء السجل لكن فشلت تعبئة النص: {e}", "warning")
        final_text = ""

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
            if hasattr(models, "get_contract_for_manager"):
                c = models.get_contract_for_manager(cid)
            else:
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
        import traceback; traceback.print_exc()
        flash(f"تعذر توليد الـ PDF: {e}", "danger")
        return redirect(url_for("contracts_detail", cid=cid))

@app.route("/notifications")
@login_required
def notifications():
    items = models.list_notifications(int(current_user.id), unread_only=False, limit=100) if hasattr(models, "list_notifications") else []
    return render_template("notifications.html", BRAND=BRAND, items=items)

@app.route("/notifications/read/<int:nid>", methods=["POST"])
@login_required
def notifications_read(nid: int):
    ok = models.mark_notification_read(nid, int(current_user.id)) if hasattr(models, "mark_notification_read") else False
    flash("تم التعليم كمقروء." if ok else "تعذّر التعديل.", "info" if ok else "danger")
    return redirect(request.referrer or url_for("notifications"))

@app.route("/notifications/read_all", methods=["POST"])
@login_required
def notifications_read_all():
    ok = models.mark_all_notifications_read(int(current_user.id)) if hasattr(models, "mark_all_notifications_read") else False
    flash("تم تعليم كل الإشعارات كمقروءة." if ok else "تعذّر التعديل.", "info" if ok else "danger")
    return redirect(request.referrer or url_for("notifications"))

@app.route("/admin/backup")
@login_required
@role_required("manager")
def admin_backup():
    db_session = get_session()
    try:
        users = db_session.query(User).all()
        contracts = db_session.query(Contract).all()
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
        db_session.close()

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
@role_required("manager")
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

            for name in z.namelist():
                if name.startswith(f"{UPLOAD_REL}/"):
                    target_path = os.path.join(app.root_path, name)
                    os.makedirs(os.path.dirname(target_path), exist_ok=True)
                    with z.open(name) as src, open(target_path, "wb") as dst:
                        shutil.copyfileobj(src, dst)

            db_session = get_session()
            try:
                db_session.query(Contract).delete()
                db_session.query(User).delete()
                db_session.commit()

                for u in data.get("users", []):
                    nu = User(
                        id=u["id"],
                        name=u["name"], phone=u["phone"], email=u["email"],
                        password_hash=u["password_hash"], stamp_path=u.get("stamp_path"),
                        role=u.get("role","user")
                    )
                    db_session.add(nu)
                db_session.commit()

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
                    db_session.add(nc)
                db_session.commit()
                flash("تمت الاستعادة بنجاح.", "success")
            except Exception as e:
                db_session.rollback()
                flash(f"فشل الاستعادة: {e}", "danger")
            finally:
                db_session.close()

        return redirect(url_for("dashboard"))

    return render_template("admin_restore.html", BRAND=BRAND)

@app.route("/manager/dashboard")
@login_required
@role_required("manager")
def manager_dashboard():
    status = request.args.get("status", "pending")
    q = request.args.get("q", "").strip() or None

    try:
        all_contracts = models.manager_list_contracts(status=status if status != "all" else None, q=q) if hasattr(models, "manager_list_contracts") else []
    except Exception:
        all_contracts = []

    metrics = {"total_count": 0, "pending_count": 0, "closed_count": 0, "employees_count": 0}
    try:
        if hasattr(models, "count_all_contracts"):
            metrics["total_count"] = models.count_all_contracts() or 0
        if hasattr(models, "count_contracts_by_status"):
            metrics["pending_count"] = models.count_contracts_by_status("pending") or 0
            metrics["closed_count"] = (models.count_contracts_by_status("approved") or 0) + (models.count_contracts_by_status("rejected") or 0)
        if hasattr(models, "count_employees"):
            metrics["employees_count"] = models.count_employees() or 0
        elif hasattr(models, "count_users"):
            metrics["employees_count"] = models.count_users() or 0
    except Exception:
        pass

    recent_activities = []
    tasks = []
    notifications = []
    chart_data = None
    try:
        if hasattr(models, "list_recent_activities"):
            recent_activities = models.list_recent_activities(limit=20) or []
    except Exception:
        recent_activities = []
    try:
        if hasattr(models, "get_manager_tasks"):
            tasks = models.get_manager_tasks(limit=20) or []
    except Exception:
        tasks = []
    try:
        if hasattr(models, "list_notifications"):
            notifications = models.list_notifications(int(current_user.id), unread_only=False, limit=50) or []
    except Exception:
        notifications = []
    try:
        if hasattr(models, "get_dashboard_chart_data"):
            chart_data = models.get_dashboard_chart_data()
        else:
            chart_data = {
                "type": "bar",
                "data": {
                    "labels": ["الأسبوع 1","الأسبوع 2","الأسبوع 3","الأسبوع 4"],
                    "datasets": [{"label":"العقود","backgroundColor":"#22B8CF","data":[5,12,9,15]}]
                },
                "options": {"responsive": True, "maintainAspectRatio": False}
            }
    except Exception:
        chart_data = None

    return render_template(
        "manager_dashboard.html",
        BRAND=BRAND,
        metrics=metrics,
        all_contracts=all_contracts,
        recent_activities=recent_activities,
        tasks=tasks,
        notifications=notifications,
        chart_data=chart_data,
        status=status,
        q=q
    )

@app.route("/manager/contracts/<int:cid>/approve", methods=["POST"])
@login_required
@role_required("manager")
def manager_approve_contract(cid: int):
    note = request.form.get("note","").strip() or None
    ok = models.manager_set_status(cid=cid, approve=True, note=note) if hasattr(models, "manager_set_status") else False
    flash("تم اعتماد العقد." if ok else "تعذر اعتماد العقد.", "success" if ok else "danger")
    return redirect(request.referrer or url_for("manager_dashboard"))

@app.route("/manager/contracts/<int:cid>/reject", methods=["POST"])
@login_required
@role_required("manager")
def manager_reject_contract(cid: int):
    note = request.form.get("note","").strip() or None
    ok = models.manager_set_status(cid=cid, approve=False, note=note) if hasattr(models, "manager_set_status") else False
    flash("تم رفض العقد." if ok else "تعذر رفض العقد.", "warning" if ok else "danger")
    return redirect(request.referrer or url_for("manager_dashboard"))

@app.route("/admin/create_user", methods=["GET","POST"])
@login_required
@role_required("manager")
def admin_create_user():
    if request.method == "POST":
        name = request.form.get("name","").strip()
        email = request.form.get("email","").strip().lower()
        password = request.form.get("password","").strip()
        role = request.form.get("role","user").strip()
        if not name or not email or not password:
            flash("املأ الحقول المطلوبة.", "danger")
            return render_template("admin_create_user.html", BRAND=BRAND)
        if len(password) < 8:
            flash("كلمة المرور يجب ألا تقل عن 8 أحرف.", "danger")
            return render_template("admin_create_user.html", BRAND=BRAND)
        ok, msg = models.create_user(name=name, phone=None, email=email, password=password)
        if ok:
            if role and role in ("user","manager","admin"):
                s = get_session()
                try:
                    u = s.query(User).filter_by(email=email).first()
                    if u:
                        u.role = role
                        s.commit()
                except Exception:
                    s.rollback()
                finally:
                    s.close()
            flash("تم إنشاء المستخدم.", "success")
            return redirect(url_for("manager_dashboard"))
        flash(msg, "danger")
    return render_template("admin_create_user.html", BRAND=BRAND)

# DB initialization kept at the end with logging; removed the early plain init_db() call
@app.errorhandler(403)
def error_403(_):
    flash("لا تملك صلاحية الوصول إلى هذه الصفحة.", "warning")
    return redirect(url_for("dashboard"))

@app.errorhandler(404)
def error_404(_):
    flash("الصفحة غير موجودة.", "warning")
    return redirect(url_for("dashboard"))

@app.route('/contracts/in-progress')
@login_required
def contracts_in_progress():
    try:
        s = get_session()
        contracts = s.query(Contract).filter_by(user_id=int(current_user.id)).filter(Contract.status.in_(['pending', 'draft', 'signed'])).all()
        s.close()
    except Exception:
        contracts = []
    return render_template('contracts_in_progress.html', contracts_in_progress=contracts)

@app.route('/contracts/closed')
@login_required
def contracts_closed():
    try:
        s = get_session()
        contracts = s.query(Contract).filter_by(user_id=int(current_user.id)).filter(Contract.status.in_(['closed', 'rejected'])).all()
        s.close()
    except Exception:
        contracts = []
    return render_template('contracts_closed.html', contracts_closed=contracts)

@app.route("/profile", methods=["GET", "POST"])
@login_required
def profile():
    s = get_session()
    try:
        u = s.get(User, int(current_user.id))
        if not u:
            flash("لم يتم العثور على المستخدم.", "danger")
            return redirect(url_for("dashboard"))

        if request.method == "POST":
            name  = request.form.get("name","").strip()
            phone = request.form.get("phone","").strip()
            if not name:
                flash("الاسم مطلوب.", "danger")
                return render_template("profile.html", BRAND=BRAND, user=u)

            # read password fields (no .strip here to preserve debug lengths)
            new_pw  = request.form.get("new_password", "")
            new_pw2 = request.form.get("confirm_password", "")

            # fallback: try alternate confirm field names
            if not new_pw2:
                for alt in ("confirm_new_password", "password_confirm", "confirm", "password2", "confirmPassword"):
                    v = request.form.get(alt)
                    if v:
                        new_pw2 = v
                        app.logger.debug("profile: using alternate confirm field '%s'", alt)
                        break

            # temporary debug: log lengths and equality after strip
            app.logger.debug("pwd debug: len_before_strip=%d,%d ; equal_after_strip=%s",
                             len(new_pw), len(new_pw2), new_pw.strip() == new_pw2.strip())

            # normalize
            new_pw = new_pw.strip()
            new_pw2 = new_pw2.strip()

            if new_pw or new_pw2:
                if len(new_pw) < 8:
                    flash("كلمة المرور يجب ألا تقل عن 8 أحرف.", "danger")
                    return render_template("profile.html", BRAND=BRAND, user=u)
                if new_pw != new_pw2:
                    flash("❌ كلمتا المرور غير متطابقتين.", "danger")
                    return render_template("profile.html", BRAND=BRAND, user=u)
                ok, msg = models.update_user_password(email=u.email, new_password=new_pw)
                if not ok:
                    flash(msg or "تعذر تحديث كلمة المرور.", "danger")
                    return render_template("profile.html", BRAND=BRAND, user=u)

            # update user basic info and commit
            u.name  = name
            u.phone = phone or None
            s.commit()
            flash("تم تحديث الملف الشخصي.", "success")
            return redirect(url_for("profile"))

        return render_template("profile.html", BRAND=BRAND, user=u)
    finally:
        s.close()

# New: user_archive endpoint to satisfy templates and provide safe behavior
@app.route("/user/<int:uid>/archive")
@login_required
def user_archive(uid: int):
    if int(current_user.id) != uid and getattr(current_user, "role", "user") != "manager":
        abort(403)
    try:
        uploads = models.get_user_uploads(uid) if hasattr(models, "get_user_uploads") else []
    except Exception:
        uploads = []
    if int(current_user.id) == uid:
        return redirect(url_for("profile"))
    items_html = ""
    for f in uploads:
        href = f.get("url") or (url_for("static", filename=f.get("path")) if f.get("path") else "#")
        items_html += f'<li style="padding:8px;border-bottom:1px solid #eee"><a href="{href}" target="_blank" rel="noopener noreferrer">{f.get("name") or f.get("filename") or "ملف"}</a> <small style="color:#6b7280">— {f.get("uploaded_by") or ""} {f.get("human_time") or ""}</small></li>'
    html = f"""
    <!doctype html>
    <html lang="ar">
    <head><meta charset="utf-8"><title>أرشيف المستخدم</title></head>
    <body style="font-family:sans-serif;direction:rtl;text-align:right;padding:18px">
      <h3>أرشيف المستخدم #{uid}</h3>
      <p><a href="{url_for('manager_dashboard')}">العودة للوحة المدير</a></p>
      <ul style="list-style:none;padding:0;margin:0">{items_html or '<li style="color:#6b7280">لا توجد ملفات</li>'}</ul>
    </body>
    </html>
    """
    return render_template_string(html)

# New: messages endpoint to satisfy templates linking to "messages"
@app.route("/messages", methods=["GET", "POST"])
@login_required
def messages():
    if request.method == "POST":
        subject = request.form.get("subject", "").strip()
        body = request.form.get("body", "").strip()
        recipient = request.form.get("recipient_id") or None
        ok = False
        try:
            if hasattr(models, "create_message"):
                ok = models.create_message(sender_id=int(current_user.id), recipient_id=int(recipient) if recipient else None, subject=subject, body=body)
            else:
                ok = True
            flash("تم إرسال الرسالة." if ok else "تعذر إرسال الرسالة.", "success" if ok else "danger")
        except Exception:
            flash("حدث خطأ عند إرسال الرسالة.", "danger")
        return redirect(url_for("messages"))

    msgs = []
    try:
        if hasattr(models, "list_messages"):
            msgs = models.list_messages(int(current_user.id))
        elif hasattr(models, "list_notifications"):
            msgs = models.list_notifications(int(current_user.id), unread_only=False, limit=50)
    except Exception:
        msgs = []

    return render_template("messages.html", messages=msgs, BRAND=BRAND)

@app.route("/upload_document", methods=["POST"])
@login_required
def upload_document():
    file = request.files.get("file")
    if not file or not file.filename:
        flash("لم يتم اختيار ملف.", "danger")
        return redirect(url_for("profile"))

    filename = secure_filename(file.filename)
    user_dir = os.path.join("static", "uploads", "users", str(current_user.id))
    abs_dir = os.path.join(app.root_path, user_dir)
    os.makedirs(abs_dir, exist_ok=True)
    save_rel_path = os.path.join(user_dir, filename)
    save_abs_path = os.path.join(app.root_path, save_rel_path)

    try:
        file.save(save_abs_path)
        try:
            if hasattr(models, "save_user_upload"):
                models.save_user_upload(user_id=int(current_user.id), filename=filename, path=save_rel_path)
            elif hasattr(models, "create_upload_record"):
                models.create_upload_record(user_id=int(current_user.id), name=filename, path=save_rel_path)
        except Exception:
            pass

        try:
            if hasattr(models, "notify_manager_new_upload"):
                models.notify_manager_new_upload(user_id=int(current_user.id), filename=filename, path=save_rel_path)
        except Exception:
            pass

        flash("تم رفع الملف وإرسال إشعار للمدير (إن أمكن).", "success")
    except Exception as e:
        flash(f"فشل رفع الملف: {e}", "danger")

    return redirect(url_for("profile"))

with app.app_context():
    # ensure DB initialized (keeps same behavior as original file)
    try:
        init_db()
    except Exception:
        app.logger.exception("init_db failed")

if __name__ == "__main__":
    app.run(debug=True)
# ...existing code...