# Deployment Guide for Contract Sama

## رفع الموقع على Heroku

### المتطلبات:
1. حساب Heroku مجاني
2. تثبيت Git
3. تثبيت Heroku CLI

### خطوات النشر:

1. **تسجيل الدخول في Heroku:**
```bash
heroku login
```

2. **إنشاء التطبيق:**
```bash
heroku create contract-sama-[your-name]
```

3. **إعداد متغيرات البيئة:**
```bash
heroku config:set APP_ENV=production
heroku config:set APP_KEY=your-secret-key
```

4. **رفع الملفات:**
```bash
git init
git add .
git commit -m "Deploy Contract Sama"
git push heroku main
```

5. **إعداد قاعدة البيانات:**
```bash
heroku addons:create heroku-postgresql:hobby-dev
```

## رفع الموقع على استضافة مشتركة

1. **ضغط ملفات المشروع**
2. **رفع على cPanel File Manager**
3. **إنشاء قاعدة بيانات MySQL**
4. **تحديث إعدادات الاتصال**

## رفع على خادم VPS

1. **إعداد Apache/Nginx**
2. **تثبيت PHP 8.0+**
3. **إعداد قاعدة البيانات**
4. **تكوين Domain/SSL**

## Ngrok للاختبار السريع

```bash
# تشغيل الخادم المحلي
php -S localhost:8080

# في terminal آخر
ngrok http 8080
```

سيعطيك رابط مثل: https://abc123.ngrok.io