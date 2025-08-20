# © 2025 ContractSama. All rights reserved.

import os
from io import BytesIO
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from reportlab.lib.colors import HexColor
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
import arabic_reshaper
from bidi.algorithm import get_display

PAGE_W, PAGE_H = A4
MARGIN = 50
FONT_NAME = "DejaVuSans"

def _ar(text: str) -> str:
    if text is None:
        return ""
    reshaped = arabic_reshaper.reshape(str(text))
    return get_display(reshaped)

def _register_font_or_fail(font_path: str):
    from reportlab.pdfbase.pdfmetrics import getFont
    try:
        getFont(FONT_NAME); return
    except KeyError:
        pass
    if not os.path.isfile(font_path):
        raise FileNotFoundError(f"لم يتم العثور على الخط: {font_path} — ضعي DejaVuSans.ttf في static/fonts/")
    pdfmetrics.registerFont(TTFont(FONT_NAME, font_path))

def _wrap_rtl(text: str, font_name: str, font_size: int, max_width: float):
    words = str(text).split()
    lines, line = [], []
    from reportlab.pdfbase.pdfmetrics import stringWidth
    for w in words:
        test = " ".join(line + [w])
        width = stringWidth(_ar(test), font_name, font_size)
        if width <= max_width:
            line.append(w)
        else:
            if line: lines.append(" ".join(line))
            line = [w]
    if line: lines.append(" ".join(line))
    return lines

def generate_contract_pdf(*, title: str, content: str, serial: str, created_at: str,
                          brand: dict, logo_path: str, font_path: str) -> bytes:
    _register_font_or_fail(font_path)

    buf = BytesIO()
    c = canvas.Canvas(buf, pagesize=A4)

    primary = HexColor(brand.get("primary", "#0E2A3B"))
    accent  = HexColor(brand.get("accent",  "#78C7C7"))
    brand_name = brand.get("name", "ContractSama")

    def header_footer(page_num: int):
        if os.path.exists(logo_path):
            c.drawImage(logo_path, PAGE_W - MARGIN - 48, PAGE_H - MARGIN - 48, width=48, height=48, mask='auto')
        c.setFillColor(primary); c.setFont(FONT_NAME, 14)
        c.drawRightString(PAGE_W - MARGIN - 58, PAGE_H - MARGIN - 18, _ar(brand_name))
        c.setStrokeColor(accent); c.setLineWidth(3)
        c.line(MARGIN, PAGE_H - MARGIN - 54, PAGE_W - MARGIN, PAGE_H - MARGIN - 54)
        c.setFillColor(HexColor("#666")); c.setFont(FONT_NAME, 9)
        c.drawCentredString(PAGE_W/2, MARGIN/2, _ar(f"صفحة {page_num}"))

    page = 1
    header_footer(page)

    y = PAGE_H - MARGIN - 80
    c.setFillColor(HexColor("#555")); c.setFont(FONT_NAME, 10)
    c.drawRightString(PAGE_W - MARGIN, y, _ar(f"الرقم: {serial}   |   التاريخ: {created_at}"))

    y -= 22
    c.setFillColor(primary); c.setFont(FONT_NAME, 16)
    c.drawRightString(PAGE_W - MARGIN, y, _ar(f"العنوان: {title}"))

    y -= 28
    c.setFillColor(HexColor("#000")); c.setFont(FONT_NAME, 12)
    usable_width = PAGE_W - 2*MARGIN; line_h = 18

    for para in str(content).split("\n"):
        lines = _wrap_rtl(para, FONT_NAME, 12, usable_width)
        for ln in lines:
            if y < MARGIN + 40:
                c.showPage(); page += 1; header_footer(page)
                c.setFillColor(HexColor("#000")); c.setFont(FONT_NAME, 12)
                y = PAGE_H - MARGIN - 30
            c.drawRightString(PAGE_W - MARGIN, y, _ar(ln)); y -= line_h
        y -= 6

    c.showPage(); c.save()
    return buf.getvalue()
