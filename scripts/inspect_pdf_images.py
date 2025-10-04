from pypdf import PdfReader
import sys
from pathlib import Path
p = Path(sys.argv[1]) if len(sys.argv) > 1 else Path('contract_fixed_v1.pdf')
if not p.exists():
    print('missing', p)
    raise SystemExit(2)
reader = PdfReader(str(p))
for i, pg in enumerate(reader.pages):
    imgs = 0
    try:
        resources = pg.get('/Resources')
        if resources and '/XObject' in resources:
            xobj = resources['/XObject']
            for nm, obj in xobj.items():
                try:
                    subtype = obj.get('/Subtype')
                except Exception:
                    subtype = None
                if subtype == '/Image':
                    imgs += 1
    except Exception:
        pass
    print(f'PAGE {i+1}: images={imgs}')
print('Total pages:', len(reader.pages))
