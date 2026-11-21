"""Replace only the narration's endpoints; retain approved middle PCM byte-for-byte."""
from pathlib import Path
import hashlib,json,os,subprocess,tempfile,wave,time,sys,traceback
import numpy as np
import soundfile as sf
import torch
from qwen_tts import Qwen3TTSModel
from faster_whisper import WhisperModel
R=Path(__file__).resolve().parents[1];M=R/'media/valet-erklaervideo';P=M/'projekt'
def endpoint_error(kind,error,tb):
 summary=os.environ.get('GITHUB_STEP_SUMMARY')
 if summary:
  with open(summary,'a')as out:out.write('## Endpoint synthesis diagnostic\n\n'+kind.__name__+': '+str(error)+'\n')
 sys.__excepthook__(kind,error,tb)
sys.excepthook=endpoint_error
BASE=P/'sprecher-basis-freigegeben.wav';REF=P/'narrator-middle-reference.wav'
with wave.open(str(BASE),'rb')as w:sr=w.getframerate();baseline=np.frombuffer(w.readframes(w.getnframes()),dtype='<i2').copy()
assert sr==24000
body_start=round(2.35*sr);body_end=round(41.4*sr);middle=baseline[body_start:body_end]
reference_text='Bei der Übergabe wird der Fahrzeugzustand dokumentiert. Anschließend bringt ein Fahrer Ihr Auto zum gebuchten Stellplatz.'
texts={
 'intro':"Ganz entspannt geht es los. Wallet-Parken am Flughafen München. Sie fahren direkt zum Terminal und übergeben Ihr Auto am vereinbarten Treffpunkt an den gebuchten Park-Sörwiss.",
 'closing':"So beginnt Ihr Urlaub schon am Flughafen, mit kurzen Wegen und ohne Parkplatzstress. Buchen Sie Ihren Wallet-Sörwiss am Flughafen München auf: Wallet, parken, Flughafen, München, Punkt deh. Den Link finden Sie in der Videobeschreibung."
}
torch.set_num_threads(min(4,os.cpu_count()or 2));torch.manual_seed(32041)
model=Qwen3TTSModel.from_pretrained('Qwen/Qwen3-TTS-12Hz-1.7B-Base',device_map='cpu',dtype=torch.float32,attn_implementation='eager')
asr=None
def recognize(path):
 segments,_=asr.transcribe(str(path),language='de',beam_size=5,word_timestamps=True,initial_prompt='Wallet-Parken, Parkservice, Flughafen München.')
 return [{'start':float(w.start),'end':float(w.end),'text':w.word.strip()}for s in segments for w in s.words]
def normalized(s):return ''.join(c for c in s.casefold()if c.isalnum())
def level(data):
 a=data.astype(float)/32768 if data.dtype==np.int16 else data
 active=a[np.abs(a)>.025];return float(np.sqrt(np.mean(active*active)))
def fit(raw,rate,target=None):
 with tempfile.TemporaryDirectory()as tmp:
  a=Path(tmp)/'in.wav';b=Path(tmp)/'out.wav';sf.write(a,raw,rate)
  factor=len(raw)/rate/target if target else 1.0
  assert .80<=factor<=1.18,('Endpoint needs a more natural take, not aggressive speed processing',factor)
  subprocess.run(['ffmpeg','-y','-v','error','-i',str(a),'-af',f'atempo={factor:.8f}','-ar',str(sr),'-ac','1',str(b)],check=True)
  data,rr=sf.read(b);assert rr==sr
  gain=min(1.7,max(.60,level(baseline[round(14.66*sr):round(21.5*sr)])/max(.01,level(data))))
  data*=gain
  if np.max(np.abs(data))>.94:data*=.94/np.max(np.abs(data))
  fade=min(round(.008*sr),len(data)//4);data[:fade]*=np.linspace(0,1,fade);data[-fade:]*=np.linspace(1,0,fade)
  return np.clip(np.rint(data*32768),-32768,32767).astype('<i2'),factor
parts={};audit={};start=time.monotonic()
with tempfile.TemporaryDirectory()as tmp:
 for key,text in texts.items():
  print('Generate contextual endpoint:',key,flush=True)
  wavs,rate=model.generate_voice_clone(text=text,language='German',ref_audio=str(REF),ref_text=reference_text,non_streaming_mode=True,max_new_tokens=650,temperature=.68,top_p=.88,repetition_penalty=1.08)
  raw=np.asarray(wavs[0]);path=Path(tmp)/(key+'.wav');sf.write(path,raw,rate)
  print('Contextual endpoint synthesized:',key,flush=True)
 # Avoid retaining both large inference engines during synthesis on the CPU runner.
 del model,wavs
 import gc
 gc.collect()
 asr=WhisperModel('small',device='cpu',compute_type='int8',cpu_threads=min(4,os.cpu_count()or 2))
 for key,text in texts.items():
  path=Path(tmp)/(key+'.wav');raw,rate=sf.read(path);words=recognize(path);assert words
  if key=='intro':
   first=next(i for i,w in enumerate(words)if normalized(w['text']).startswith(('wallet','valet')))
   last=next(i for i in range(first+1,len(words))if 'münchen'in words[i]['text'].casefold())
  else:
   first=next(i for i,w in enumerate(words)if normalized(w['text'])in ['buchen','bucht']);last=next(i for i in range(first,len(words))if 'videobeschreibung'in normalized(words[i]['text']))
  begin=max(0,words[first]['start']-.055);end=min(len(raw)/rate,words[last]['end']+.09)
  segment=raw[round(begin*rate):round(end*rate)];selected=words[first:last+1]
  data,factor=fit(segment,rate,2.19 if key=='intro'else None)
  prefix=.08 if key=='intro'else .34
  data=np.concatenate([np.zeros(round(prefix*sr),dtype='<i2'),data])
  if key=='intro':
   assert len(data)<=body_start;data=np.pad(data,(0,body_start-len(data)))
  aligned=[{'start':round(prefix+(w['start']-begin)/factor,4),'end':round(prefix+(w['end']-begin)/factor,4),'text':w['text']}for w in selected]
  parts[key]=(data,aligned)
  with wave.open(str(P/('sprecher-einstieg-korrigiert.wav'if key=='intro'else'sprecher-abschluss-korrigiert.wav')),'wb')as w:w.setnchannels(1);w.setsampwidth(2);w.setframerate(sr);w.writeframes(data.tobytes())
  audit[key]={'context_text':text,'recognized_context':' '.join(w['text']for w in words),'selected_words':' '.join(w['text']for w in selected),'tempo_factor':factor,'duration':len(data)/sr}
intro,intro_words=parts['intro'];closing,closing_words=parts['closing'];joined=np.concatenate([intro,middle,closing])
assert joined[body_start:body_end].tobytes()==middle.tobytes()
with wave.open(str(M/'sprecher-maennlich.wav'),'wb')as w:w.setnchannels(1);w.setsampwidth(2);w.setframerate(sr);w.writeframes(joined.tobytes())
old=json.loads((P/'sprechpruefung.json').read_text());words=intro_words+[w for w in old['recognized_words']if w['start']>=2.35 and w['end']<=41.4]+[dict(w,start=round(w['start']+41.4,4),end=round(w['end']+41.4,4))for w in closing_words]
old.update(duration=len(joined)/sr,recognized_words=words,recognized_text=' '.join(w['text']for w in words),endpoint_revision=True)
old['speech_only_aliases']['valet-parken-flughafen-muenchen.de']='Wallet, parken, Flughafen, München, Punkt deh'
(P/'sprechpruefung.json').write_text(json.dumps(old,ensure_ascii=False,indent=2)+'\n')
audit.update({'sample_rate':sr,'duration':len(joined)/sr,'middle_start':2.35,'middle_end':41.4,'middle_pcm_sha256_before':hashlib.sha256(middle.tobytes()).hexdigest(),'middle_pcm_sha256_after':hashlib.sha256(joined[body_start:body_end].tobytes()).hexdigest(),'middle_preserved_byte_for_byte':True,'generation_seconds':round(time.monotonic()-start,1),'baseline_file_sha256':hashlib.sha256(BASE.read_bytes()).hexdigest(),'timing_note':'Context is removed; retained middle samples and their absolute times are unchanged.'})
(P/'endpunkt-korrektur.json').write_text(json.dumps(audit,ensure_ascii=False,indent=2)+'\n')
print('Endpoint patch ready; approved middle PCM unchanged:',len(joined)/sr,'seconds',flush=True)
