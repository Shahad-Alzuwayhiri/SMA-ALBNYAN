# pdf_utils.py — ContractSama
# © 2025 ContractSama. All rights reserved.
# pyright: reportMissingImports=false
import re, os
from io import BytesIO
from datetime import datetime
from typing import Optional
from html import escape as _html_escape

from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.enums import TA_RIGHT, TA_CENTER, TA_LEFT
from reportlab.lib.units import mm
from reportlab.lib import colors
from reportlab.pdfgen import canvas
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, KeepTogether
)
from reportlab.pdfbase.pdfmetrics import registerFontFamily

# ===== Arabic shaping / bidi =====
try:
    import arabic_reshaper
except Exception:
    arabic_reshaper = None

try:
    from bidi.algorithm import get_display
except Exception:
    get_display = None

# Build a reshaper if possible (handles ligatures like "الله")
_reshaper = None
if arabic_reshaper:
    try:
        cfg = arabic_reshaper.config_for_true_type_font(
            "static/fonts/DejaVuSans.ttf",
            arabic_reshaper.ENABLE_ALL_LIGATURES
        )
        _reshaper = arabic_reshaper.ArabicReshaper(cfg)
    except Exception:
        # fallback to default
        try:
            _reshaper = arabic_reshaper.ArabicReshaper(arabic_reshaper.config_for_language('Arabic'))
        except Exception:
            _reshaper = None

def _shape_ar(s: str) -> str:
    if not s:
        return ""
    try:
        text = _reshaper.reshape(s) if _reshaper else (arabic_reshaper.reshape(s) if arabic_reshaper else s)
        return get_display(text) if get_display else text
    except Exception:
        return s

RLE = "\u202B"   # Right-to-Left Embedding (actual char)
PDF = "\u202C"   # Pop Directional Formatting (actual char)
RLM = "\u200F"   # Right-to-Left Mark (actual char)

def _prepare_rtl_html(raw: str) -> str:
    """Preserve <br/> and wrap each visual line with RTL embedding; then shape Arabic."""
    if not raw:
        return ""
    t = (raw.replace("</br>", "<br/>")
             .replace("<br>", "<br/>")
             .replace("<BR>", "<br/>")
             .replace("<BR/>", "<br/>"))
    parts = t.split("<br/>")
    out = []
    for p in parts:
        shaped = _shape_ar(p)
        out.append(f"{RLE}{shaped}{PDF}{RLM}")
    return "<br/>".join(out)

# ===== Fonts =====
def _register_font(font_path: Optional[str]) -> str:
    """Register DejaVuSans (and family if available). Fallback to Helvetica."""
    try:
        if font_path and os.path.isfile(font_path):
            base = "DejaVuSans"
            pdfmetrics.registerFont(TTFont(base, font_path))

            folder = os.path.dirname(font_path)
            bold = os.path.join(folder, "DejaVuSans-Bold.ttf")
            oblique = os.path.join(folder, "DejaVuSans-Oblique.ttf")
            boldob = os.path.join(folder, "DejaVuSans-BoldOblique.ttf")

            have_b, have_i, have_bi = False, False, False
            if os.path.isfile(bold):
                pdfmetrics.registerFont(TTFont("DejaVuSans-Bold", bold)); have_b=True
            if os.path.isfile(oblique):
                pdfmetrics.registerFont(TTFont("DejaVuSans-Oblique", oblique)); have_i=True
            if os.path.isfile(boldob):
                pdfmetrics.registerFont(TTFont("DejaVuSans-BoldOblique", boldob)); have_bi=True

            registerFontFamily(
                "DejaVuSans",
                normal="DejaVuSans",
                bold="DejaVuSans-Bold" if have_b else "DejaVuSans",
                italic="DejaVuSans-Oblique" if have_i else "DejaVuSans",
                boldItalic=("DejaVuSans-BoldOblique" if have_bi else ("DejaVuSans-Bold" if have_b else "DejaVuSans"))
            )
            return "DejaVuSans"
    except Exception:
        pass

    registerFontFamily("Helvetica", normal="Helvetica", bold="Helvetica-Bold",
                       italic="Helvetica-Oblique", boldItalic="Helvetica-BoldOblique")
    return "Helvetica"

# ===== Header / footer =====
def _draw_header_footer(canv: canvas.Canvas, doc, brand: dict, logo_path: Optional[str], header_font: str):
    canv.saveState()

    prim = colors.HexColor((brand or {}).get("primary", "#0F2A5A"))
    acc  = colors.HexColor((brand or {}).get("accent",  "#22B8CF"))
    brand_name = (brand or {}).get("name", "ContractSama")

    w, h = A4
    bar_h = 28

    canv.setFillColor(prim)
    canv.rect(0, h - bar_h, w, bar_h, fill=1, stroke=0)

    x_margin = 15 * mm

    if logo_path and os.path.isfile(logo_path):
        try:
            canv.drawImage(logo_path, x_margin, h - 16*mm, width=22*mm, height=12*mm,
                           preserveAspectRatio=True, mask="auto")
        except Exception:
            pass

    hdr_style = ParagraphStyle(
        name="Hdr",
        fontName=header_font,
        fontSize=12,
        leading=14,
        alignment=TA_RIGHT,
        textColor=colors.white,
        spaceBefore=0, spaceAfter=0,
    )
    p = Paragraph(_shape_ar(brand_name), hdr_style)
    usable_w = w - (x_margin + 25*mm) - x_margin
    _, ah = p.wrap(usable_w, bar_h)
    p.drawOn(canv, x_margin + 25*mm, h - ah - 8)

    canv.setFillColor(acc)
    canv.rect(0, 0, w, 10, fill=1, stroke=0)

    ftr_style = ParagraphStyle(
        name="Ftr",
        fontName=header_font,
        fontSize=9,
        leading=11,
        alignment=TA_LEFT,
        textColor=colors.white,
    )
    fp = Paragraph(_shape_ar(f"صفحة {doc.page}"), ftr_style)
    _, _ = fp.wrap(w, 10)
    fp.drawOn(canv, x_margin, 2)

    canv.restoreState()

# ...existing imports and code...

def generate_contract_pdf(
    *,
    title: str,
    content: str,
    serial: str,
    created_at: str,
    brand: dict,
    logo_path: Optional[str],
    font_path: Optional[str],
    client_name: Optional[str] = None,
    client_id_number: Optional[str] = None,
    client_phone: Optional[str] = None,
    client_address: Optional[str] = None,
    investment_amount: Optional[float] = None,
    signature_path: Optional[str] = None,  # currently unused
    prepared_by: Optional[str] = None,
) -> bytes:

    font_name = _register_font(font_path)
    styles = getSampleStyleSheet()

    style_title = ParagraphStyle(
        name="TitleAR", parent=styles["Normal"],
        fontName=font_name, fontSize=16, leading=22, alignment=TA_RIGHT,
        spaceAfter=8, textColor=colors.HexColor((brand or {}).get("primary", "#1F3C88"))
    )
    style_section = ParagraphStyle(
        name="SectionAR", parent=styles["Normal"],
        fontName=font_name, fontSize=13, leading=20, alignment=TA_RIGHT,
        spaceBefore=10, spaceAfter=4,
        textColor=colors.HexColor((brand or {}).get("primary", "#1F3C88"))
    )
    style_label = ParagraphStyle(
        name="LabelAR", parent=styles["Normal"],
        fontName=font_name, fontSize=11, leading=16, alignment=TA_RIGHT
    )
    style_value = ParagraphStyle(
        name="ValueAR", parent=styles["Normal"],
        fontName=font_name, fontSize=11, leading=16, alignment=TA_RIGHT
    )
    style_body = ParagraphStyle(
        name="BodyAR", parent=styles["Normal"],
        fontName=font_name, fontSize=12, leading=18, alignment=TA_RIGHT
    )
    style_small = ParagraphStyle(
        name="SmallAR", parent=styles["Normal"],
        fontName=font_name, fontSize=9, leading=13, alignment=TA_RIGHT
    )
    style_basmala = ParagraphStyle(
        name="Basmala", parent=styles["Normal"],
        fontName=font_name, fontSize=12, leading=18, alignment=TA_CENTER,
        textColor=colors.HexColor((brand or {}).get("primary", "#1F3C88")), spaceAfter=6
    )

    right_margin = 18 * mm; left_margin = 18 * mm
    top_margin = 22 * mm; bottom_margin = 15 * mm

    buff = BytesIO()
    doc = SimpleDocTemplate(
        buff, pagesize=A4,
        rightMargin=right_margin, leftMargin=left_margin,
        topMargin=top_margin, bottomMargin=bottom_margin,
        title=_shape_ar(title or "عقد"),
    )

    # Helpers that close over styles
    def _para_from_raw(raw: str) -> Paragraph:
        try:
            safe = _html_escape(raw or "")
            safe = safe.replace("\r\n","\n").replace("\r","\n").replace("\t","    ")
            safe = safe.replace("\n","<br/>")
            shaped_html = _prepare_rtl_html(safe)
            return Paragraph(shaped_html, style_body)
        except Exception:
            fallback = _shape_ar((raw or "").replace("\r","").replace("\t","    "))
            return Paragraph(_html_escape(fallback).replace("\n","<br/>"), style_body)

    story = []
    story.append(Paragraph(_shape_ar("بِسْمِ اللهِ الرَّحْمٰنِ الرَّحِيمِ"), style_basmala))
    story.append(Spacer(1, 4))
    story.append(Paragraph(_shape_ar(title or "عقد"), style_title))
    story.append(Spacer(1, 6))

    date_display = created_at
    try:
        date_display = datetime.fromisoformat(created_at.replace("Z", "")).strftime("%Y-%m-%d %H:%M")
    except Exception:
        pass

    def _cell_label(txt):  return Paragraph(_shape_ar(txt), style_label)
    def _cell_value(txt):  return Paragraph(_shape_ar(txt), style_value)

    inv_text = "-"
    if isinstance(investment_amount, (int, float)):
        inv_text = f"{investment_amount:,.2f} ريال"

    meta_data = [
        [_cell_value(serial or "-"), _cell_label("رقم العقد:"),
         _cell_value(date_display or "-"), _cell_label("تاريخ الإنشاء:")],
        [_cell_value(client_name or "-"), _cell_label("اسم العميل:"),
         _cell_value(client_id_number or "-"), _cell_label("رقم الهوية/السجل:")],
        [_cell_value(client_phone or "-"), _cell_label("رقم الجوال:"),
         _cell_value(client_address or "-"), _cell_label("العنوان:")],
        [_cell_value(inv_text), _cell_label("مبلغ المشاركة:"),
         _cell_value(prepared_by or "-"), _cell_label("أُعدّ بواسطة:")],
    ]
    meta_tbl = Table(meta_data, colWidths=[50*mm, 33*mm, 50*mm, 33*mm], hAlign="RIGHT")
    meta_tbl.setStyle(TableStyle([
        ("ALIGN",(0,0),(-1,-1),"RIGHT"), ("VALIGN",(0,0),(-1,-1),"MIDDLE"),
        ("INNERGRID",(0,0),(-1,-1),0.25,colors.HexColor("#e5e7eb")),
        ("BOX",(0,0),(-1,-1),0.5,colors.HexColor("#cbd5e1")),
        ("RIGHTPADDING",(0,0),(-1,-1),6), ("LEFTPADDING",(0,0),(-1,-1),6),
        ("TOPPADDING",(0,0),(-1,-1),4), ("BOTTOMPADDING",(0,0),(-1,-1),4),
        ("BACKGROUND",(0,0),(-1,0),colors.HexColor("#f8fafc")),
    ]))
    story.append(meta_tbl)
    story.append(Spacer(1, 10))

    story.append(Paragraph(_shape_ar("نص العقد:"), style_section))
    story.append(Spacer(1, 6))

    lines = (content or "").split("\n")
    buf = []
    def _flush_buf():
        if buf:
            story.append(_para_from_raw("\n".join(buf)))
            story.append(Spacer(1, 6))
            buf.clear()

    _rx_heading = re.compile(r"^\s*(تمهيد|البند\s+.+?)\s*[:：]?\s*$")

    i = 0
    while i < len(lines):
        ln = lines[i]
        stripped = ln.strip()

        if _rx_heading.match(stripped):
            _flush_buf()
            story.append(Paragraph(_shape_ar(stripped), style_section))
            if stripped == "تمهيد":
                story.append(Table([[""]], colWidths=[170*mm], style=TableStyle([
                    ("LINEBELOW",(0,0),(-1,-1),0.6,colors.HexColor("#cbd5e1")),
                    ("TOPPADDING",(0,0),(-1,-1),2),
                    ("BOTTOMPADDING",(0,0),(-1,-1),6),
                ])))
            i += 1
            para_lines = []
            while i < len(lines):
                nxt = lines[i]
                nxts = nxt.strip()
                if not nxts:
                    break
                if _rx_heading.match(nxts):
                    break
                para_lines.append(nxt)
                i += 1
            if para_lines:
                story.append(KeepTogether([_para_from_raw("\n".join(para_lines)), Spacer(1,6)]))
            else:
                story.append(Spacer(1,6))
            continue

        if not stripped:
            _flush_buf()
            i += 1
            continue

        buf.append(ln)
        i += 1

    _flush_buf()

    sign_tbl = Table([
        [Paragraph(_shape_ar("توقيع الطرف الأول (شركة سما البنيان التجارية)"), style_small),
         Paragraph(_shape_ar("توقيع الطرف الثاني"), style_small)],
        [Paragraph(_shape_ar("_________________________"), style_small),
         Paragraph(_shape_ar("_________________________"), style_small)],
    ], colWidths=[90*mm, 90*mm], hAlign="RIGHT")
    sign_tbl.setStyle(TableStyle([
        ("ALIGN",(0,0),(-1,-1),"RIGHT"),
        ("VALIGN",(0,0),(-1,-1),"TOP"),
        ("TOPPADDING",(0,0),(-1,-1),6),
        ("BOTTOMPADDING",(0,0),(-1,-1),6),
    ]))
    story.append(sign_tbl)

    if prepared_by:
        story.append(Spacer(1, 8))
        story.append(Paragraph(_shape_ar(f"أُعدّ بواسطة: {prepared_by}"), style_small))

    def _on_page(canv, _doc):
        _draw_header_footer(canv, _doc, brand=brand or {}, logo_path=logo_path, header_font=font_name)

    doc.build(story, onFirstPage=_on_page, onLaterPages=_on_page)
    return buff.getvalue()
