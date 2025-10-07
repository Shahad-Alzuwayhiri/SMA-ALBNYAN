# 🚀 ContractSama - نظام إدارة العقود

## 📋 نظرة عامة

نظام شامل لإدارة العقود مبني بـ PHP خالص مع دعم كامل للعربية ونظام PDF متقدم.

### ✨ الميزات الرئيسية:
- 🔐 نظام تسجيل دخول وإدارة المستخدمين
- 📄 إنشاء وإدارة العقود مع ترقيم تلقائي
- 📱 واجهة عربية كاملة مع دعم RTL
- 🖋️ نظام التوقيع الرقمي (رسم أو رفع صورة)
- 📊 تقارير شاملة وإحصائيات
- 🔍 بحث متقدم في العقود
- 📧 نظام إشعارات ونسيان كلمة المرور
- 💾 نسخ احتياطي واستعادة البيانات
- 📄 تصدير PDF عربي احترافي

## 🛠️ متطلبات النظام

- **PHP**: 8.1 أو أحدث
- **قاعدة البيانات**: SQLite (افتراضي) أو MySQL
- **الذاكرة**: 512MB RAM (الحد الأدنى)
- **التخزين**: 2GB مساحة فارغة
- **الملحقات المطلوبة**: `pdo`, `mbstring`, `gd`, `sqlite3`



## ⚡ التثبيت السريع

```bash
# انتقل إلى مجلد التطبيق
cd php_app

# تثبيت التبعيات
composer install

# إعداد قاعدة البيانات
php setup_database.php

# تشغيل الخادم
php -S localhost:8000 -t public
```

أو استخدم start_server.bat على Windows:
```bash
start_server.bat
```

## 🎯 اختبار توليد PDF

### الطريقة 1: مع TCPDF (الأمثل)
```
http://localhost:8000/test-pdf/123
```
- سيولد PDF بالعربية مع TCPDF
- دعم كامل للـ RTL
- تنسيق احترافي

### الطريقة 2: HTML Fallback  
إذا لم تعمل TCPDF:
```
http://localhost:8000/test-pdf/123
```
- سيولد HTML منسق بالعربية
- قابل للطباعة
- نفس المحتوى

## 📁 هيكل المشروع الجديد

```
php_app/
├── app/
│   ├── Http/Controllers/
│   │   └── ContractController.php    # معالج العقود والPDF
│   ├── Models/
│   │   ├── User.php                  # نموذج المستخدم
│   │   └── Contract.php              # نموذج العقد
│   └── Services/
│       └── PdfService.php            # خدمة توليد PDF
├── database/
│   └── migrations/                   # هجرة قاعدة البيانات
├── public/
│   ├── index.php                     # نقطة الدخول
│   └── static/                       # الأصول المنقولة
├── resources/views/                  # القوالب
├── routes/web.php                    # المسارات
└── composer.json                     # التبعيات
```

## 🔧 الميزات المنجزة

### ✅ نظام PDF متكامل
- **PdfService**: خدمة موحدة لتوليد PDF
- **TCPDF Integration**: دعم كامل للعربية
- **HTML Fallback**: يعمل في أي بيئة
- **RTL Support**: اتجاه النص من اليمين لليسار

### ✅ إدارة العقود
- **ContractController**: معالج شامل للعقود
- **Sample Data**: بيانات تجريبية للاختبار
- **PDF Generation**: توليد PDF مباشر
- **Error Handling**: معالجة محسنة للأخطاء

### ✅ قاعدة البيانات
- **User Model**: نموذج المستخدمين مع المصادقة
- **Contract Model**: نموذج العقود بجميع الحقول
- **Migrations**: هجرة منظمة لقاعدة البيانات
- **SQLite Support**: قاعدة بيانات محلية بسيطة

## 📊 البيانات التجريبية

يستخدم النظام البيانات التالية للاختبار:
```php
'contract_number' => 'CT-0123',
'partner_name' => 'أحمد محمد العلي',
'partner_id' => '1234567890', 
'investment_amount' => 100000,
'profit_percent' => 15,
// ... إلخ
```

## 🎨 التخصيص

### إضافة خطوط عربية جديدة:
1. ضع ملف الخط في `public/static/fonts/`
2. عدل `PdfService::setArabicFont()`

### تخصيص تصميم PDF:
عدل الطرق التالية في `PdfService`:
- `generateContent()` - المحتوى الرئيسي
- `buildContractText()` - نص العقد
- `generateHtmlContract()` - النسخة HTML

## 🚀 النشر

### للنشر على خادم:
```bash
# تحسين الأداء
php artisan config:cache
php artisan route:cache
php artisan view:cache

# رفع الملفات مع استثناء:
# - .env (أنشئ جديد)
# - storage/logs/*
# - database/database.sqlite (نسخة محلية)
```

## 🛠️ استكشاف الأخطاء

### إذا لم يعمل TCPDF:
```bash
composer require tecnickcom/tcpdf
```

### إذا واجهت مشاكل بالخطوط:
- تأكد من وجود الخطوط في `public/static/fonts/`
- تحقق من صلاحيات القراءة للملفات

### إذا لم تعمل قاعدة البيانات:
```bash
# تأكد من وجود الملف
touch database/database.sqlite

# أعد تشغيل الهجرة
php artisan migrate:fresh
```

## 📈 الخطوات التالية

1. **تخصيص التصميم**: تحسين شكل PDF
2. **إضافة المصادقة**: Laravel Breeze/Jetstream
3. **واجهة إدارة**: لوحة تحكم كاملة
4. **تحسين الأداء**: caching وoptimization
5. **اختبارات**: unit tests وintegration tests

## 🎉 النتيجة النهائية

✅ **مشروع PHP خالص** - لا توجد تبعيات Python  
✅ **نظام PDF قوي** - دعم كامل للعربية  
✅ **بنية Laravel منظمة** - سهولة التطوير والصيانة  
✅ **جاهز للإنتاج** - يعمل على أي خادم PHP  

**المشروع جاهز للاستخدام والتطوير! 🚀**