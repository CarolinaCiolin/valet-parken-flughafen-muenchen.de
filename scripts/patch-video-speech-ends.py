"""Repair Parkservice and explicitly spell D E; keep approved body PCM unchanged."""
from pathlib import Path
import hashlib,json,os,subprocess,tempfile,wave,time,sys,contextlib
import numpy as np
import soundfile as sf
import torch
from qwen_tts import Qwen3TTSModel
from faster_whisper import WhisperModel
R=Path(__file__).resolve().parents[1];M=R/'media/valet-erklaervideo';P=M/'projekt'
def report_error(kind,error,tb):
 summary=os.environ.get('GITHUB_STEP_SUMMARY')
 if summary:
  with open(summary,'a')as out:out.write('## Speech correction diagnostic\n\n'+kind.__name__+': '+str(error)+'\n')
 sys.__excepthook__(kind,error,tb)
sys.excepthook=report_error
BASE=P/'sprecher-basis-freigegeben.wav';REF=P/'narrator-middle-reference.wav'
def read_pcm(path):
 with wave.open(str(path),'rb')as w:return w.getframerate(),np.frombuffer(w.readframes(w.getnframes()),dtype='<i2').copy()
sr,baseline=read_pcm(BASE);rr,current=read_pcm(M/'sprecher-maennlich.wav');assert sr==rr==24000
body_start=round(8.84*sr);body_end=round(41.4*sr);middle=baseline[body_start:body_end]
reference_text='Bei der Übergabe wird der Fahrzeugzustand dokumentiert. Anschließend bringt ein Fahrer Ihr Auto zum gebuchten Stellplatz.'
texts={
 'intro':'Ganz entspannt geht es los. Wallet-Parken am Flughafen München. Sie fahren direkt zum Terminal und übergeben Ihr Auto am vereinbarten Treffpunkt an den gebuchten Parkservice.',
 'closing':'So beginnt Ihr Urlaub schon am Flughafen, mit kurzen Wegen und ohne Parkplatzstress. Buchen Sie Ihren Wallet-Sörwiss am Flughafen München auf: Wallet, parken, Flughafen, München, Punkt. Den Link finden Sie in der Videobeschreibung.',
 'letters':'Die Buchstaben einzeln gesprochen: D wie Dora. E wie Emil. Danke.'
}
torch.set_num_threads(min(4,os.cpu_count()or 2));torch.manual_seed(32119)
model=Qwen3TTSModel.from_pretrained('Qwen/Qwen3-TTS-12Hz-1.7B-Base',device_map='cpu',dtype=torch.float32,attn_implementation='eager')
start=time.monotonic();audit={};asr=None
norm=lambda s:''.join(c for c in s.casefold()if c.isalnum())
def recognize(path):
 segments,_=asr.transcribe(str(path),language='de',beam_size=5,word_timestamps=True,initial_prompt='Wallet-Parken, Parkservice, Flughafen München. D wie Dora. E wie Emil.')
 return [{'start':float(w.start),'end':float(w.end),'text':w.word.strip()}for s in segments for w in s.words]
def fit(raw,rate,target=None):
 with tempfile.TemporaryDirectory()as tmp:
  a=Path(tmp)/'in.wav';b=Path(tmp)/'out.wav';sf.write(a,raw,rate);factor=len(raw)/rate/target if target else 1
  assert .65<=factor<=1.30,('Natural take outside mild tempo range',factor)
  subprocess.run(['ffmpeg','-y','-v','error','-i',str(a),'-af',f'atempo={factor:.8f}','-ar',str(sr),'-ac','1',str(b)],check=True);data,_=sf.read(b)
  ref=baseline[round(14.66*sr):round(21.5*sr)].astype(float)/32768
  def rms(x):
   a=x[np.abs(x)>.025];return max(.01,float(np.sqrt(np.mean(a*a))))
  data*=min(1.7,max(.6,rms(ref)/rms(data)))
  if max(abs(data))>.94:data*=.94/max(abs(data))
  fade=min(round(.005*sr),len(data)//4);data[:fade]*=np.linspace(0,1,fade);data[-fade:]*=np.linspace(1,0,fade)
  return np.rint(data*32768).astype('<i2'),factor
parts={};recognized={};takes=P/'sprech-takes';takes.mkdir(exist_ok=True)
(takes/'request.json').write_text(json.dumps({'texts':texts,'seed':32119},ensure_ascii=False,indent=2)+'\n')
with contextlib.nullcontext(str(takes))as tmp:
 for key,text in texts.items():
  print('Generate contextual correction:',key,flush=True)
  wavs,rate=model.generate_voice_clone(text=text,language='German',ref_audio=str(REF),ref_text=reference_text,non_streaming_mode=True,max_new_tokens=750,temperature=.64,top_p=.86,repetition_penalty=1.08)
  sf.write(Path(tmp)/(key+'.wav'),np.asarray(wavs[0]),rate)
  print('Saved raw take:',key,flush=True)
 del model,wavs
 import gc;gc.collect()
 asr=WhisperModel('small',device='cpu',compute_type='int8',cpu_threads=min(4,os.cpu_count()or 2))
 for key in texts:
  path=Path(tmp)/(key+'.wav');raw,rate=sf.read(path);words=recognize(path);assert words;parts[key]=(raw,rate,words)
  recognized[key]=' '.join(w['text']for w in words);print(key,recognized[key],flush=True)
  (takes/(key+'-words.json')).write_text(json.dumps(words,ensure_ascii=False,indent=2)+'\n')
 # The first title was accepted. Replace the complete following sentence, without a hyphenated phonetic alias.
 raw,rate,words=parts['intro'];first=next(i for i,w in enumerate(words)if norm(w['text'])=='sie');last=next(i for i in range(first,len(words))if any(k in norm(words[i]['text'])for k in ['service','servis','sörwiss','sörvis']))
 begin=max(0,words[first]['start']-.04);end=min(len(raw)/rate,words[last]['end']+.10)
 sentence,factor=fit(raw[round(begin*rate):round(end*rate)],rate,5.96)
 intro=np.concatenate([current[:round(2.35*sr)],np.zeros(round(.08*sr),dtype='<i2'),sentence]);assert len(intro)<=body_start;intro=np.pad(intro,(0,body_start-len(intro)))
 old=json.loads((P/'sprechpruefung.json').read_text());intro_words=[w for w in old['recognized_words']if w['end']<=2.35]+[dict(w,start=round(2.43+(w['start']-begin)/factor,4),end=round(2.43+(w['end']-begin)/factor,4))for w in words[first:last+1]]
 audit['intro']={'context_text':texts['intro'],'recognized_context':recognized['intro'],'duration':len(intro)/sr,'tempo_factor':factor,'parkservice_without_hyphen':True}
 # Extract the two actual letter names from unambiguous alphabet examples; the examples themselves are not used.
 raw,rate,words=parts['letters'];wie=[i for i,w in enumerate(words)if norm(w['text'])=='wie'];assert len(wie)>=2,recognized['letters'];letters=[];letter_info=[]
 for label,index in zip(['D','E'],wie[:2]):
  letter=words[index-1];begin=max(0,letter['start']-.035);end=min(words[index]['start'],letter['end']+.025)
  if end-begin<.12:
   previous_end=words[index-2]['end']if index>=2 else 0
   begin=max(previous_end+.01,words[index]['start']-.40);end=words[index]['start']
  assert end-begin>.09,(label,words)
  data,_=fit(raw[round(begin*rate):round(end*rate)],rate);letters.append(data);letter_info.append({'letter':label,'source_word':letter['text'],'duration':len(data)/sr})
 raw,rate,words=parts['closing'];first=next(i for i,w in enumerate(words)if norm(w['text'])in ['buchen','bucht']);dot=next(i for i in range(first,len(words))if norm(words[i]['text'])=='punkt');tail=next(i for i in range(dot+1,len(words))if norm(words[i]['text'])=='den');last=next(i for i in range(tail,len(words))if 'videobeschreibung'in norm(words[i]['text']))
 def segment(lo,hi):
  begin=max(0,words[lo]['start']-.035);end=min(len(raw)/rate,words[hi]['end']+.05);data,_=fit(raw[round(begin*rate):round(end*rate)],rate);return data,[dict(w,start=w['start']-begin,end=w['end']-begin)for w in words[lo:hi+1]]
 prefix,pwords=segment(first,dot);suffix,swords=segment(tail,last)
 silence=lambda n:np.zeros(round(n*sr),dtype='<i2')
 closing=np.concatenate([silence(.34),prefix,silence(.18),letters[0],silence(.20),letters[1],silence(.28),suffix])
 closing_words=[dict(w,start=w['start']+.34,end=w['end']+.34)for w in pwords];at=.34+len(prefix)/sr+.18
 for label,data in zip(['D','E'],letters):closing_words.append({'start':at,'end':at+len(data)/sr,'text':label});at+=len(data)/sr+(.20 if label=='D'else .28)
 closing_words += [dict(w,start=w['start']+at,end=w['end']+at)for w in swords]
 audit['closing']={'context_text':texts['closing'],'recognized_context':recognized['closing'],'duration':len(closing)/sr,'letter_source_context':texts['letters'],'letter_source_recognition':recognized['letters'],'separate_letters':letter_info,'gap_between_letters_seconds':.20}
joined=np.concatenate([intro,middle,closing]);assert joined[body_start:body_end].tobytes()==middle.tobytes()
for path,data in [(M/'sprecher-maennlich.wav',joined),(P/'sprecher-einstieg-korrigiert.wav',intro),(P/'sprecher-abschluss-korrigiert.wav',closing)]:
 with wave.open(str(path),'wb')as w:w.setnchannels(1);w.setsampwidth(2);w.setframerate(sr);w.writeframes(data.tobytes())
words=intro_words+[w for w in old['recognized_words']if w['start']>=8.84 and w['end']<=41.4]+[dict(w,start=round(w['start']+41.4,4),end=round(w['end']+41.4,4))for w in closing_words]
old.update(duration=len(joined)/sr,recognized_words=words,recognized_text=' '.join(w['text']for w in words),endpoint_revision=True)
old['speech_only_aliases'].update({'Parkservice':'Parkservice','valet-parken-flughafen-muenchen.de':'Wallet, parken, Flughafen, München, Punkt D E (zwei einzeln gesprochene Buchstaben)'})
(P/'sprechpruefung.json').write_text(json.dumps(old,ensure_ascii=False,indent=2)+'\n')
audit.update({'sample_rate':sr,'duration':len(joined)/sr,'middle_start':8.84,'middle_end':41.4,'middle_pcm_sha256_before':hashlib.sha256(middle.tobytes()).hexdigest(),'middle_pcm_sha256_after':hashlib.sha256(joined[body_start:body_end].tobytes()).hexdigest(),'middle_preserved_byte_for_byte':True,'generation_seconds':round(time.monotonic()-start,1),'baseline_file_sha256':hashlib.sha256(BASE.read_bytes()).hexdigest(),'timing_note':'Title retained; first complete Parkservice sentence repaired; D and E independently synthesized and spaced.'})
(P/'endpunkt-korrektur.json').write_text(json.dumps(audit,ensure_ascii=False,indent=2)+'\n');print('Pronunciation correction ready:',len(joined)/sr,'seconds',flush=True)
