PDF utilities (pdf_utils.py)
============================

This project uses a single canonical PDF utility module: `pdf_utils.py`.

Purpose
-------
- Generate contract PDFs using ReportLab and the project's fonts
- Provide small helper wrappers for common tasks (generate from file, export by id, inspect PDF)
- Offer a minimal CLI entrypoint for local usage

Quick examples
--------------

Generate a PDF from a plain text file:

Python API:

    from pdf_utils import generate_from_content_file
    generate_from_content_file('contract_fixed_v1.txt', out='out.pdf')

CLI:

    python -m pdf_utils generate --in contract_fixed_v1.txt --out out.pdf

Export a contract from the database by id (from within the app environment):

Python API:

    from pdf_utils import export_contract_by_id
    export_contract_by_id(1, out='contract-1.pdf')

Inspect a PDF file:

    from pdf_utils import inspect_pdf_file
    print(inspect_pdf_file('out.pdf'))

Notes
-----
- The module registers TTF fonts from `static/fonts/` on first use.
- The ReportLab generator expects UTF-8 text; shaping (Arabic) is handled when available libraries are installed.
- If you need more advanced HTML-to-PDF rendering, consider the Playwright-based renderer (`pdf_renderer_playwright.py`) which is separate.
