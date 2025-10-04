from pathlib import Path
import PyPDF2
p=Path('tmp_html_render.pdf')
if not p.exists():
    print('missing', p)
    raise SystemExit(1)
reader=PyPDF2.PdfReader(str(p))
for i,page in enumerate(reader.pages):
    t=page.extract_text() or ''
    print('--- PAGE %d ---\n' % (i+1))
    print(t[:1200])
    print('\n')
