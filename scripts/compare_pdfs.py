from pathlib import Path
import PyPDF2
import difflib

orig = Path(r'C:/Users/Admin/Desktop/العقد.pdf')
gen = Path('tmp_html_render.pdf')
if not orig.exists():
    print('Original PDF missing:', orig)
    raise SystemExit(1)
if not gen.exists():
    print('Generated PDF missing:', gen)
    raise SystemExit(1)

def extract_text(p):
    r = PyPDF2.PdfReader(str(p))
    pages = []
    for page in r.pages:
        pages.append(page.extract_text() or '')
    return '\n\n---PAGE BREAK---\n\n'.join(pages)

orig_text = extract_text(orig).splitlines()
gen_text = extract_text(gen).splitlines()

print('Original length:', len(orig_text), 'Generated length:', len(gen_text))

# Show a small unified diff
for i, line in enumerate(difflib.unified_diff(orig_text, gen_text, fromfile='original', tofile='generated', lineterm='')):
    if i < 200:
        print(line)
    else:
        break

# Print first 40 lines of each for quick glance
print('\n--- ORIGINAL (first 40 lines) ---')
print('\n'.join(orig_text[:40]))
print('\n--- GENERATED (first 40 lines) ---')
print('\n'.join(gen_text[:40]))
