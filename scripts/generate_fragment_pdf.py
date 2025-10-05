import os, sys
# Ensure repository root is on sys.path so imports like `pdf_utils` resolve
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from pdf_utils import generate_from_content_file

out = 'tmp_contract_fragment.pdf'
try:
    path = generate_from_content_file('templates/contract_fragment_pre.html', out=out, data={}, preserve_layout=True, force_canvas=True)
    print('WROTE', path)
except Exception as e:
    print('ERROR', type(e).__name__, e)
