التأكد من DOCTYPE وQuirks Mode

مشكلة شائعة: بعض الصفحات أو الملفات الجزئية تطبع محتوى (مسافة/تعليق/DOCTYPE) قبل أن يبدأ المستند بـ <!DOCTYPE html>، مما يجعل المتصفح يدخل في Quirks Mode.

خطوات سريعة للتحقق محليًا:

1) افتح المتصفح واذهب إلى الصفحة:
   http://localhost/ContractSama/public/notifications.php

2) افتح DevTools → Console ثم نفّذ:
   document.compatMode

   - إذا أعاد "CSS1Compat" فهذا جيد — Standards Mode.
   - إذا أعاد "BackCompat" فهذا يعنى Quirks Mode → تحقق من وجود أي مخرجات قبل <!DOCTYPE html>.

3) تحقق أن أول بايت في الملف الذي يطبع الصفحة هو <!DOCTYPE html>:
   - لا توجد مسافات أو تعليقات أو BOM قبلها.
   - إذا كان لديك قالب رئيسي (templates/master_layout.php أو templates/layouts/main.php) فتأكد أنه يطبع <!DOCTYPE html> كأول سطر.

4) فحص سريع عبر PowerShell (في مجلد المشروع):

   # يعرض أول ثمانية بايت من ملف
   $b = [IO.File]::ReadAllBytes('templates/notifications/index.php');
   ($b[0..7] | ForEach-Object { '{0:X2}' -f $_ }) -join ' '

   - يجب أن ترى: 3C 21 44 4F 43 54 59 50  (أي '<!DOCTYP') إذا كانت الصفحة تحتوي على DOCTYPE مباشرة.

نصائح إصلاح:
- اجعل `templates/master_layout.php` هو المسؤول الوحيد عن طباعة DOCTYPE.
- اجعل الملفات الجزئية (includes/partials) تبدأ بدون أي HTML راسخ — فقط HTML داخل <head> أو داخل الجسم عندما يتم تضمينها.
- تأكد من حفظ الملفات بدون BOM (UTF-8 without BOM).

المتابعة:
- شغّل صفحة الاشعارات محليًا وتحقق من document.compatMode.
- إذا بقيت المشكلة، أرسل لي نتيجة الأمر document.compatMode ولقطة من أول 64 بايت للصفحة (يمكن تنفيذ نفس فحص البايتات على الملف الذي يخدم الصفحة أو على الملف المضمن الذي يطبعها).