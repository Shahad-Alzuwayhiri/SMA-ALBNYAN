from pypdf import PdfReader

reader = PdfReader('sample_contract.pdf')
all_text = []
for i, p in enumerate(reader.pages):
    try:
        t = p.extract_text() or ''
    except Exception as e:
        t = f'<err:{e}>'
    print(f'--- PAGE {i+1} len {len(t)} ---')
    print(t[:1000])
    all_text.append(t)
print('TOTAL len=', sum(len(x) for x in all_text))
