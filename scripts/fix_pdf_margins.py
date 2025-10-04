#!/usr/bin/env python3
"""Fix PDF margins by setting a consistent CropBox on each page.

This is a safe operation that does not change page content streams; it only
adjusts the visible page box (CropBox/TrimBox/ArtBox) so margins appear
consistent. Default margin: 18 mm.

Usage:
    python scripts/fix_pdf_margins.py in.pdf out.pdf --margin-mm 18

Options:
    --mode crop   (default) sets CropBox/TrimBox/ArtBox to margin inset
    --mode pad    increases media box and centers existing content (not implemented)

This script requires `pypdf` installed in the environment.
"""
import sys
import argparse
from pypdf import PdfReader, PdfWriter
from pypdf.generic import RectangleObject

PT_PER_MM = 72.0 / 25.4


def fix_margins(in_path: str, out_path: str, margin_mm: float = 18.0, mode: str = 'crop'):
    r = PdfReader(in_path)
    w = PdfWriter()
    margin_pt = float(margin_mm) * PT_PER_MM
    for i, page in enumerate(r.pages):
        try:
            mb = page.mediabox
            page_w = float(mb.width)
            page_h = float(mb.height)
        except Exception:
            # fallback to direct numbers
            page_w = float(page.mediabox[2]) - float(page.mediabox[0])
            page_h = float(page.mediabox[3]) - float(page.mediabox[1])
        if mode == 'crop':
            # compute new crop rectangle inset by margin_pt on all sides
            llx = margin_pt
            lly = margin_pt
            urx = page_w - margin_pt
            ury = page_h - margin_pt
            # sanity clamp
            if urx <= llx or ury <= lly:
                print(f"Page {i+1}: margin too large for page size; skipping crop for this page")
            else:
                rect = RectangleObject([llx, lly, urx, ury])
                page.cropbox = rect
                try:
                    page.trimbox = rect
                    page.artbox = rect
                except Exception:
                    pass
        else:
            raise NotImplementedError("Only 'crop' mode is implemented in this script")
        w.add_page(page)
    with open(out_path, 'wb') as outf:
        w.write(outf)


if __name__ == '__main__':
    p = argparse.ArgumentParser()
    p.add_argument('input', help='input PDF path')
    p.add_argument('output', help='output PDF path')
    p.add_argument('--margin-mm', type=float, default=18.0, help='margin inset in millimeters (default 18)')
    p.add_argument('--mode', choices=['crop','pad'], default='crop')
    args = p.parse_args()
    fix_margins(args.input, args.output, margin_mm=args.margin_mm, mode=args.mode)
    print('Wrote', args.output)
