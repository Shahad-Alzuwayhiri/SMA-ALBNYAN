from app import app
with app.app_context():
    loader = app.jinja_loader
    try:
        print('loader:', loader)
        # print search paths if filesystem loader
        if hasattr(loader, 'searchpath'):
            print('searchpath:', loader.searchpath)
        print('list_templates contains _macros.html?', '_macros.html' in app.jinja_env.list_templates())
        try:
            src = loader.get_source(app.jinja_env, '_macros.html')
            print('get_source OK, length:', len(src[0]))
        except Exception as e:
            print('get_source error:', repr(e))
    except Exception as e:
        print('diag error', e)
