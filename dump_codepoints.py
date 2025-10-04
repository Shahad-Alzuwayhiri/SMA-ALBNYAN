from models import get_session, Contract
s = get_session()
try:
    c = s.get(Contract, 1)
    content = (c.content or '') if c else ''
    lines = content.splitlines()
    for i in range(38, 48):
        if i-1 < len(lines):
            ln = lines[i-1]
            cps = [hex(ord(ch)) for ch in ln]
            print(f'{i:03d}:', ln)
            print('     codepoints:', ' '.join(cps[:120]))
finally:
    s.close()
