# 🚀 دليل النشر الواقعي لنظام إدارة العقود

## 📋 متطلبات الخادم

### الحد الأدنى:
- **PHP**: 8.1 أو أحدث
- **الذاكرة**: 512MB RAM 
- **التخزين**: 5GB مساحة فارغة
- **قاعدة البيانات**: SQLite (مدمجة) أو MySQL

### المستحسن للإنتاج:
- **CPU**: 2 نواة
- **الذاكرة**: 2GB RAM
- **التخزين**: 20GB SSD
- **النطاق الترددي**: غير محدود

## 🌐 خيارات الاستضافة

### 1. الاستضافة المشتركة (الأبسط)
**التكلفة**: $5-15/شهر
**مناسبة لـ**: المشاريع الصغيرة والمتوسطة

**الخطوات**:
1. رفع الملفات عبر cPanel File Manager
2. إنشاء قاعدة بيانات MySQL (اختياري)
3. تحديث إعدادات الاتصال

### 2. الخوادم الافتراضية VPS (مرونة أكبر)
**التكلفة**: $20-50/شهر
**مناسبة لـ**: المشاريع الكبيرة والمؤسسات

**موفري الخدمة المقترحين**:
- **DigitalOcean**: سهل الاستخدام
- **Linode**: أداء ممتاز
- **Vultr**: أسعار تنافسية
- **AWS Lightsail**: مرونة Amazon

### 3. الاستضافة السحابية (الأفضل للمؤسسات)
**التكلفة**: $30-100+/شهر
**مناسبة لـ**: المشاريع الكبيرة مع حركة مرور عالية

## 🔧 إعداد الخادم (VPS/Cloud)

### 1. إعداد نظام Ubuntu 22.04

```bash
# تحديث النظام
sudo apt update && sudo apt upgrade -y

# تثبيت PHP 8.1 والإضافات المطلوبة
sudo apt install -y php8.1 php8.1-fpm php8.1-cli php8.1-common php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml php8.1-bcmath php8.1-sqlite3

# تثبيت Nginx
sudo apt install -y nginx

# تثبيت Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# تثبيت Git
sudo apt install -y git unzip
```

### 2. إعداد Nginx

```bash
sudo nano /etc/nginx/sites-available/contractsama
```

إضافة التكوين التالي:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/contractsama/php_app/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

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

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 3. تمكين الموقع وإعادة تشغيل Nginx

```bash
sudo ln -s /etc/nginx/sites-available/contractsama /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 4. إعداد SSL مع Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

## 📦 نشر التطبيق

### الطريقة 1: النشر اليدوي

```bash
# إنشاء مجلد المشروع
sudo mkdir -p /var/www/contractsama
cd /var/www/contractsama

# استنساخ المشروع من GitHub
sudo git clone https://github.com/Shahad-Alzuwayhiri/ContractSama.git .

# تبديل إلى فرع الإنتاج
sudo git checkout feature/php-migration

# تثبيت التبعيات
cd php_app
sudo composer install --no-dev --optimize-autoloader

# إعداد الصلاحيات
sudo chown -R www-data:www-data /var/www/contractsama
sudo chmod -R 755 /var/www/contractsama
sudo chmod -R 775 /var/www/contractsama/php_app/storage
sudo chmod -R 775 /var/www/contractsama/php_app/database

# إعداد قاعدة البيانات
php setup_database.php
```

### الطريقة 2: النشر الآلي

```bash
# رفع وتشغيل سكريبت النشر
cd /var/www/contractsama
php deploy.php
```

## 🔒 إعدادات الأمان

### 1. إعداد جدار الحماية

```bash
sudo ufw enable
sudo ufw allow 22  # SSH
sudo ufw allow 80  # HTTP
sudo ufw allow 443 # HTTPS
```

### 2. إعداد النسخ الاحتياطي التلقائي

```bash
# إنشاء سكريبت النسخ الاحتياطي
sudo nano /usr/local/bin/backup-contractsama.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/backups/contractsama"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# نسخ احتياطي لقاعدة البيانات
cp /var/www/contractsama/php_app/database/contracts.db $BACKUP_DIR/contracts_$DATE.db

# نسخ احتياطي للملفات المرفوعة
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/contractsama/php_app/storage/

# حذف النسخ القديمة (أكثر من 30 يوم)
find $BACKUP_DIR -name "*.db" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

```bash
sudo chmod +x /usr/local/bin/backup-contractsama.sh

# إضافة مهمة cron للنسخ الاحتياطي اليومي
sudo crontab -e
# إضافة السطر التالي:
0 2 * * * /usr/local/bin/backup-contractsama.sh
```

## 📊 المراقبة والصيانة

### 1. مراقبة الأداء

```bash
# تثبيت htop لمراقبة الموارد
sudo apt install -y htop

# مراقبة استخدام القرص
df -h

# مراقبة استخدام الذاكرة
free -h
```

### 2. تحديث التطبيق

```bash
cd /var/www/contractsama
sudo git pull origin feature/php-migration
cd php_app
sudo composer install --no-dev --optimize-autoloader
php upgrade_system.php
sudo systemctl reload php8.1-fpm
```

## 📧 إعداد البريد الإلكتروني (اختياري)

لتفعيل إشعارات البريد الإلكتروني:

```bash
sudo apt install -y postfix mailutils
```

## 🌍 ربط النطاق

1. **شراء النطاق**: من Namecheap، GoDaddy، أو أي مزود آخر
2. **توجيه DNS**: 
   - A Record: yourdomain.com → IP العام للخادم
   - CNAME: www.yourdomain.com → yourdomain.com
3. **انتظار انتشار DNS**: 24-48 ساعة

## ✅ قائمة التحقق قبل النشر

- [ ] إعداد PHP 8.1+ مع جميع الإضافات المطلوبة
- [ ] تكوين Nginx أو Apache
- [ ] إعداد SSL Certificate
- [ ] تثبيت وتكوين التطبيق
- [ ] اختبار جميع الوظائف الأساسية
- [ ] إعداد النسخ الاحتياطي التلقائي
- [ ] تكوين جدار الحماية
- [ ] إعداد مراقبة الخادم
- [ ] ربط النطاق وتوجيه DNS

## 🎯 التكلفة الإجمالية المتوقعة

### للمشاريع الصغيرة:
- استضافة مشتركة: $10/شهر
- نطاق: $15/سنة
- SSL: مجاني (Let's Encrypt)
- **المجموع**: ~$135/سنة

### للمشاريع المتوسطة:
- VPS: $25/شهر
- نطاق: $15/سنة
- SSL: مجاني
- **المجموع**: ~$315/سنة

### للمشاريع الكبيرة:
- Cloud Server: $50/شهر
- نطاق: $15/سنة
- CDN: $10/شهر (اختياري)
- **المجموع**: ~$735/سنة

---

**📞 الدعم التقني**: إذا واجهت أي مشاكل، يمكن مراجعة ملفات LOG في `/var/www/contractsama/php_app/storage/logs/`