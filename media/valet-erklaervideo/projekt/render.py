"""Editable 2D motion graphics. Pillow + NumPy + FFmpeg; no stock footage, music or tracking."""
from pathlib import Path
from PIL import Image,ImageDraw,ImageFont,ImageFilter
import numpy as np,json,math,subprocess,wave,textwrap,sys,re
P=Path(__file__).resolve().parent;OUT=P.parent;D=json.loads((P/'design.json').read_text());A=json.loads((P/'sprachzeiten.json').read_text());W,H=D['width'],D['height'];S=W/1280;NAVY=D['navy'];GOLD=D['gold'];CREAM=D['cream'];WHITE=D['white'];BLUE='#c7dbe2';MUTED='#6a6e73';FONTS={};im=None;dr=None

def font(size,bold=False):
 k=(size,bold)
 if k not in FONTS:FONTS[k]=ImageFont.truetype(str(P/'fonts'/('DejaVuSans-Bold.ttf'if bold else'DejaVuSans.ttf')),round(size*S))
 return FONTS[k]
def box(b):return tuple(round(x*S)for x in b)
def rr(b,fill,r=10,outline=None,width=1):dr.rounded_rectangle(box(b),round(r*S),fill,outline,width=round(width*S))
def el(b,fill,outline=None,width=1):dr.ellipse(box(b),fill,outline,round(width*S))
def ln(points,fill,width=2):dr.line([(round(x*S),round(y*S))for x,y in points],fill,width=round(width*S),joint='curve')
def poly(points,fill):dr.polygon([(round(x*S),round(y*S))for x,y in points],fill)
def tx(x,y,s,size=24,fill=NAVY,bold=False,anchor=None):dr.text((round(x*S),round(y*S)),s,font=font(size,bold),fill=fill,anchor=anchor,spacing=round(7*S))
def ease(v):v=max(0,min(1,v));return v*v*(3-2*v)
def lerp(a,b,v):return a+(b-a)*ease(v)
def car(x,y,z=1,color=NAVY,t=0):
 def q(a,b):return x+a*z,y+b*z
 el((x-14*z,y+75*z,x+250*z,y+102*z),'#e3ded4')
 poly([q(5,55),q(30,9),q(43,4),q(167,4),q(191,51),q(227,62),q(239,76),q(234,90),q(0,90),q(-7,76)],color)
 poly([q(47,17),q(35,49),q(174,49),q(159,17)],BLUE);ln([q(103,17),q(103,49)],color,6*z)
 rr((x+7*z,y+61*z,x+34*z,y+68*z),'#e7d8ac',3*z);rr((x+204*z,y+64*z,x+226*z,y+70*z),GOLD,3*z)
 for cx in [42,191]:
  el((x+(cx-19)*z,y+72*z,x+(cx+19)*z,y+110*z),NAVY);el((x+(cx-9)*z,y+82*z,x+(cx+9)*z,y+100*z),GOLD)
  for k in range(3):
   theta=t*2+k*math.pi/1.5;ln([q(cx,91),q(cx+7*math.cos(theta),91+7*math.sin(theta))],WHITE,1.5*z)
 ln([q(116,62),q(132,62)],'#d3c8b4',2*z)
def person(x,y,z=1,role='traveler',phase=0,arm=0,walk=False):
 c=GOLD if role=='agent'else NAVY;skin='#c99573';swing=math.sin(phase)*10 if walk else 0
 ln([(x,y-35*z),(x-13*z+swing*z,y-4*z)],NAVY,12*z);ln([(x+8*z,y-35*z),(x+20*z-swing*z,y-4*z)],NAVY,12*z)
 rr((x-20*z,y-88*z,x+23*z,y-28*z),c,13*z);el((x-16*z,y-126*z,x+17*z,y-91*z),skin)
 if role=='agent':rr((x-15*z,y-128*z,x+17*z,y-117*z),NAVY,5*z);rr((x-13*z,y-82*z,x+12*z,y-76*z),WHITE,2*z)
 else:poly([(x-17*z,y-106*z),(x-19*z,y-123*z),(x-8*z,y-132*z),(x+15*z,y-126*z),(x+17*z,y-115*z)],NAVY)
 el((x+6*z,y-114*z,x+8*z,y-112*z),NAVY)
 ln([(x-12*z,y-77*z),(x-29*z,y-46*z+arm*z)],c,10*z);el((x-34*z,y-51*z+arm*z,x-25*z,y-41*z+arm*z),skin)
 ln([(x+14*z,y-76*z),(x+34*z,y-55*z-arm*z)],c,10*z);el((x+30*z,y-60*z-arm*z,x+39*z,y-50*z-arm*z),skin)
 rr((x-24*z+swing*z,y-6*z,x-2*z+swing*z,y+3*z),NAVY,3*z);rr((x+11*z-swing*z,y-6*z,x+36*z-swing*z,y+3*z),NAVY,3*z)
def bag(x,y,z=1):
 rr((x,y-49*z,x+30*z,y-3*z),GOLD,5*z);ln([(x+9*z,y-48*z),(x+9*z,y-67*z),(x+22*z,y-67*z),(x+22*z,y-48*z)],NAVY,2*z);ln([(x+9*z,y-40*z),(x+9*z,y-10*z)],'#e4d6b5',2*z);el((x+3*z,y-4*z,x+9*z,y+2*z),NAVY);el((x+22*z,y-4*z,x+28*z,y+2*z),NAVY)
def key(x,y,z=1):
 el((x,y,x+12*z,y+12*z),None,GOLD,3*z);ln([(x+11*z,y+6*z),(x+29*z,y+6*z),(x+29*z,y+13*z)],GOLD,4*z)
def plane(x,y,z=1):poly([(x,y+8*z),(x+25*z,y+8*z),(x+40*z,y-8*z),(x+47*z,y-7*z),(x+39*z,y+8*z),(x+63*z,y+8*z),(x+70*z,y+3*z),(x+76*z,y+4*z),(x+72*z,y+14*z),(x+3*z,y+14*z)],'#aebfc6')
def terminal(t):
 # Schematic Munich airport scene; no specific terminal number or claimed meeting coordinates.
 rr((88,378,1187,584),'#edf0ed',16);rr((765,312,1125,485),'#e1e8e8',7)
 for k in range(9):rr((780+k*37,330,805+k*37,452),BLUE,2)
 pts=[(728+i*5,318-47*math.sin(math.pi*i/86))for i in range(87)];ln(pts,NAVY,9);ln([(749,319),(749,481),(1156,481),(1156,318)],'#becacc',5)
 rr((896,326,1064,364),NAVY,5);tx(980,346,'MÜNCHEN · MUC',14,WHITE,True,'mm')
 rr((1107,217,1124,310),'#becacc',2);poly([(1090,216),(1138,216),(1132,248),(1097,248)],NAVY);rr((1092,211,1137,224),GOLD,2)
 ln([(92,578),(1187,578)],'#d4d5cf',3)
 for x in range(118,1150,115):ln([(x,613),(x+58,613)],'#d2c7b2',4)
 plane(924+(t*7)%170,240,.65)
def check(x,y,v):
 rr((x,y,x+175,y+34),WHITE,7,outline='#e1dacb');el((x+12,y+10,x+27,y+25),GOLD);ln([(x+16,y+17),(x+20,y+21),(x+25,y+13)],NAVY,2)
 ln([(x+40,y+16),(x+40+100*v,y+16)],'#b9b9b2',4)
def base(t,scene):
 global im,dr;im=Image.new('RGB',(W,H),WHITE);dr=ImageDraw.Draw(im)
 rr((0,0,1280,720),WHITE,0);el((1000,-130,1440,290),CREAM);el((-110,450,250,810),CREAM)
 rr((55,36,105,83),NAVY,11);tx(80,60,'P',32,GOLD,True,'mm');ln([(89,42),(105,31),(113,33)],GOLD,3)
 tx(121,34,'VALET PARKEN',21,NAVY,True);tx(122,63,'FLUGHAFEN MÜNCHEN',11,MUTED,True);tx(1215,58,f'{scene+1:02d} / 06',14,MUTED,False,'rm')
 title=D['captions'][scene];tx(70,124,title,46 if scene==0 else 40,NAVY,True)
 # One coherent illustrated stage, separate from the title.
 rr((59,267,1221,653),'#eae5da',24);rr((55,260,1225,646),WHITE,24,outline='#e1dacb')
 rr((60,686,1220,690),'#e7e0d5',2);rr((60,686,60+1160*min(1,t/D['duration']),690),GOLD,2)
 tx(60,665,'valet-parken-flughafen-muenchen.de',11,MUTED)
def raw_frame(t):
 global im,dr;scene=max(i for i,x in enumerate(D['chapters'])if t>=x);u=t-D['chapters'][scene];base(t,scene)
 if scene==0:
  terminal(t);car(lerp(-260,300,u/3.6),487,1.05,t=t)
  rr((100,301,386,343),CREAM,20);tx(243,322,'Direkt zum Terminal',17,NAVY,True,'mm')
 elif scene==1:
  terminal(t);car(215,482,1.05,t=0);person(604,575,1.28,'agent',arm=-9)
  x=lerp(674,930,(u-3.4)/6);person(x,574,1.22,'traveler',phase=u*5,arm=-8 if u<3 else 0,walk=u>3.4);bag(x+47,577,1.05)
  key(638+min(u,2)*4,483,1.2)
  if u>3:rr((756,392,1085,438),NAVY,8);tx(920,414,'CHECK-IN →',22,WHITE,True,'mm')
 elif scene==2:
  car(185,472,1.7,t=0);person(738,596,1.65,'agent',arm=8)
  rr((784,466,827,528),NAVY,5);rr((790,473,821,518),BLUE,2);rr((844,320,1112,590),NAVY,21);rr((860,339,1096,572),CREAM,10);tx(978,371,'ÜBERGABE',17,NAVY,True,'mm')
  for k,v in enumerate([.82,.65,.92]):check(882,414+k*45,v)
  for k,(cx,cy)in enumerate([(290,480),(535,539)]):
   z=8+3*math.sin(u*2+k);el((cx-z,cy-z,cx+z,cy+z),None,GOLD,3)
 elif scene==3:
  for j,x in enumerate([105,665]):
   rr((x,300,x+510,603),CREAM,16);tx(x+255,328,'Außenstellplatz'if j==0 else'Überdachter Stellplatz',23,NAVY,True,'mm')
   if j==0:
    el((x+399,365,x+440,406),'#dfc68b')
    for k in range(9):ln([(x+40+k*51,446),(x+40+k*51,546)],'#d1c9b6',3)
    ln([(x+36,463),(x+473,463)],'#c5bea9',3)
   else:
    rr((x+48,392,x+458,417),'#d8c6a0',3);ln([(x+72,418),(x+72,560)],GOLD,10);ln([(x+434,418),(x+434,560)],GOLD,10)
   car(x+127,460,.95,t=u*.2)
  tx(920,580,'Garage · Halle · Parkhaus',14,MUTED,False,'mm')
 elif scene==4:
  terminal(t);progress=ease((u-5)/5);x=lerp(294,1030,progress);car(x,486,1.03,t=u if u>5 else 0)
  if u<5.5:
   person(681,580,1.28,'traveler',arm=8);bag(724,582,1.05);person(589,578,1.28,'agent',arm=-7);key(631,493,1.1)
  else:
   rr((120,310,586,363),CREAM,25);tx(353,337,'Entspannt nach Hause',24,NAVY,True,'mm')
 else:
  rr((0,0,1280,720),NAVY,0);el((895,-205,1420,320),'#16283e');el((-200,470,240,930),'#16283e')
  rr((72,46,120,94),GOLD,11);tx(96,70,'P',33,NAVY,True,'mm');tx(140,53,'VALET PARKEN · MÜNCHEN',19,WHITE,True)
  tx(640,225,'Valet-Service in München buchen',43,WHITE,True,'mm')
  tx(640,335,'valet-parken-flughafen-muenchen.de',37,WHITE,True,'mm')
  rr((328,419,952,482),GOLD,11);tx(640,450,'Link in der Videobeschreibung',26,NAVY,True,'mm');car(lerp(-250,510,min(1,u/2.1)),548,.95,color='#c0a064',t=u)
 return im

def frame(t):
 current=raw_frame(t)
 for boundary in D['chapters'][1:]:
  if 0<=t-boundary<.36:
   previous=raw_frame(max(0,boundary-.001));return Image.blend(previous,current,ease((t-boundary)/.36))
 return current

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
def main():
 music();subtitles()
 for i,t in enumerate([D['chapters'][i]+min(2.9,((D['chapters']+[D['duration']])[i+1]-D['chapters'][i])*.45)for i in range(6)]):frame(t).save(OUT/f'szene-{i+1}.png')
 cols=[]
 for i in range(6):cols.append(Image.open(OUT/f'szene-{i+1}.png').resize((640,360)))
 sheet=Image.new('RGB',(1280,1080),WHITE)
 for i,v in enumerate(cols):sheet.paste(v,((i%2)*640,(i//2)*360))
 sheet.save(OUT/'storyboard.png')
 if '--stills'in sys.argv:return
 log=(P/'render.log').open('w');cmd=['ffmpeg','-y','-loglevel','warning','-f','rawvideo','-vcodec','rawvideo','-pixel_format','rgb24','-video_size',f'{W}x{H}','-framerate',str(D['fps']),'-i','-','-i',str(OUT/'sprecher-maennlich.wav'),'-i',str(OUT/'hintergrundmusik-original.wav'),'-filter_complex',f"[1:a]loudnorm=I={D.get('voice_lufs',-16)}:TP=-1.5:LRA=8,aformat=channel_layouts=stereo[v];[2:a]loudnorm=I={D.get('music_lufs',-26)}:TP=-3:LRA=5,afade=t=out:st={D['duration']-D.get('ending_fade_seconds',1.3)}:d={D.get('ending_fade_seconds',1.3)}[m];[v][m]amix=inputs=2:duration=longest:normalize=0,alimiter=limit=0.89:level=false:latency=true[a]",'-map','0:v','-map','[a]','-c:v','libx264','-preset','fast','-crf','19','-pix_fmt','yuv420p','-threads','2','-c:a','aac','-b:a','192k','-ar','48000','-t',str(D['duration']),'-movflags','+faststart',str(OUT/'valet-parken-muenchen-erklaervideo.mp4')]
 process=subprocess.Popen(cmd,stdin=subprocess.PIPE,stderr=log)
 for n in range(math.ceil(D['duration']*D['fps'])):process.stdin.write(frame(n/D['fps']).tobytes())
 process.stdin.close();code=process.wait();log.close();assert code==0,(P/'render.log').read_text();print('MP4 rendered:',D['duration'],'seconds,',W,'x',H)
if __name__=='__main__':main()
