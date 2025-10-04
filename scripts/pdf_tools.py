"""
PDF tools consolidated CLI for ContractSama

Usage:
  python scripts\pdf_tools.py generate --out out.pdf --title "..." --content-file file.txt
  python scripts\pdf_tools.py sample
  python scripts\pdf_tools.py export --cid 1 --out contract-1.pdf
  python scripts\pdf_tools.py font-test
  python scripts\pdf_tools.py inspect --file contract-1.pdf

This script wraps existing pdf_utils functions and the helper scripts to give a
single entrypoint for PDF generation and diagnostics.
"""

import argparse
import os
import sys
from pathlib import Path

# ensure repo root is on sys.path
ROOT = Path(__file__).resolve().parents[1]
if str(ROOT) not in sys.path:
    sys.path.insert(0, str(ROOT))

from pdf_utils import generate_contract_pdf, _find_preferred_font


def cmd_generate(args):
    content = ''
    if args.content_file:
        with open(args.content_file, 'r', encoding='utf-8') as f:
            content = f.read()
    else:
        content = args.content or 'نص افتراضي'

    font_path = args.font or _find_preferred_font()
    logo = args.logo if args.logo and os.path.isfile(args.logo) else None

    pdf_bytes = generate_contract_pdf(
        title=args.title or 'عقد',
        content=content,
        serial=args.serial or 'GEN-CLI',
        created_at=args.created_at or '2025-01-01T00:00:00',
        brand={'name': 'ContractSama', 'primary':'#1F3C88', 'accent':'#22B8CF'},
        logo_path=logo,
        font_path=font_path,
        client_name=args.client_name,
        client_id_number=args.client_id,
        client_phone=args.client_phone,
        client_address=args.client_address,
        investment_amount=args.investment_amount,
        signature_path=args.signature if args.signature and os.path.isfile(args.signature) else None,
        prepared_by=args.prepared_by,
    )
    out = args.out or 'out.pdf'
    with open(out, 'wb') as f:
        f.write(pdf_bytes)
    print('Wrote', out, 'size=', len(pdf_bytes))


def cmd_sample(args):
    # simple wrapper to generate the sample PDF used in development
    sample_content = "\n\n".join([
        'تمهيد',
        'هذا نص تجريبي للعقد.\n\nالبند الأول\nالنص التجريبي للبند الأول.',
    ])
    args.title = args.title or 'عقد - عينة'
    args.content = sample_content
    args.out = args.out or 'sample_contract.pdf'
    cmd_generate(args)


def cmd_export(args):
    # replicate behavior of export_contract_pdf.py but call generate_contract_pdf directly
    try:
        from models import get_session, Contract
    except Exception as e:
        print('models module not importable from scripts context:', e)
        raise

    s = get_session()
    try:
        c = s.get(Contract, int(args.cid))
        if not c:
            print('contract not found')
            return
        content = c.content or ''
        title = c.title or 'عقد'
        serial = c.client_contract_no or str(c.internal_serial or c.id)
        created_at = c.created_at.isoformat() if c.created_at else ''
        preferred = _find_preferred_font(prefer=['Amiri', 'Cairo', 'DejaVu'])
        pdf = generate_contract_pdf(
            title=title,
            content=content,
            serial=serial,
            created_at=created_at,
            brand={'primary':'#123456','accent':'#AA3344'},
            logo_path=None,
            font_path=preferred,
            client_name=c.client_name,
            client_id_number=c.client_id_number,
            client_phone=c.client_phone,
            client_address=c.client_address,
            investment_amount=c.investment_amount,
            signature_path=c.signature_path if c.signature_path and os.path.isfile(c.signature_path) else None,
            prepared_by=None,
        )
        out = args.out or f'contract-{c.id}.pdf'
        with open(out, 'wb') as f:
            f.write(pdf)
        print('Wrote', out, 'size=', len(pdf))
    finally:
        s.close()


def cmd_font_test(args):
    # quick generator that writes to a file
    args.title = args.title or 'اختبار خطوط'
    args.out = args.out or 'font_test.pdf'
    cmd_generate(args)


def cmd_inspect(args):
    # basic inspect similar to debug_pdf_inspect.py
    p = Path(args.file)
    if not p.exists():
        print('file not found:', args.file)
        return
    print('exists:', p.exists())
    print('size:', p.stat().st_size)
    head = p.read_bytes(512)
    print('first bytes sample:', head[:120])


def main():
    parser = argparse.ArgumentParser(prog='scripts/pdf_tools.py')
    sub = parser.add_subparsers(dest='cmd')

    gen = sub.add_parser('generate')
    gen.add_argument('--title')
    gen.add_argument('--content-file')
    gen.add_argument('--content')
    gen.add_argument('--out')
    gen.add_argument('--serial')
    gen.add_argument('--created-at')
    gen.add_argument('--font')
    gen.add_argument('--logo')
    gen.add_argument('--client-name')
    gen.add_argument('--client-id')
    gen.add_argument('--client-phone')
    gen.add_argument('--client-address')
    gen.add_argument('--investment-amount', type=float)
    gen.add_argument('--signature')
    gen.add_argument('--prepared-by')

    sub.add_parser('sample')
    exp = sub.add_parser('export')
    exp.add_argument('--cid', required=True)
    exp.add_argument('--out')

    sub.add_parser('font-test')
    insp = sub.add_parser('inspect')
    insp.add_argument('--file', required=True)

    args = parser.parse_args()
    if not args.cmd:
        parser.print_help()
        return
    if args.cmd == 'generate':
        cmd_generate(args)
    elif args.cmd == 'sample':
        cmd_sample(args)
    elif args.cmd == 'export':
        cmd_export(args)
    elif args.cmd == 'font-test':
        cmd_font_test(args)
    elif args.cmd == 'inspect':
        cmd_inspect(args)


if __name__ == '__main__':
    main()
