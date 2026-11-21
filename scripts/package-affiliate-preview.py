"""Package the completed offline review/video; never publish or deploy."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import hashlib
ROOT=Path(__file__).resolve().parents[1]
p=ROOT/'pakete';m=ROOT/'media/valet-erklaervideo';o=ROOT/'vorschau/affiliate-video-2026-11'
def archive(name,items):
    with ZipFile(p/name,'w',ZIP_DEFLATED) as z:
        for source,target in items:z.write(source,target)
    with ZipFile(p/name) as z:
        assert z.testzip() is None
        assert not any('.runtime' in n or 'admin-password' in n or n.endswith(('.onnx','.pyc')) for n in z.namelist())
    print(name,(p/name).stat().st_size)
archive('vpm-affiliate-webvorschau.zip',[(f,str(f.relative_to(o))) for f in sorted(o.rglob('*')) if f.is_file()])
upload=['valet-parken-muenchen-erklaervideo.mp4','thumbnail-youtube.png','untertitel-de.srt','youtube-titel.txt','youtube-beschreibung.txt','README.md','projekt/STIMMENMODELL-LIZENZ.txt','projekt/fonts/LIZENZ.txt']
archive('vpm-youtube-upload.zip',[(m/n,n) for n in upload])
archive('vpm-erklaervideo-projekt.zip',[(f,str(f.relative_to(m))) for f in sorted(m.rglob('*')) if f.is_file() and f.name!='render.log'])
archive('vpm-affiliate-entwurfspaket.zip',[(p/'vpm-providers.zip','vpm-providers.zip'),(p/'vpm-content-drafts.zip','vpm-content-drafts.zip'),(ROOT/'docs/AFFILIATE-VIDEO-VORSCHAU.md','ANLEITUNG.md')])
(p/'SHA256SUMS.txt').write_text(''.join(hashlib.sha256(f.read_bytes()).hexdigest()+'  '+f.name+'\n' for f in sorted(p.glob('*.zip'))))
