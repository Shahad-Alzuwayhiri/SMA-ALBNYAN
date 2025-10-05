import os, sys, re
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
import pdf_utils

IN = os.path.join('templates', 'contract_fragment.html')
OUT = 'tmp_contract_fragment_simple.pdf'

# Read template as raw text (do not render Jinja) so placeholders stay as-is
with open(IN, 'r', encoding='utf-8') as f:
    raw = f.read()

# Remove HTML tags but keep inner text and Jinja placeholders like {{ ... }}
# Strategy: temporarily protect Jinja markers, strip tags, then restore markers
raw = raw.replace('{{', '<<<JINJA_OPEN>>>').replace('}}', '<<<JINJA_CLOSE>>>')
text_only = re.sub(r'<[^>]+>', '', raw)
text_only = text_only.replace('<<<JINJA_OPEN>>>', '{{').replace('<<<JINJA_CLOSE>>>', '}}')

# Split into non-empty lines and normalize whitespace
lines = [l.strip() for l in text_only.splitlines()]
lines = [l for l in lines if l]

# Register font (use project's font helper)
font_name = pdf_utils._register_font(None)

w, h = A4
margin = 18 * mm
y = h - 25 * mm
line_height = 12 * mm

c = canvas.Canvas(OUT, pagesize=A4)
# Choose a base font size that fits; Amiri works well around 12-14
base_size = 12
try:
    c.setFont(font_name, base_size)
except Exception:
    c.setFont('Helvetica', base_size)

for line in lines:
    # Shape Arabic text for proper display
    try:
        shaped = pdf_utils._shape_ar(line)
    except Exception:
        shaped = line
    # Draw right-aligned near right margin
    try:
        c.drawRightString(w - margin, y, shaped)
    except Exception:
        c.drawString(margin, y, shaped)
    y -= line_height
    if y < margin:
        c.showPage()
        try:
            c.setFont(font_name, base_size)
        except Exception:
            c.setFont('Helvetica', base_size)
        y = h - 25 * mm

c.save()
print('WROTE', OUT)
