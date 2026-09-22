from pathlib import Path
from pypdf import PdfReader
p=Path(r'C:\Users\thien\Downloads\Kich_ban_tich_hop_smartca_v4.1.pdf')
reader=PdfReader(str(p))
terms=['gwsca.vnpt.vn','rmgateway.vnptit.vn','v2/signatures/sign','v2/signatures/confirm','MobileCode','mobile_code','mobileCode','get_certificate','sp769']
for i,page in enumerate(reader.pages, start=1):
    text=page.extract_text() or ''
    hits=[t for t in terms if t.lower() in text.lower()]
    if hits:
        print('\n===== PAGE', i, 'HITS', hits, '=====')
        for line in text.splitlines():
            if any(t.lower() in line.lower() for t in terms):
                print(line)
