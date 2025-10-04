"""
Quick smoke script to render auth templates and ensure they don't raise template errors.
Run from project root: python scripts/render_auth_templates.py
"""
import sys
import os
root = os.path.dirname(os.path.dirname(__file__))
if root not in sys.path:
    sys.path.insert(0, root)

from app import app, BRAND
from flask import render_template

with app.app_context():
    # Render templates inside a test request context so url_for() works
    for tpl in ("login.html", "signup.html", "forgot_password.html", "reset_password.html"):
        try:
            with app.test_request_context('/', base_url='http://localhost'):
                html = render_template(tpl, BRAND=BRAND, auth_page=True)
            print(f"Rendered {tpl}: {len(html)} bytes")
        except Exception as e:
            print(f"Error rendering {tpl}: {e}")
