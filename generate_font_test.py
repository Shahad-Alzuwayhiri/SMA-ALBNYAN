from models import get_session, Contract
from pdf_utils import generate_contract_pdf, _find_preferred_font
import sys
import os


def main():
    s = get_session()
    try:
        c = s.get(Contract, 1)
        if not c:
            print('contract not found')
            raise SystemExit(1)
        content = c.content or ''
        title = c.title or 'عقد'
        serial = c.client_contract_no or str(c.internal_serial or c.id)
        created_at = c.created_at.isoformat() if c.created_at else ''
        client_name = c.client_name
        client_id_number = c.client_id_number
        client_phone = c.client_phone
        client_address = c.client_address
        investment_amount = c.investment_amount
        signature_path = c.signature_path if c.signature_path and os.path.isfile(c.signature_path) else None

        preferred = _find_preferred_font(prefer=['DejaVu', 'Cairo', 'Amiri'])
        print('using font path:', preferred)
        out = 'contract-dejavu.pdf'
        pdf = generate_contract_pdf(
            title=title, content=content, serial=serial, created_at=created_at,
            brand={'primary':'#123456','accent':'#AA3344'}, logo_path=None, font_path=preferred,
            client_name=client_name, client_id_number=client_id_number,
            client_phone=client_phone, client_address=client_address,
            investment_amount=investment_amount, signature_path=signature_path,
            prepared_by=None
        )
        with open(out, 'wb') as f:
            f.write(pdf)
        print('Wrote', out, 'size=', len(pdf))
        print('This script has been archived under scripts/legacy_pdf/generate_font_test.py')
        print('Use scripts/pdf_tools.py font-test or the new CLI for generation')
    finally:
        s.close()


if __name__ == '__main__':
    sys.exit(main())
