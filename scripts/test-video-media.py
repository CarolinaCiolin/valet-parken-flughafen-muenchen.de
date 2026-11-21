"""Validate revised media, approved captions, timing and actual music in the mix."""
from pathlib import Path
import json,re,subprocess
import numpy as np
R=Path(__file__).resolve().parents[1];M=R/'media/valet-erklaervideo';P=M/'projekt';T=R/'.runtime/video-revision';T.mkdir(parents=True,exist_ok=True)
def run(args):return subprocess.check_output(args,stderr=subprocess.DEVNULL)
def probe(path):return json.loads(run(['ffprobe','-v','error','-show_format','-show_streams','-of','json',str(path)]))
def normalize(path):
    p=subprocess.run(['ffmpeg','-hide_banner','-i',str(path),'-af','loudnorm=I=-16:TP=-1.5:LRA=8:print_format=json','-f','null','-'],capture_output=True,text=True,check=True)
    return json.loads(re.findall(r'\{[^{}]+\}',p.stderr)[-1])
def decode(path):
    data=run(['ffmpeg','-v','error','-i',str(path),'-vn','-ac','2','-ar','48000','-f','f32le','-'])
    return np.frombuffer(data,dtype='<f4').reshape(-1,2)
f=probe(M/'valet-parken-muenchen-erklaervideo.mp4');video=next(s for s in f['streams']if s['codec_type']=='video');audio=next(s for s in f['streams']if s['codec_type']=='audio')
assert (video['width'],video['height'],video['codec_name'])==(1920,1080,'h264')
assert audio['codec_name']=='aac' and audio['sample_rate']=='48000' and audio['channels']==2
expected=json.loads((P/'design.json').read_text())['duration'];assert abs(float(f['format']['duration'])-expected)<.1
approved=(M/'sprechertext.txt').read_text();srt=(M/'untertitel-de.srt').read_text();caption=re.sub(r'^\d+\n|^.*-->.*\n','',srt,flags=re.M);norm=lambda x:re.sub(r'\W+','',x).casefold()
assert norm(caption)==norm(approved)
assert (M/'untertitel-de.vtt').read_text()=='WEBVTT\n\n'+re.sub(r'(\d{2}:\d{2}:\d{2}),(\d{3})',r'\1.\2',srt)
a=json.loads((P/'sprachzeiten.json').read_text());d=json.loads((P/'design.json').read_text());assert d['duration']-d['chapters'][-1]>=6
assert a['recognized_character_coverage']>=.9 and a['segments'][-1]['end']<d['duration']-1
speech=json.loads((P/'sprechpruefung.json').read_text());assert speech['speech_only_aliases']['Valet-Parken']=='Wallet-Parken';assert 'wallet' in speech['recognized_text'].casefold();assert approved.startswith('Valet-Parken')
audit={}
if speech.get('endpoint_revision'):
 import wave,hashlib
 audit=json.loads((P/'endpunkt-korrektur.json').read_text())
 def pcm(path):
  with wave.open(str(path),'rb')as w:return w.getframerate(),np.frombuffer(w.readframes(w.getnframes()),dtype='<i2')
 sr0,base=pcm(P/'sprecher-basis-freigegeben.wav');sr1,now=pcm(M/'sprecher-maennlich.wav');assert sr0==sr1==24000;lo=round(audit['middle_start']*sr0);hi=round(audit['middle_end']*sr0);assert base[lo:hi].tobytes()==now[lo:hi].tobytes();assert hashlib.sha256(now[lo:hi].tobytes()).hexdigest()==audit['middle_pcm_sha256_before']
 assert not np.array_equal(base[:lo],now[:lo]);assert audit['middle_preserved_byte_for_byte']
# Compare against the exact previously supplied narration when that local fixture exists.
previous=R/'.runtime/video-endpoints/previous-narration.wav'
if previous.exists() and speech.get('endpoint_revision'):
 _,old_pcm=pcm(previous);assert old_pcm[:round(2.35*sr1)].tobytes()==now[:round(2.35*sr1)].tobytes()
 assert old_pcm[round(8.84*sr1):round(41.4*sr1)].tobytes()==now[round(8.84*sr1):round(41.4*sr1)].tobytes()
 assert old_pcm[round(2.35*sr1):round(8.84*sr1)].tobytes()!=now[round(2.35*sr1):round(8.84*sr1)].tobytes()
if audit.get('accepted_closing_preserved'):
 _,prior=pcm(P/'sprecher-vor-aussprachekorrektur.wav');window=audit['closing']['replaced_old_single_letter'];start=round(window['start']*sr1);end=round(window['end']*sr1);insert_size=len(now)-len(prior)+end-start
 assert prior[round(8.84*sr1):start].tobytes()==now[round(8.84*sr1):start].tobytes()
 assert prior[end:].tobytes()==now[start+insert_size:].tobytes()
# D and E must be two independently sourced letter clips, with a real silent gap.
if speech.get('endpoint_revision') and 'separate_letters' in audit.get('closing',{}):
 letters=audit['closing']['separate_letters'];assert [x['letter'] for x in letters]==['D','E'];assert all(x['duration']>.09 for x in letters)
 assert audit['closing']['gap_between_letters_seconds']>=.18
 assert audit['intro']['parkservice_without_hyphen'] and speech['speech_only_aliases']['Parkservice']=='Parkservice'
 letter_words=[w for w in speech['recognized_words']if w['start']>=41.4 and w['text'] in ['D','E']];assert [w['text']for w in letter_words]==['D','E']
 for word in letter_words:
  clip=now[round(word['start']*sr1):round(word['end']*sr1)].astype(float)/32768;assert np.sqrt(np.mean(clip*clip))>.025,('Letter clip must contain audible speech',word)
 gap_start=round(letter_words[0]['end']*sr1);gap_end=round(letter_words[1]['start']*sr1);assert gap_end-gap_start>=round(.18*sr1);assert np.max(np.abs(now[gap_start+round(.005*sr1):gap_end-round(.005*sr1)]))==0
# Recreate the actual music branch. Stereo difference cancels centered speech.
music=T/'musik-normalisiert.wav';subprocess.run(['ffmpeg','-y','-v','error','-i',str(M/'hintergrundmusik-original.wav'),'-af',f"loudnorm=I={d['music_lufs']}:TP=-3:LRA=5,afade=t=out:st={d['duration']-d['ending_fade_seconds']}:d={d['ending_fade_seconds']}",'-ar','48000',str(music)],check=True)
mixed=decode(M/'valet-parken-muenchen-erklaervideo.mp4');reference=decode(music);n=min(len(mixed),len(reference));sl=slice(48000*2,min(n,48000*48));x=(reference[sl,0]-reference[sl,1])/2;y=(mixed[sl,0]-mixed[sl,1])/2
coefficient=float(np.dot(x,y)/np.dot(x,x));correlation=float(np.corrcoef(x,y)[0,1]);assert coefficient>.65 and correlation>.8,('Music missing/too weak in actual mix',coefficient,correlation)
letter_mix_checks=[]
if audit.get('closing',{}).get('separate_letters'):
 voice_ref=T/'voice-normalisiert.wav';subprocess.run(['ffmpeg','-y','-v','error','-i',str(M/'sprecher-maennlich.wav'),'-af','loudnorm=I=-16:TP=-1.5:LRA=8','-ar','48000',str(voice_ref)],check=True)
 voice_pcm=decode(voice_ref).mean(axis=1);recovered=mixed[:n].mean(axis=1)-reference[:n].mean(axis=1)
 for word in letter_words:
  lo=round((word['start']+.01)*48000);hi=round((word['end']-.01)*48000);corr=float(np.corrcoef(voice_pcm[lo:hi],recovered[lo:hi])[0,1]);assert corr>.95,('Wrong letter audio in actual MP4',word,corr)
  letter_mix_checks.append({'letter':word['text'],'actual_mp4_voice_correlation':corr})
silence_check=subprocess.run(['ffmpeg','-hide_banner','-i',str(M/'sprecher-maennlich.wav'),'-af','silencedetect=noise=-42dB:d=0.85','-f','null','-'],capture_output=True,text=True,check=True)
long_silences=[float(x) for x in re.findall(r'silence_duration: ([0-9.]+)',silence_check.stderr)]
levels={name:normalize(path) for name,path in [('voice',M/'sprecher-maennlich.wav'),('music',music),('mix',M/'valet-parken-muenchen-erklaervideo.mp4')]}
assert -28<float(levels['music']['input_i'])<-24
assert float(levels['mix']['input_tp'])<0
result={'result':'passed','duration_seconds':float(f['format']['duration']),'trimmed_from_previous_seconds':round(58.52-float(f['format']['duration']),3),'video':{'codec':video['codec_name'],'width':video['width'],'height':video['height']},'audio':{'codec':audio['codec_name'],'sample_rate':audio['sample_rate']},'voice_model':a['voice'],'recognized_character_coverage':a['recognized_character_coverage'],'letter_audio_verified_in_actual_mp4':letter_mix_checks,'accepted_address_and_closing_preserved':bool(audit.get('accepted_closing_preserved')),'domain_suffix_separate_letters': [x['letter']for x in audit.get('closing',{}).get('separate_letters',[])],'gap_between_domain_letters_seconds':audit.get('closing',{}).get('gap_between_letters_seconds'),'parkservice_without_hyphen':audit.get('intro',{}).get('parkservice_without_hyphen',False),'approved_subtitles_unchanged':True,'approved_middle_pcm_unchanged':bool(speech.get('endpoint_revision')), 'requested_spoken_form':'Wallet','requested_form_in_recognition':True,'closing_cta_seconds':round(d['duration']-d['chapters'][-1],3),'detected_voice_silences_over_850ms':long_silences,'music_present_in_actual_mp4':{'stereo_side_correlation':correlation,'gain_coefficient':coefficient},'loudness':levels,'limits':['Automated transcription and mix checks do not replace the user’s listening assessment.','No YouTube upload or Hostinger deployment.']}
(R/'docs/ERKLAERVIDEO-PRUEFUNG.json').write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n');print('Revised video, captions and actual music mix passed')
