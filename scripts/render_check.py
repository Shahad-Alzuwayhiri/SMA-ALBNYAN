from jinja2 import Environment, FileSystemLoader, select_autoescape
import os, sys
sys.path.insert(0, r'c:\Users\Admin\Desktop\ContractSama')
TPL_DIR = os.path.join(r'c:\Users\Admin\Desktop\ContractSama','templates')
env = Environment(loader=FileSystemLoader(TPL_DIR), autoescape=select_autoescape(['html','xml']))
def _mock_url_for(endpoint, **kwargs):
    # minimal mock: return a plausible path for static files and named routes
    if endpoint == 'static' and 'filename' in kwargs:
        return '/static/' + kwargs['filename']
    if endpoint == 'favicon':
        return '/favicon.ico'
    return f'/{endpoint}'
env.globals['url_for'] = _mock_url_for
def _mock_get_flashed_messages(with_categories=False):
    return []
env.globals['get_flashed_messages'] = _mock_get_flashed_messages
env.globals['BRAND'] = {'name': 'سما البنيان التجارية'}
env.globals['csrf_token'] = lambda: ''
class _MockUser: pass
env.globals['current_user'] = _MockUser()
class _MockRequest:
    def __init__(self):
        self.endpoint = None
env.globals['request'] = _MockRequest()
class _MockUser2:
    def __init__(self):
        self.avatar_url = None
        self.id = 1
env.globals['user'] = _MockUser2()
try:
    tpl = env.get_template('contracts_list.html')
    out = tpl.render()
    print('Rendered contracts_list.html (len):', len(out))
    tpl2 = env.get_template('dashboard.html')
    print('Rendered dashboard.html (len):', len(tpl2.render()))
except Exception as e:
    print('Render error:', e)
    raise
