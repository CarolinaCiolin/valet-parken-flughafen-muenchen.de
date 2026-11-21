"""Refresh only the offline video/captions/poster after an audio or motion render."""
from pathlib import Path
import shutil,base64,re
R=Path(__file__).resolve().parents[1];M=R/'media/valet-erklaervideo';O=R/'vorschau/affiliate-video-2026-11'
for source,target in [('valet-parken-muenchen-erklaervideo.mp4','erklaervideo.mp4'),('untertitel-de.srt','untertitel-de.srt'),('untertitel-de.vtt','untertitel-de.vtt'),('szene-2.png','video-poster.png')]:shutil.copy(M/source,O/target)
# Blob-backed subtitles also work after opening the fully unpacked index.html via file://.
script='<script data-vpm-captions>document.querySelectorAll("track[data-vpm-vtt]").forEach(t=>{const b=Uint8Array.from(atob(t.dataset.vpmVtt),c=>c.charCodeAt(0));t.src=URL.createObjectURL(new Blob([b],{type:"text/vtt"}));});</script>'
b64=base64.b64encode((M/'untertitel-de.vtt').read_bytes()).decode()
for name in ['index.html','startseite.html']:
 p=O/name;s=p.read_text();s=re.sub(r'<script data-vpm-captions>.*?</script>','',s,flags=re.S)
 def track(m):
  tag=re.sub(r'\s+(?:src|data-vpm-vtt)="[^"]*"','',m[0]);return tag.replace('<track','<track data-vpm-vtt="'+b64+'"',1)
 s=re.sub(r'<track\b[^>]*>',track,s)
 s=s.replace('</body>',script+'</body>')if'</body>'in s else s.replace('</html>',script+'</html>');p.write_text(s)
print('Offline video, poster and file-compatible captions refreshed')
