import os
from pdf_utils import generate_contract_pdf, HEADING_RX
from pypdf import PdfReader


def extract_text_from_pdf(path):
    r = PdfReader(path)
    out = []
    for p in r.pages:
        out.append(p.extract_text() or '')
    return '\n'.join(out)


def test_heading_variants(tmp_path):
    variants = [
        "تمهيد",
        "البند الأول",
        "بند 2:",
        "البند-الثالث",
        "البند (4)",
        "البند الخامس",
        "البند الثامن",
    ]
    content = "\n\n".join(variants + ["نص تجريبي تحت كل بند."])

    # Validate the heading regex matches each plain variant string
    for v in variants:
        assert HEADING_RX.match(v), f'HEADING_RX did not match variant: {v!r}'
