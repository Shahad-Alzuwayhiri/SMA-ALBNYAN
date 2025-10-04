from models import get_session, Contract
import unicodedata, re

def sanitize_text(s: str) -> str:
    if not s:
        return s
    # normalize
    s = unicodedata.normalize('NFKC', s)
    # replace en/em dashes with hyphen
    s = s.replace('\u2013', '-').replace('\u2014', '-')
    # remove black square and replacement char
    s = s.replace('\u25A0', ' ').replace('\ufffd', ' ')
    s = s.replace('■', ' ')
    # remove all Unicode control/format/private-use chars
    s = ''.join(ch for ch in s if unicodedata.category(ch)[0] != 'C')
    # collapse multiple spaces
    s = re.sub(r'[ \t]{2,}', ' ', s)
    # normalize line endings
    s = s.replace('\r\n', '\n').replace('\r', '\n')
    return s

s = get_session()
try:
    c = s.get(Contract, 1)
    if not c:
        print('contract id=1 not found')
    else:
        orig = c.content or ''
        print('orig len', len(orig))
        # show lines around البند الثامن
        lines = orig.splitlines()
        for i in range(38, 47):
            if i-1 < len(lines):
                print(f'Bef {i:03d}:', lines[i-1])
        new = sanitize_text(orig)
        print('new len', len(new))
        lines2 = new.splitlines()
        for i in range(38, 47):
            if i-1 < len(lines2):
                print(f'Aft {i:03d}:', lines2[i-1])
        if new != orig:
            c.content = new
            s.add(c)
            s.commit()
            print('DB updated')
        else:
            print('No changes needed')
finally:
    s.close()
