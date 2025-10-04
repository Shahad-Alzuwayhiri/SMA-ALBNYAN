from pdf_utils import generate_contract_pdf

content = "\n\n".join([
    "تمهيد",
    "هذا نص تجريبي ليختبر تباعد الفقرات. السطر الأول من الفقرة التجريبية.",
    "سطر تابع للفقرة ذاتها لضمان التجميع داخل فقرة واحدة.",
    "",
    "البند 1",
    "هذا بند يحتوي على نص كافٍ ليشغل أكثر من سطر ويُظهر المسافات بين البنود والفقرات.",
    "",
    "البند 2",
    "نص تجريبي آخر للتأكد من المسافة بعد العنوان.",
])

pdf = generate_contract_pdf(
    title='اختبار التباعد',
    content=content,
    serial='TEST-0001',
    created_at='2025-09-10T12:00:00',
    brand={'primary': '#1F3C88', 'accent': '#22B8CF', 'name': 'ContractSama'},
    logo_path=None,
    font_path=None,
    prepared_by='المطور'
)

with open('contract-test.pdf', 'wb') as f:
    f.write(pdf)

print('Wrote contract-test.pdf')
