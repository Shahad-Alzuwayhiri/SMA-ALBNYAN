# ContractSama

منصة عقود مع:
- تسجيل/دخول
- إنشاء عقد من قالب ثابت + معاينة + PDF
- رقم عقد متسلسل من القاعدة (B123-1447-0001…)
- توقيع العميل (لوحة رسم أو رفع صورة)
- بحث في العقود
- نسيت كلمة المرور (إيميل)
- نسخ احتياطي/استعادة (JSON + صور توقيع)
- Bootstrap RTL

## المتطلبات
- Python 3.11+
- pip
- (اختياري) PostgreSQL عند النشر

## تشغيل محلي
```bash
python -m venv venv
venv\Scripts\activate  # ويندوز
pip install -r requirements.txt

# إنشاء ملف .env بناءً على .env.example
# ضع الخط DejaVuSans.ttf و logo.png في مجلد static كما بالهيكلة

python app.py
