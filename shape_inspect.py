from models import get_session, Contract
from pdf_utils import _shape_ar

s = get_session()
try:
    c = s.get(Contract, 1)
    content = (c.content or '') if c else ''
    lines = content.splitlines()
    for i in range(38, 47):
        if i-1 < len(lines):
            ln = lines[i-1]
            shaped = _shape_ar(ln)
            print(f'{i:03d}: RAW: {ln}')
            print(f'     SHAPED: {shaped}')
            print('     RAW cps:', ' '.join(hex(ord(ch)) for ch in ln))
            print('     SHP cps:', ' '.join(hex(ord(ch)) for ch in shaped))
finally:
    s.close()
