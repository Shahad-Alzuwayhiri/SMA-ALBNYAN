"""
Smoke script to render main (non-auth) templates to ensure topnav/sidebar appear correctly.
"""
import sys, os
root = os.path.dirname(os.path.dirname(__file__))
if root not in sys.path:
    sys.path.insert(0, root)
from app import app, BRAND
from flask import render_template

with app.app_context():
    class DummyUser:
        def __init__(self):
            self.id = 1
            self.name = 'Test User'
            self.initials = 'ت'
            self.title = 'موظف'
            self.department = 'القسم'

    pages = [
        ("dashboard.html", {"user": DummyUser(), "tasks": [], "uploads": [], "notifications": [], "activities": []}),
        ("contracts_list.html", {"items": [], "q": None}),
    ]
    for tpl, ctx in pages:
        try:
            with app.test_request_context('/', base_url='http://localhost'):
                html = render_template(tpl, BRAND=BRAND, auth_page=False, **ctx)
            print(f"Rendered {tpl}: {len(html)} bytes")
        except Exception as e:
            print(f"Error rendering {tpl}: {e}")
