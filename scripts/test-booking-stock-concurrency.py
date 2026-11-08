"""Two independent MySQL connections contend for a last parking space, local test only."""
from pathlib import Path
import subprocess,json,time,os
CONTAINER=os.environ.get("VPM_TEST_CONTAINER","vpm-local-wp")
base='define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);global $wpdb;$wpdb->suppress_errors(true);'
def php(c):return subprocess.check_output(['docker','exec',CONTAINER,'php','-r',base+c],text=True)
tables=json.loads(php('echo json_encode($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%")));'))
for t in tables:assert php('echo $wpdb->get_var("SELECT COUNT(*) FROM '+t+'");')=='0','Nonempty booking tables refused'
try:
 setup=json.loads(php('wp_set_current_user(1);$p=VPM\\Booking\\Core::partner(["airport"=>"FRA","name"=>"Stock race fixture","stripe_account"=>"acct_racefixture","commission_bps"=>2000]);VPM\\Booking\\Products::save_template(["partner_id"=>(string)$p["id"],"name_de"=>"Test","name_en"=>"Test","parking_type"=>"outdoor"]);$all=VPM\\Booking\\Products::catalogue();$product=array_key_last($all);VPM\\Booking\\Adapters::configure((int)$p["id"],["mode"=>"manual"]);VPM\\Booking\\Adapters::save_rate((int)$p["id"],["product_id"=>$product,"price_cents"=>"10000","pricing"=>"stay","capacity"=>"1","enabled"=>"1"]);echo json_encode(["partner"=>(int)$p["id"],"rate"=>(int)$wpdb->get_var("SELECT id FROM ".VPM\\Booking\\DB::table("rates")." LIMIT 1")]);'))
 subprocess.run(['docker','exec',CONTAINER,'sh','-c','rm -f /tmp/vpm-stock-race-start /tmp/vpm-stock-race-ready-1 /tmp/vpm-stock-race-ready-2'],check=True)
 workers=[]
 for n in[1,2]:
  code=base+'file_put_contents("/tmp/vpm-stock-race-ready-'+str(n)+'","ready");$until=microtime(true)+10;while(!file_exists("/tmp/vpm-stock-race-start")&&microtime(true)<$until)usleep(10000);try{$b=VPM\\Booking\\Flow::create(["partner_id"=>'+str(setup['partner'])+',"email"=>"race'+str(n)+'@example.invalid","base_cents"=>10000,"travel"=>["airport"=>"FRA","timezone"=>"Europe/Berlin","departure_utc"=>time()+864000,"return_utc"=>time()+1728000]],"race-booking-000'+str(n)+'",["mode"=>"manual","rate_id"=>'+str(setup['rate'])+']);echo json_encode(["ok"=>true]);}catch(Throwable $e){echo json_encode(["ok"=>false,"code"=>$e->getMessage()]);}'
  workers.append(subprocess.Popen(['docker','exec',CONTAINER,'php','-r',code],stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True))
 for _ in range(50):
  ready=subprocess.run(['docker','exec',CONTAINER,'sh','-c','test -f /tmp/vpm-stock-race-ready-1 && test -f /tmp/vpm-stock-race-ready-2'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
  if ready.returncode==0:break
  time.sleep(.05)
 subprocess.run(['docker','exec',CONTAINER,'touch','/tmp/vpm-stock-race-start'],check=True)
 results=[]
 for w in workers:
  out,err=w.communicate(timeout=20);assert w.returncode==0,err;results.append(json.loads(out))
 assert sum(1 for r in results if r['ok'])==1,results
 assert next(r['code'] for r in results if not r['ok'])=='capacity_unavailable',results
 assert php('echo $wpdb->get_var("SELECT COUNT(*) FROM ".VPM\\Booking\\DB::table("allocations"));')=='1'
 assert php('echo $wpdb->get_var("SELECT COUNT(*) FROM ".VPM\\Booking\\DB::table("bookings"));')=='1'
 print('Concurrent capacity test passed: exactly one booking and one allocation for the last space.')
finally:
 php('foreach('+ 'json_decode('+json.dumps(json.dumps(tables))+',true)' +'as $t)$wpdb->query("DELETE FROM ".$t);')
 subprocess.run(['docker','exec',CONTAINER,'sh','-c','rm -f /tmp/vpm-stock-race-start /tmp/vpm-stock-race-ready-1 /tmp/vpm-stock-race-ready-2'],check=True)
