from jinja2 import Environment, FileSystemLoader
import os
from pdf_utils import generate_contract_pdf

env = Environment(loader=FileSystemLoader('templates'))
template = env.get_template('contract_fixed_v1.txt')

# sample context values for rendering
ctx = {
    'serial': 'B123-1447-0001',
    'meeting_day_name': 'الاثنين',
    'meeting_date_h': '1447-01-01',
    'city': 'الرياض',
    'partner2_name': 'هيا الزويهري',
    'capital_amount': '500,000',
    'withdrawal_notice_days': '30',
    'profit_percent': '10',
    'profit_interval_months': '6',
    'commission_percent': '2',
    'exit_notice_days': '60',
    'jurisdiction': 'المحاكم السعودية',
    'start_date_h': '1447-01-01',
    'end_date_h': '1448-01-01',
    'penalty_amount': '1000',
    'sign2_name': 'هيا الزويهري',
    'sign2_id': '1234567890',
    'sign2_phone': '0501234567',
}

rendered = template.render(**ctx)

pdf = generate_contract_pdf(
    title='عقد مشاركة',
    content=rendered,
    serial=ctx['serial'],
    created_at='2025-09-15T12:00:00',
    brand={'primary': '#1F3C88', 'accent': '#22B8CF', 'name': 'ContractSama'},
    logo_path=os.path.join('static', 'img', 'logo.png'),
    font_path=None,
    prepared_by='الموظف'
)

out = 'contract_fixed_v1.pdf'
open(out, 'wb').write(pdf)
print('Wrote', out)
