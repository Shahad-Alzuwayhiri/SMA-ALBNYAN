# PDF Service (FastAPI)

Lightweight Python microservice to handle PDF tasks that require PyMuPDF precision:

- /extract_positions: extract per-line text positions from an uploaded PDF (returns JSON positions)
- /render_overlay: render an overlay PDF from positions JSON and return PDF bytes

Run locally:

1. Create a virtualenv and install deps:
   python -m venv .venv
   .\.venv\Scripts\activate
   pip install -r requirements.txt

2. Run the service:
   uvicorn main:app --host 127.0.0.1 --port 8001

Example PHP integration (cURL):

# Upload PDF and get positions
curl -F "file=@contract_fixed_v1.pdf" http://127.0.0.1:8001/extract_positions -o positions.json

# Render overlay from positions.json
curl -X POST -H "Content-Type: application/json" -d @positions.json http://127.0.0.1:8001/render_overlay --output overlay.pdf
