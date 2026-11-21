"""Layered 2D animation: illustrated assets, vehicle/wheels, camera, scanner and brand.
All geometry uses a 1280x720 artboard and renders at the requested Full HD size.
"""
from pathlib import Path
from functools import lru_cache
from PIL import Image,ImageDraw,ImageFont,ImageFilter,ImageOps
import math,json
P=Path(__file__).resolve().parent
NAVY='#101c2c';GOLD='#b99a5b';CREAM='#fcfaf7';BLUE='#9cb7c8';WHITE='#ffffff'
def ease(v):
 v=max(0,min(1,v));return v*v*(3-2*v)
def lerp(a,b,v):return a+(b-a)*ease(v)
class Motion:
 def __init__(self,design):
  self.d=design;self.w=design['width'];self.h=design['height'];self.s=self.w/1280;self.assets={}
  for n in ['terminal','fahrzeug','uebergabe','parkflaechen','fahrzeugpruefung']:
   im=Image.open(P/'illustrationen'/f'{n}.png').convert('RGBA')
   if n not in ['terminal','parkflaechen']:
    # Asset trim and wheel extraction are animation compositing, not a new still design.
    bb=im.getchannel('A').point(lambda x:255 if x>10 else 0).getbbox();im=im.crop(bb)
   self.assets[n]=im
  self.backgrounds={n:ImageOps.fit(self.assets[n],(self.w,self.h),method=Image.Resampling.LANCZOS)for n in ['terminal','parkflaechen']}
  self.im=None;self.dr=None
 @lru_cache(maxsize=150)
 def font(self,size,bold=False):
  f=ImageFont.truetype(str(P/'fonts/Manrope-Variable.ttf'),round(size*self.s));f.set_variation_by_axes([750 if bold else 500]);return f
 def pos(self,box):return tuple(round(v*self.s)for v in box)
 def rr(self,b,fill,r=12,outline=None,width=1):self.dr.rounded_rectangle(self.pos(b),round(r*self.s),fill,outline,width=round(width*self.s))
 def line(self,points,fill=GOLD,width=2):self.dr.line([(round(x*self.s),round(y*self.s))for x,y in points],fill,width=max(1,round(width*self.s)),joint='curve')
 def ellipse(self,b,fill=None,outline=None,width=1):self.dr.ellipse(self.pos(b),fill,outline,width=round(width*self.s))
 def tx(self,x,y,text,size=24,fill=NAVY,bold=False,anchor=None):self.dr.text((round(x*self.s),round(y*self.s)),text,font=self.font(size,bold),fill=fill,anchor=anchor,spacing=round(8*self.s))
 def set(self,im):self.im=im;self.dr=ImageDraw.Draw(im)
 @lru_cache(maxsize=128)
 def sprite(self,n,width):
  a=self.assets[n];return a.resize((round(width*self.s),round(width*self.s*a.height/a.width)),Image.Resampling.LANCZOS)
 def pose(self,n,a,t):
  # Compact local mesh deformation: subtle hand/key gestures, fixed head and feet.
  if n not in ['uebergabe','fahrzeugpruefung']:return a
  w,h=a.size;cx,cy,rx,ry=(.45,.30,.43,.15)if n=='uebergabe'else(.31,.29,.46,.16)
  amount=math.sin(t*(1.65 if n=='uebergabe'else 5.6))*h*(.008 if n=='uebergabe'else .006)
  def point(x,y):
   fx=max(0,1-((x/w-cx)/rx)**2)**2;fy=max(0,1-((y/h-cy)/ry)**2)**2
   return x,y+amount*fx*fy
  mesh=[]
  for j in range(16):
   for i in range(12):
    x0=round(i*w/12);x1=round((i+1)*w/12);y0=round(j*h/16);y1=round((j+1)*h/16)
    q=[point(x0,y0),point(x0,y1),point(x1,y1),point(x1,y0)];mesh.append(((x0,y0,x1,y1),tuple(v for pt in q for v in pt)))
  return a.transform(a.size,Image.Transform.MESH,mesh,Image.Resampling.BICUBIC)
 def put(self,n,x,y,width,opacity=1,phase=None):
  a=self.sprite(n,width)
  if phase is not None:a=self.pose(n,a,phase)
  if opacity<1:a=a.copy();a.putalpha(a.getchannel('A').point(lambda v:round(v*max(0,opacity))))
  self.im.alpha_composite(a,(round(x*self.s),round(y*self.s)));return a.width/self.s,a.height/self.s
 @lru_cache(maxsize=80)
 def shadow_image(self,w,h,alpha):
  im=Image.new('RGBA',(round((w+40)*self.s),round((h+40)*self.s)),(0,0,0,0));ImageDraw.Draw(im).ellipse(self.pos((20,20,w+20,h+20)),fill=(10,22,35,alpha));return im.filter(ImageFilter.GaussianBlur(7*self.s))
 def shadow(self,x,y,w,h,alpha=40):self.im.alpha_composite(self.shadow_image(w,h,alpha),(round((x-20)*self.s),round((y-20)*self.s)))
 def tracked(self,x,y,text,size,spacing,fill,center=True,bold=False):
  f=self.font(size,bold);width=sum(self.dr.textlength(c,font=f) for c in text)+spacing*self.s*(len(text)-1)
  at=x*self.s-width/2 if center else x*self.s
  for c in text:self.dr.text((at,y*self.s),c,font=f,fill=fill);at+=self.dr.textlength(c,font=f)+spacing*self.s
 def wordmark(self,x,y,width=235,light=False,alpha=1):
  old=self.im;layer=Image.new('RGBA',old.size);self.set(layer);scale=width/235;ink=(252,250,247,round(alpha*255))if light else(16,28,44,round(alpha*255));gold=(185,154,91,round(alpha*255))
  self.tracked(x+width/2,y,'VALET PARKEN',26*scale,3.0*scale,ink,bold=True)
  self.tracked(x+width/2,y+39*scale,'MÜNCHEN',10.5*scale,4.9*scale,gold)
  self.line([(x,y+47*scale),(x+44*scale,y+47*scale)],gold,1*scale);self.line([(x+width-44*scale,y+47*scale),(x+width,y+47*scale)],gold,1*scale)
  old.alpha_composite(layer);self.set(old)
 def watermark(self,t,scene):
  self.wordmark(1008,34,205,scene in [0,1,4,5],.32 if scene==5 else .67 if scene in [0,1,4] else .46)
 def header(self,scene,u):
  entering=ease(u/.65);dy=18*(1-entering);titles=['Valet-Parken am\nFlughafen München','Direkt am Terminal\nabgeben','Fahrzeugzustand\ndokumentieren','Außen oder überdacht','Zurückkommen\nund losfahren']
  self.tx(70,52,'MÜNCHEN  /  MUC',14,GOLD,True);self.line([(70,86),(119,86)],GOLD,3)
  if scene<5:self.tx(70,110+dy,titles[scene],45 if scene==0 else 41,NAVY,True)
 def plane(self,x,y,angle=0,scale=1,color=GOLD):
  pts=[(-25,0),(-2,-5),(3,-22),(10,-22),(10,-4),(27,-1),(27,4),(9,6),(5,15),(-1,15),(-3,6),(-25,7)]
  ca=math.cos(angle);sa=math.sin(angle);self.dr.polygon([(round((x+scale*(a*ca-b*sa))*self.s),round((y+scale*(a*sa+b*ca))*self.s))for a,b in pts],fill=color)
 def route(self,u,x=746,y=221,w=421,h=134):
  pts=[]
  for i in range(81):
   v=i/80;pts.append((x+w*v,y-h*math.sin(v*math.pi*.72)))
  progress=max(0,min(1,u/4));count=round(progress*80)
  for i in range(0,count,3):self.line(pts[i:min(i+2,count)+1],(185,154,91,160),1.8)
  j=min(79,count);point=pts[j];self.plane(*point,-.15,.75)
 @lru_cache(maxsize=12)
 def wheel(self,index,size):
  # Inner illustrated alloy rims. Rotate in their own circular plane, then project.
  a=self.assets['fahrzeug'];cx,cy,rx,ry=[(210,488,84,112),(1015,515,103,135)][index]
  patch=a.crop((cx-rx,cy-ry,cx+rx,cy+ry)).resize((size,size),Image.Resampling.LANCZOS)
  mask=Image.new('L',(size,size));ImageDraw.Draw(mask).ellipse((1,1,size-2,size-2),fill=255);patch.putalpha(mask)
  return patch
 def car(self,x,y,width,t=0,moving=False,opacity=1):
  a=self.sprite('fahrzeug',width);self.shadow(x+width*.08,y+a.height/self.s*.94,width*.84,23,45)
  self.put('fahrzeug',x,y,width,opacity)
  if moving and opacity>.99:
   factor=width/1760
   for idx,(cx,cy,rx,ry)in enumerate([(210,488,84,112),(1015,515,103,135)]):
    size=round(rx*2*factor*self.s);patch=self.wheel(idx,max(180,size*3)).rotate(-x/width*950,resample=Image.Resampling.BICUBIC).resize((size,round(ry*2*factor*self.s)),Image.Resampling.LANCZOS)
    self.im.alpha_composite(patch,(round((x+(cx-rx)*factor)*self.s),round((y+(cy-ry)*factor)*self.s)))
 def pulse(self,x,y,t,r=16):
  for i in range(2):
   p=(t*.7+i*.5)%1;rr=r+p*13;self.ellipse((x-rr,y-rr,x+rr,y+rr),outline=(185,154,91,round((1-p)*200)),width=2)
 def pill(self,x,y,text,white=False):
  width=self.dr.textlength(text,font=self.font(17,True))/self.s+46;self.rr((x,y,x+width,y+42),NAVY if white else CREAM,12);self.tx(x+23,y+9,text,17,CREAM if white else NAVY,True);return width
 def check(self,x,y,progress):
  self.ellipse((x,y,x+25,y+25),fill=GOLD)
  pts=[(x+6,y+13),(x+11,y+18),(x+20,y+7)];p=max(0,min(1,progress))
  if p<.35:self.line([pts[0],(lerp(pts[0][0],pts[1][0],p/.35),lerp(pts[0][1],pts[1][1],p/.35))],NAVY,2.5)
  else:self.line([pts[0],pts[1],(lerp(pts[1][0],pts[2][0],(p-.35)/.65),lerp(pts[1][1],pts[2][1],(p-.35)/.65))],NAVY,2.5)
 def raw(self,t):
  chapters=self.d['chapters'];scene=max(i for i,a in enumerate(chapters)if t>=a);u=t-chapters[scene]
  if scene in [0,1,4]:
   # Same coherent location across arrival, handover and return; slow camera dolly.
   bg=self.backgrounds['terminal'];zoom=1.025+(.012*min(u,10)/10);bw=round(self.w*zoom);bh=round(self.h*zoom);im=bg.resize((bw,bh),Image.Resampling.BICUBIC).crop((round((bw-self.w)*.48),round((bh-self.h)*.6),round((bw-self.w)*.48)+self.w,round((bh-self.h)*.6)+self.h));self.set(im)
  elif scene==3:
   bg=self.backgrounds['parkflaechen'];zoom=1+.022*min(u,11)/11;bw=round(self.w*zoom);bh=round(self.h*zoom);cx=(bw-self.w)//2;cy=(bh-self.h)//2;self.set(bg.resize((bw,bh),Image.Resampling.BICUBIC).crop((cx,cy,cx+self.w,cy+self.h)))
  elif scene==2:
   self.set(Image.new('RGBA',(self.w,self.h),CREAM));ghost=self.backgrounds['terminal'].copy();ghost.putalpha(23);self.im.alpha_composite(ghost)
  else:self.set(Image.new('RGBA',(self.w,self.h),NAVY))
  if scene<5:self.header(scene,u)
  if scene==0:
   self.car(lerp(-760,142,u/3.45),360,720,u,True)
   if u>.7:self.pill(72,279,'Direkt zum Terminal',True)
   self.route(max(0,u-.2),735,235,369,111)
  elif scene==1:
   self.car(215,377,650,0)
   entry=ease(u/.75);w=295;h=self.assets['uebergabe'].height/self.assets['uebergabe'].width*w
   self.shadow(897,656,295,22,35);self.put('uebergabe',901+24*(1-entry),660-h,w,entry,phase=u)
   self.pulse(901+w*.435,660-h+w*400/1050,u,13)
   if u>3.1:
    slide=ease((u-3.1)/.6);self.rr((765,246+(1-slide)*15,1174,302+(1-slide)*15),NAVY,14);self.tx(789,258+(1-slide)*15,'CHECK-IN',23,CREAM,True);self.line([(1087,275),(1140,275),(1127,263),(1140,275),(1127,287)],GOLD,3)
  elif scene==2:
   self.car(38,382,685)
   agent_h=454;agent_w=round(agent_h*self.assets['fahrzeugpruefung'].width/self.assets['fahrzeugpruefung'].height);self.put('fahrzeugpruefung',655,650-agent_h,agent_w,phase=u)
   for i,(x,y)in enumerate([(302,432),(525,486)]):self.pulse(x,y,u+i*.5,13)
   scan=385+166*((u*.42)%1);self.line([(83,scan),(679,scan)],(185,154,91,110),1.2)
   self.shadow(853,649,344,22,35);self.rr((853,219,1200,649),NAVY,24);self.rr((863,231,1190,635),CREAM,16);self.ellipse((1019,237,1027,245),fill='#40505e')
   self.tx(887,263,'ÜBERGABE',13,GOLD,True);self.tx(887,292,'Fahrzeugzustand',21,NAVY,True)
   self.put('fahrzeug',900,337,260)
   for i in range(3):
    self.rr((883,458+i*46,1168,494+i*46),'#f0ece3',8);self.check(893,464+i*46,ease((u-.25-i*.4)/.45));self.line([(935,476+i*46),(1140-i*24,476+i*46)],'#bdc2c3',3)
   self.tx(888,608,'Dokumentiert',13,NAVY,True)
  elif scene==3:
   self.pill(90,311,'Außenstellplatz',True);self.pill(858,311,'Überdachter Stellplatz',True)
   self.rr((858,358,1151,387),(252,250,247,238),8);self.tx(875,363,'Garage · Halle · Parkhaus',14,NAVY)
   self.car(lerp(-550,100,u/2.5),443,489,u,u<2.5)
   self.car(lerp(690,825,u/2.1),440,386,u,u<2.1)
   self.line([(657,289),(657,289+316*ease(u/1.1))],(185,154,91,180),1.5)
   self.route(u,758,255,381,86)
  elif scene==4:
   leave=ease((u-4.5)/4.6);self.car(lerp(215,1490,leave),377,653,u,leave>0)
   opacity=1-ease((u-3.8)/1.0);w=295;h=self.assets['uebergabe'].height/self.assets['uebergabe'].width*w;self.put('uebergabe',901,660-h,w,opacity,phase=u)
   if u>5.4:self.pill(74,281,'Entspannt nach Hause',True)
  else:
   # Branded closing frame: exact site wordmark, quiet architecture and strong CTA.
   arch=self.backgrounds['terminal'].copy();arch.putalpha(30);self.im.alpha_composite(arch)
   self.wordmark(74,66,315,True,1)
   self.tx(74,231,'Valet-Service in\nMünchen buchen',43,CREAM,True)
   self.tx(74,409,'valet-parken-flughafen-muenchen.de',25,CREAM,True)
   self.rr((74,480,693,550),GOLD,14);self.tx(101,502,'Link in der Videobeschreibung',23,NAVY,True)
   ax=648+4*math.sin(u*2);self.line([(ax-12,515),(ax+8,515),(ax-1,506),(ax+8,515),(ax-1,524)],NAVY,2.5)
   self.car(lerp(1390,755,u/1.5),445,490,u,u<1.5)
   self.route(u,822,257,372,105)
  return self.im
 def frame(self,t):
  current=self.raw(t).copy();scene=max(i for i,a in enumerate(self.d['chapters'])if t>=a)
  for b in self.d['chapters'][1:]:
   if 0<=t-b<.52:
    p=ease((t-b)/.52);previous=self.raw(max(0,b-.001)).copy();mask=Image.new('L',(self.w,self.h));d=ImageDraw.Draw(mask);edge=round((self.w+100)*p)-50;d.rectangle((0,0,max(0,edge),self.h),fill=255)
    if 0<edge<self.w:
     for dx in range(50):d.line((edge+dx,0,edge+dx,self.h),fill=max(0,255-round(dx/50*255)),width=1)
    current=Image.composite(current,previous,mask);self.set(current)
    if 0<edge<self.w:self.line([(edge/self.s,0),(edge/self.s,720)],(185,154,91,190),2)
    break
  self.set(current);self.watermark(t,scene)
  # Discreet continuous progress line, safe above the player's caption region.
  self.rr((65,691,1215,693),(185,154,91,55),1);self.rr((65,691,65+1150*min(1,t/self.d['duration']),693),GOLD,1)
  return self.im.convert('RGB')
