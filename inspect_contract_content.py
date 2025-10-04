from models import get_session, Contract

s = get_session()
try:
    c = s.get(Contract, 1)
    if not c:
        print('contract not found')
    else:
        content = c.content or ''
        print('\n--- RAW PREVIEW (800 chars) ---')
        print(content[:800])

        raw = content.replace('\r\n', '\n').replace('\r', '\n')
        paras = [p.strip() for p in raw.split('\n\n') if p.strip()]
        print('\n--- PARAGRAPHS COUNT:', len(paras), '---')
        for i, p in enumerate(paras[:40], start=1):
            print(f'[{i}]', p[:300].replace('\n','\\n'))
finally:
    s.close()
