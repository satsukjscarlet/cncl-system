from pathlib import Path
from pypdf import PdfReader
p=Path(r'C:\Users\thien\Downloads\Kich_ban_tich_hop_smartca_v4.1.pdf')
reader=PdfReader(str(p))
text='\n'.join(page.extract_text() or '' for page in reader.pages)
print('pages=', len(reader.pages), 'chars=', len(text))
for pat in ['gwsca','rmgateway','sp_id','sp_password','MobileCode','signatures/sign','calculateHash','get_certificate','mobile_code','mobileCode']:
    print('\n---',pat)
    low=text.lower(); idx=low.find(pat.lower())
    if idx>=0:
        print(text[max(0,idx-700):idx+1500])
    else:
        print('not found')
