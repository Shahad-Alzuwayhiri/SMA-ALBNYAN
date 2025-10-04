"""Render a text-only PDF overlay using extracted positions (outputs/orig_text_positions.json).

The overlay will draw each line's text using the registered font (Amiri-Regular) at the
same coordinates extracted from the original PDF so that when merged over the design
PDF the selectable/extractable text order and coordinates match the original.

Writes outputs/overlay_exact.pdf
"""
from pathlib import Path
import json
from io import BytesIO
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
import os

OUT = Path('outputs/overlay_exact.pdf')
POS = Path('outputs/orig_text_positions.json')

if not POS.exists():
    print('positions json missing:', POS)
    raise SystemExit(1)

with POS.open('r', encoding='utf-8') as f:
    data = json.load(f)

w, h = A4
buf = BytesIO()
canv = canvas.Canvas(buf, pagesize=A4)

# Register Amiri if available
fonts_dir = os.path.join(os.path.dirname(__file__), '..', 'static', 'fonts')
amiri_path = os.path.join(fonts_dir, 'Amiri-Regular.ttf')
font_name = 'Amiri-Regular-overlay'
try:
    if os.path.isfile(amiri_path):
        pdfmetrics.registerFont(TTFont(font_name, amiri_path))
        print('Registered overlay font', amiri_path)
    else:
        print('Amiri font not found, using Helvetica')
        font_name = 'Helvetica'
except Exception as e:
    print('Font registration failed:', e)
    font_name = 'Helvetica'

# Draw each page
for p in data.get('pages', []):
    for ln in p.get('lines', []):
        txt = ln.get('text', '')
        bbox = ln.get('bbox', [0,0,0,0])
        x0, y0, x1, y1 = bbox
        # PyMuPDF and ReportLab have same coordinate origin at lower-left for PDFs
        # We'll use the right edge for right-aligned drawing
        try:
            # estimate font size from bbox height
            height = max(6.0, (y1 - y0))
            fsize = height * 0.7
            canv.setFont(font_name, fsize)
        except Exception:
            canv.setFont(font_name, 12)
        # Draw text right-aligned to x1
        # shape text? we rely on the pdf_utils shaping pipeline; here we assume
        # the extracted text is already in visual order from PyMuPDF
        try:
            canv.drawRightString(x1, y0, txt)
        except Exception:
            try:
                canv.drawString(x0, y0, txt)
            except Exception:
                pass
    canv.showPage()

canv.save()
buf.seek(0)
OUT.parent.mkdir(parents=True, exist_ok=True)
with OUT.open('wb') as f:
    f.write(buf.read())

print('Wrote', OUT)
