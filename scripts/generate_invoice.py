import os, sys
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
if ROOT not in sys.path:
    sys.path.insert(0, ROOT)
from pdf_utils import generate_invoice_pdf

out = 'invoice_sample.pdf'
logo = os.path.join('static', 'img', 'company_logo.png')
# fallback to the repo's static logo if present
if not os.path.exists(logo):
    # try top-level static img or static/fonts logo name
    possible = [os.path.join('static', 'img', 'logo.png'), os.path.join('static', 'fonts', 'Amiri-Regular.ttf')]
    for p in possible:
        if os.path.exists(p):
            logo = p
            break

brand = {'name':'شركة سما البنيان التجارية', 'primary':'#0F2A5A', 'accent':'#E9C7C7', 'sidebar':'#0F6161'}

sample = {
    'client_name':'عميل تجريبي',
    'invoice_no':'INV-2025-0001',
    'date':'2025-10-04',
    'items':[{'desc':'خدمة تطوير عقاري','unit':'10,000.00','qty':'1','total':'10,000.00'},
             {'desc':'استشارة','unit':'3,000.00','qty':'1','total':'3,000.00'}],
    'subtotal':'13,000.00','tax':'650.00','total':'13,650.00'
}

print('Generating', out)
path = generate_invoice_pdf(out_path=out, logo_path=logo, brand=brand, data=sample)
print('Wrote', path)
if os.path.exists(path):
    print('Size:', os.path.getsize(path))
else:
    print('File not found')
