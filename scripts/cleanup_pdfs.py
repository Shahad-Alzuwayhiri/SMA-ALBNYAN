"""Cleanup generated PDF files but keep tmp_html_render.pdf as the design reference.
Deletes any .pdf under the repo except the reference file.
"""
import os
from pathlib import Path

root = Path(__file__).resolve().parent.parent
keep = {'tmp_html_render.pdf'}
removed = []

for dirpath, dirnames, filenames in os.walk(root):
    for fn in filenames:
        if fn.lower().endswith('.pdf') and fn not in keep:
            p = Path(dirpath) / fn
            try:
                p.unlink()
                removed.append(str(p.relative_to(root)))
            except Exception as e:
                print('Failed to remove', p, e)

print('Removed PDFs:')
for r in removed:
    print('-', r)

# List remaining PDFs
remaining = []
for dirpath, dirnames, filenames in os.walk(root):
    for fn in filenames:
        if fn.lower().endswith('.pdf'):
            remaining.append(str(Path(dirpath) / fn))

print('\nRemaining PDFs:')
for r in remaining:
    print('-', os.path.relpath(r, root))
