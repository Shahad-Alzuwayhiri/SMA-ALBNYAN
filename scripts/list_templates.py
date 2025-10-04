import os,sys
root = os.path.dirname(os.path.dirname(__file__))
if root not in sys.path:
    sys.path.insert(0, root)
from app import app
with app.app_context():
    tpl_list = app.jinja_env.list_templates()
    print('templates count', len(tpl_list))
    for t in sorted(tpl_list):
        print(t)
