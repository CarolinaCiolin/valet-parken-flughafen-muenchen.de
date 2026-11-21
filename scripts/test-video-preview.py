"""Check the delivered review HTML, actual decoded video and timed subtitles."""
from pathlib import Path
from functools import partial
from http.server import ThreadingHTTPServer,SimpleHTTPRequestHandler
from threading import Thread
from playwright.sync_api import sync_playwright
import json
R=Path(__file__).resolve().parents[1];OUT=R/'vorschau/affiliate-video-2026-11';D=json.loads((R/'media/valet-erklaervideo/projekt/design.json').read_text())
class Quiet(SimpleHTTPRequestHandler):
 def log_message(self,*args):pass
 def handle(self):
  try:super().handle()
  except (BrokenPipeError,ConnectionResetError):pass
server=ThreadingHTTPServer(('127.0.0.1',0),partial(Quiet,directory=str(OUT)));thread=Thread(target=server.serve_forever,daemon=True);thread.start();checks=[];failed=[]
try:
 with sync_playwright() as p:
  b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox','--autoplay-policy=no-user-gesture-required']);g=b.new_page();g.on('response',lambda r:failed.append([r.url,r.status])if r.status>=400 else None)
  for name,width in [('index.html',1440),('startseite.html',1440),('startseite.html',390)]:
   g.set_viewport_size({'width':width,'height':900});g.goto(f'http://127.0.0.1:{server.server_port}/'+name,wait_until='domcontentloaded');v=g.locator('video');v.scroll_into_view_if_needed();v.evaluate('(v)=>{v.muted=true;v.textTracks[0].mode="showing";return v.play()}');g.wait_for_function('document.querySelector("video").currentTime>0.8')
   state=v.evaluate('(v)=>({duration:v.duration,width:v.videoWidth,height:v.videoHeight,readyState:v.readyState})');assert abs(state['duration']-D['duration'])<.1 and state['width']==1920
   v.evaluate('(v)=>new Promise(resolve=>{let n=0;function next(){if(++n===2)resolve(true);else v.requestVideoFrameCallback(next)}v.requestVideoFrameCallback(next)})')
   g.wait_for_function('document.querySelector("video").textTracks[0].cues && document.querySelector("video").textTracks[0].cues.length>0');assert g.evaluate('document.documentElement.scrollWidth<=innerWidth+1')
   if name=='startseite.html':
    assert g.locator('.vpm-provider-card').count()==9
    # Capture the decoded illustration in its page placement, without transient native controls.
    g.wait_for_function('document.querySelector("video").currentTime>3.7');v.evaluate('(v)=>{v.pause();v.controls=false;v.textTracks[0].mode="disabled"}');v.scroll_into_view_if_needed();g.wait_for_timeout(250);g.screenshot(path=str(OUT/f'video-im-ablauf-{width}.png'));v.evaluate('(v)=>v.controls=true')
   checks.append({'page':name,'viewport':width,'decoded_video':state,'subtitles_loaded':True})
  checks.append({'page':'index.html via file://','status':'not_verified','reason':'Cloud Chrome blocks file:// navigation by administrator policy; HTTP playback and Blob-backed captions verified.'})
  assert not failed,failed
  b.close()
finally:server.shutdown();server.server_close()
(R/'docs/ERKLAERVIDEO-BROWSER-PRUEFUNG.json').write_text(json.dumps({'result':'passed','checks':checks,'failed_requests':failed,'limitations':['Unpacked file:// navigation is blocked by this Cloud browser policy and was not verified.']},ensure_ascii=False,indent=2)+'\n');print('Review player: actual video frames and captions passed on desktop/mobile')
