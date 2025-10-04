from models import get_session, Contract
from pdf_utils import generate_contract_pdf
import os

CID = 1
s = get_session()
try:
    c = s.get(Contract, CID)
    if not c:
        print('contract not found')
        content = ''
    else:
        content = c.content or ''
        print('contract id=', CID)
        print('title=', repr(c.title))
        print('client_contract_no=', c.client_contract_no)
        print('created_at=', c.created_at)
        print('content length=', len(content))
        print('content preview:\n', content[:800])

    pdf = generate_contract_pdf(
        title=(c.title if c else 'test'), content=content, serial=(c.client_contract_no if c else 'T-1'),
        created_at=(c.created_at.isoformat() if c and c.created_at else '2025-09-10T12:00:00'),
        brand={'primary':'#123456','accent':'#AA3344'}, logo_path=None, font_path='static/fonts/DejaVuSans.ttf'
    )
    print('generated pdf bytes:', len(pdf))
    with open('debug_contract.pdf','wb') as f:
        f.write(pdf)
    print('wrote debug_contract.pdf')
finally:
    s.close()
