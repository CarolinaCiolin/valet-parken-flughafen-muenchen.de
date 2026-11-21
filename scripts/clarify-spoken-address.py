"""Give just the spoken address more time; keep every approved middle sample."""
from pathlib import Path
import json,subprocess,wave,tempfile,hashlib
import numpy as np
R=Path(__file__).resolve().parents[1];M=R/'media/valet-erklaervideo';P=M/'projekt';audit=json.loads((P/'endpunkt-korrektur.json').read_text())
if audit.get('address_clarity'):
 print('Address pacing already applied');raise SystemExit(0)
meta=json.loads((P/'sprechpruefung.json').read_text());words=meta['recognized_words']
norm=lambda s:''.join(c for c in s.casefold()if c.isalnum())
auf=max(i for i,w in enumerate(words)if norm(w['text'])=='auf');den=next(i for i in range(auf+1,len(words)-1)if norm(words[i]['text'])=='den'and norm(words[i+1]['text'])=='link')
with wave.open(str(M/'sprecher-maennlich.wav'),'rb')as f:sr=f.getframerate();original=np.frombuffer(f.readframes(f.getnframes()),dtype='<i2').copy()
def quiet(t):
 choices=[]
 for x in np.arange(t-.10,t+.1001,.005):
  a=original[round((x-.012)*sr):round((x+.012)*sr)].astype(float)/32768
  choices.append((float(np.sqrt(np.mean(a*a))),float(x)))
 return min(choices)[1]
start=quiet(words[auf]['end']);end=quiet(words[den]['start']);assert 1.5<end-start<7
lo=round(start*sr);hi=round(end*sr);part=original[lo:hi];tempo=min(1.0,max(.78,(end-start)/3.9))
with tempfile.TemporaryDirectory()as tmp:
 a=Path(tmp)/'address.wav';b=Path(tmp)/'clear.wav'
 with wave.open(str(a),'wb')as w:w.setnchannels(1);w.setsampwidth(2);w.setframerate(sr);w.writeframes(part.tobytes())
 subprocess.run(['ffmpeg','-y','-v','error','-i',str(a),'-af',f'atempo={tempo:.9f}','-c:a','pcm_s16le',str(b)],check=True)
 with wave.open(str(b),'rb')as w:stretched=np.frombuffer(w.readframes(w.getnframes()),dtype='<i2').copy()
# Boundaries are picked in low-energy transitions; tiny fades avoid audible seams.
def fade(data,which):
 a=data.astype(float);n=min(round(.006*sr),len(a)//4)
 if which=='in':a[:n]*=np.linspace(0,1,n)
 else:a[-n:]*=np.linspace(1,0,n)
 return np.clip(np.rint(a),-32768,32767).astype('<i2')
prefix=fade(original[:lo],'out');suffix=fade(original[hi:],'in');stretched=fade(fade(stretched,'in'),'out');pre=round(.10*sr);post=round(.13*sr)
new=np.concatenate([prefix,np.zeros(pre,dtype='<i2'),stretched,np.zeros(post,dtype='<i2'),suffix]);shift=(len(new)-len(original))/sr
body_lo=round(audit['middle_start']*sr);body_hi=round(audit['middle_end']*sr);assert new[body_lo:body_hi].tobytes()==original[body_lo:body_hi].tobytes()
with wave.open(str(M/'sprecher-maennlich.wav'),'wb')as w:w.setnchannels(1);w.setsampwidth(2);w.setframerate(sr);w.writeframes(new.tobytes())
def mapped(t):
 if t<=start:return t
 if t>=end:return t+shift
 return start+.10+(t-start)/tempo
for w in words:w['start']=round(mapped(w['start']),4);w['end']=round(mapped(w['end']),4)
meta['duration']=len(new)/sr;meta['address_pacing_adjusted']=True
(P/'sprechpruefung.json').write_text(json.dumps(meta,ensure_ascii=False,indent=2)+'\n')
audit['address_clarity']={'start_before':round(start,4),'end_before':round(end,4),'duration_before':round(end-start,4),'duration_after':round(len(stretched)/sr,4),'tempo_factor':tempo,'pause_before':.10,'pause_after':.13,'changes_confined_to_closing':True}
audit['duration']=len(new)/sr;audit['closing']['duration']=len(new[body_hi:])/sr;audit['middle_pcm_sha256_after']=hashlib.sha256(new[body_lo:body_hi].tobytes()).hexdigest()
(P/'endpunkt-korrektur.json').write_text(json.dumps(audit,ensure_ascii=False,indent=2)+'\n')
with wave.open(str(P/'sprecher-abschluss-korrigiert.wav'),'wb')as w:w.setnchannels(1);w.setsampwidth(2);w.setframerate(sr);w.writeframes(new[body_hi:].tobytes())
print('Spoken address given',round(len(stretched)/sr,2),'seconds plus clear pauses; approved middle unchanged')
