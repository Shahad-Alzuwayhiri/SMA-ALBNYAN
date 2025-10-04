import os
from pathlib import Path

files = ["debug_contract.pdf", "contract-1.pdf"]
for fn in files:
    p = Path(fn)
    print('---', fn, '---')
    if not p.exists():
        print('MISSING')
        continue
    data = p.read_bytes()
    print('size=', len(data))
    print('head bytes:', data[:256].decode('latin-1', errors='replace'))
    print('contains /Font? ', b'/Font' in data)
    # try simple text extraction with pypdf if available
    try:
        from pypdf import PdfReader
        reader = PdfReader(fn)
        txt = []
        for pg in reader.pages:
            try:
                t = pg.extract_text() or ''
            except Exception as e:
                t = f'<err {e}>'
            txt.append(t)
        alltxt = '\n'.join(txt)
        print('extracted text length=', len(alltxt))
        print('preview:\n', alltxt[:800])
    except Exception as e:
        print('pypdf not available or failed:', e)
    # list font names occurrences
    try:
        import re
        fonts = set(re.findall(rb'/Font\s*/([^\s/<>\[\]()]+)', data))
        print('fonts found (raw tokens):', [f.decode('latin-1') for f in fonts])
    except Exception as e:
        print('font scan failed', e)
    print()
