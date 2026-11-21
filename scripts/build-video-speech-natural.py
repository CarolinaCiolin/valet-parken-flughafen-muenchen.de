"""Generate an original German male neural voice; no cloning or visitor data."""
from pathlib import Path
import json, os, subprocess, tempfile, time, urllib.request
import numpy as np
import soundfile as sf
import torch
from qwen_tts import Qwen3TTSModel
ROOT=Path(__file__).resolve().parents[1];OUT=ROOT/'media/valet-erklaervideo';P=OUT/'projekt'
MODEL='Qwen/Qwen3-TTS-12Hz-1.7B-VoiceDesign'
# Speech-only spelling hints. Public script and subtitles remain unchanged.
approved=(OUT/'sprechertext.txt').read_text().strip()
aliases={'Shuttletransfer':'Schattel-Transfer','Parkservice':'Park-Sörwiss','Valet-Service':'Valet-Sörwiss','Check-in':'Tschek-in'}
spoken=approved
for original,pronounced in aliases.items():spoken=spoken.replace(original,pronounced)
instruction=('A native German male narrator in his mid-thirties with a warm, pleasant, clear medium-low voice. '
 'Speak fluent standard German in a natural, conversational explanatory style, like a friendly professional travel presenter. '
 'Smooth connected phrases, varied gentle intonation, no robotic cadence, no dramatic announcement, no sentence-by-sentence stop-start delivery. '
 'Moderately brisk but relaxed, about 145 words per minute. Short natural pauses only. '
 'Pronounce the English loanwords naturally: shuttle is shuttl, service is sur-viss, check-in is check-in. '
 'Clean dry studio voice, without background music or sound effects.')
torch.set_num_threads(min(4,os.cpu_count() or 2));torch.manual_seed(4187)
print('Loading',MODEL,flush=True)
model=Qwen3TTSModel.from_pretrained(MODEL,device_map='cpu',dtype=torch.float32,attn_implementation='eager')
print('Generating full continuous narration',flush=True);start=time.monotonic()
wavs,sr=model.generate_voice_design(text=spoken,language='German',instruct=instruction,non_streaming_mode=True,max_new_tokens=1250,temperature=.65,top_p=.85,repetition_penalty=1.08)
a=np.asarray(wavs[0],dtype=np.float32);assert np.isfinite(a).all() and len(a)>sr*25
with tempfile.TemporaryDirectory() as tmp:
 raw=Path(tmp)/'voice.wav';trimmed=Path(tmp)/'trimmed.wav';sf.write(raw,a,sr)
 subprocess.run(['ffmpeg','-y','-v','error','-i',str(raw),'-af','silenceremove=start_periods=1:start_duration=0.05:start_threshold=-48dB,areverse,silenceremove=start_periods=1:start_duration=0.1:start_threshold=-48dB,areverse',str(trimmed)],check=True)
 a,sr=sf.read(trimmed);duration=len(a)/sr
 # Preserve complete words; speed adjustment is limited and never truncates the script.
 factor=max(1.0,duration/49.5);assert factor<1.32,('Unexpectedly slow narration',duration)
 filters=f'atempo={factor:.7f},adelay=200:all=1,loudnorm=I=-16:TP=-1.5:LRA=8'
 subprocess.run(['ffmpeg','-y','-v','error','-i',str(trimmed),'-af',filters,'-ar','24000','-ac','1','-c:a','pcm_s16le',str(OUT/'sprecher-maennlich.wav')],check=True)
a,sr=sf.read(OUT/'sprecher-maennlich.wav');duration=len(a)/sr
# Word timestamps provide the new scene timings and subtitle alignment.
print('Transcribing for timing/quality inspection',flush=True)
from faster_whisper import WhisperModel
asr=WhisperModel('small',device='cpu',compute_type='int8',cpu_threads=min(4,os.cpu_count() or 2))
segments,info=asr.transcribe(str(OUT/'sprecher-maennlich.wav'),language='de',beam_size=5,word_timestamps=True,initial_prompt=approved)
words=[]
for segment in segments:
 for w in segment.words:words.append({'start':round(w.start,4),'end':round(w.end,4),'text':w.word.strip()})
assert words
(P/'sprechpruefung.json').write_text(json.dumps({'model':MODEL,'sample_rate':sr,'duration':duration,'generation_seconds':round(time.monotonic()-start,1),'speech_only_aliases':aliases,'instruction':instruction,'tempo_factor':factor,'recognized_words':words,'recognized_text':' '.join(w['text'] for w in words)},ensure_ascii=False,indent=2)+'\n')
license_url='https://huggingface.co/'+MODEL+'/raw/main/LICENSE'
try:
 with urllib.request.urlopen(license_url,timeout=30) as r:license_text=r.read().decode()
except Exception:
 with urllib.request.urlopen('https://raw.githubusercontent.com/QwenLM/Qwen3-TTS/main/LICENSE',timeout=30) as r:license_text=r.read().decode()
(P/'STIMMENMODELL-LIZENZ.txt').write_text(license_text+'\nModell: '+MODEL+'\nQuelle: '+license_url+'\nOriginale generierte Männerstimme; keine geklonte reale Person.\n')
print('Natural narration ready:',duration,'seconds',flush=True)
