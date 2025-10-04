"""
Render `contracts_detail.html` in app context to validate Jinja template compiles.
Run: python scripts\render_contracts_detail.py
"""
import os, sys
root = os.path.dirname(os.path.dirname(__file__))
if root not in sys.path:
    sys.path.insert(0, root)
from app import app, BRAND
from flask import render_template

with app.app_context():
    try:
        with app.test_request_context('/', base_url='http://localhost'):
            html = render_template('contracts_detail.html', BRAND=BRAND, auth_page=False, item={
                'id': 1, 'client_contract_no': 'C-0001', 'internal_serial': 'S-0001',
                'meeting_day_name': 'الاثنين', 'meeting_date_h': '1446/01/01', 'city': 'الرياض',
                'partner2_name': 'أحمد', 'sign2_id': '1234567890', 'sign2_phone': '0555555555',
                'client_address': 'الرياض, السعودية', 'investment_amount': '250000',
                'capital_amount': '200000', 'profit_percent': '10', 'profit_interval_months': '6',
                'withdrawal_notice_days': '30', 'start_date_h': '1446/01/01', 'end_date_h': '1447/01/01',
                'commission_percent': '2', 'exit_notice_days': '60', 'jurisdiction': 'الرياض',
                'content': 'هذا هو نص العقد\nببعض التفاصيل.'
            })
            print('Rendered contracts_detail.html length:', len(html))
    except Exception as e:
        print('Render error:', e)
