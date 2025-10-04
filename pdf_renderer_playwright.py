import os
from jinja2 import Environment, FileSystemLoader, select_autoescape
import html as _html
from pathlib import Path
from typing import Optional
from playwright.sync_api import sync_playwright


# Render a contract via HTML template + Playwright (Chromium) to PDF
TEMPLATES_DIR = os.path.join(os.path.dirname(__file__), 'templates')


def generate_contract_pdf_html(*, title: str, content: str, serial: str, created_at: str,
                 brand: dict, logo_path: Optional[str],
                 prepared_by: Optional[str] = None) -> bytes:
  env = Environment(
    loader=FileSystemLoader(TEMPLATES_DIR),
    autoescape=select_autoescape(['html', 'xml'])
  )
  tpl_path = os.path.join(TEMPLATES_DIR, 'contracts_preview.html')
  tpl = env.get_template('contracts_preview.html') if os.path.exists(tpl_path) else None

  def _build_blocks_from_text(raw_text: str):
    raw = (raw_text or '')
    raw = raw.replace('\r\n', '\n').replace('\r', '\n')
    paras = [p.strip() for p in raw.split('\n\n') if p.strip()]
    # fallback: if content had no double-newline paragraph separators but contains many single newlines,
    # split on single newlines to preserve intended line/paragraph ordering (avoids single giant paragraph)
    if len(paras) == 1 and raw.count('\n') >= 4:
      paras = [p.strip() for p in raw.split('\n') if p.strip()]

    import re
    heading_re = re.compile(r'^(?:\s*)(البند\b[^\n\r:–-]*)(.*)$', flags=re.IGNORECASE)
    blocks = []
    for p in paras:
      m = heading_re.match(p)
      if m:
        heading = _html.escape(m.group(1).strip())
        rest = m.group(2).strip()
        blocks.append(f'<div class="section-title">{heading}</div>')
        if rest:
          blocks.append(f'<p class="para">{_html.escape(rest).replace("\n","<br/>")}</p>')
      else:
        blocks.append(f'<p class="para">{_html.escape(p).replace("\n","<br/>")}</p>')
    return blocks

  if not tpl:
    blocks = _build_blocks_from_text(content)
    content_html = '\n'.join(blocks)

    # debug output to check order
    print('\n[pdf_renderer_playwright] debug content_html (start):')
    print(content_html[:2000])

    html_template = (
      "<!doctype html>\n"
      "<html lang=\"ar\" dir=\"rtl\">\n"
      "<head>\n"
      "  <meta charset=\"utf-8\" />\n"
      "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />\n"
      "  <style>\n"
      "    @font-face {{{{\n"
      "      font-family: 'AmiriLocal';\n"
      "      src: url('file://{font_path}');\n"
      "    }}}}\n"
      "    body {{{{\n"
      "      font-family: 'AmiriLocal', serif;\n"
      "      font-size: 13pt;\n"
      "      line-height: 15mm;\n"
      "      direction: rtl;\n"
      "      text-align: justify;\n"
      "      margin: 12mm 16mm 18mm 16mm;\n"
      "    }}}}\n"
      "    h1 {{{{ text-align: center; margin-bottom: 6mm; }}}}\n"
      "    .para {{{{ margin: 0 0 6mm 0; }}}}\n"
      "    .section-title {{{{ font-weight: bold; margin-top: 8mm; margin-bottom: 6mm; font-size: 14pt; text-align: right; color: {brand_primary}; }}}}\n"
      "    .meta {{{{ margin-top: 10mm; font-size: 10pt; text-align: left; direction: ltr; }}}}\n"
      "  </style>\n"
      "</head>\n"
      "<body>\n"
      "<h1>{title}</h1>\n"
      "<main>\n{content_html}\n</main>\n"
      "<div class=\"meta\">المرجع: {serial} &nbsp;|&nbsp; تاريخ الإنشاء: {created_at}</div>\n"
      "<p>أُعدّ بواسطة: {prepared_by}</p>\n"
      "</body>\n"
      "</html>\n"
    )

    font_path_val = Path(os.path.join(os.path.dirname(__file__), 'static', 'fonts', 'Amiri-Regular.ttf')).as_posix()
    html = html_template.format(
      font_path=font_path_val,
      brand_primary=brand.get('primary', '#1F3C88'),
      title=_html.escape(title or ''),
      content_html=content_html,
      serial=(serial or '-'),
      created_at=(created_at or ''),
      prepared_by=(prepared_by or '')
    )
  else:
    blocks = _build_blocks_from_text(content)
    # produce a simple preview_text for the template; keep same visual order
    preview_text = '\n\n'.join([
      _html.unescape(b.replace('<div class="section-title">', '').replace('</div>', ''))
      if b.startswith('<div') else _html.unescape(b.replace('<p class="para">', '').replace('</p>', ''))
      for b in blocks
    ])

    print('\n[pdf_renderer_playwright] debug preview_text (start):')
    print(preview_text[:2000])

    html = tpl.render(
      title=title,
      content=content,
      serial=serial,
      created_at=created_at,
      brand=brand,
      logo_path=logo_path,
      prepared_by=prepared_by,
      preview_text=preview_text,
    )

  # render with Playwright
  with sync_playwright() as p:
    browser = p.chromium.launch()
    page = browser.new_page()
    page.set_content(html, wait_until='networkidle')
    pdf_bytes = page.pdf(format='A4', margin={'top': '20mm', 'bottom': '20mm', 'left': '18mm', 'right': '18mm'})
    browser.close()
    return pdf_bytes

