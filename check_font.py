import os
from pdf_utils import _register_font
root = os.path.dirname(__file__)
fonts_dir = os.path.join(root, 'static', 'fonts')
print('fonts_dir=', fonts_dir)
try:
    files = os.listdir(fonts_dir)
except Exception as e:
    files = []
print('files:', files[:50])
res = _register_font('static/fonts/DejaVuSans.ttf')
print('register result:', res)
