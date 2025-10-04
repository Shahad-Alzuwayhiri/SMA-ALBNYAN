from pathlib import Path
from pypdf import PdfReader
import re

infile = Path('tmp_html_render.pdf')
outfile = Path('outputs/tmp_html_render_clean.txt')
reader = PdfReader(str(infile))
all_lines = []
for p in reader.pages:
    text = p.extract_text() or ''
    # normalize line endings
    text = text.replace('\r\n','\n').replace('\r','\n')
    lines = text.split('\n')
    # strip trailing/leading spaces, collapse multiple spaces
    for L in lines:
        s = re.sub(r'\s+', ' ', L).strip()
        if s:
            all_lines.append(s)
# remove consecutive duplicate lines
clean = []
prev = None
for l in all_lines:
    if l!=prev:
        clean.append(l)
    prev = l
# write
outfile.parent.mkdir(parents=True, exist_ok=True)
with outfile.open('w', encoding='utf-8') as f:
    for l in clean:
        f.write(l + '\n')
print('Wrote', outfile)
