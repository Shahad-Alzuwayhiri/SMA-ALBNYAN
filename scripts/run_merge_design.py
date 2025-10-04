import sys, os
sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))
from pdf_utils import generate_from_content_file

if __name__ == '__main__':
    out = generate_from_content_file(content_file='templates/contract_fixed_v1.html', out='outputs/tmp_from_design.pdf', design_pdf='tmp_html_render.pdf', watermark_path='static/img/sama_logo.png', preserve_layout=True)
    print('Wrote', out)
