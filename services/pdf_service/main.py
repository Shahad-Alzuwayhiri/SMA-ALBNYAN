from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.responses import JSONResponse, Response
import fitz
import io, json
from reportlab.pdfgen import canvas
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfbase import pdfmetrics
from reportlab.lib.pagesizes import letter
import arabic_reshaper
from bidi.algorithm import get_display
from PIL import Image

app = FastAPI(title="ContractSama PDF Service")

def shape_ar(s: str) -> str:
    try:
        reshaped = arabic_reshaper.reshape(s)
        bidi = get_display(reshaped)
        return bidi
    except Exception:
        return s

@app.post('/extract_positions')
async def extract_positions(file: UploadFile = File(...)):
    if not file.filename.lower().endswith('.pdf'):
        raise HTTPException(status_code=400, detail='Only PDF allowed')
    data = await file.read()
    doc = fitz.open(stream=data, filetype='pdf')
    results = []
    for pno in range(len(doc)):
        page = doc[pno]
        blocks = page.get_text('blocks')
        # blocks: x0, y0, x1, y1, "text", block_no
        for b in blocks:
            x0, y0, x1, y1, text, block_no = b[0], b[1], b[2], b[3], b[4], b[6]
            lines = text.split('\n')
            # approximate line heights by dividing block height
            block_h = y1 - y0 if (y1 - y0) > 0 else 12
            if len(lines) > 1:
                line_h = block_h / len(lines)
            else:
                line_h = block_h
            for i, line in enumerate(lines):
                if not line.strip():
                    continue
                top = y0 + i * line_h
                left = x0
                right = x1
                bottom = top + line_h
                results.append({
                    'page': pno+1,
                    'text': line,
                    'bbox': [left, top, right, bottom]
                })
    return JSONResponse(content={'positions': results})

@app.post('/render_overlay')
async def render_overlay(positions: dict):
    # positions: {'positions': [{page, text, bbox:[l,t,r,b]}, ...]}
    pos_list = positions.get('positions') or []
    if not isinstance(pos_list, list):
        raise HTTPException(status_code=400, detail='Malformed positions payload')

    # register font if exists in parent repo
    try:
        pdfmetrics.registerFont(TTFont('Amiri', '../../static/fonts/Amiri-Regular.ttf'))
        font_name = 'Amiri'
    except Exception:
        font_name = 'Helvetica'

    # group by page
    pages = {}
    for p in pos_list:
        pages.setdefault(p['page'], []).append(p)

    out_buf = io.BytesIO()
    # For simplicity render only first page size to letter
    c = canvas.Canvas(out_buf, pagesize=letter)
    width, height = letter
    for pno in sorted(pages.keys()):
        items = pages[pno]
        for it in items:
            text = it.get('text','')
            # Shape Arabic if contains arabic letters
            if any('\u0600' <= ch <= '\u06FF' for ch in text):
                text = shape_ar(text)
            left, top, right, bottom = it['bbox']
            # ReportLab origin is bottom-left; convert
            x = left
            y = height - top
            c.setFont(font_name, 10)
            c.drawString(x, y, text)
        c.showPage()
    c.save()
    out_buf.seek(0)
    return Response(content=out_buf.read(), media_type='application/pdf')

@app.get("/")
async def root():
    return {"message": "ContractSama PDF Service is running", "version": "1.0.0"}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8001)