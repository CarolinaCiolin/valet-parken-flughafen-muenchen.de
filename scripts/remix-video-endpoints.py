"""Reuse the approved animation and mix only the corrected narration endpoints."""
from pathlib import Path
import sys,json,subprocess,shutil
R=Path(__file__).resolve().parents[1];P=R/'media/valet-erklaervideo/projekt';M=P.parent;T=R/'.runtime/video-endpoints';T.mkdir(parents=True,exist_ok=True);sys.path.insert(0,str(P))
from render import music,subtitles,audio_filter,D
source=T/'approved-animation.mp4'
if not source.exists():shutil.copy(M/'valet-parken-muenchen-erklaervideo.mp4',source)
old=float(subprocess.check_output(['ffprobe','-v','error','-show_entries','format=duration','-of','default=noprint_wrappers=1:nokey=1',str(source)]))
music();subtitles()
cmd=['ffmpeg','-y','-v','warning','-i',str(source),'-i',str(M/'sprecher-maennlich.wav'),'-i',str(M/'hintergrundmusik-original.wav'),'-filter_complex',audio_filter(),'-map','0:v','-map','[a]']
if D['duration']<=old+.05:cmd+=['-c:v','copy']
else:cmd+=['-vf',f"tpad=stop_mode=clone:stop_duration={D['duration']-old+.1}",'-c:v','libx264','-crf','18','-preset','fast','-threads','2']
cmd+=['-c:a','aac','-b:a','192k','-ar','48000','-t',str(D['duration']),'-movflags','+faststart',str(M/'valet-parken-muenchen-erklaervideo.mp4')]
subprocess.run(cmd,check=True)
report={'approved_animation_source_duration':old,'new_duration':D['duration'],'video_stream_copied':D['duration']<=old+.05,'scene_composition_unchanged':True,'middle_voice_samples_unchanged':True}
def video_hash(path):
 return subprocess.check_output(['ffmpeg','-v','error','-i',str(path),'-map','0:v','-c','copy','-f','hash','-hash','sha256','-'],text=True).strip()
if report['video_stream_copied']:
 report['video_payload_sha256_before']=video_hash(source);report['video_payload_sha256_after']=video_hash(M/'valet-parken-muenchen-erklaervideo.mp4');assert report['video_payload_sha256_before']==report['video_payload_sha256_after']
(R/'docs/ERKLAERVIDEO-ENDPUNKTE-MUX.json').write_text(json.dumps(report,indent=2)+'\n');print('Corrected narration remixed; approved animation preserved')
