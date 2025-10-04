This folder contains archived PDF helper scripts from the ContractSama project.

Why these were moved:
- The repository now exposes a single consolidated CLI at `scripts/pdf_tools.py` which
  provides subcommands for generating, exporting, and inspecting PDFs.
- To reduce clutter at the repository root the older helper scripts were archived
  here. They are kept intact for reference and reproducibility.

How to use the new CLI:
- Generate a PDF from a content file:
    python scripts\pdf_tools.py generate --content-file path/to/content.txt --out out.pdf

- Create the sample PDF used during development:
    python scripts\pdf_tools.py sample

- Export a contract by id (reads from the database):
    python scripts\pdf_tools.py export --cid 1 --out contract-1.pdf

- Run a quick font test:
    python scripts\pdf_tools.py font-test --out font_test.pdf

- Inspect a PDF header bytes:
    python scripts\pdf_tools.py inspect --file contract-1.pdf

Notes:
- The archived scripts remain in this directory for debugging and traceability.
- If you rely on any of the old filenames in tooling or CI, update those workflows to
  call `scripts/pdf_tools.py` instead.
