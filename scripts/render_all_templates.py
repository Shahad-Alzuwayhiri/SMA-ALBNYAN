"""
Render all templates in the templates/ directory (non-recursive) and report success/errors.
Run: python scripts\render_all_templates.py
"""
import os, sys
root = os.path.dirname(os.path.dirname(__file__))
if root not in sys.path:
    sys.path.insert(0, root)
from app import app, BRAND
from flask import render_template

TEMPLATES_DIR = os.path.join(root, 'templates')
files = [f for f in os.listdir(TEMPLATES_DIR) if f.endswith('.html')]

class DummyUser:
    def __init__(self):
        self.id = 1
        self.name = 'اختبار'
        self.email = 'test@example.com'
        self.role = 'user'

class DummyItem:
    def __init__(self):
        self.id = 1
        self.title = 'العقد التجريبي'
        self.client_contract_no = 'C-0001'
        self.internal_serial = 'S-0001'
        self.meeting_day_name = 'الاثنين'
        self.meeting_date_h = '1446/01/01'
        self.city = 'الرياض'
        self.partner2_name = 'علي'
        self.sign2_id = '1234567890'
        self.sign2_phone = '0500000000'
        self.client_address = 'الرياض'
        self.investment_amount = '100000'
        self.capital_amount = '80000'
        self.profit_percent = '10'
        self.profit_interval_months = '6'
        self.withdrawal_notice_days = '30'
        self.start_date_h = '1446/01/01'
        self.end_date_h = '1447/01/01'
        self.commission_percent = '2'
        self.exit_notice_days = '60'
        self.jurisdiction = 'الرياض'
        self.content = 'نص تجريبي للعقد.'

results = []
with app.app_context():
    for tpl in files:
        try:
            with app.test_request_context('/', base_url='http://localhost'):
                html = render_template(
                    tpl,
                    BRAND=BRAND,
                    auth_page=False,
                    item=DummyItem(),
                    items=[],
                    user=DummyUser(),
                    metrics={},
                    notifications=[],
                    activities=[],
                    friends=[],
                    chart_data=None,
                    all_contracts=[],
                    recent_activities=[],
                    tasks=[],
                    formvals={},
                    form_data={},
                    preview_text='نص المعاينة'
                )
            results.append((tpl, 'OK', len(html)))
        except Exception as e:
            results.append((tpl, 'ERROR', str(e)))

for r in results:
    print(r)

errors = [r for r in results if r[1] == 'ERROR']
print('\nSummary: {} templates, {} errors'.format(len(results), len(errors)))
if errors:
    for e in errors:
        print('ERROR:', e[0], e[2])
