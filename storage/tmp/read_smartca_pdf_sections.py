from pathlib import Path
from pypdf import PdfReader
p=Path(r'C:\Users\thien\Downloads\Kich_ban_tich_hop_smartca_v4.1.pdf')
text='\n'.join(page.extract_text() or '' for page in PdfReader(str(p)).pages)
for pat in ['4.1 Thông tin chung','4.2 v1/credentials/get_certificate','4.3 v2/signatures/sign','4.4 v2/signatures/confirm','mobile']:
    print('\n---',pat)
    idx=text.lower().find(pat.lower())
    print(text[max(0,idx-300):idx+2500] if idx>=0 else 'not found')
