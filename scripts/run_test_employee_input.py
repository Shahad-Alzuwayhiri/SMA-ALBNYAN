import sys
sys.path.insert(0, r'.')
from pdf_utils import generate_from_content_file

employee_data = {
  "meeting_day_name": "الأربعاء",
  "meeting_date_h": "12-2-1447هـ",
  "city": "جدة",
  "partner2_name": "سهام بنت سرحان",
  "sign2_id": "2193599665",
  "sign2_phone": "1018906188",
  "client_address": "حي الحمدانية, جدة",
  "investment_amount": "100000",
  "capital_amount": "100000",
  "profit_percent": "30",
  "profit_interval_months": "6",
  "withdrawal_notice_days": "60",
  "start_date_h": "12-2-1447هـ",
  "end_date_h": "12-8-1447هـ",
  "commission_percent": "2.5",
  "exit_notice_days": "90",
  "jurisdiction": "المملكة العربية السعودية",
  "penalty_amount": "3000"
}

out = 'tmp_from_employee.pdf'
print('Rendering with employee data ->', out)
generate_from_content_file(None, out=out, data=employee_data, preserve_layout=True)
print('Done')
