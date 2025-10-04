import os, sys
# Ensure repo root is on sys.path so pdf_utils (module file) can be imported
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
if ROOT not in sys.path:
    sys.path.insert(0, ROOT)
from pdf_utils import generate_from_content_file

out = 'tmp_html_render.pdf'
data = {
    'contract_number': 'B123-1447',
    'contract_date': '12-02-1447',
    'partner_name': 'سهام بنت سِرحان بن هليل المطرفي',
    'partner_id': '2193599665',
    'partner_phone': '1018906188',
    'capital': '100,000',
    'profit': '30',
    'duration': '6 أشهر',
    'manager_signature': 'شركة سما البنيان التجارية'
}

print('Rendering template to', out)
brand = {'name':'شركة سما البنيان التجارية', 'primary':'#0F2A5A', 'accent':'#E9C7C7', 'sidebar':'#0F2A5A'}
logo = os.path.join('static', 'img', 'company_logo.png')
if not os.path.exists(logo):
    # try other common names
    alt = os.path.join('static', 'img', 'logo.png')
    if os.path.exists(alt):
        logo = alt

# Use preserve_layout + Platypus Paragraph flowables (Option A), but supply
# brand and logo so the preserve-layout background drawing will apply.
path = generate_from_content_file('templates/contract_fixed_v1.html', out=out, data=data, title='عقد مشاركة', serial='B123-1447', created_at='2025-10-04T12:00:00', preserve_layout=True, force_canvas=True, brand=brand, logo_path=logo)
print('Wrote:', path)
if os.path.exists(path):
    print('Size:', os.path.getsize(path))
else:
    print('File not found')
