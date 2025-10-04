from pypdf import PdfReader
import pypdf

print('pypdf version', getattr(pypdf, '__version__', 'unknown'))

reader = PdfReader('tmp_html_render.pdf')
page = reader.pages[0]
print('Page type:', type(page))
print('Has attributes:')
for name in ['mediabox','cropbox','trimbox','bleedbox','artbox','add_transformation','add_transformation_matrix','merge_page','merge_page_into','rotate','rotate_clockwise','rotate_counter_clockwise']:
    print(f'  {name}:', hasattr(page, name))
try:
    mb = page.mediabox
    print('mediabox width/height:', float(mb.width), float(mb.height))
except Exception as e:
    print('mediabox read error:', e)
