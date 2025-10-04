# Export a contract by id to PDF using generate_contract_pdf
from models import get_session, Contract
from pdf_utils import generate_contract_pdf, _find_preferred_font
import os
import sys

CID = 1
OUT = f"contract-{CID}.pdf"

s = get_session()
try:
    c = s.get(Contract, CID)
    if not c:
        print(f"contract id={CID} not found, using fallback sample")
        content = "تمهيد\nالمقدمة أعلاه جزء لا يتجزأ من هذا العقد.\n\nالبند 1\nالنص التجريبي للبند الأول.\n\nالبند 2\nالنص التجريبي للبند الثاني.\n\nالختام"
        title = "عقد تجريبي"
        serial = "T-1"
        created_at = "2025-09-10T12:00:00"
        client_name = "عميل تجريبي"
        client_id_number = "1234567890"
        client_phone = "0500000000"
        client_address = "الرياض"
        investment_amount = 10000
        signature_path = None
    else:
        content = c.content or ""
        title = c.title or "عقد"
        serial = c.client_contract_no or str(c.internal_serial or c.id)
        created_at = c.created_at.isoformat() if c.created_at else ""
        client_name = c.client_name
        client_id_number = c.client_id_number
        client_phone = c.client_phone
        client_address = c.client_address
        investment_amount = c.investment_amount
        signature_path = c.signature_path if c.signature_path and os.path.isfile(c.signature_path) else None

        pdf_path = 'contract-1.pdf'
        # prefer Amiri font if available to ensure Arabic ligatures render correctly
        preferred = _find_preferred_font(prefer=['Amiri', 'Amiri-Regular', 'Cairo', 'DejaVuSans'])
        try:
            from pdf_renderer_playwright import generate_contract_pdf_html
        except Exception:
            generate_contract_pdf_html = None
        
        if generate_contract_pdf_html:
            pdf = generate_contract_pdf_html(
                title=title,
                content=content,
                serial=serial,
                created_at=created_at,
                brand={'primary':'#123456','accent':'#AA3344'},
                logo_path=None,
                font_path=preferred,
                prepared_by=None
            )
        else:
            pdf = generate_contract_pdf(
                title=title,
                content=content,
                serial=serial,
                created_at=created_at,
                brand={'primary':'#123456','accent':'#AA3344'},
                logo_path=None,
                font_path=preferred,
                client_name=client_name,
                client_id_number=client_id_number,
                client_phone=client_phone,
                client_address=client_address,
                investment_amount=investment_amount,
                signature_path=signature_path,
                prepared_by=None
            )
    print('This script has been consolidated into scripts/pdf_tools.py')
    print('Run: python scripts/pdf_tools.py export --cid 1 --out contract-1.pdf')
    sys.exit(0)
finally:
    s.close()
