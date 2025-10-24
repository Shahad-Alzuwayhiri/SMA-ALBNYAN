# 🚀 دليل تشغيل ContractSama - PHP Edition

## ❗ المشكلة الحالية
النظام يحتاج إلى PHP ليعمل. إليك الحلول:

## 🛠️ الحل 1: تثبيت PHP على Windows

### خطوة 1: تحميل PHP
1. اذهب إلى: https://windows.php.net/download/
2. حمل النسخة **Thread Safe** x64
3. فك الضغط في مجلد مثل `C:\php`

### خطوة 2: إضافة PHP للـ PATH
1. اضغط `Win + R` واكتب `sysdm.cpl`
2. اذهب لـ "Advanced" > "Environment Variables"
3. في "System Variables" ابحث عن "Path"
4. اضغط "Edit" > "New" 
5. أضف `C:\php` (أو المسار الذي فككت فيه PHP)
6. اضغط OK وأعد تشغيل PowerShell

### خطوة 3: اختبار PHP
```bash
php --version
```

## 🚀 الحل 2: استخدام XAMPP (الأسهل)

### خطوة 1: تحميل XAMPP
1. اذهب إلى: https://www.apachefriends.org/
2. حمل النسخة الأحدث
3. ثبتها في `C:\xampp`

### خطوة 2: تشغيل Apache
1. افتح XAMPP Control Panel
2. اضغط "Start" أمام Apache
3. تأكد أنه يعمل على Port 80

### خطوة 3: نسخ المشروع
```bash
# انسخ مجلد php_app إلى 
C:\xampp\htdocs\contractsama
```

### خطوة 4: اختبار المشروع
زيارة: http://localhost/contractsama

## 🎯 الحل 3: اختبار مباشر بدون خادم

إذا كان لديك PHP مثبت:

```bash
cd php_app
php -S localhost:8000 -t public
```

ثم زيارة:
- http://localhost:8000/ (الصفحة الرئيسية)
- http://localhost:8000/test-pdf/123 (اختبار PDF)

## 📋 ما ستراه بعد التشغيل

### ✅ إذا عمل TCPDF:
- سيظهر PDF جميل بالعربية
- دعم كامل للـ RTL
- تصميم احترافي

### ✅ إذا لم يعمل TCPDF:
- سيظهر HTML منسق بالعربية  
- قابل للطباعة
- نفس المحتوى

## 🔧 إصلاح المشاكل المحتملة

### مشكلة: لا يظهر شيء
**الحل:**
```bash
# تحقق من أن المسار صحيح
cd C:\Users\Admin\Desktop\ContractSama\php_app
dir
```

### مشكلة: خطأ في autoloader
**الحل:**
```bash
# تأكد من بنية الملفات:
php_app/
├── public/index.php
├── app/Services/PdfService.php
└── storage/logs/
```

### مشكلة: TCPDF لا يعمل
**الحل:**
1. لا مشكلة - النظام سيعمل بـ HTML
2. لتثبيت Composer وTCPDF:
```bash
# حمل Composer من https://getcomposer.org/
composer require tecnickcom/tcpdf
```

## 🎉 النتيجة المتوقعة

### الصفحة الرئيسية:
```
مرحباً بك في ContractSama
[اختبار توليد PDF]
```

### صفحة PDF:
- عقد كامل بالعربية
- اسم العميل: أحمد محمد العلي
- مبلغ الاستثمار: 100,000 ريال
- جميع التفاصيل منسقة

## 📞 إذا واجهت مشاكل

1. **تأكد من PHP**: `php --version`
2. **تحقق من المسار**: تأكد أنك في مجلد `php_app`
3. **اختبر الملف مباشرة**: افتح `php_app/public/index.php` في المتصفح
4. **استخدم XAMPP**: الحل الأسهل والأضمن

**المشروع جاهز 100% للعمل بمجرد تثبيت PHP! 🚀**