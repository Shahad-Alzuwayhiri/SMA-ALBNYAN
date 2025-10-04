import os
from pdf_utils import generate_from_content_file
from pypdf import PdfReader


def extract_text_from_pdf(path):
    r = PdfReader(path)
    out = []
    for p in r.pages:
        out.append(p.extract_text() or '')
    return '\n'.join(out)


def test_preserve_layout_and_partner_fields(tmp_path):
    out = tmp_path / 'smoke_preserve.pdf'
    data = {
        'contract_number': 'B123-1447',
        'contract_date': '12-02-1447',
        'partner_name': 'سهام بنت سِرحان بن هليل المطرفي',
        'partner_id': '2193599665',
        'partner_phone': '1018906188',
        'investment_amount': '100000',
        'profit': '30',
        'duration': '6 أشهر',
        'manager_signature': 'شركة سما البنيان التجارية'
    }

    # generate - our helper writes to the out path
    generate_from_content_file('templates/contract_fixed_v1.html', out=str(out), data=data, preserve_layout=True)

    assert out.exists(), 'PDF output was not written'
    txt = extract_text_from_pdf(str(out))

    # ensure partner fields appear verbatim in the extracted text
    assert data['partner_name'] in txt
    assert data['partner_id'] in txt
    assert data['partner_phone'] in txt

    # ensure key headings/order are present
    assert 'تمهيد' in txt
    assert 'البند الأول' in txt or 'البند 1' in txt
