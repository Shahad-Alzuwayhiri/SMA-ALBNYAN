import sys, os
sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))
from pdf_utils import generate_contract_pdf, _find_preferred_font
from pathlib import Path
from pypdf import PdfReader, PdfWriter
import difflib

ROOT = Path(__file__).resolve().parent.parent
orig_path = Path(r'C:/Users/Admin/Desktop/العقد.pdf')
design_pdf = ROOT / 'tmp_html_render.pdf'
out_merged = ROOT / 'outputs' / 'tmp_exact_merged.pdf'
overlay_tmp = ROOT / 'outputs' / 'tmp_exact_overlay.pdf'

# prepare parameters
font = _find_preferred_font(prefer=['Amiri','Cairo','DejaVu'])
if not font:
    font = None

# Read template content from templates/contract_fixed_v1.html (rendering handled inside)
content_file = str(ROOT / 'templates' / 'contract_fixed_v1.html')

# Generate an overlay bytes with strict per-line canvas: preserve_layout + force_canvas
pdf_bytes = generate_contract_pdf(
    title='عقد',
    content=open(content_file,'r',encoding='utf-8').read(),
    serial='GEN-STRICT',
    created_at='2025-10-04T00:00:00',
    brand={'name':'ContractSama'},
    logo_path=None,
    font_path=font,
    preserve_layout=True,
    force_canvas=True,
    draw_background=False,
)
# write overlay
overlay_tmp.parent.mkdir(parents=True, exist_ok=True)
with open(overlay_tmp, 'wb') as f:
    f.write(pdf_bytes)
print('Wrote overlay:', overlay_tmp)

# merge overlay onto design
if not design_pdf.exists():
    print('Design PDF missing:', design_pdf)
    raise SystemExit(1)

reader_design = PdfReader(str(design_pdf))
reader_overlay = PdfReader(str(overlay_tmp))
writer = PdfWriter()
for i, dp in enumerate(reader_design.pages):
    page = dp
    # merge overlay page if exists
    if i < len(reader_overlay.pages):
        try:
            page.merge_page(reader_overlay.pages[i])
        except Exception:
            pass
    writer.add_page(page)
# append any remaining overlay pages
for j in range(len(reader_design.pages), len(reader_overlay.pages)):
    writer.add_page(reader_overlay.pages[j])
with open(out_merged, 'wb') as f:
    writer.write(f)
print('Wrote merged:', out_merged)

# extract text helper
def extract_text(path: Path):
    r = PdfReader(str(path))
    pages = []
    for page in r.pages:
        pages.append(page.extract_text() or '')
    return '\n\n---PAGE BREAK---\n\n'.join(pages).splitlines()

orig_lines = extract_text(orig_path)
gen_lines = extract_text(out_merged)
print('Original length:', len(orig_lines), 'Merged length:', len(gen_lines))
# print first diff lines
for i, line in enumerate(difflib.unified_diff(orig_lines, gen_lines, fromfile='original', tofile='merged', lineterm='')):
    if i < 400:
        print(line)
    else:
        break

print('\n--- ORIGINAL (first 40 lines) ---')
print('\n'.join(orig_lines[:40]))
print('\n--- MERGED (first 40 lines) ---')
print('\n'.join(gen_lines[:40]))
