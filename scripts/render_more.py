from jinja2 import Environment, FileSystemLoader, select_autoescape
import os, sys
sys.path.insert(0, r'c:\Users\Admin\Desktop\ContractSama')
TPL_DIR = os.path.join(r'c:\Users\Admin\Desktop\ContractSama','templates')
env = Environment(loader=FileSystemLoader(TPL_DIR), autoescape=select_autoescape(['html','xml']))

def _mock_url_for(endpoint, **kwargs):
    if endpoint == 'static' and 'filename' in kwargs:
        return '/static/' + kwargs['filename']
    return f'/{endpoint}'

env.globals['url_for'] = _mock_url_for
env.globals['get_flashed_messages'] = lambda **k: []
env.globals['BRAND'] = {'name':'سما البنيان التجارية'}
env.globals['csrf_token'] = lambda: ''
env.globals['request'] = type('R',(object,),{'endpoint':None})()
env.globals['user'] = type('U',(object,),{'avatar_url':None,'id':1})()

tpls = ['contracts_closed.html','contracts_edit.html']
for t in tpls:
    try:
        tpl = env.get_template(t)
        if t == 'contracts_edit.html':
            mock_item = {
                'title': 'عقد تجريبي',
                'content': 'نص العقد التجريبي',
                'client_name': 'عميل تجريبي',
                'client_id_number': '1234567890',
                'client_phone': '0500000000',
                'client_address': 'الرياض',
                'investment_amount': '10000',
            }
            out = tpl.render(item=mock_item)
        else:
            out = tpl.render()
        print(f'Rendered {t} length={len(out)}')
    except Exception as e:
        print(f'Error rendering {t}: {e}')
        raise
print('done')
