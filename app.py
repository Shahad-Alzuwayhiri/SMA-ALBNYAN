# © 2025 ContractSama. All rights reserved.

import os
from io import BytesIO
from flask import Flask, render_template, request, redirect, url_for, flash, send_file
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
from flask_wtf import CSRFProtect
import models
from pdf_utils import generate_contract_pdf

app = Flask(__name__)
app.config["SECRET_KEY"] = os.environ.get("SECRET_KEY", "CHANGE_ME_SECRET")
csrf = CSRFProtect(app)

# ===== الهوية =====
BRAND = {
    "name": "شركة سما البنيان التجارية",
    "primary": "#0E2A3B",
    "accent":  "#78C7C7",
}
@app.context_processor
def inject_brand():
    return dict(BRAND=BRAND)

# ===== Login =====
login_manager = LoginManager(app)
login_manager.login_view = "login"

class User(UserMixin):
    def __init__(self, id, name, email):
        self.id = str(id)
        self.name = name
        self.email = email

@login_manager.user_loader
def load_user(user_id):
    row = models.get_user_by_id(int(user_id))
    if not row: return None
    uid, name, email, _ = row
    return User(uid, name, email)

# ===== حارس حماية عام =====
from flask import request
@app.before_request
def require_login_for_protected_pages():
    protected_prefixes = ("/dashboard", "/contracts")
    allow_list = ("/static/", "/login", "/signup", "/logout", "/")
    p = request.path or "/"
    if p.startswith(allow_list):
        return
    if p.startswith(protected_prefixes) and not current_user.is_authenticated:
        return redirect(url_for("login"))

# ===== Auth =====
@app.route("/", methods=["GET"])
def root_redirect():
    # لو مسجل ادخلي لوحة التحكم، غيره لصفحة الدخول
    return redirect(url_for("dashboard" if current_user.is_authenticated else "login"))

@app.route("/login", methods=["GET", "POST"])
def login():
    if request.method == "POST":
        email = request.form.get("email","").strip().lower()
        password = request.form.get("password","").strip()
        data = models.verify_login(email, password)
        if data:
            login_user(User(data["id"], data["name"], data["email"]))
            flash("تم تسجيل الدخول ✅", "success")
            return redirect(url_for("dashboard"))
        flash("❌ البريد أو كلمة المرور غير صحيحة", "danger")
    return render_template("login.html")

@app.route("/logout")
@login_required
def logout():
    logout_user()
    flash("تم تسجيل الخروج.", "info")
    return redirect(url_for("login"))

@app.route("/signup", methods=["GET", "POST"])
def signup():
    if request.method == "POST":
        name  = request.form.get("name","").strip()
        phone = request.form.get("phone","").strip()
        email = request.form.get("email","").strip().lower()
        password = request.form.get("password","").strip()
        confirm  = request.form.get("confirm_password","").strip()

        if not name or not phone or not email or not password:
            flash("رجاءً املئي الاسم والجوال والبريد وكلمة المرور.", "danger")
            return render_template("signup.html")

        if password != confirm:
            flash("❌ كلمتا المرور غير متطابقتين", "danger")
            return render_template("signup.html")

        ok, msg = models.create_user(name=name, phone=phone, email=email, password=password)
        if not ok:
            flash(msg, "danger")
            return render_template("signup.html")

        flash("تم إنشاء الحساب! سجّلي الدخول الآن.", "success")
        return redirect(url_for("login"))
    return render_template("signup.html")

# ===== Dashboard =====
@app.route("/dashboard")
@login_required
def dashboard():
    return render_template("dashboard.html")

# ===== Contracts =====
@app.route("/contracts")
@login_required
def contracts_list():
    rows = models.list_contracts(int(current_user.id))
    return render_template("contracts_list.html", items=rows)

@app.route("/contracts/create", methods=["GET", "POST"])
@login_required
def contracts_create():
    if request.method == "POST":
        title = request.form.get("title","").strip()
        content = request.form.get("content","").strip()
        if not title or not content:
            flash("❌ رجاءً املئي عنوان العقد ومحتواه.", "danger")
            return render_template("contracts_create.html")
        models.create_contract(title, content, int(current_user.id))
        flash("تم إنشاء العقد ✅", "success")
        return redirect(url_for("contracts_list"))
    return render_template("contracts_create.html")

@app.route("/contracts/<int:cid>")
@login_required
def contracts_detail(cid: int):
    row = models.get_contract(cid, int(current_user.id))
    if not row:
        flash("العقد غير موجود.", "danger")
        return redirect(url_for("contracts_list"))
    return render_template("contracts_detail.html", item=row)

@app.route("/contracts/<int:cid>/edit", methods=["GET", "POST"])
@login_required
def contracts_edit(cid: int):
    row = models.get_contract(cid, int(current_user.id))
    if not row:
        flash("العقد غير موجود.", "danger")
        return redirect(url_for("contracts_list"))

    if request.method == "POST":
        title   = request.form.get("title","").strip()
        content = request.form.get("content","").strip()
        if not title or not content:
            flash("❌ رجاءً املئي عنوان العقد ومحتواه.", "danger")
            return render_template("contracts_edit.html", item=row)
        models.update_contract(cid, title, content, int(current_user.id))
        flash("تم تحديث العقد ✅", "success")
        return redirect(url_for("contracts_detail", cid=cid))
    return render_template("contracts_edit.html", item=row)

@app.route("/contracts/<int:cid>/delete", methods=["POST"])
@login_required
def contracts_delete(cid: int):
    models.delete_contract(cid, int(current_user.id))
    flash("تم حذف العقد.", "info")
    return redirect(url_for("contracts_list"))

# ===== PDF =====
@app.route("/contracts/<int:cid>/pdf")
@login_required
def contracts_pdf(cid: int):
    row = models.get_contract(cid, int(current_user.id))
    if not row:
        flash("العقد غير موجود.", "danger")
        return redirect(url_for("contracts_list"))

    id_, title, content, serial, created_at = row
    logo_path = os.path.join(app.root_path, "static", "img", "logo.png")
    font_path = os.path.join(app.root_path, "static", "fonts", "DejaVuSans.ttf")

    pdf_bytes = generate_contract_pdf(
        title=title,
        content=content,
        serial=serial,
        created_at=created_at,
        brand=BRAND,
        logo_path=logo_path,
        font_path=font_path
    )

    return send_file(BytesIO(pdf_bytes),
                     mimetype="application/pdf",
                     as_attachment=False,
                     download_name=f"contract_{id_}.pdf")

if __name__ == "__main__":
    app.run(debug=True)
