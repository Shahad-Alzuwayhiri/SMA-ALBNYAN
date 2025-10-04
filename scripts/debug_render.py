import os, sys, traceback
root = os.path.dirname(os.path.dirname(__file__))
if root not in sys.path:
    sys.path.insert(0, root)
from app import app, BRAND
from flask import render_template

def render_tpl(tpl):
    try:
        with app.app_context():
            with app.test_request_context('/', base_url='http://localhost'):
                # quick check: can we load _macros.html?
                try:
                    app.jinja_env.get_template('_macros.html')
                    print('_macros.html: OK in jinja env')
                except Exception as e:
                    print('macros load error:', e)
                return render_template(tpl, BRAND=BRAND, auth_page=False, item={}, items=[], user=None, metrics={}, notifications=[], activities=[], friends=[])
    except Exception as e:
        traceback.print_exc()
        return None

if __name__ == '__main__':
    import sys
    tpl = sys.argv[1] if len(sys.argv)>1 else 'contracts_list.html'
    print('Rendering', tpl)
    html = render_tpl(tpl)
    if html is not None:
        print('Rendered length', len(html))
