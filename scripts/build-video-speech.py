"""Build the approved German male narration with a CC0 Piper voice. No credentials required."""
from pathlib import Path
import json,hashlib,urllib.request,wave,sys
import numpy as np
from piper import PiperVoice,SynthesisConfig
ROOT=Path(__file__).resolve().parents[1];OUT=ROOT/'media/valet-erklaervideo';CACHE=ROOT/'.runtime/video-voice-high';CACHE.mkdir(parents=True,exist_ok=True)
BASE='https://huggingface.co/rhasspy/piper-voices/resolve/main/de/de_DE/thorsten/high/'
for name in ['de_DE-thorsten-high.onnx','de_DE-thorsten-high.onnx.json','MODEL_CARD']:
 p=CACHE/name
 if not p.exists():urllib.request.urlretrieve(BASE+name,p)
card=(CACHE/'MODEL_CARD').read_text();(OUT/'projekt/STIMMENMODELL-LIZENZ.txt').write_text(card+'\nQuelle: '+BASE+'MODEL_CARD\n')
segments=json.loads((OUT/'projekt/sprechsegmente.json').read_text());approved=(OUT/'sprechertext.txt').read_text().strip();assert ' '.join(' '.join(segments).split())==' '.join(approved.split())
voice=PiperVoice.load(CACHE/'de_DE-thorsten-high.onnx');sr=voice.config.sample_rate
parts=[]
for i,text in enumerate(segments):
 path=CACHE/f'segment-{i}.wav'
 with wave.open(str(path),'wb')as w:voice.synthesize_wav(text,w,syn_config=SynthesisConfig(length_scale=1.03,noise_scale=0.55,noise_w_scale=0.65))
 with wave.open(str(path),'rb')as r:parts.append(np.frombuffer(r.readframes(r.getnframes()),dtype='<i2').astype(np.float64)/32768)
# Keep natural local speech, with short pauses. Timing is derived from the resulting waveform.
raw=sum(len(a)/sr for a in parts)+.3*(len(parts)-1)
if not 51<=raw<=61:
 factor=min(1.20,max(.86,56/raw));parts2=[]
 for i,text in enumerate(segments):
  path=CACHE/f'segment-timed-{i}.wav'
  with wave.open(str(path),'wb')as w:voice.synthesize_wav(text,w,syn_config=SynthesisConfig(length_scale=1.03*factor,noise_scale=.55,noise_w_scale=.65))
  with wave.open(str(path),'rb')as r:parts2.append(np.frombuffer(r.readframes(r.getnframes()),dtype='<i2').astype(np.float64)/32768)
 parts=parts2
speech=[];alignment=[];at=.35;speech.append(np.zeros(round(at*sr)))
for i,(text,data)in enumerate(zip(segments,parts)):
 start=at;end=at+len(data)/sr;alignment.append({'index':i,'start':round(start,4),'end':round(end,4),'text':text});speech.append(data);speech.append(np.zeros(round(.3*sr)));at=end+.3
samples=np.concatenate(speech);samples*=.88/max(.88,float(np.max(np.abs(samples))))
with wave.open(str(OUT/'sprecher-maennlich.wav'),'wb')as w:w.setnchannels(1);w.setsampwidth(2);w.setframerate(sr);w.writeframes((samples*32767).astype('<i2').tobytes())
(OUT/'projekt/sprachzeiten.json').write_text(json.dumps({'sample_rate':sr,'duration':round(len(samples)/sr,4),'segments':alignment,'voice':'de_DE-thorsten-high','source':BASE,'model_sha256':hashlib.sha256((CACHE/'de_DE-thorsten-high.onnx').read_bytes()).hexdigest()},ensure_ascii=False,indent=2)+'\n')
print('Narration rendered:',round(len(samples)/sr,2),'seconds; approved wording retained')
