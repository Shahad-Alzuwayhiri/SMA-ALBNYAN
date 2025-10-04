"""Extract per-line text and bounding boxes from a PDF using PyMuPDF (fitz).

Writes outputs/orig_text_positions.json with structure:
{
  "pages": [
    {"page": 1, "lines": [ {"text": "...", "bbox": [x0,y0,x1,y1] }, ... ] },
    ...
  ]
}

This is used to render an exact overlay by drawing each line at the same
coordinates using ReportLab.
"""
from pathlib import Path
import json

try:
    import fitz
except Exception as e:
    print('PyMuPDF (fitz) is required but not installed:', e)
    raise SystemExit(1)

orig = Path('C:/Users/Admin/Desktop/العقد.pdf')
out = Path('outputs/orig_text_positions.json')
if not orig.exists():
    print('Original file not found:', orig)
    raise SystemExit(1)

doc = fitz.open(str(orig))
pages = []
for i in range(doc.page_count):
    p = doc.load_page(i)
    blocks = p.get_text('dict')
    lines = []
    # iterate blocks -> lines -> spans
    for b in blocks.get('blocks', []):
        for l in b.get('lines', []):
            line_text = ''
            x0 = y0 = x1 = y1 = None
            for s in l.get('spans', []):
                txt = s.get('text', '')
                if txt.strip() == '':
                    continue
                if line_text:
                    line_text += ' ' + txt
                else:
                    line_text = txt
                bx0, by0, bx1, by1 = s.get('bbox', (0,0,0,0))
                if x0 is None:
                    x0, y0, x1, y1 = bx0, by0, bx1, by1
                else:
                    x0 = min(x0, bx0); y0 = min(y0, by0); x1 = max(x1, bx1); y1 = max(y1, by1)
            if line_text:
                lines.append({'text': line_text, 'bbox':[x0,y0,x1,y1]})
    pages.append({'page': i+1, 'lines': lines})

out.parent.mkdir(parents=True, exist_ok=True)
with out.open('w', encoding='utf-8') as f:
    json.dump({'pages': pages}, f, ensure_ascii=False, indent=2)

print('Wrote', out)
