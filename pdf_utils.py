# pdf_utils.py — ContractSama
# © 2025 ContractSama. All rights reserved.
# pyright: reportMissingImports=false

"""pdf utilities and generator helpers.

This module is the canonical place for PDF generation in this project.

It exposes the main function `generate_contract_pdf(...)` used by the Flask app,
plus small convenience wrappers and a minimal CLI:

- generate_from_content_file(infile, out='out.pdf')
- export_contract_by_id(contract_id, out=None)
- inspect_pdf_file(path)

See PDF_UTILS.md for quick examples on using the functions and the CLI.
"""

import re, os, sys
import json
import unicodedata
from io import BytesIO
from pathlib import Path
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
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, KeepTogether, Image, PageBreak, Flowable
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

_reshaper = None
if arabic_reshaper:
    try:
        cfg = arabic_reshaper.config_for_true_type_font(
            "static/fonts/DejaVuSans.ttf",
            arabic_reshaper.ENABLE_ALL_LIGATURES
        )
        _reshaper = arabic_reshaper.ArabicReshaper(cfg)
    except Exception:
        try:
            _reshaper = arabic_reshaper.ArabicReshaper(arabic_reshaper.config_for_language('Arabic'))
        except Exception:
            _reshaper = None

def _shape_ar(s: str) -> str:
    if not s:
        return ""
    # remove invisible/format chars that sometimes render as small boxes in PDF viewers
    try:
        s = re.sub(r"[\u061C\u200B\u200C\u200D\u200E\u200F\u202A-\u202E\u2066-\u2069\uFEFF]", "", s)
    except Exception:
        pass
    # replace some common punctuation that can render poorly in some fonts
    try:
        # en-dash / em-dash -> simple hyphen
        s = s.replace('\u2013', '-').replace('\u2014', '-')
        # black square or replacement glyph -> space; also remove literal black-square and replacement char
        s = s.replace('\u25A0', ' ').replace('■', ' ').replace('\ufffd', ' ')
        # strip ASCII control chars except newline (0x0A) and carriage return (0x0D)
        # keep tabs and newlines so paragraph structure is preserved
        s = s.replace('\t', ' ')
        s = re.sub(r'[\x00-\x09\x0B-\x0C\x0E-\x1F\x7F]', '', s)
    except Exception:
        pass
    # normalize compatibility/presentation characters (map presentation forms to base letters)
    try:
        s = unicodedata.normalize('NFKC', s)
    except Exception:
        pass
    # aggressively drop control/format/non-printable characters that may
    # survive normalization and appear as black boxes in some PDF viewers.
    try:
        # remove all Unicode categories that start with 'C' (control/format/private-use)
        s = ''.join(ch for ch in s if unicodedata.category(ch)[0] != 'C')
        # also remove common symbol placeholders like black square
        s = s.replace('\u25A0', ' ').replace('\ufffd', ' ')
    except Exception:
        pass
    # replace presentation-form ligature for Basmala (U+FDF2) with spelled text
    try:
        if '\ufdf2' in s:
            s = s.replace('\ufdf2', 'بسم الله الرحمن الرحيم')
    except Exception:
        pass
    # strip any remaining Arabic presentation form block characters that may not have font glyphs
    try:
        s = re.sub(r"[\uFB50-\uFDFF\uFE70-\uFEFF]", lambda m: unicodedata.normalize('NFKC', m.group(0)), s)
    except Exception:
        pass
    try:
        text = _reshaper.reshape(s) if _reshaper else (arabic_reshaper.reshape(s) if arabic_reshaper else s)
        # Ask python-bidi to use RTL base direction so numbers and punctuation
        # are ordered correctly in a predominantly-RTL paragraph.
        try:
            display_text = get_display(text, base_dir='R') if get_display else text
        except TypeError:
            # Older versions accept only one argument; fall back to default behavior
            display_text = get_display(text) if get_display else text
        # Do NOT insert explicit bidi control characters (RLM/LRM) here because
        # some PDF viewers render them as visible boxes when the font lacks glyphs.
        return display_text
    except Exception:
        return s

def _prepare_rtl_html(raw: str) -> str:
    if not raw:
        return ""
    # First sanitize allowed inline HTML (keep <br>, <b>, <i>, <strong>, <em>, <p>, <a>)
    allowed_tags = {"A", "B", "I", "BR", "P", "SPAN", "STRONG", "EM", "PRE"}
    allowed_attrs = {
        "href": lambda v: (isinstance(v, str) and (v.startswith("http://") or v.startswith("https://") or v == "#")),
        "target": lambda v: v == "_blank",
        "class": lambda v: True,
    }
    safe_raw = _sanitize_html(raw, allowed_tags=allowed_tags, allowed_attrs=allowed_attrs)
    t = (safe_raw.replace("</br>", "<br/>")
             .replace("<br>", "<br/>")
             .replace("<BR>", "<br/>")
             .replace("<BR/>", "<br/>"))
    # Map class-based spans to ReportLab-friendly inline tags, then
    # extract paragraph groups so inline tags remain balanced per part.
    try:
        # fill fields -> underline
        t = re.sub(r'<span[^>]*class=["\']fill["\'][^>]*>(.*?)</span>', r'<u>\1</u>', t, flags=re.IGNORECASE | re.DOTALL)
        # heading / section-title -> bold
        t = re.sub(r'<span[^>]*class=["\'](?:heading|section-title)["\'][^>]*>(.*?)</span>', r'<b>\1</b>', t, flags=re.IGNORECASE | re.DOTALL)
        # meta -> smaller font
        t = re.sub(r'<span[^>]*class=["\']meta["\'][^>]*>(.*?)</span>', r'<font size=12>\1</font>', t, flags=re.IGNORECASE | re.DOTALL)
        # signature -> underline (signature line)
        t = re.sub(r'<span[^>]*class=["\']signature["\'][^>]*>(.*?)</span>', r'<u>\1</u>', t, flags=re.IGNORECASE | re.DOTALL)
        # remove any remaining class attributes (ReportLab doesn't accept them)
        t = re.sub(r'\sclass=["\'][^"\']*["\']', '', t)
        # strip div wrappers
        t = re.sub(r'</?div[^>]*>', '', t)
        # extract <p>...</p> contents if present to preserve inline tag balance
        para_matches = re.findall(r'<p[^>]*>(.*?)</p>', t, flags=re.IGNORECASE | re.DOTALL)
        if para_matches:
            parts = [p.strip() for p in para_matches]
        else:
            parts = t.split('<br/>')
    except Exception:
        parts = t.split('<br/>')
    out = []
    for p in parts:
        # Avoid inserting control characters; rely on python-bidi get_display
        # and ReportLab paragraph alignment to render RTL correctly.
        shaped = _shape_ar(p)
        out.append(shaped)
    return "<br/>".join(out)


# ===== Simple HTML sanitizer (whitelist-based) =====
# Purpose: safely allow a small subset of inline tags in contract content
# Ported/trimmed conceptually from Chromium's parseHtmlSubset/sanitizeInnerHtml
from html.parser import HTMLParser


class _SafeHtmlParser(HTMLParser):
    def __init__(self, allowed_tags=None, allowed_attrs=None):
        super().__init__(convert_charrefs=False)
        self.allowed_tags = allowed_tags or {"A", "B", "I", "BR", "DIV", "P", "SPAN", "STRONG", "EM", "PRE"}
        # allowed_attrs maps attr name to predicate(value) -> bool
        self.allowed_attrs = allowed_attrs or {"href": lambda v: v.startswith("http://") or v.startswith("https://") or v == "#", "target": lambda v: v == "_blank"}
        self.out = []
        self.stack = []

    def handle_starttag(self, tag, attrs):
        tag_u = tag.upper()
        if tag_u not in self.allowed_tags:
            # skip entirely
            return
        safe_attrs = []
        for k, v in attrs:
            if k in self.allowed_attrs and self.allowed_attrs[k](v):
                safe_attrs.append(f'{k}="{_html_escape(v)}"')
        attr_text = (" " + " ".join(safe_attrs)) if safe_attrs else ""
        self.out.append(f"<{tag}{attr_text}>")
        self.stack.append(tag_u)

    def handle_endtag(self, tag):
        tag_u = tag.upper()
        if tag_u not in self.allowed_tags:
            return
        # close only if we opened it
        if self.stack and self.stack[-1] == tag_u:
            self.stack.pop()
            self.out.append(f"</{tag}>")

    def handle_data(self, data):
        # escape text nodes
        self.out.append(_html_escape(data))

    def handle_entityref(self, name):
        self.out.append(f"&{name};")

    def handle_charref(self, name):
        self.out.append(f"&#{name};")

    def get_html(self):
        # close any leftover tags conservatively
        while self.stack:
            t = self.stack.pop()
            self.out.append(f"</{t.lower()}>")
        return "".join(self.out)


def _sanitize_html(raw: str, allowed_tags=None, allowed_attrs=None) -> str:
    """Return a sanitized HTML string containing only allowed tags/attributes."""
    if not raw:
        return ""
    p = _SafeHtmlParser(allowed_tags=allowed_tags, allowed_attrs=allowed_attrs)
    try:
        p.feed(raw)
        p.close()
    except Exception:
        # fallback: return escaped text
        return _html_escape(raw)
    return p.get_html()


def _register_font(font_path: Optional[str]) -> str:
    try:
        candidate = None
        # If a font_path is provided, resolve relative paths against the package
        if font_path:
            if not os.path.isabs(font_path):
                candidate = os.path.join(os.path.dirname(__file__), font_path)
            else:
                candidate = font_path
            if not os.path.isfile(candidate):
                candidate = None

        # fallback: scan static/fonts for prioritized Arabic-capable ttf files
        if not candidate:
            fonts_dir = os.path.join(os.path.dirname(__file__), 'static', 'fonts')
            try:
                priority = ['Amiri-Regular.ttf', 'Cairo-Regular.ttf', 'DejaVuSans.ttf']
                found = None
                for pfn in priority:
                    fp = os.path.join(fonts_dir, pfn)
                    if os.path.isfile(fp):
                        found = pfn
                        candidate = fp
                        break
                # if none of the prioritized names exist, pick the first TTF available
                if not found:
                    for fname in os.listdir(fonts_dir):
                        if fname.lower().endswith('.ttf'):
                            candidate = os.path.join(fonts_dir, fname)
                            break
            except Exception:
                candidate = None

        if candidate and os.path.isfile(candidate):
            base = os.path.splitext(os.path.basename(candidate))[0]
            # make a short safe font name (no spaces)
            base = base.replace(' ', '_')
            try:
                pdfmetrics.registerFont(TTFont(base, candidate))
            except Exception:
                base = None
            folder = os.path.dirname(candidate)
            # attempt to register common style variants if they exist
            bold = os.path.join(folder, base + "-Bold.ttf") if base else os.path.join(folder, "DejaVuSans-Bold.ttf")
            oblique = os.path.join(folder, base + "-Oblique.ttf") if base else os.path.join(folder, "DejaVuSans-Oblique.ttf")
            boldob = os.path.join(folder, base + "-BoldOblique.ttf") if base else os.path.join(folder, "DejaVuSans-BoldOblique.ttf")
            have_b, have_i, have_bi = False, False, False
            if base and os.path.isfile(bold):
                try:
                    pdfmetrics.registerFont(TTFont(base + "-Bold", bold)); have_b=True
                except Exception:
                    have_b=False
            if base and os.path.isfile(oblique):
                try:
                    pdfmetrics.registerFont(TTFont(base + "-Oblique", oblique)); have_i=True
                except Exception:
                    have_i=False
            if base and os.path.isfile(boldob):
                try:
                    pdfmetrics.registerFont(TTFont(base + "-BoldOblique", boldob)); have_bi=True
                except Exception:
                    have_bi=False
            try:
                registerFontFamily(
                    base,
                    normal=base,
                    bold=(base + "-Bold") if have_b else base,
                    italic=(base + "-Oblique") if have_i else base,
                    boldItalic=(base + "-BoldOblique" if have_bi else ((base + "-Bold") if have_b else base))
                )
            except Exception:
                pass
            print(f"pdf_utils: registered TTF font from {candidate} as '{base}'")
            return base
            folder = os.path.dirname(candidate)
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
            print(f"pdf_utils: registered TTF font from {candidate}")
            return "DejaVuSans"
    except Exception:
        pass
    registerFontFamily("Helvetica", normal="Helvetica", bold="Helvetica-Bold",
                       italic="Helvetica-Oblique", boldItalic="Helvetica-BoldOblique")
    return "Helvetica"


def _make_watermark_pdf(image_path: str, pages: int = 1, alpha: float = 0.3) -> bytes:
    """Return PDF bytes containing the provided image centered on each page
    with the requested alpha transparency. Uses Pillow to bake alpha into a
    PNG if available, otherwise draws the image directly (alpha support may
    vary by backend).
    """
    from io import BytesIO
    from reportlab.pdfgen import canvas as _canvas
    from reportlab.lib.pagesizes import A4 as _A4
    from reportlab.lib.utils import ImageReader as _ImageReader

    buf = BytesIO()
    w, h = _A4
    c = _canvas.Canvas(buf, pagesize=_A4)
    try:
        from PIL import Image
        # Open and apply alpha
        im = Image.open(image_path).convert('RGBA')
        # apply overall alpha
        a = int(255 * float(alpha))
        r, g, b, old_a = im.split()
        new_a = old_a.point(lambda p: int(p * (a / 255.0)))
        im.putalpha(new_a)
        tmp = BytesIO()
        im.save(tmp, format='PNG')
        tmp.seek(0)
        img = _ImageReader(tmp)
    except Exception:
        # Pillow not available; use ImageReader directly
        img = _ImageReader(image_path)

    iw, ih = img.getSize()
    max_w = w * 0.6
    ratio = min(max_w / iw, (h * 0.6) / ih)
    draw_w = iw * ratio
    draw_h = ih * ratio
    x = (w - draw_w) / 2.0
    y = (h - draw_h) / 2.0

    for _ in range(max(1, int(pages or 1))):
        try:
            # some canvases support setFillAlpha; best-effort to lower opacity
            c.saveState()
            try:
                c.setFillAlpha(alpha)
            except Exception:
                pass
            c.drawImage(img, x, y, width=draw_w, height=draw_h, mask='auto')
            try:
                c.setFillAlpha(1.0)
            except Exception:
                pass
            c.restoreState()
        except Exception:
            try:
                c.drawImage(img, x, y, width=draw_w, height=draw_h)
            except Exception:
                # give up drawing this page
                pass
        c.showPage()
    c.save()
    buf.seek(0)
    return buf.read()


def _overlay_from_positions(positions_path: str, font_hint: Optional[str] = None) -> bytes:
    """Render a text-only PDF overlay from a PyMuPDF-extracted positions JSON.

    The positions JSON is expected to have the structure written by
    `scripts/extract_text_positions.py`:
      { "pages": [ {"page": 1, "lines": [ {"text":"...","bbox":[x0,y0,x1,y1]}, ... ]}, ... ] }

    Returns PDF bytes with the same page count (one page per JSON page).
    """
    from io import BytesIO
    try:
        with open(positions_path, 'r', encoding='utf-8') as f:
            pos = json.load(f)
    except Exception:
        raise

    buf = BytesIO()
    c = canvas.Canvas(buf, pagesize=A4)

    # Ensure a usable font is registered
    try:
        font_to_use = font_hint or font_name_global or _register_font(None)
    except Exception:
        font_to_use = font_name_global or _register_font(None)

    for p in pos.get('pages', []):
        lines = p.get('lines', []) or []
        for ln in lines:
            txt = ln.get('text', '') or ''
            bbox = ln.get('bbox', [0, 0, 0, 0])
            if not bbox or len(bbox) < 4:
                continue
            x0, y0, x1, y1 = bbox
            # derive an approximate font size from bbox height
            try:
                height = max(6.0, float(y1) - float(y0))
                fsize = max(6.0, height * 0.72)
            except Exception:
                fsize = 12.0
            try:
                c.setFont(font_to_use, fsize)
            except Exception:
                try:
                    c.setFont('Helvetica', fsize)
                except Exception:
                    pass
            # Draw right-aligned at the right bbox edge to preserve RTL placement
            try:
                c.drawRightString(x1, y0, txt)
            except Exception:
                try:
                    c.drawString(x0, y0, txt)
                except Exception:
                    pass
        c.showPage()

    c.save()
    buf.seek(0)
    return buf.read()


def _find_preferred_font(prefer=None) -> Optional[str]:
    """Return a font path from static/fonts that matches one of the preferred basenames.
    prefer: list of name fragments (e.g. ['Amiri','Cairo','DejaVu'])
    Returns absolute path or None.
    """
    if prefer is None:
        prefer = ['Amiri', 'Cairo', 'DejaVu']
    fonts_dir = os.path.join(os.path.dirname(__file__), 'static', 'fonts')
    try:
        # try exact preference order first
        for name in prefer:
            for fname in os.listdir(fonts_dir):
                if name.lower() in fname.lower() and fname.lower().endswith('.ttf'):
                    return os.path.join(fonts_dir, fname)
        # fallback to any ttf
        for fname in os.listdir(fonts_dir):
            if fname.lower().endswith('.ttf'):
                return os.path.join(fonts_dir, fname)
    except Exception:
        return None
    return None


# Module-level heading regex: match many variants of 'البند' or 'تمهيد'
HEADING_RX = re.compile(r"^\s*(تمهيد|(?:ال)?بند\b[\s\-：:\(\)\d\u0600-\u06FF\w\-]*)\s*$", flags=re.IGNORECASE)

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
    # Draw the company logo only on the first page to avoid repeating it on every page.
    # Prefer canvas page number when available (more reliable across ReportLab versions)
    try:
        page_num = canv.getPageNumber()
    except Exception:
        page_num = getattr(doc, 'page', getattr(doc, 'pageNumber', None))

    if page_num == 1:
        if logo_path and os.path.isfile(logo_path):
            try:
                # Target logo size (width x height) in mm
                logo_w = 50 * mm
                logo_h = 40 * mm
                # position logo centered horizontally, slightly below the top edge
                logo_x = (w - logo_w) / 2.0
                logo_y = h - logo_h - 6 * mm
                canv.drawImage(logo_path, logo_x, logo_y, width=logo_w, height=logo_h,
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

# ========== أنماط التصميم ==========
font_name_global = None
styles_global = getSampleStyleSheet()
style_title = None
style_section = None
style_section_number = None
style_label = None
style_value = None
style_body = None
style_small = None
style_basmala = None

def setup_styles(font_name, brand):
    global style_title, style_section, style_section_number, style_label, style_value, style_body, style_small, style_basmala
    style_title = ParagraphStyle(
        name="TitleAR", parent=styles_global["Normal"],
        fontName=font_name, fontSize=20, leading=28, alignment=TA_CENTER,
        spaceAfter=10, textColor=colors.HexColor((brand or {}).get("primary", "#1F3C88"))
    )
    style_section = ParagraphStyle(
        name="SectionAR",
        parent=styles_global["Normal"],
        fontName=font_name,
        fontSize=15,
        leading=30,
        alignment=TA_RIGHT,
        spaceBefore=26,
        spaceAfter=18,
        textColor=colors.HexColor((brand or {}).get("primary", "#1F3C88")),
    )
    # numbered section style (slightly larger, uses accent color for emphasis)
    # Only add the style once; getSampleStyleSheet raises if a style with same
    # name is added multiple times (happens when called repeatedly).
    if "SectionNumberAR" not in getattr(styles_global, 'byName', {}):
        styles_global.add(ParagraphStyle(
            name="SectionNumberAR",
            parent=styles_global["Normal"],
            fontName=font_name,
            fontSize=14,
            leading=22,
            alignment=TA_RIGHT,
            spaceBefore=12,
            spaceAfter=6,
            textColor=colors.HexColor((brand or {}).get("accent", "#22B8CF")),
            bulletFontName=font_name
        ))
    style_section_number = styles_global["SectionNumberAR"]
    style_label = ParagraphStyle(
        name="LabelAR",
        parent=styles_global["Normal"],
        fontName=font_name,
        fontSize=11,
        leading=16,
        alignment=TA_RIGHT,
    )
    style_value = ParagraphStyle(
        name="ValueAR",
        parent=styles_global["Normal"],
        fontName=font_name,
        fontSize=11,
        leading=16,
        alignment=TA_RIGHT,
    )
    # Set leading to a smaller, more typical value and reduce paragraph gaps
    style_body = ParagraphStyle(
        name="BodyAR",
        parent=styles_global["Normal"],
        # prefer Amiri for Arabic body text when available
        fontName=(font_name or 'Amiri') if font_name else 'Amiri',
        # slightly smaller & tighter leading to better match original PDF line breaks
    fontSize=13.7,
    leading=19.15,
        alignment=TA_RIGHT,
        # reduce paragraph gaps for preserve-layout fidelity
        spaceBefore=2,
        spaceAfter=2,
    )
    style_small = ParagraphStyle(
        name="SmallAR", parent=styles_global["Normal"],
        fontName=font_name, fontSize=9, leading=13, alignment=TA_RIGHT
    )
    style_basmala = ParagraphStyle(
        name="Basmala", parent=styles_global["Normal"],
        fontName=font_name, fontSize=12, leading=18, alignment=TA_CENTER,
        textColor=colors.HexColor((brand or {}).get("primary", "#1F3C88")), spaceAfter=8
    )

    # make section-number and section headings use accent color for emphasis
    try:
        acc_col = colors.HexColor((brand or {}).get("accent", "#22B8CF"))
        style_section_number.textColor = acc_col
        style_section.textColor = acc_col
    except Exception:
        pass

def _paragraphs_from_raw(raw: str) -> list:
    """Return a list of Paragraph objects for the given raw text.
    Splits on blank lines and preserves original line order by creating a
    separate Paragraph for each non-empty line. This avoids using HTML
    <br/> inside a single Paragraph which can cause bidi reordering issues.
    """
    out = []
    if not raw:
        return out
    # normalize newlines and tabs
    text = (raw.replace("\r\n", "\n").replace("\r", "\n").replace("\t", "    "))
    lines = text.split("\n")
    current_lines = []
    for ln in lines:
        if ln.strip() == "":
            # blank line -> flush current paragraph and add a modest gap
            if current_lines:
                # join original lines with explicit <br/> so ReportLab treats them as hard breaks
                para_text = "<br/>".join(l.strip() for l in current_lines)
                shaped = _prepare_rtl_html(para_text)
                out.append(Paragraph(shaped, style_body))
                current_lines = []
            # modest gap between paragraphs (reduced)
                out.append(Spacer(1, 4.5 * mm))
        else:
            current_lines.append(ln)

    # flush remaining
    if current_lines:
        para_text = "<br/>".join(l.strip() for l in current_lines)
        shaped = _prepare_rtl_html(para_text)
        out.append(Paragraph(shaped, style_body))
    out.append(Spacer(1, 4.5 * mm))

    return out


class SpacedParagraph(Flowable):
    """Flowable that draws wrapped lines with a fixed vertical gap between them.
    Preserves selectable text (draws Paragraph text operations) but controls
    exact spacing by drawing each visual line separately.
    """
    def __init__(self, text: str, style: ParagraphStyle, max_width: float, line_gap: float):
        super().__init__()
        self.raw = text
        self.style = style
        self.max_width = max_width
        self.line_gap = line_gap
        # prepare wrapped visual lines lazily
        self.lines = None

    def _wrap_lines(self):
        if self.lines is not None:
            return self.lines
        text = self.raw or ''
        words = text.split(' ')
        cur = ''
        wrapped = []
        fn = self.style.fontName
        fs = self.style.fontSize
        for w in words:
            cand = w if cur == '' else cur + ' ' + w
            try:
                wlen = pdfmetrics.stringWidth(_shape_ar(cand), fn, fs)
            except Exception:
                wlen = 0
            if wlen <= self.max_width:
                cur = cand
            else:
                if cur:
                    wrapped.append(cur)
                    cur = w
                else:
                    # break long word by characters
                    tmp = ''
                    for ch in w:
                        tw = pdfmetrics.stringWidth(_shape_ar(tmp + ch), fn, fs)
                        if tw <= self.max_width:
                            tmp += ch
                        else:
                            if tmp:
                                wrapped.append(tmp)
                            tmp = ch
                    if tmp:
                        cur = tmp
        if cur:
            wrapped.append(cur)
        self.lines = wrapped
        return self.lines

    def wrap(self, availWidth, availHeight):
        lines = self._wrap_lines()
        h = len(lines) * self.line_gap
        return availWidth, h

    def draw(self):
        canv = self.canv
        lines = self._wrap_lines()
        x_right = self.max_width
        y = (len(lines) - 1) * self.line_gap
        canv.saveState()
        canv.setFillColor(colors.black)
        try:
            canv.setFont(self.style.fontName, self.style.fontSize)
        except Exception:
            canv.setFont('Helvetica', self.style.fontSize)
        for ln in lines:
            shaped = _shape_ar(ln)
            if self.style.alignment == TA_RIGHT:
                canv.drawRightString(x_right, y, shaped)
            elif self.style.alignment == TA_CENTER:
                canv.drawCentredString(x_right / 2.0, y, shaped)
            else:
                canv.drawString(0, y, shaped)
            y -= self.line_gap
        canv.restoreState()

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
    signature_path: Optional[str] = None,
    prepared_by: Optional[str] = None,
    preserve_layout: bool = False,
    force_canvas: bool = False,
    # When False, do not draw headers/sidebars/watermark backgrounds so the
    # returned PDF can be used as a transparent text-overlay to be merged
    # on top of a visual design PDF. Default True to preserve existing behavior.
    draw_background: bool = True,
) -> bytes:

    global font_name_global
    font_name_global = _register_font(font_path)
    setup_styles(font_name_global, brand)

    right_margin = 18 * mm; left_margin = 18 * mm
    top_margin = 28 * mm; bottom_margin = 22 * mm

    buff = BytesIO()
    doc = SimpleDocTemplate(
        buff, pagesize=A4,
        rightMargin=right_margin, leftMargin=left_margin,
        topMargin=top_margin, bottomMargin=bottom_margin,
        title=_shape_ar(title or "عقد"),
    )

    story = []
    # NOTE: logo is drawn by the header/footer function (only on the first page)
    # Do not insert the logo as an inline Image in the story to avoid duplication.
    # By default we render the Basmala and title as in the original contract layout.
    # If the content has been provided as a preformatted block (preserve_layout=True
    # or content coming from an HTML <pre> block), the content will be used verbatim
    # and we avoid inserting extra metadata paragraphs that can reorder lines when
    # text is later extracted from the PDF.
    # preserve_layout is controlled by caller; when True we avoid inserting
    # meta paragraphs and auto-numbering so the input text layout is preserved.
    if not preserve_layout:
        # الهيدر الديني
        story.append(Paragraph(_shape_ar("بِسْمِ اللهِ الرَّحْمٰنِ الرَّحِيمِ"), style_basmala))
        story.append(Spacer(1, 6))
        # عنوان العقد
        story.append(Paragraph(_shape_ar(title or "عقد"), style_title))
        story.append(Spacer(1, 8))
        # فاصل أفقي
        story.append(Table([[""]], colWidths=[170*mm], style=TableStyle([
            ("LINEBELOW",(0,0),(-1,-1),1.0,colors.HexColor("#1F3C88")),
            ("TOPPADDING",(0,0),(-1,-1),2),
            ("BOTTOMPADDING",(0,0),(-1,-1),6),
        ])))

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

    # Inline metadata (no table) — renders as small paragraphs above the contract body
    # These are omitted when preserve_layout is requested to avoid changing the
    # input text ordering (the original contract places these lines within the
    # main preformatted body).
    if not preserve_layout:
        meta_lines = [
            ("رقم العقد:", serial or "-"),
            ("تاريخ الإنشاء:", date_display or "-"),
        ]
        if client_name:
            meta_lines.append(("اسم العميل:", client_name))
        if client_id_number:
            meta_lines.append(("رقم الهوية/السجل:", client_id_number))
        if client_phone:
            meta_lines.append(("رقم الجوال:", client_phone))
        if client_address:
            meta_lines.append(("العنوان:", client_address))
        if investment_amount is not None:
            meta_lines.append(("مبلغ المشاركة:", inv_text))
        if prepared_by:
            meta_lines.append(("أُعدّ بواسطة:", prepared_by))

        for k, v in meta_lines:
            story.append(Paragraph(_shape_ar(f"{k} {v}"), style_label))
            story.append(Spacer(1, 4))
        story.append(Spacer(1, 8))

    # فاصل أفقي اخر
    if not preserve_layout:
        story.append(Table([[""]], colWidths=[170*mm], style=TableStyle([
            ("LINEBELOW",(0,0),(-1,-1),0.7,colors.HexColor("#1F3C88")),
            ("TOPPADDING",(0,0),(-1,-1),2),
            ("BOTTOMPADDING",(0,0),(-1,-1),6),
        ])))

        # نص العقد مقسم إلى بنود مع عناوين بارزة
        story.append(Paragraph(_shape_ar("نص العقد:"), style_section))
        story.append(Spacer(1, 6))
    # auto-number detected section headings to give a whitepaper-like structure
    lines = (content or "").split("\n")
    # match many variants: 'تمهيد', 'البند', 'بند', optional number/word, optional punctuation
    _rx_heading = HEADING_RX

    # group lines into blocks separated by blank lines (preserves order)
    blocks = []
    current = []
    for ln in lines:
        if ln.strip() == "":
            if current:
                blocks.append(current)
                current = []
            else:
                # multiple blank lines -> skip
                continue
        else:
            current.append(ln)
    if current:
        blocks.append(current)

    # paginate by blocks into 3 roughly-equal chunks — skip pagination when
    # preserve_layout is requested so page breaks remain as the content dictates.
    total_blocks = len(blocks)
    if preserve_layout:
        boundaries = set()
    else:
        if total_blocks == 0:
            boundaries = set()
        else:
            import math
            bsize = max(1, math.ceil(total_blocks / 3))
            boundaries = {bsize, bsize * 2}

    heading_counter = 0
    for bi, block in enumerate(blocks):
        if bi in boundaries:
            story.append(PageBreak())

        # single-line block that matches heading -> render numbered heading
        # allow disabling auto-numbering when preserve_layout is requested
        if not preserve_layout and len(block) == 1 and HEADING_RX.match(block[0].strip()):
            heading_counter += 1
            hdr_text = block[0].strip()
            # convert counter to Arabic ordinal words for nicer display
            def _arabic_ordinal(n: int) -> str:
                mapping = {1: 'الأول', 2: 'الثاني', 3: 'الثالث', 4: 'الرابع', 5: 'الخامس',
                           6: 'السادس', 7: 'السابع', 8: 'الثامن', 9: 'التاسع', 10: 'العاشر'}
                return mapping.get(n, str(n))

            ordinal = _arabic_ordinal(heading_counter)
            # If the heading is a bare 'تمهيد', render as 'البند {ordinal} - التمهيد:'
            if re.match(r'^\s*تمهيد\s*$', hdr_text):
                numbered = f"البند {ordinal} - التمهيد:"
            else:
                # If heading already contains 'البند', strip it and render as
                # 'البند {ordinal} - <rest>:' to match sample style.
                m = re.match(r'^\s*البند\s*(.*)$', hdr_text)
                if m:
                    rest = m.group(1).strip()
                    numbered = f"البند {ordinal} - {rest}:" if rest else f"البند {ordinal}:"
                else:
                    # non-Arabic headings - keep number before text
                    if re.search(r"[\u0600-\u06FF]", hdr_text):
                        numbered = f"البند {ordinal} - {hdr_text}:"
                    else:
                        numbered = f"{heading_counter}. {hdr_text}"
            try:
                story.append(Paragraph(_shape_ar(numbered), style_section_number))
            except Exception:
                story.append(Paragraph(_shape_ar(numbered), style_section))
            story.append(Table([[""]], colWidths=[170*mm], style=TableStyle([
                ("LINEBELOW",(0,0),(-1,-1),0.6,colors.HexColor((brand or {}).get("primary", "#1F3C88"))),
                ("TOPPADDING",(0,0),(-1,-1),2),
                ("BOTTOMPADDING",(0,0),(-1,-1),6),
            ])))
            # no following paragraph in this block (heading-only), continue
            story.append(Spacer(1,6))
            continue

        # otherwise treat the block as a paragraph (preserve original line order)
        para_text = "\n".join(block)
        # When preserve_layout is requested, treat the block as preformatted text
        # with tight spacing, preserving original blank lines and line breaks.
        if preserve_layout:
            # To preserve the exact ordering of lines and avoid bidi reordering
            # inside a single Paragraph, create one Paragraph per original
            # input line. This keeps the start-of-line ordering the same as
            # the source text (important for exact contract parity).
            paras = []
            for ln in block:
                ln_text = ln.rstrip()
                if ln_text.strip() == "":
                    # preserve an intentional blank line as a small spacer
                    paras.append(Spacer(1, 6))
                else:
                    shaped = _prepare_rtl_html(ln_text)
                    paras.append(Paragraph(shaped, style_body))
        else:
            paras = _paragraphs_from_raw(para_text)
        # Append paragraph flowables directly so Platypus can break across pages
        # (KeepTogether can push a large block to the next page leaving big gaps).
        for p in paras:
            story.append(p)
    # add a clearer separation after each block (reduced)
    story.append(Spacer(1, 12))

    # فاصل قبل التوقيع
    story.append(Table([[""]], colWidths=[170*mm], style=TableStyle([
        ("LINEBELOW",(0,0),(-1,-1),0.8,colors.HexColor("#1F3C88")),
        ("TOPPADDING",(0,0),(-1,-1),2),
        ("BOTTOMPADDING",(0,0),(-1,-1),8),
    ])))

    # التوقيع
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

    # إضافة توقيع الطرف الثاني إذا كان موجوداً
    if signature_path and os.path.isfile(signature_path):
        story.append(Spacer(1, 8))
        story.append(Paragraph(_shape_ar("توقيع الطرف الثاني:"), style_small))
        try:
            story.append(Image(signature_path, width=60, height=30))
        except Exception:
            story.append(Paragraph(_shape_ar("[تعذر عرض التوقيع]"), style_small))

    if prepared_by:
        story.append(Spacer(1, 8))
        story.append(Paragraph(_shape_ar(f"أُعدّ بواسطة: {prepared_by}"), style_small))

    def _on_page(canv, _doc):
        _draw_header_footer(canv, _doc, brand=brand or {}, logo_path=logo_path, header_font=font_name_global)
    # Background drawing for preserve_layout: draw sidebar + faint watermark
    def _draw_brand_bg(canv, _doc):
        # Draw left sidebar and faint centered watermark using provided brand/logo
        w, h = A4
        sidebar_w = 28 * mm
        try:
            canv.saveState()
            # sidebar
            try:
                canv.setFillColor(colors.HexColor((brand or {}).get('sidebar', '#0F6161')))
            except Exception:
                canv.setFillColor(colors.HexColor('#0F6161'))
            canv.rect(0, 0, sidebar_w, h, fill=1, stroke=0)
            # watermark
            if logo_path and os.path.isfile(logo_path):
                try:
                    # Create a faded temporary image using Pillow so the watermark
                    # is visible across backends that don't support setFillAlpha.
                    try:
                        from PIL import Image
                        from io import BytesIO
                        from reportlab.lib.utils import ImageReader
                        with Image.open(logo_path) as im:
                            im = im.convert('RGBA')
                            # reduce alpha to ~12% for a faint watermark
                            alpha = int(255 * 0.12)
                            # create an alpha mask
                            r, g, b, a = im.split()
                            a = a.point(lambda _: alpha)
                            im.putalpha(a)
                            buf = BytesIO()
                            im.save(buf, format='PNG')
                            buf.seek(0)
                            img = ImageReader(buf)
                            iw, ih = img.getSize()
                    except Exception:
                        # Pillow not available or failed; fall back to drawing image directly
                        from reportlab.lib.utils import ImageReader
                        img = ImageReader(logo_path)
                        iw, ih = img.getSize()

                    max_w = w * 0.6
                    ratio = min(max_w / iw, (h * 0.6) / ih)
                    draw_w = iw * ratio
                    draw_h = ih * ratio
                    x = (w - draw_w) / 2.0
                    y = (h - draw_h) / 2.0
                    canv.drawImage(img, x, y, width=draw_w, height=draw_h, mask='auto')
                except Exception:
                    pass
        finally:
            try:
                canv.restoreState()
            except Exception:
                pass

    # If we're preserving the original layout (verbatim <pre> text), draw
    # the background (sidebar + watermark) but DO NOT add any extra textual
    # elements which would change the source line ordering when extracting
    # text from the PDF.
    if preserve_layout:
        on_page_cb = _draw_brand_bg if draw_background else (lambda canv, _doc: None)
    else:
        on_page_cb = _on_page if draw_background else (lambda canv, _doc: None)
    # Fast visual fallback: draw text directly on canvas if rendering issues occur
    # This branch is used when requested explicitly via the `force_canvas` flag
    if force_canvas or os.environ.get('PDF_FORCE_CANVAS', '') == '1':
        w, h = A4
        canv = canvas.Canvas(buff, pagesize=A4)
        header_font = font_name_global or 'Helvetica'
        # create a tiny doc-like object to carry page number
        class _MiniDoc:
            def __init__(self, page=1):
                self.page = page
        _m = _MiniDoc(page=1)
        # draw header/footer for first page (skip if preserving layout)
        if not preserve_layout:
            _draw_header_footer(canv, _m, brand=brand or {}, logo_path=logo_path, header_font=header_font)
        # starting y position below header. When preserving layout we want
        # to start closer to the top so the preformatted text aligns like the
        # original scanned contract (original template often contains its own
        # leading and top whitespace). Use style metrics when available.
        default_leading = None
        try:
            default_leading = style_body.leading if style_body and hasattr(style_body, 'leading') else None
        except Exception:
            default_leading = None
        if default_leading is None:
            default_leading = 1.2 * (style_body.fontSize if style_body and hasattr(style_body, 'fontSize') else 14)

        if preserve_layout:
            y = h - top_margin - (default_leading * 0.5)
        else:
            y = h - top_margin - 24
        left_x = left_margin
        right_x = w - right_margin
        # base line height multiplier used when drawing on canvas
        line_height = 18

        def draw_line(text, style_name=None, is_heading=False):
            nonlocal y
            # choose font and size
            try:
                # prefer paragraph styles so canvas spacing matches Paragraph rendering
                use_style = style_section_number if is_heading and style_section_number is not None else style_body
                if use_style is None:
                    use_font = font_name_global or header_font or 'Helvetica'
                    fsize = 14 if is_heading else 13
                else:
                    use_font = use_style.fontName or font_name_global or header_font or 'Helvetica'
                    fsize = getattr(use_style, 'fontSize', 14 if is_heading else 13)
                canv.setFont(use_font, fsize)
            except Exception:
                use_font = 'Helvetica'
                fsize = 13
                canv.setFont(use_font, fsize)

            # wrap text to fit between left and right margins
            max_w = right_x - left_x - 6  # small padding

            def _wrap_text_for_width(txt, fontn, fontsize, maxw):
                # greedy word-wrapping; handles long words by char-slicing
                parts = txt.split(' ')
                lines = []
                cur = ''
                for p in parts:
                    cand = (p if cur == '' else cur + ' ' + p)
                    w = pdfmetrics.stringWidth(_shape_ar(cand), fontn, fontsize)
                    if w <= maxw:
                        cur = cand
                    else:
                        if cur:
                            lines.append(cur)
                            cur = p
                        else:
                            # word itself longer than line: break by chars
                            tmp = ''
                            for ch in p:
                                tw = pdfmetrics.stringWidth(_shape_ar(tmp + ch), fontn, fontsize)
                                if tw <= maxw:
                                    tmp += ch
                                else:
                                    if tmp:
                                        lines.append(tmp)
                                    tmp = ch
                            if tmp:
                                cur = tmp
                if cur:
                    lines.append(cur)
                return lines

            # shape original text first (preserve ordering for shaping), then wrap
            raw = text or ''
            raw_shaped = _shape_ar(raw)
            lines = _wrap_text_for_width(raw_shaped, use_font, fsize, max_w)

            # paginate if not enough vertical space for all wrapped lines
            # use the paragraph-style leading when available to compute required height
            leading = None
            try:
                leading = use_style.leading if use_style is not None and hasattr(use_style, 'leading') else None
            except Exception:
                leading = None
            if leading is None:
                leading = default_leading
            required_h = len(lines) * leading
            if y < bottom_margin + required_h + 10:
                canv.showPage()
                _m.page = canv.getPageNumber()
                if not preserve_layout:
                    _draw_header_footer(canv, _m, brand=brand or {}, logo_path=logo_path, header_font=header_font)
                # reset y using the same top offset as initial
                if preserve_layout:
                    y = h - top_margin - (default_leading * 0.5)
                else:
                    y = h - top_margin - 24

            canv.setFillColor(colors.black)
            for ln in lines:
                shaped = _shape_ar(ln)
                canv.drawRightString(right_x, y, shaped)
                # use style leading (or default_leading) for decrement
                y -= leading

        # draw prepared story lines instead of using platypus
        # reuse the same block logic from above
        lines = (content or "").split("\n")
        blocks = []
        current = []
        for ln in lines:
            if ln.strip() == "":
                if current:
                    blocks.append(current)
                    current = []
            else:
                current.append(ln)
        if current:
            blocks.append(current)

        heading_counter = 0
        # If both preserve_layout and force_canvas are set, prefer a strict
        # per-line drawing mode: draw each original source line verbatim at a
        # fixed leading. This avoids any wrapping or auto-numbering which can
        # alter the extractable text ordering.
        strict_per_line = bool(preserve_layout and force_canvas)

        for block in blocks:
            # When not preserving layout, keep existing behavior (auto-number
            # headings, wrap lines). When preserving, and strict_per_line is
            # enabled, simply draw each input line as-is.
            if not preserve_layout and len(block) == 1 and HEADING_RX.match(block[0].strip()):
                heading_counter += 1
                hdr_text = block[0].strip()
                # form heading like before
                ordinal_map = {1: 'الأول', 2: 'الثاني', 3: 'الثالث'}
                ordinal = ordinal_map.get(heading_counter, str(heading_counter))
                if re.match(r'^\s*تمهيد\s*$', hdr_text):
                    numbered = f"البند {ordinal} - التمهيد:"
                else:
                    m = re.match(r'^\s*البند\s*(.*)$', hdr_text)
                    if m:
                        rest = m.group(1).strip()
                        numbered = f"البند {ordinal} - {rest}:" if rest else f"البند {ordinal}:"
                    else:
                        numbered = f"البند {ordinal} - {hdr_text}:"
                draw_line(numbered, is_heading=True)
                draw_line('')
                continue

            if strict_per_line:
                # draw each original line verbatim without wrapping
                for ln in block:
                    raw = ln or ''
                    shaped = _shape_ar(raw)
                    # paginate if needed for single line
                    if y < bottom_margin + default_leading + 6:
                        canv.showPage()
                        _m.page = canv.getPageNumber()
                        if not preserve_layout:
                            _draw_header_footer(canv, _m, brand=brand or {}, logo_path=logo_path, header_font=header_font)
                        if preserve_layout:
                            y = h - top_margin - (default_leading * 0.5)
                        else:
                            y = h - top_margin - 24
                    canv.setFillColor(colors.black)
                    try:
                        canv.setFont(font_name_global or header_font or 'Helvetica', getattr(style_body, 'fontSize', 14))
                    except Exception:
                        canv.setFont('Helvetica', getattr(style_body, 'fontSize', 14))
                    canv.drawRightString(right_x, y, shaped)
                    y -= default_leading
                # small blank line after block
                y -= (default_leading * 0.2)
                continue

            # otherwise (non-strict) draw with wrapping
            for ln in block:
                draw_line(ln)
            draw_line('')

        # Draw signature block at the end (two columns, right-aligned)
        # move to a new page if necessary
        if y < bottom_margin + 120:
            canv.showPage()
            _m.page = canv.getPageNumber()
            _draw_header_footer(canv, _m, brand=brand or {}, logo_path=logo_path, header_font=header_font)
            y = h - top_margin - 24

        # left and right column positions
        col_w = (w - left_margin - right_margin) / 2
        col1_x = right_x - col_w
        col2_x = left_x + 10
        # right column: توقيع الطرف الأول
        canv.setFont(header_font, 11)
        canv.drawRightString(right_x - 10, y, _shape_ar("توقيع الطرف الأول (شركة سما البنيان التجارية)"))
        y -= 28
        canv.drawRightString(right_x - 10, y, _shape_ar("_________________________"))
        # left column: توقيع الطرف الثاني
        canv.drawString(col2_x, y + 28, _shape_ar("توقيع الطرف الثاني"))
        canv.drawString(col2_x, y, _shape_ar("_________________________"))

        canv.save()
        return buff.getvalue()

    # If preserve_layout is requested we still want a subtle institutional
    # design: draw a left navy sidebar and a faint centered watermark image
    # behind the Platypus story, but avoid adding any textual elements that
    # would change the extract order. We implement this by wrapping doc.build
    # with an onFirstPage/onLaterPages callback that draws background graphics.
    if preserve_layout:
        def _draw_preserve_bg(canv, _doc):
            # Draw left sidebar in brand color and subtle document decorations
            try:
                sidebar_col = colors.HexColor((brand or {}).get('sidebar', '#0F2A5A'))
            except Exception:
                sidebar_col = colors.HexColor('#0F2A5A')
            try:
                accent_col = colors.HexColor((brand or {}).get('accent', '#22B8CF'))
            except Exception:
                accent_col = colors.HexColor('#22B8CF')

            w, h = A4
            sb_w = 28 * mm
            canv.saveState()
            # sidebar
            canv.setFillColor(sidebar_col)
            canv.rect(0, 0, sb_w, h, fill=1, stroke=0)

            # faint centered watermark (logo) if present
            logo_path_local = logo_path if 'logo_path' in locals() else None
            if logo_path_local and os.path.isfile(logo_path_local):
                try:
                    from reportlab.lib.utils import ImageReader
                    img = ImageReader(logo_path_local)
                    iw, ih = img.getSize()
                    max_w = w * 0.6
                    ratio = min(max_w / iw, (h * 0.6) / ih)
                    draw_w = iw * ratio
                    draw_h = ih * ratio
                    img_x = (w - draw_w) / 2.0
                    img_y = (h - draw_h) / 2.0
                    # try to use alpha if available; otherwise draw image as-is
                    try:
                        canv.setFillAlpha(0.06)
                    except Exception:
                        pass
                    canv.drawImage(img, img_x, img_y, width=draw_w, height=draw_h, mask='auto')
                    try:
                        canv.setFillAlpha(1.0)
                    except Exception:
                        pass
                except Exception:
                    pass

            # Small top-right corner logo (non-textual decoration) if requested
            try:
                if logo_path_local and os.path.isfile(logo_path_local):
                    from reportlab.lib.utils import ImageReader
                    img = ImageReader(logo_path_local)
                    iw, ih = img.getSize()
                    corner_w = 28 * mm
                    corner_h = corner_w * (ih / iw) if iw else corner_w
                    # position inside page margins near top-right
                    corner_x = w - right_margin - corner_w
                    corner_y = h - corner_h - (6 * mm)
                    try:
                        canv.setFillAlpha(0.18)
                    except Exception:
                        pass
                    canv.drawImage(img, corner_x, corner_y, width=corner_w, height=corner_h, mask='auto')
                    try:
                        canv.setFillAlpha(1.0)
                    except Exception:
                        pass
            except Exception:
                pass

            # Decorative accents based on source lines — position approximately
            try:
                # flatten the source lines (we use the original `lines` variable)
                source_lines = (content or "").replace('\r\n', '\n').replace('\r', '\n').split('\n')
                # starting y roughly where Platypus begins placing the first paragraph
                leading = getattr(style_body, 'leading', 19.15) or 19.15
                fsize = getattr(style_body, 'fontSize', 13.7) or 13.7
                # top of first line: slightly below the top margin
                y = h - top_margin - (fsize * 0.3)
                left_x = left_margin
                right_x = w - right_margin
                # iterate and draw small decorations without touching any text
                for ln in source_lines:
                    txt = (ln or '').strip()
                    if txt == "":
                        # blank line: small subtle separator to equalize spacing between blocks
                        sep_y = y - (leading * 0.35)
                        canv.setStrokeColor(colors.HexColor('#E6E6E6'))
                        canv.setLineWidth(0.5)
                        canv.setDash(1, 3)
                        canv.line(left_x + (6 * mm), sep_y, right_x - (6 * mm), sep_y)
                        canv.setDash()
                        y -= (6)  # small gap for blank spacer
                        continue

                    # Heading accent: draw a small colored bar near the left of content
                    try:
                        if HEADING_RX.match(txt):
                            bar_h = leading * 0.6
                            bx = sb_w + (6 * mm)
                            by = y - (bar_h * 0.5)
                            canv.setFillColor(accent_col)
                            canv.rect(bx, by, (3 * mm), bar_h, fill=1, stroke=0)
                            # also draw a faint underline for visual separation
                            canv.setStrokeColor(accent_col)
                            canv.setLineWidth(0.6)
                            canv.line(left_x + (6 * mm), y - (leading * 0.3), right_x - (6 * mm), y - (leading * 0.3))
                    except Exception:
                        pass

                    # Signature lines and explicit underscored lines: draw a dotted underline
                    lowered_y = y - (leading * 0.35)
                    try:
                        if 'توقيع' in txt or '________________' in ln:
                            canv.setStrokeColor(colors.black)
                            canv.setLineWidth(0.9)
                            canv.setDash(3, 3)
                            start_x = right_x - (90 * mm)
                            end_x = right_x - (6 * mm)
                            canv.line(start_x, lowered_y, end_x, lowered_y)
                            canv.setDash()
                    except Exception:
                        pass

                    # Label:value lines — draw a dotted/faint underline on the right where values typically appear
                    try:
                        if ':' in ln or '\t' in ln:
                            # draw a short dotted fill indicator on the value side
                            canv.setStrokeColor(colors.HexColor('#9AA6B2'))
                            canv.setLineWidth(0.6)
                            canv.setDash(1, 2)
                            start_x = right_x - (72 * mm)
                            end_x = right_x - (6 * mm)
                            canv.line(start_x, lowered_y, end_x, lowered_y)
                            canv.setDash()
                    except Exception:
                        pass

                    # advance y by the paragraph leading
                    y -= leading
            except Exception:
                pass

            canv.restoreState()
        # Use the bg drawer for both first and later pages but keep textual
        # on_page callbacks noop so no meta paragraphs are added.
        doc.build(story, onFirstPage=_draw_preserve_bg, onLaterPages=_draw_preserve_bg)
        return buff.getvalue()

    doc.build(story, onFirstPage=on_page_cb, onLaterPages=on_page_cb)
    return buff.getvalue()


# --------------------- CLI helpers & convenience wrappers ---------------------
def export_contract_by_id(cid: int, out: str | None = None, brand: dict | None = None) -> str:
    """Generate a PDF for contract `cid` by reading the DB and writing a file.

    Returns the output path written.
    """
    try:
        from models import get_session, Contract
    except Exception as e:
        raise RuntimeError(f"models not available: {e}")

    s = get_session()
    try:
        c = s.get(Contract, int(cid))
        if not c:
            raise ValueError(f"contract id={cid} not found")
        content = c.content or ''
        title = c.title or 'عقد'
        serial = c.client_contract_no or str(c.internal_serial or c.id)
        created_at = c.created_at.isoformat() if c.created_at else ''
        preferred = _find_preferred_font(prefer=['Amiri', 'Cairo', 'DejaVu'])
        pdf = generate_contract_pdf(
            title=title,
            content=content,
            serial=serial,
            created_at=created_at,
            brand=brand or {'primary':'#123456','accent':'#AA3344'},
            logo_path=None,
            font_path=preferred,
            client_name=c.client_name,
            client_id_number=c.client_id_number,
            client_phone=c.client_phone,
            client_address=c.client_address,
            investment_amount=c.investment_amount,
            signature_path=c.signature_path if c.signature_path and os.path.isfile(c.signature_path) else None,
            prepared_by=None,
        )
        out_path = out or f'contract-{c.id}.pdf'
        with open(out_path, 'wb') as f:
            f.write(pdf)
        return out_path
    finally:
        s.close()


def generate_from_content_file(content_file: Optional[str] = None, out: str = 'out.pdf', **kwargs) -> str:
    """Generate a PDF from a plain content file. If `content_file` is None,
    attempt to use the repository's `contract_fixed_v1.txt` (top-level) as the
    default source. Returns the output path written.
    """
    if content_file is None:
        # prefer top-level file, fall back to templates/contract_fixed_v1.txt
        possible = [
            os.path.join(os.path.dirname(__file__), 'contract_fixed_v1.txt'),
            os.path.join(os.path.dirname(__file__), 'templates', 'contract_fixed_v1.txt'),
        ]
        for p in possible:
            if os.path.isfile(p):
                content_file = p
                break
        if content_file is None:
            raise FileNotFoundError('No content_file provided and contract_fixed_v1.txt not found')

    with open(content_file, 'r', encoding='utf-8') as f:
        content = f.read()

    # Support rendering an HTML or text template with a `data` context
    # passed via kwargs['data'] (preferred for complex templates). If the
    # file has an .html extension, and Jinja2 is available, render it and
    # extract the textual portion for PDF flow. For plain text templates
    # we render directly.
    data_ctx = kwargs.get('data') or {}

    # If Jinja2 is available, render the content as a template when it
    # appears to contain Jinja placeholders or when the file lives under
    # the templates/ directory. This allows using the repository's
    # `templates/contract_fixed_v1.txt` as a template with placeholders.
    try:
        from jinja2 import Template
        want_render = False
        # Render HTML files always when Jinja2 is present
        if content_file.lower().endswith('.html'):
            want_render = True
        # Or render if the file contains template markers
        if ("{{" in content and "}}" in content):
            want_render = True
        if want_render:
            try:
                tpl = Template(content)
                # Build a merged data context so callers can supply either a
                # `data` dict or individual kwargs (e.g. from an employee record).
                merged_data = {}
                if isinstance(data_ctx, dict):
                    merged_data.update(data_ctx)

                # copy a set of commonly-used kwargs into the data context so
                # templates that reference `data.X` pick up values from either
                # source.
                keys_to_copy = [
                    'client_name', 'client_id_number', 'client_phone', 'client_address',
                    'investment_amount', 'exit_notice_days', 'signature_contact', 'prepared_by',
                    'profit', 'duration', 'contract_number', 'contract_date',
                    'partner_name', 'partner_id', 'partner_phone', 'capital', 'manager_signature',
                    'start_date_h', 'end_date_h', 'withdrawal_notice_days', 'profit_interval_months',
                    'commission_percent', 'exit_notice_days', 'force_majeure_days', 'penalty_amount'
                ]
                for k in keys_to_copy:
                    v = kwargs.get(k)
                    if v is None:
                        # try alternate name without underscore (legacy keys)
                        v = kwargs.get(k.replace('_', ''))
                    if v is not None:
                        merged_data[k] = v

                # Accept additional employee-facing key names and map them to
                # the canonical template keys so callers can pass the JSON the
                # UI produces without changing the contract template text.
                # Do not modify any contract text; only populate the `data` map.
                # Common alternate keys mapping:
                # Map common UI/form field names to the canonical template keys
                # so callers (the web form) can pass the raw form data directly
                # and the template pulls the expected names under `data.*`.
                alt_map = {
                    # financials
                    'profit_percent': 'profit',
                    'capital_amount': 'capital',
                    'investment_amount': 'investment_amount',
                    'commission_percent': 'commission_percent',
                    'penalty_amount': 'penalty_amount',

                    # partner / counterparty
                    'partner2_name': 'partner_name',
                    'sign2_id': 'partner_id',
                    'sign2_phone': 'partner_phone',

                    # meeting / dates
                    'meeting_date_h': 'contract_date',
                    'meeting_day_name': 'meeting_day_name',
                    'start_date_h': 'start_date_h',
                    'end_date_h': 'end_date_h',

                    # periodic / notice fields
                    'profit_interval_months': 'profit_interval_months',
                    'withdrawal_notice_days': 'withdrawal_notice_days',
                    'exit_notice_days': 'exit_notice_days',

                    # location / jurisdiction
                    'city': 'city',
                    'jurisdiction': 'jurisdiction',

                    # client / contact fields
                    'client_address': 'client_address',

                    # identity / misc
                    'meeting_day_name': 'meeting_day_name',
                }
                for alt, canon in alt_map.items():
                    # priority: kwargs passed directly, then merged_data existing keys
                    if canon not in merged_data:
                        v = kwargs.get(alt)
                        if v is None:
                            v = kwargs.get(alt.replace('_', ''))
                        if v is None:
                            v = merged_data.get(alt)
                        if v is not None:
                            merged_data[canon] = v

                # top-level render context still exposes some separate fields
                render_ctx = dict(
                    title=kwargs.get('title'),
                    serial=kwargs.get('serial'),
                    created_at=kwargs.get('created_at'),
                    prepared_by=kwargs.get('prepared_by'),
                )
                render_ctx['data'] = merged_data
                try:
                    content = tpl.render(**{k: (v if v is not None else '') for k, v in render_ctx.items()})
                except Exception:
                    # If rendering fails, fall back to raw content
                    pass
            except Exception:
                pass
    except Exception:
        # Jinja2 not available — proceed with raw content
        pass

    # If the rendered content contains a leading <pre> block, treat it as
    # preformatted contract text and enable preserve_layout so the generator
    # doesn't add extra metadata or auto-numbering which can shuffle lines.
    preserve_layout = False
    try:
        if '<pre' in content.lower() and '</pre>' in content.lower():
            preserve_layout = True
            # extract inside <pre>...</pre>
            m = re.search(r"<pre[^>]*>(.*?)</pre>", content, flags=re.IGNORECASE | re.DOTALL)
            if m:
                content = m.group(1)
    except Exception:
        preserve_layout = False

    # Allow caller to force preserve_layout via kwargs (explicit override)
    if isinstance(kwargs.get('preserve_layout'), bool):
        preserve_layout = bool(kwargs.get('preserve_layout'))

    # Allow using a design PDF as the visual reference. When `design_pdf` is
    # provided, we will render a text-only PDF (no background) and then merge
    # it over the design. `watermark_path` can override the default logo used
    # for a faint watermark placed behind the text.
    design_pdf = kwargs.get('design_pdf')
    watermark_path = kwargs.get('watermark_path') or kwargs.get('logo_path')

    pdf_bytes = generate_contract_pdf(
        title=kwargs.get('title', 'عقد'),
        content=content,
        serial=kwargs.get('serial', 'GEN-CLI'),
        created_at=kwargs.get('created_at', datetime.utcnow().isoformat()),
        brand=kwargs.get('brand', {'name':'ContractSama','primary':'#1F3C88','accent':'#22B8CF'}),
        logo_path=kwargs.get('logo_path'),
        font_path=kwargs.get('font_path'),
        preserve_layout=preserve_layout,
        force_canvas=bool(kwargs.get('force_canvas', False)),
        client_name=kwargs.get('client_name'),
        client_id_number=kwargs.get('client_id_number'),
        client_phone=kwargs.get('client_phone'),
        client_address=kwargs.get('client_address'),
        investment_amount=kwargs.get('investment_amount'),
        signature_path=kwargs.get('signature_path'),
        prepared_by=kwargs.get('prepared_by'))
    # If a design PDF is provided, merge design + watermark + generated text
    # so the design remains the visual reference and code-controlled values
    # appear on top. This keeps contract words intact in the design PDF and
    # overlays only the dynamic values produced by the generator.
    if design_pdf and os.path.isfile(design_pdf):
        try:
            from pypdf import PdfReader, PdfWriter
        except Exception:
            # pypdf not available: fall back to writing the generated PDF
            with open(out, 'wb') as f:
                f.write(pdf_bytes)
            return out

        # Read design and generated PDFs
        design_reader = PdfReader(design_pdf)
        gen_reader = PdfReader(BytesIO(pdf_bytes))

        # Build a watermark PDF (one page per design page) if watermark_path
        # is provided and exists; use 30% alpha as requested.
        watermark_bytes = None
        if watermark_path and os.path.isfile(watermark_path):
            try:
                watermark_bytes = _make_watermark_pdf(watermark_path, pages=len(design_reader.pages), alpha=0.30)
            except Exception:
                watermark_bytes = None

        writer = PdfWriter()
        # For each design page, overlay watermark (if any) then overlay generated
        for i, dp in enumerate(design_reader.pages):
            page = dp
            # If watermark exists, merge it over the design page (so watermark sits above design)
            if watermark_bytes:
                wm = PdfReader(BytesIO(watermark_bytes))
                try:
                    page.merge_page(wm.pages[i])
                except Exception:
                    # if differing page counts or merge fails, skip watermark
                    pass
            # Overlay generated text page (if available) so it appears above watermark
            try:
                gp = gen_reader.pages[i]
                page.merge_page(gp)
            except Exception:
                # if gen has fewer pages, just keep design(+watermark)
                pass
            writer.add_page(page)

        # If generated has more pages than design, append them at the end
        if len(gen_reader.pages) > len(design_reader.pages):
            for j in range(len(design_reader.pages), len(gen_reader.pages)):
                writer.add_page(gen_reader.pages[j])

        with open(out, 'wb') as f:
            writer.write(f)
        return out

    # default behavior: write the generated PDF bytes to out
    with open(out, 'wb') as f:
        f.write(pdf_bytes)
    return out

    # Header: company name and small logo on left margin area
    try:
        c.setFont('Helvetica-Bold', 18)
    except Exception:
        c.setFont('Helvetica', 18)
    header_x = sidebar_w + 12 * mm
    c.setFillColor(colors.HexColor(brand.get('primary', '#1F3C88')))
    c.drawString(header_x, h - 28 * mm, _shape_ar(brand.get('name', '')))

    # small company info block top-left (next to header)
    c.setFont('Helvetica', 9)
    info_x = header_x
    info_y = h - 34 * mm
    c.setFillColor(colors.black)
    client = data.get('client_name', 'Client Name')
    c.drawString(info_x, info_y, _shape_ar(f"Invoice To: {client}"))
    c.drawString(info_x, info_y - 10, _shape_ar(f"Invoice No: {data.get('invoice_no','INV-001')}"))
    c.drawString(info_x, info_y - 20, _shape_ar(f"Date: {data.get('date','2025-10-04')}"))

    # Table header (light pink bar)
    tbl_x = sidebar_w + 12 * mm
    tbl_w = w - tbl_x - 12 * mm
    tbl_y = h - 70 * mm
    tbl_h = 12 * mm
    try:
        c.setFillColor(colors.HexColor(brand.get('accent', '#E5B7B7')))
    except Exception:
        c.setFillColor(colors.HexColor('#E5B7B7'))
    c.rect(tbl_x, tbl_y, tbl_w, tbl_h, fill=1, stroke=0)
    c.setFillColor(colors.black)
    c.setFont('Helvetica-Bold', 10)
    # Column titles
    c.drawRightString(tbl_x + tbl_w - 10, tbl_y + 3, _shape_ar('Total'))
    c.drawCentredString(tbl_x + tbl_w * 0.6, tbl_y + 3, _shape_ar('Qnt'))
    c.drawCentredString(tbl_x + tbl_w * 0.4, tbl_y + 3, _shape_ar('Unit Price'))
    c.drawString(tbl_x + 8, tbl_y + 3, _shape_ar('Items Description'))

    # Sample items
    items = data.get('items', [
        {'desc': 'Items Name', 'unit': '0.00', 'qty': '1', 'total': '0.00'},
        {'desc': 'Items Name', 'unit': '0.00', 'qty': '1', 'total': '0.00'},
    ])
    y = tbl_y - 14
    c.setFont('Helvetica', 9)
    for it in items:
        c.drawString(tbl_x + 8, y, _shape_ar(it.get('desc', '')))
        c.drawCentredString(tbl_x + tbl_w * 0.4, y, _shape_ar(str(it.get('unit', ''))))
        c.drawCentredString(tbl_x + tbl_w * 0.6, y, _shape_ar(str(it.get('qty', ''))))
        c.drawRightString(tbl_x + tbl_w - 10, y, _shape_ar(str(it.get('total', ''))))
        y -= 12

    # Totals box bottom-right
    totals_x = tbl_x + tbl_w - 60 * mm
    totals_y = y - 8
    c.setFont('Helvetica-Bold', 10)
    c.drawRightString(totals_x + 55 * mm, totals_y, _shape_ar(f"SUBTOTAL: {data.get('subtotal','0.00')}"))
    c.drawRightString(totals_x + 55 * mm, totals_y - 14, _shape_ar(f"TAX: {data.get('tax','0.00')}"))
    c.drawRightString(totals_x + 55 * mm, totals_y - 28, _shape_ar(f"TOTAL DUE: {data.get('total','0.00')}"))

    # Footer colored bar
    try:
        c.setFillColor(colors.HexColor(brand.get('accent', '#E5B7B7')))
    except Exception:
        c.setFillColor(colors.HexColor('#E5B7B7'))
    c.rect(sidebar_w, 6 * mm, w - sidebar_w, 22 * mm, fill=1, stroke=0)

    c.showPage()
    c.save()
    return out_path


def _cli_main():
    import argparse
    parser = argparse.ArgumentParser(prog='python -m pdf_utils')
    sub = parser.add_subparsers(dest='cmd')

    gen = sub.add_parser('generate')
    gen.add_argument('--title')
    gen.add_argument('--content-file')
    gen.add_argument('--out')
    gen.add_argument('--serial')
    gen.add_argument('--created-at')
    gen.add_argument('--font')
    gen.add_argument('--logo')
    gen.add_argument('--client-name')
    gen.add_argument('--client-id')
    gen.add_argument('--client-phone')
    gen.add_argument('--client-address')
    gen.add_argument('--investment-amount', type=float)
    gen.add_argument('--signature')
    gen.add_argument('--prepared-by')

    sub.add_parser('sample')
    exp = sub.add_parser('export')
    exp.add_argument('--cid', required=True)
    exp.add_argument('--out')

    sub.add_parser('font-test')
    insp = sub.add_parser('inspect')
    insp.add_argument('--file', required=True)

    args = parser.parse_args()
    if not args.cmd:
        parser.print_help()
        return
    if args.cmd == 'generate':
        if not args.content_file:
            parser.error('generate requires --content-file')
        out = args.out or 'out.pdf'
        generate_from_content_file(args.content_file, out=out,
                                   title=args.title, serial=args.serial, created_at=args.created_at,
                                   font_path=args.font, logo_path=args.logo,
                                   client_name=args.client_name, client_id_number=args.client_id,
                                   client_phone=args.client_phone, client_address=args.client_address,
                                   investment_amount=args.investment_amount, signature_path=args.signature,
                                   prepared_by=args.prepared_by)
        print('Wrote', out)
    elif args.cmd == 'sample':
        sample_content = "\n\n".join(['تمهيد', 'هذا نص تجريبي للعقد.\n\nالبند الأول\nالنص التجريبي للبند الأول.'])
        tmp = 'sample_contract.pdf'
        with open('tmp_contract_content.txt', 'w', encoding='utf-8') as f:
            f.write(sample_content)
        out = generate_from_content_file('tmp_contract_content.txt', out=tmp)
        os.remove('tmp_contract_content.txt')
        print('Wrote', out)
    elif args.cmd == 'export':
        out = export_contract_by_id(int(args.cid), out=args.out)
        print('Wrote', out)
    elif args.cmd == 'font-test':
        # simple wrapper that calls sample generation with a font hint
        sample_content = "\n\n".join(['تمهيد', 'نص لاختبار الخطوط'])
        with open('tmp_ft_content.txt', 'w', encoding='utf-8') as f:
            f.write(sample_content)
        out = generate_from_content_file('tmp_ft_content.txt', out=(getattr(sys, 'argv', [''])[0] or 'font_test.pdf'))
        try:
            os.remove('tmp_ft_content.txt')
        except Exception:
            pass
        print('Wrote', out)
    elif args.cmd == 'inspect':
        info = inspect_pdf_file(args.file)
        print('size=', info['size'])
        print('header bytes (first 120):', info['header'][:120])


def inspect_pdf_file(path: str) -> dict:
    """Return simple inspection info for a PDF file (exists, size, header bytes)."""
    p = Path(path)
    if not p.exists():
        raise FileNotFoundError(path)
    raw = p.read_bytes()
    return {'exists': True, 'size': p.stat().st_size, 'header': raw[:512]}


if __name__ == '__main__':
    try:
        _cli_main()
    except SystemExit:
        raise
    except Exception as e:
        print('pdf_utils CLI error:', e)