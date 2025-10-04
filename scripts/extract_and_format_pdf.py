#!/usr/bin/env python3
from pypdf import PdfReader
import sys
import unicodedata
import re
from pathlib import Path

PDF = Path(sys.argv[1]) if len(sys.argv) > 1 else Path('sample_contract.pdf')
OUT = Path(sys.argv[2]) if len(sys.argv) > 2 else PDF.with_suffix('.txt')

def normalize_text(s: str) -> str:
    try:
        s = unicodedata.normalize('NFKC', s)
    except Exception:
        pass
    # remove control characters
    s = ''.join(ch for ch in s if unicodedata.category(ch)[0] != 'C')
    # collapse whitespace
    s = re.sub(r"\s+", ' ', s)
    return s.strip()

reader = PdfReader(str(PDF))
all_text = []
for i, p in enumerate(reader.pages):
    t = p.extract_text() or ''
    t = normalize_text(t)
    # split heuristically into lines/paragraphs by double newlines or long runs
    all_text.append(f'--- PAGE {i+1} ---')
    # try to re-insert line breaks where section markers appear ("البند", "تمهيد")
    t = re.sub(r"(\bالبند\b|\bتمهيد\b|\bنص العقد\b)", r"\n\n\1", t)
    # split on our inserted breaks or existing newlines
    parts = re.split(r"\n\n|\n", t)
    for ptxt in parts:
        ptxt = ptxt.strip()
        if not ptxt:
            continue
        # ensure Arabic text is right-to-left in plain file by just printing as-is
        all_text.append(ptxt)

OUT.write_text('\n\n'.join(all_text), encoding='utf-8')
print('Wrote', OUT)
