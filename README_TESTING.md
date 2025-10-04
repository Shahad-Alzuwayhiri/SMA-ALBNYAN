Testing
=======

تشغيل الاختبارات محليًا:

```powershell
python -m pip install -r requirements.txt
python -m pytest -q
```

اختبار التكامل الذي يولّد PDF معني بالتحقّق من ظهور بنود مثل `البند الثامن` موسوم بـ `integration`.

يتم تشغيل الاختبارات تلقائيًا عبر GitHub Actions باستخدام الملف `.github/workflows/ci.yml`.
