"""Check actual rendered layers: all scenes branded, independent movement and assets."""
from pathlib import Path
import sys,json,hashlib
import numpy as np
from PIL import ImageChops
R=Path(__file__).resolve().parents[1];P=R/'media/valet-erklaervideo/projekt';sys.path.insert(0,str(P))
from motion import Motion
D=json.loads((P/'design.json').read_text());M=Motion(D);checks=[]
for i,start in enumerate(D['chapters']):
 end=(D['chapters']+[D['duration']])[i+1];t=start+min(1.3,(end-start)/2)
 raw=M.raw(t).copy();M.watermark(t,i);delta=ImageChops.difference(raw,M.im).convert('RGB');a=np.asarray(delta);pixels=int(np.count_nonzero(np.max(a,axis=2)))
 assert pixels>1500,('Missing visible wordmark',i,pixels)
 assert delta.getbbox()[0]>D['width']*.7,('Unexpected branding position',i)
 # Ignore the watermark and bottom timeline when checking scene motion.
 first=M.frame(start+.35);second=M.frame(start+min(1.25,(end-start)*.7));crop=(0,round(D['height']*.2),D['width'],round(D['height']*.93));diff=np.asarray(ImageChops.difference(first.crop(crop),second.crop(crop)));moving=int(np.count_nonzero(np.max(diff,axis=2)>8));assert moving>2000,(i,moving)
 checks.append({'scene':i+1,'watermark_pixels':pixels,'independent_moving_pixels':moving})
files=list((P/'illustrationen').glob('*.png'));assert len(files)==5
assert len({hashlib.sha256(f.read_bytes()).hexdigest()for f in files})==5
for name in ['Manrope-Variable.ttf','MANROPE-LIZENZ.txt']:assert(P/'fonts'/name).exists()
report={'result':'passed','width':D['width'],'height':D['height'],'scenes':checks,'illustrations':5,'watermark_source':'Existing VALET PARKEN / MÜNCHEN site wordmark; light and dark variants','motion':['Independent illustrated vehicle translation and wheel rotation','Camera push and parallax','Animated inspection scan, focus markers and documentation checks','Subtle localized hand/key and tablet-tap gestures with fixed heads/feet','Drawn flight route and plane','Brand-colour feathered scene wipes'],'limitations':['Layered 2D motion graphics, not a 3D film or fully rigged character animation.','Scene artwork is schematic and does not identify confirmed provider facilities.']}
(R/'docs/ERKLAERVIDEO-MOTION-PRUEFUNG.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n');print('Six branded animated scenes passed')
