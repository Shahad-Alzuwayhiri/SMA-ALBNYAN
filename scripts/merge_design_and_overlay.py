"""Merge design reference (tmp_html_render.pdf) with watermark and the overlay outputs/overlay_exact.pdf

Produces outputs/final_exact.pdf
"""
from pathlib import Path
from io import BytesIO
import os

DESIGN = Path('tmp_html_render.pdf')
OVERLAY = Path('outputs/overlay_exact.pdf')
WATERMARK = Path('static/img/sama_logo.png')
OUT = Path('outputs/final_exact.pdf')

if not DESIGN.exists():
    print('Design PDF missing:', DESIGN)
    raise SystemExit(1)
if not OVERLAY.exists():
    print('Overlay PDF missing:', OVERLAY)
    raise SystemExit(1)

try:
    from pypdf import PdfReader, PdfWriter
except Exception as e:
    print('pypdf required but not installed:', e)
    raise SystemExit(1)

# Build watermark Pdf bytes (reuse pdf_utils._make_watermark_pdf if available)
try:
    from pdf_utils import _make_watermark_pdf
    wm_bytes = None
    if WATERMARK.exists():
        try:
            # create watermark with 30% alpha
            wm_bytes = _make_watermark_pdf(str(WATERMARK), pages=1, alpha=0.30)
        except Exception:
            wm_bytes = None
except Exception:
    wm_bytes = None

d = PdfReader(str(DESIGN))
o = PdfReader(str(OVERLAY))
writer = PdfWriter()

for i, dp in enumerate(d.pages):
    page = dp
    # if watermark exists and has at least this page
    if wm_bytes:
        try:
            from pypdf import PdfReader
            wm = PdfReader(BytesIO(wm_bytes))
            if i < len(wm.pages):
                try:
                    page.merge_page(wm.pages[i])
                except Exception:
                    pass
        except Exception:
            pass
    # overlay page
    if i < len(o.pages):
        try:
            page.merge_page(o.pages[i])
        except Exception:
            pass
    writer.add_page(page)

# append extra overlay pages if overlay longer than design
if len(o.pages) > len(d.pages):
    for j in range(len(d.pages), len(o.pages)):
        writer.add_page(o.pages[j])

OUT.parent.mkdir(parents=True, exist_ok=True)
with OUT.open('wb') as f:
    writer.write(f)

print('Wrote', OUT)
