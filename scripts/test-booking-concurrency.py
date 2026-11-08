"""Two independent MySQL connections contend for a single coupon use, local test only."""
from pathlib import Path
import subprocess,json,time
base='define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);global $wpdb;$wpdb->suppress_errors(true);'
def php(c):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r',base+c],text=True)
tables=json.loads(php('echo json_encode($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%")));'))
for t in tables:assert php('echo $wpdb->get_var("SELECT COUNT(*) FROM '+t+'");')=='0','Nonempty booking tables refused'
try:
 setup=json.loads(php('$p=VPM\\Booking\\Core::partner(["airport"=>"FRA","name"=>"Race fixture","stripe_account"=>"acct_racefixture","commission_bps"=>2000]);$c=VPM\\Booking\\Core::coupon(["code"=>"RACE","kind"=>"fixed","value"=>1000,"max_uses"=>1,"budget_cents"=>1000,"starts_at"=>time()-60,"ends_at"=>time()+3600]);echo json_encode(["partner"=>(int)$p["id"],"coupon"=>(int)$c["id"]]);'))
 subprocess.run(['docker','exec','vpm-local-wp','sh','-c','rm -f /tmp/vpm-race-start /tmp/vpm-race-ready-1 /tmp/vpm-race-ready-2'],check=True)
 workers=[]
 for n in[1,2]:
  code=base+'file_put_contents("/tmp/vpm-race-ready-'+str(n)+'","ready");$until=microtime(true)+10;while(!file_exists("/tmp/vpm-race-start")&&microtime(true)<$until)usleep(10000);try{$b=VPM\\Booking\\Core::create(["partner_id"=>'+str(setup['partner'])+',"email"=>"race'+str(n)+'@example.invalid","base_cents"=>10000,"coupon"=>"RACE"],"race-booking-000'+str(n)+'");echo json_encode(["ok"=>true]);}catch(Throwable $e){echo json_encode(["ok"=>false,"code"=>$e->getMessage()]);}'
  workers.append(subprocess.Popen(['docker','exec','vpm-local-wp','php','-r',code],stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True))
 for _ in range(50):
  ready=subprocess.run(['docker','exec','vpm-local-wp','sh','-c','test -f /tmp/vpm-race-ready-1 && test -f /tmp/vpm-race-ready-2'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
  if ready.returncode==0:break
  time.sleep(.05)
 subprocess.run(['docker','exec','vpm-local-wp','touch','/tmp/vpm-race-start'],check=True)
 results=[]
 for w in workers:
  out,err=w.communicate(timeout=20);assert w.returncode==0,err;results.append(json.loads(out))
 assert sum(1 for r in results if r['ok'])==1,results
 c=json.loads(php('echo json_encode(VPM\\Booking\\DB::row("coupons","id",'+str(setup['coupon'])+'));'))
 assert int(c['held_uses'])==1 and int(c['held_cents'])==1000
 assert php('echo $wpdb->get_var("SELECT COUNT(*) FROM ".VPM\\Booking\\DB::table("bookings"));')=='1'
 print('Concurrent coupon test passed: exactly one booking, one reserved use and 1000-cent budget, no double redemption.')
finally:
 php('foreach('+ 'json_decode('+json.dumps(json.dumps(tables))+',true)' +'as $t)$wpdb->query("DELETE FROM ".$t);')
 subprocess.run(['docker','exec','vpm-local-wp','sh','-c','rm -f /tmp/vpm-race-start /tmp/vpm-race-ready-1 /tmp/vpm-race-ready-2'],check=True)
