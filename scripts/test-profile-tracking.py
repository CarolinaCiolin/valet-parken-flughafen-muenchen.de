"""HTML-only local checks: own CleanLinking ID required outside Munich; no script executes."""
from pathlib import Path
import subprocess,json,urllib.request,re
root=Path(__file__).resolve().parents[1]
def php(c):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);'+c],text=True)
def lit(x):return 'json_decode('+json.dumps(json.dumps(x))+',true)'
keys=['vpm_site_profile','vpm_cleanlinking_enabled','vpm_cleanlinking_site_id'];state=json.loads(php('$s=[];foreach('+lit(keys)+'as $k)$s[$k]=["exists"=>get_option($k,"__absent__")!=="__absent__","value"=>get_option($k)];echo json_encode($s);'))
try:
 php('update_option("vpm_cleanlinking_enabled",true);delete_option("vpm_cleanlinking_site_id");update_option("vpm_site_profile",vpm_profile_preset("munich"));')
 def html():return urllib.request.urlopen('http://127.0.0.1:8088/').read().decode()
 s=html();assert re.search(r'<script[^>]*id="vpm-tradetracker-cleanlinking"[^>]*type="text/plain"[^>]*data-category="marketing"',s);assert 'site: 518003'in s
 php('update_option("vpm_site_profile",vpm_profile_preset("frankfurt"));')
 assert 'vpm-tradetracker-cleanlinking'not in html()
 php('update_option("vpm_cleanlinking_site_id",12345);')
 s=html();assert 'site: 12345'in s and 'site: 518003'not in s
 assert re.search(r'<script[^>]*id="vpm-tradetracker-cleanlinking"[^>]*type="text/plain"[^>]*data-category="marketing"',s)
 print('Tracking profiles passed: Munich legacy ID retained; other profiles require own ID; emitted script remains plain text/marketing-gated. No browser script or tracking request executed.')
finally:
 php('foreach('+lit(state)+'as $k=>$v){if($v["exists"])update_option($k,$v["value"]);else delete_option($k);}')
