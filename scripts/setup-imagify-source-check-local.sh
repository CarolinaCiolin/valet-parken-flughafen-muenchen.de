#!/usr/bin/env bash
# Optional source/HTML test. Does not install or activate Imagify or contact its API.
set -euo pipefail
cd "$(dirname "$0")/.."
python3 - <<'PY'
from pathlib import Path
from urllib.request import urlopen
import subprocess
root=Path.cwd();source=root/'.runtime/compatibility-plugins/imagify';source.parent.mkdir(parents=True,exist_ok=True)
commit='0fae188479f4186436c085f20857090e542d8c5c'
if not source.exists():
 subprocess.run(['git','init',str(source)],check=True)
 subprocess.run(['git','-C',str(source),'remote','add','origin','https://github.com/wp-media/imagify-plugin.git'],check=True)
 subprocess.run(['git','-C',str(source),'fetch','--depth','1','origin',commit],check=True)
 subprocess.run(['git','-C',str(source),'checkout','--detach',commit],check=True)
assert subprocess.check_output(['git','-C',str(source),'rev-parse','HEAD'],text=True).strip()==commit
subprocess.run(['docker','exec','vpm-local-wp','mkdir','-p','/tmp/vpm-imagify-source/classes/Picture','/tmp/vpm-imagify-source/inc/classes/Dependencies/wp-media/event-manager','/tmp/vpm-imagify-fixture'],check=True)
for name in ['classes/Picture/Display.php','inc/classes/Dependencies/wp-media/event-manager/SubscriberInterface.php']:
 # Validate tested files against the pinned manufacturer commit, even on a cached checkout.
 assert (source/name).read_bytes()==subprocess.check_output(['git','-C',str(source),'show',commit+':'+name])
 subprocess.run(['docker','cp',str(source/name),'vpm-local-wp:/tmp/vpm-imagify-source/'+name],check=True)
fixture=root/'.runtime/imagify-fixture';fixture.mkdir(parents=True,exist_ok=True)
for slug,path in [('index','/'),('preise','/preise/'),('sicherheit','/valet-parken-sicherheit/'),('ueber-uns','/ueber-uns/')]:
 (fixture/(slug+'.html')).write_bytes(urlopen('http://127.0.0.1:8088'+path).read())
subprocess.run(['docker','cp',str(fixture)+'/.','vpm-local-wp:/tmp/vpm-imagify-fixture/'],check=True)
subprocess.run(['docker','cp','scripts/test-imagify-markup-local.php','vpm-local-wp:/tmp/vpm-test-imagify-markup-local.php'],check=True)
subprocess.run(['docker','exec','-e','VPM_LOCAL_SETUP=1','vpm-local-wp','php','/tmp/vpm-test-imagify-markup-local.php'],check=True)
subprocess.run(['docker','cp','vpm-local-wp:/tmp/vpm-imagify-fixture/.',str(fixture)+'/'],check=True)
PY
python3 scripts/test-imagify-picture-browser.py
