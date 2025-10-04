from pdf_utils import generate_contract_pdf
from io import BytesIO
import os

sample_content = '''تمهيد

هذا نص تجريبي للعقد. 

البند الأول
يبدأ النص هنا ويجب أن يظهر في ملف الـ PDF بشكل صحيح.

البند الثاني
نص إضافي للتأكد من الترقيم والتباعد.

البند الثامن
هذا البند الثامن يجب أن يظهر بالكامل ويحتوي على أسطر متعددة لقياس التباعد.

خاتمة
شكراً.
'''

out = generate_contract_pdf(
    title='عقد تجريبي',
    content=sample_content,
    serial='TEST-0001',
    created_at='2025-09-15T12:00:00',
    brand={'name':'شركة سما البنيان التجارية','primary':'#1F3C88','accent':'#22B8CF'},
    logo_path=os.path.join('static','img','logo.png'),
    font_path=os.path.join('static','fonts','Amiri-Regular.ttf'),
    client_name='العميل',
    client_id_number='1234567890',
    client_phone='0500000000',
    client_address='الرياض',
    investment_amount=10000.0,
    signature_path=None,
    prepared_by='الموظف'
)

with open('sample_contract.pdf','wb') as f:
    f.write(out)

print('Wrote sample_contract.pdf, size=', os.path.getsize('sample_contract.pdf'))
import sys
print('This script has been consolidated into scripts/pdf_tools.py')
print('Run: python scripts/pdf_tools.py sample')
sys.exit(0)
