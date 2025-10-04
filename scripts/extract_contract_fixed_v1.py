import PyPDF2
from pathlib import Path
p = Path('contract_fixed_v1.pdf')
if not p.exists():
    print('contract_fixed_v1.pdf missing')
    raise SystemExit(2)

r = PyPDF2.PdfReader(str(p))
alltext = []
for i, pg in enumerate(r.pages):
    try:
        t = pg.extract_text() or ''
    except Exception as e:
        t = f'<err:{e}>'
    print(f'--- PAGE {i+1} len {len(t)} ---')
    print(t[:2000])
    alltext.append(t)
    imgs = 0
    try:
        res = pg.get('/Resources') or {}
        if isinstance(res, dict):
            xobj = res.get('/XObject') or {}
            if isinstance(xobj, dict):
                for k in xobj:
                    try:
                        obj = xobj[k]
                        subtype = obj.get('/Subtype') if isinstance(obj, dict) else None
                        if subtype == '/Image':
                            imgs += 1
                    except Exception:
                        pass
    except Exception:
        pass
    print('images on page', i+1, '=', imgs)
print('TOTAL extracted len=', sum(len(t) for t in alltext))
