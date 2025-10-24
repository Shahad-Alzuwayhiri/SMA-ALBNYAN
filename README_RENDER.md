# 🚀 Contract Sama - نظام إدارة العقود
## جاهز للنشر على Render.com في 15 دقيقة!

[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/Shahad-Alzuwayhiri/ContractSama)

---

## 📋 نظرة عامة

نظام إدارة عقود احترافي مبني بـ PHP خالص، مع دعم كامل للعربية ونظام PDF متقدم.

### ✨ الميزات الرئيسية:
- 🔐 **نظام مصادقة آمن** مع أدوار متعددة
- 📄 **توليد PDF بالعربية** مع دعم RTL كامل
- ✍️ **التوقيع الرقمي** عبر رسم أو رفع صورة
- 📊 **لوحة تحكم شاملة** للمديرين والموظفين
- 🔍 **بحث متقدم** في العقود
- 📧 **إشعارات** عبر النظام
- 💾 **نسخ احتياطية** تلقائية

---

## 🚀 النشر السريع على Render

### الطريقة الأولى: النشر بنقرة واحدة (الأسرع)
[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/Shahad-Alzuwayhiri/ContractSama)

### الطريقة الثانية: النشر اليدوي (15 دقيقة)
1. **انشئ حساب** على [render.com](https://render.com)
2. **اربط GitHub** repository
3. **اتبع الدليل المفصل**: [`RENDER_DEPLOYMENT_GUIDE.md`](RENDER_DEPLOYMENT_GUIDE.md)

---

## 💰 خيارات الاستضافة والتكلفة

| الخيار | التكلفة | الوقت | الصعوبة | التوصية |
|--------|---------|-------|----------|----------|
| **🥇 Render.com** | **مجاني** → $7 | 15 دقيقة | ⭐⭐ | **الأفضل للبداية** |
| **🥈 Hostinger** | $12/شهر | 90 دقيقة | ⭐⭐⭐ | للمؤسسات التقليدية |
| **🥉 VPS** | $24/شهر | 3 ساعات | ⭐⭐⭐⭐⭐ | للمشاريع الكبيرة |

### 📚 أدلة النشر التفصيلية:
- 🚀 [`RENDER_DEPLOYMENT_GUIDE.md`](RENDER_DEPLOYMENT_GUIDE.md) - **مُوصى به**
- 🏢 [`HOSTINGER_DEPLOYMENT_GUIDE.md`](HOSTINGER_DEPLOYMENT_GUIDE.md)
- ⚙️ [`VPS_DEPLOYMENT_PLAN.md`](VPS_DEPLOYMENT_PLAN.md)
- 🎯 [`HOSTING_COMPARISON.md`](HOSTING_COMPARISON.md)

---

## 🛠️ التشغيل المحلي

### متطلبات النظام:
- PHP 8.1+
- SQLite أو MySQL
- Composer

### خطوات سريعة:
```bash
# استنساخ المستودع
git clone https://github.com/Shahad-Alzuwayhiri/ContractSama.git
cd ContractSama/php_app

# تثبيت التبعيات
composer install

# إعداد قاعدة البيانات
php setup_database.php

# تشغيل الخادم
php -S localhost:8000 -t public/
```

**افتح المتصفح**: http://localhost:8000

---

## 🔐 بيانات الدخول الافتراضية

| الدور | اسم المستخدم | كلمة المرور |
|-------|--------------|--------------|
| **مدير** | admin | admin123 |
| **موظف** | employee | emp123 |

⚠️ **مهم**: غيّر كلمات المرور فوراً بعد النشر!

---

## 📁 هيكل المشروع

```
ContractSama/
├── 🚀 النشر والإعداد
│   ├── RENDER_DEPLOYMENT_GUIDE.md    # دليل Render (مُوصى)
│   ├── HOSTINGER_DEPLOYMENT_GUIDE.md # دليل Hostinger
│   ├── build.sh                      # سكريبت البناء
│   └── start.sh                      # سكريبت التشغيل
│
├── 📱 التطبيق الرئيسي
│   └── php_app/
│       ├── public/           # نقطة الدخول
│       ├── models/          # نماذج البيانات
│       ├── controllers/     # معالجات المسارات
│       ├── services/        # خدمات (PDF، إلخ)
│       ├── templates/       # قوالب HTML
│       └── config/          # إعدادات النظام
│
└── 📚 الوثائق
    ├── README.md            # هذا الملف
    ├── HOSTING_COMPARISON.md # مقارنة خيارات الاستضافة
    └── hosting_advisor.html  # مساعد اختيار الاستضافة
```

---

## 🧪 الاختبار

### اختبارات سريعة:
```bash
cd php_app

# اختبار أساسي
php test_simple.php

# اختبار النظام
php system_test.php

# اختبار PDF
php test_pdf.php
```

---

## 🆘 الدعم والمساعدة

### 📖 الوثائق الشاملة:
- [`RENDER_DEPLOYMENT_GUIDE.md`](RENDER_DEPLOYMENT_GUIDE.md) - دليل النشر على Render
- [`FINAL_RECOMMENDATION.md`](FINAL_RECOMMENDATION.md) - التوصيات النهائية
- [`MEDIUM_BUSINESS_OPTIONS.md`](MEDIUM_BUSINESS_OPTIONS.md) - للمؤسسات المتوسطة

### 🛠️ أدوات مساعدة:
- [`hosting_advisor.html`](hosting_advisor.html) - مساعد اختيار الاستضافة
- [`deploy.php`](deploy.php) - سكريبت النشر التلقائي

### 🐛 الإبلاغ عن المشاكل:
افتح [Issue جديد](https://github.com/Shahad-Alzuwayhiri/ContractSama/issues) مع تفاصيل المشكلة.

---

## 📊 الميزات التقنية

### 🏗️ البنية:
- **PHP 8.1+** مع أفضل الممارسات
- **SQLite/MySQL** قاعدة بيانات مرنة
- **TCPDF** لتوليد PDF بالعربية
- **Bootstrap RTL** للواجهة
- **أمان متقدم** مع تشفير كلمات المرور

### 🔒 الحماية:
- حماية من XSS و SQL Injection
- تشفير كلمات المرور (bcrypt)
- جلسات آمنة مع انتهاء صلاحية
- حماية CSRF للنماذج
- تحكم في الوصول بالأدوار

### 📱 الواجهة:
- تصميم متجاوب (Mobile-friendly)
- دعم كامل للعربية وRTL
- واجهة حديثة وسهلة الاستخدام
- رسائل تأكيد وتنبيهات

---

## 🎯 حالات الاستخدام

### 🏢 للشركات الصغيرة:
- إدارة عقود الموظفين
- عقود الموردين والعملاء
- تتبع حالة العقود

### 🏭 للمؤسسات المتوسطة:
- نظام عقود متقدم
- أدوار متعددة للمستخدمين
- تقارير وإحصائيات

### 🌐 للمؤسسات الكبيرة:
- نظام مؤسسي شامل
- تكامل مع أنظمة أخرى
- مراقبة وتحليلات متقدمة

---

## 🚀 ابدأ الآن!

### للنشر الفوري:
[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/Shahad-Alzuwayhiri/ContractSama)

### للتجربة المحلية:
```bash
git clone https://github.com/Shahad-Alzuwayhiri/ContractSama.git
cd ContractSama && php_app && php -S localhost:8000 -t public/
```

---

## 📜 الترخيص

هذا المشروع مرخص تحت رخصة MIT - راجع ملف [LICENSE](LICENSE) للتفاصيل.

---

**صُنع بـ ❤️ للمجتمع العربي | Made with ❤️ for the Arabic Community**

[![GitHub stars](https://img.shields.io/github/stars/Shahad-Alzuwayhiri/ContractSama.svg?style=social&label=Star)](https://github.com/Shahad-Alzuwayhiri/ContractSama)
[![GitHub forks](https://img.shields.io/github/forks/Shahad-Alzuwayhiri/ContractSama.svg?style=social&label=Fork)](https://github.com/Shahad-Alzuwayhiri/ContractSama/fork)