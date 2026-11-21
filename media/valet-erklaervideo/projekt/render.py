"""Render the layered illustrated 2D film. Python/Pillow/NumPy + FFmpeg."""
from pathlib import Path
from PIL import Image
import numpy as np,json,math,subprocess,wave,textwrap,sys,re
from motion import Motion
P=Path(__file__).resolve().parent;OUT=P.parent
D=json.loads((P/'design.json').read_text());A=json.loads((P/'sprachzeiten.json').read_text())
W,H=D['width'],D['height'];WHITE=D['white'];M=Motion(D)
def frame(t):return M.frame(t)
def music():
 sr=44100;length=D['duration'];t=np.arange(round(sr*length))/sr;s=np.zeros_like(t)
 # Original composition: soft synthesized keys/pad. No samples or external music.
 chords=[[220,261.6256,329.6276],[174.614,220,261.6256],[130.8128,164.8138,195.9977],[195.9977,246.9417,293.6648]]
 for n,start in enumerate(np.arange(0,length,3.2)):
  rel=t-start;mask=(rel>=0)&(rel<6.4);x=rel[mask];env=(1-np.exp(-x*3))*np.exp(-x*.75)
  for f in chords[n%4]:s[mask]+=.038*env*(np.sin(2*np.pi*f*x)+.23*np.sin(2*np.pi*2*f*x)+.08*np.sin(2*np.pi*3*f*x))
 s*=np.minimum(1,t/.8)*np.minimum(1,(length-t)/D.get('ending_fade_seconds',1.3));s=np.clip(s,-.3,.3)
 stereo=np.stack([s,np.roll(s,round(sr*.017))],axis=1)
 with wave.open(str(OUT/'hintergrundmusik-original.wav'),'wb')as w:w.setnchannels(2);w.setsampwidth(2);w.setframerate(sr);w.writeframes((stereo*32767).astype('<i2').tobytes())
def subtitles():
 def stamp(x):
  ms=round(x*1000);return f'{ms//3600000:02d}:{ms//60000%60:02d}:{ms//1000%60:02d},{ms%1000:03d}'
 cues=[]
 for a in A['segments']:
  words=a['text'].split();groups=[];g=[]
  for word in words:
   if len(' '.join(g+[word]))>73 and g:groups.append(g);g=[]
   g.append(word)
  if g:groups.append(g)
  count=sum(len(' '.join(g))for g in groups);at=a['start']
  for g in groups:
   text=' '.join(g);end=at+(a['end']-a['start'])*len(text)/count
   lines=textwrap.wrap(text,40,break_long_words=False,break_on_hyphens=False);cues.append((at,end,'\n'.join(lines)));at=end
 out='\n\n'.join(f'{i+1}\n{stamp(a)} --> {stamp(b)}\n{s}'for i,(a,b,s)in enumerate(cues))+'\n'
 (OUT/'untertitel-de.srt').write_text(out);(OUT/'untertitel-de.vtt').write_text('WEBVTT\n\n'+re.sub(r'(\d{2}:\d{2}:\d{2}),(\d{3})',r'\1.\2',out));assert ' '.join(' '.join(s.split())for a,b,s in cues)==' '.join((OUT/'sprechertext.txt').read_text().split())
def audio_filter():
 return f"[1:a]loudnorm=I={D.get('voice_lufs',-16)}:TP=-1.5:LRA=8,aformat=channel_layouts=stereo[v];[2:a]loudnorm=I={D.get('music_lufs',-26)}:TP=-3:LRA=5,afade=t=out:st={D['duration']-D.get('ending_fade_seconds',1.3)}:d={D.get('ending_fade_seconds',1.3)}[m];[v][m]amix=inputs=2:duration=longest:normalize=0,alimiter=limit=0.89:level=false:latency=true[a]"
def main():
 music();subtitles()
 for i in range(6):
  end=(D['chapters']+[D['duration']])[i+1];t=D['chapters'][i]+min(2.9,(end-D['chapters'][i])*.5);frame(t).save(OUT/f'szene-{i+1}.png')
 sheet=Image.new('RGB',(1280,1080),WHITE)
 for i in range(6):
  v=Image.open(OUT/f'szene-{i+1}.png').resize((640,360),Image.Resampling.LANCZOS);sheet.paste(v,((i%2)*640,(i//2)*360))
 sheet.save(OUT/'storyboard.png')
 if '--stills'in sys.argv:return
 log=(P/'render.log').open('w')
 cmd=['ffmpeg','-y','-loglevel','warning','-f','rawvideo','-vcodec','rawvideo','-pixel_format','rgb24','-video_size',f'{W}x{H}','-framerate',str(D['fps']),'-i','-','-i',str(OUT/'sprecher-maennlich.wav'),'-i',str(OUT/'hintergrundmusik-original.wav'),'-filter_complex',audio_filter(),'-map','0:v','-map','[a]','-c:v','libx264','-preset','fast','-crf','18','-pix_fmt','yuv420p','-threads','2','-c:a','aac','-b:a','192k','-ar','48000','-t',str(D['duration']),'-movflags','+faststart',str(OUT/'valet-parken-muenchen-erklaervideo.mp4')]
 process=subprocess.Popen(cmd,stdin=subprocess.PIPE,stderr=log)
 for n in range(math.ceil(D['duration']*D['fps'])):
  process.stdin.write(frame(n/D['fps']).tobytes())
  if n%D['fps']==0 and n%(D['fps']*10)==0:print('Rendered',n/D['fps'],'seconds',flush=True)
 process.stdin.close();code=process.wait();log.close();assert code==0,(P/'render.log').read_text();print('MP4 rendered:',D['duration'],'seconds,',W,'x',H)
if __name__=='__main__':main()
