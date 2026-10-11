"""Repeatable lab measurement; synthetic 4G/4x CPU, not Hostinger field data."""
import json,sys,statistics
from pathlib import Path
from playwright.sync_api import sync_playwright
root=Path(__file__).resolve().parent.parent
phase=sys.argv[1] if len(sys.argv)>1 else 'after'
runs=[]
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
 for i in range(3):
  ctx=b.new_context(viewport={'width':390,'height':844},device_scale_factor=1);page=ctx.new_page();cdp=ctx.new_cdp_session(page)
  cdp.send('Network.enable');cdp.send('Network.setCacheDisabled',{'cacheDisabled':True});cdp.send('Network.emulateNetworkConditions',{'offline':False,'latency':150,'downloadThroughput':200000,'uploadThroughput':93750});cdp.send('Emulation.setCPUThrottlingRate',{'rate':4})
  page.add_init_script("window.vpmPerf={lcp:0,cls:0};new PerformanceObserver(l=>l.getEntries().forEach(e=>window.vpmPerf.lcp=e.startTime)).observe({type:'largest-contentful-paint',buffered:true});new PerformanceObserver(l=>l.getEntries().forEach(e=>{if(!e.hadRecentInput)window.vpmPerf.cls+=e.value})).observe({type:'layout-shift',buffered:true});")
  response=page.goto('http://127.0.0.1:8088/',wait_until='networkidle');assert response.status==200
  page.wait_for_function('document.querySelector(".vpm-hero-image img").complete')
  data=page.evaluate('(()=>{const n=performance.getEntriesByType("navigation")[0],r=performance.getEntriesByType("resource");return {...vpmPerf,ttfb:n.responseStart,load:n.loadEventEnd,bytes:n.transferSize+r.reduce((s,e)=>s+e.transferSize,0),requests:r.length+1,images:[...document.images].map(i=>({url:i.currentSrc,width:i.clientWidth}))}})()')
  runs.append(data);print(phase,i+1,round(data['lcp']),round(data['cls'],4),data['bytes'],flush=True);ctx.close()
 b.close()
report={'scope':'Lokaler Browser-Labortest: 390px/DPR1, 150ms Latenz, 1.6Mbit/s Download, CPU4x, Browsercache aus; kein Hostinger-Benchmark und keine Feldmessung.','runs':runs,'median':{k:round(statistics.median(d[k] for d in runs),3) for k in ['lcp','cls','ttfb','load','bytes','requests']}}
(root/'dist'/f'speed-{phase}.json').write_text(json.dumps(report,indent=2))
