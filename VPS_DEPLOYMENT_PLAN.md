# 🎯 خطة النشر للمؤسسات المتوسطة - DigitalOcean VPS

## 📊 مواصفات الخادم المقترح

### **Basic Droplet - $24/شهر**
- **CPU**: 2 vCPU
- **RAM**: 4GB 
- **SSD**: 80GB
- **نقل البيانات**: 4TB/شهر
- **الشبكة**: 1000 Mbps
- **المنطقة**: Frankfurt أو Amsterdam (أقرب للمنطقة العربية)

### 💪 قدرة التحمل المتوقعة:
- ✅ **100+ مستخدم متزامن**
- ✅ **1000+ عقد شهرياً** 
- ✅ **توليد 500+ PDF يومياً**
- ✅ **استجابة أقل من ثانيتين**

---

## 🚀 خطة التنفيذ الكاملة (120 دقيقة)

### المرحلة 1: إعداد الخادم (30 دقيقة)

#### الخطوة 1: إنشاء الحساب والخادم (10 دقائق)
```bash
# 1. اذهب إلى digitalocean.com
# 2. سجّل حساب جديد (احصل على $200 رصيد مجاني لأول شهرين)
# 3. اختر "Create Droplet"
# 4. اختر Ubuntu 22.04 LTS
# 5. اختر Basic Plan - $24/month (2GB RAM, 2 vCPU)
# 6. اختر Frankfurt datacenter
# 7. أضف SSH Key أو استخدم كلمة مرور
```

#### الخطوة 2: الاتصال بالخادم (5 دقائق)
```bash
# من Windows PowerShell أو Command Prompt
ssh root@your-server-ip

# إذا كنت تستخدم PuTTY على Windows
# ادخل IP الخادم في PuTTY وافتح اتصال SSH
```

#### الخطوة 3: تحديث النظام (15 دقيقة)
```bash
# تحديث قوائم الحزم
apt update && apt upgrade -y

# تثبيت الحزم الأساسية
apt install -y curl wget unzip software-properties-common

# إعادة تشغيل إذا لزم الأمر
reboot
```

### المرحلة 2: تثبيت البيئة البرمجية (45 دقيقة)

#### الخطوة 4: تثبيت PHP 8.1 (15 دقيقة)
```bash
# إضافة مستودع PHP
add-apt-repository ppa:ondrej/php -y
apt update

# تثبيت PHP وإضافاته
apt install -y php8.1-fpm php8.1-cli php8.1-common php8.1-mysql \
php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml \
php8.1-bcmath php8.1-sqlite3 php8.1-json php8.1-intl

# التحقق من التثبيت
php -v
```

#### الخطوة 5: تثبيت Nginx (10 دقيقة)
```bash
# تثبيت Nginx
apt install -y nginx

# تشغيل وتفعيل Nginx
systemctl start nginx
systemctl enable nginx

# فتح منافذ الشبكة
ufw allow 'Nginx Full'
ufw allow OpenSSH
ufw --force enable
```

#### الخطوة 6: تثبيت Composer (5 دقيقة)
```bash
# تحميل وتثبيت Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# التحقق من التثبيت
composer --version
```

#### الخطوة 7: إعداد MySQL (15 دقيقة)
```bash
# تثبيت MySQL
apt install -y mysql-server

# تأمين MySQL
mysql_secure_installation
# اتبع الإرشادات:
# - اختر كلمة مرور قوية لـ root
# - احذف المستخدمين المجهولين
# - امنع دخول root عن بُعد
# - احذف قاعدة test

# إنشاء قاعدة بيانات ومستخدم للمشروع
mysql -u root -p
```

```sql
CREATE DATABASE contractsama;
CREATE USER 'contractuser'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON contractsama.* TO 'contractuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### المرحلة 3: نشر المشروع (30 دقيقة)

#### الخطوة 8: تحميل المشروع (10 دقيقة)
```bash
# إنشاء مجلد المشروع
mkdir -p /var/www/contractsama
cd /var/www/contractsama

# استزساخ المشروع من GitHub
git clone https://github.com/Shahad-Alzuwayhiri/ContractSama.git .
git checkout feature/php-migration

# نسخ ملفات التطبيق
cp -r php_app/* /var/www/contractsama/
```

#### الخطوة 9: تثبيت تبعيات PHP (10 دقيقة)
```bash
cd /var/www/contractsama
composer install --no-dev --optimize-autoloader

# إعداد الصلاحيات
chown -R www-data:www-data /var/www/contractsama
chmod -R 755 /var/www/contractsama
chmod -R 775 /var/www/contractsama/storage
chmod -R 775 /var/www/contractsama/database
```

#### الخطوة 10: إعداد قاعدة البيانات (10 دقيقة)
```bash
# تشغيل سكريبت إعداد قاعدة البيانات
cd /var/www/contractsama
php setup_database.php

# أو إعداد MySQL بدلاً من SQLite
# عدّل config/database.php لاستخدام MySQL
```

### المرحلة 4: إعداد Nginx وSSL (15 دقيقة)

#### الخطوة 11: تكوين Nginx (10 دقيقة)
```bash
# إنشاء ملف تكوين الموقع
nano /etc/nginx/sites-available/contractsama
```

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/contractsama/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.ht {
        deny all;
    }

    # Static files caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
# تفعيل الموقع
ln -s /etc/nginx/sites-available/contractsama /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

#### الخطوة 12: تثبيت SSL (5 دقيقة)
```bash
# تثبيت Certbot
apt install -y certbot python3-certbot-nginx

# الحصول على شهادة SSL (استبدل yourdomain.com بنطاقك)
certbot --nginx -d yourdomain.com -d www.yourdomain.com

# إعداد تجديد تلقائي
systemctl enable certbot.timer
```

---

## 🔧 إعدادات إضافية مطلوبة لمؤسستك

### 1. إعداد البريد الإلكتروني الاحترافي

#### الخيار الأول: Google Workspace ($6/مستخدم/شهر)
```bash
# اتبع هذه الخطوات:
# 1. اذهب إلى workspace.google.com
# 2. سجّل باستخدام نطاقك
# 3. أضف السجلات المطلوبة في DNS
# 4. أنشئ حسابات البريد الإلكتروني
```

#### الخيار الثاني: إعداد بريد على الخادم (مجاني ولكن معقد)
```bash
# تثبيت Postfix وDovecot
apt install -y postfix dovecot-imapd dovecot-pop3d

# تكوين Postfix
dpkg-reconfigure postfix
# اختر "Internet Site"
# ادخل نطاقك الأساسي
```

### 2. إعداد النسخ الاحتياطية التلقائية

```bash
# إنشاء سكريبت النسخ الاحتياطي
nano /usr/local/bin/backup-contractsama.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/backups/contractsama"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# نسخ احتياطي لقاعدة البيانات
mysqldump -u contractuser -p contractsama > $BACKUP_DIR/database_$DATE.sql

# نسخ احتياطي للملفات
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/contractsama

# حذف النسخ القديمة (أكثر من 30 يوم)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

# رفع إلى التخزين السحابي (اختياري)
# aws s3 sync $BACKUP_DIR s3://your-backup-bucket/
```

```bash
# إعطاء صلاحيات التنفيذ
chmod +x /usr/local/bin/backup-contractsama.sh

# إضافة مهمة cron للنسخ الاحتياطي اليومي
crontab -e
# أضف هذا السطر:
0 2 * * * /usr/local/bin/backup-contractsama.sh
```

### 3. إعداد المراقبة والتنبيهات

```bash
# تثبيت htop لمراقبة الموارد
apt install -y htop

# تثبيت fail2ban لحماية إضافية
apt install -y fail2ban

# إعداد تنبيهات البريد الإلكتروني للأخطاء
apt install -y mailutils
```

---

## 💰 التكلفة الإجمالية الشهرية

| الخدمة | التكلفة | ملاحظات |
|--------|---------|----------|
| **DigitalOcean VPS** | $24 | الخادم الأساسي |
| **النطاق** | $1.25 | ($15/سنة) |
| **Google Workspace** | $18 | (3 مستخدمين × $6) |
| **النسخ الاحتياطية** | $5 | تخزين سحابي إضافي |
| **المجموع** | **$48.25/شهر** | في حدود ميزانيتك |

---

## 🎯 خطوات ما بعد النشر

### 1. اختبار شامل للنظام
```bash
# اختبار الموقع
curl -I https://yourdomain.com

# اختبار PHP
cd /var/www/contractsama
php test_simple.php

# اختبار قاعدة البيانات
php system_test.php
```

### 2. إعداد المراقبة
- **UptimeRobot**: مراقبة مجانية لتوفر الموقع
- **Google Analytics**: تحليل حركة المرور
- **LogWatch**: مراقبة سجلات الخادم

### 3. تحسين الأداء
```bash
# تفعيل OPcache
echo "opcache.enable=1" >> /etc/php/8.1/fpm/php.ini
echo "opcache.memory_consumption=128" >> /etc/php/8.1/fpm/php.ini
systemctl restart php8.1-fpm
```

---

## 🚨 نصائح الأمان المهمة

1. **غيّر كلمة مرور root** بانتظام
2. **فعّل SSH Keys** بدلاً من كلمات المرور
3. **حدّث النظام** أسبوعياً: `apt update && apt upgrade`
4. **راقب السجلات** بانتظام: `tail -f /var/log/nginx/error.log`
5. **اختبر النسخ الاحتياطية** شهرياً

---

## ✅ قائمة تحقق النشر النهائية

- [ ] إنشاء خادم DigitalOcean
- [ ] تثبيت PHP 8.1 وNginx
- [ ] تحميل ونشر المشروع
- [ ] إعداد قاعدة البيانات
- [ ] تكوين Nginx وSSL
- [ ] إعداد البريد الإلكتروني
- [ ] تفعيل النسخ الاحتياطية
- [ ] اختبار جميع الوظائف
- [ ] إعداد المراقبة
- [ ] تدريب المستخدمين

**وقت التنفيذ الإجمالي**: 2-3 ساعات
**الخبرة المطلوبة**: متوسطة (مع الدعم التقني)
**النتيجة**: موقع عقود احترافي يتحمل 100+ مستخدم متزامن 🚀