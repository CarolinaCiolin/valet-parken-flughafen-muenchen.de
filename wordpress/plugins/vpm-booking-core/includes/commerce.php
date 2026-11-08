<?php
namespace VPM\Booking;
if (!defined('ABSPATH')) exit;
/** Shared inventory, partner service hours and deterministic parking tariffs. */
final class Commerce {
    public static function number($v,int $min=0,int $max=100000000):int { if(is_int($v))return Config::integer($v,$min,$max);return Operations::number($v,$min,$max); }
    public static function get(int $partner):array { global $wpdb; return json_decode((string)$wpdb->get_var($wpdb->prepare('SELECT payload FROM '.DB::table('commerce').' WHERE partner_id=%d',$partner)),true)?:array(); }
    public static function minutes(string $time):int { Config::require((bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D',$time),'invalid_service_time');return (int)substr($time,0,2)*60+(int)substr($time,3,2); }
    public static function schedule(array $days):array {
        $out=array();foreach($days as $day=>$text){Config::require(in_array((int)$day,range(1,7),true)&&is_string($text),'invalid_service_hours');$text=trim($text);if($text==='')continue;
            $ranges=array();foreach(explode(',',$text)as $range){$parts=explode('-',trim($range));Config::require(count($parts)===2,'invalid_service_hours');$start=self::minutes(trim($parts[0]));$end=self::minutes(trim($parts[1]));$ranges[]=array($start,$end);}Config::require(count($ranges)<=4,'invalid_service_hours');$out[(int)$day]=$ranges;
        }return $out;
    }
    public static function save(int $partner,array $d):void {
        Tenants::authorize($partner,true);$services=array();foreach(array('parking','valet','shuttle')as $key){$s=$d['services'][$key]??array();if(empty($s['enabled']))continue;
            $basis=$s['fee_basis']??'booking';Config::require(in_array($basis,array('booking','leg'),true),'invalid_service_fee_basis');
            $services[$key]=array('fee_cents'=>self::number($s['fee_cents']??0,0,10000000),'fee_basis'=>$basis,'hours'=>self::schedule($s['hours']??array()),'night_start'=>self::minutes((string)($s['night_start']??'22:00')),'night_end'=>self::minutes((string)($s['night_end']??'06:00')),'night_arrival_cents'=>self::number($s['night_arrival_cents']??0,0,10000000),'night_return_cents'=>self::number($s['night_return_cents']??0,0,10000000));
        }
        $out=array('services'=>$services,'handover_minutes'=>self::number($d['handover_minutes']??180,120,180));global $wpdb;DB::query($wpdb->prepare('INSERT INTO '.DB::table('commerce').' (partner_id,payload) VALUES (%d,%s) ON DUPLICATE KEY UPDATE payload=VALUES(payload)',$partner,wp_json_encode($out)));Operations::audit('partner.services_saved','partner',$partner);
    }
    public static function open(array $hours,int $instant,string $timezone):bool {
        if(!$hours)return true; $dt=(new \DateTimeImmutable('@'.$instant))->setTimezone(new \DateTimeZone($timezone));$day=(int)$dt->format('N');$minute=(int)$dt->format('G')*60+(int)$dt->format('i');$previous=$day===1?7:$day-1;
        foreach($hours[$day]??array()as [$start,$end])if($start===$end||($start<$end&&$minute>=$start&&$minute<=$end)||($start>$end&&$minute>=$start))return true;
        foreach($hours[$previous]??array()as [$start,$end])if($start>$end&&$minute<=$end)return true;return false;
    }
    public static function services(int $partner,array $context):array {
        $profile=self::get($partner);$all=$profile['services']??array('parking'=>array('fee_cents'=>0,'fee_basis'=>'booking','hours'=>array(),'night_start'=>1320,'night_end'=>360,'night_arrival_cents'=>0,'night_return_cents'=>0));$out=array();
        foreach($all as $id=>$s){if(($context['service']??'any')!=='any'&&$context['service']!==$id)continue;if(self::open($s['hours'],$context['departure_utc'],$context['timezone'])&&self::open($s['hours'],$context['return_utc'],$context['timezone']))$out[$id]=$s;}return $out;
    }
    public static function rules(array $d):array {
        $rules=array('seasons'=>array(),'durations'=>array(),'discounts'=>array());foreach(array('seasons','durations','discounts')as $group){$rows=$d[$group]??array();Config::require(is_array($rows)&&count($rows)<=30,'invalid_tariff_rules');foreach($rows as $r){if(empty($r['enabled']))continue;
            if($group==='seasons'){ $start=(string)($r['start']??'');$end=(string)($r['end']??'');foreach(array($start,$end)as $date){$dt=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);Config::require($dt&&$dt->format('Y-m-d')===$date,'invalid_season');}Config::require($end>=$start,'invalid_season');foreach($rules[$group]as $old)Config::require($end<$old['start']||$start>$old['end'],'overlapping_seasons');$rules[$group][]=array('start'=>$start,'end'=>$end,'price_cents'=>self::number($r['price_cents']??0,50));
            }elseif($group==='durations'){$days=self::number($r['days']??0,1,181);foreach($rules[$group]as $old)Config::require($days!==$old['days'],'duplicate_duration');$rules[$group][]=array('days'=>$days,'price_cents'=>self::number($r['price_cents']??0,50));
            }else{$min=self::number($r['min_days']??1,1,181);$from=self::number($r['from_day']??1,1,181);$to=self::number($r['to_day']??181,1,181);Config::require($to>=$from,'invalid_discount_days');$kind=$r['kind']??'stay';Config::require(in_array($kind,array('stay','days'),true),'invalid_discount_kind');$rules[$group][]=array('min_days'=>$min,'from_day'=>$from,'to_day'=>$to,'kind'=>$kind,'bps'=>self::number($r['bps']??0,1,10000));}
        }}return $rules;
    }
    public static function price(array $rate,array $context,array $service):array {
        $days=(int)ceil(($context['return_utc']-$context['departure_utc'])/DAY_IN_SECONDS);$rules=json_decode($rate['rules']??'',true)?:array();$daily=array();$zone=new \DateTimeZone($context['timezone']);
        for($i=0;$i<$days;$i++){$amount=(int)$rate['price_cents'];$date=(new \DateTimeImmutable('@'.($context['departure_utc']+$i*DAY_IN_SECONDS)))->setTimezone($zone)->format('Y-m-d');foreach($rules['seasons']??array()as $r)if($date>=$r['start']&&$date<=$r['end'])$amount=$r['price_cents'];$daily[]=$amount;}
        $parking=$rate['pricing']==='24h'?array_sum($daily):(int)$rate['price_cents'];$fixed=false;foreach($rules['durations']??array()as $r)if($r['days']===$days){$parking=$r['price_cents'];$fixed=true;break;}
        $discount=0;foreach($rules['discounts']??array()as $r){if($days<$r['min_days'])continue;if($r['kind']==='stay')$eligible=$parking;else{if($rate['pricing']!=='24h'||$fixed)continue;$eligible=array_sum(array_slice($daily,$r['from_day']-1,$r['to_day']-$r['from_day']+1));}$discount=max($discount,intdiv($eligible*$r['bps']+5000,10000));}
        $fee=(int)$service['fee_cents']*($service['fee_basis']==='leg'?2:1);$night=array();foreach(array('arrival'=>'departure_utc','return'=>'return_utc')as $leg=>$field){$dt=(new \DateTimeImmutable('@'.$context[$field]))->setTimezone($zone);$m=(int)$dt->format('G')*60+(int)$dt->format('i');$start=$service['night_start'];$end=$service['night_end'];$in=$start===$end||($start<$end?($m>=$start&&$m<$end):($m>=$start||$m<$end));$night[$leg]=$in?(int)$service['night_'.$leg.'_cents']:0;}
        $total=$parking-$discount+$fee+array_sum($night);Config::integer($total,50);return array('days'=>$days,'parking_cents'=>$parking,'duration_discount_cents'=>$discount,'service_fee_cents'=>$fee,'arrival_surcharge_cents'=>$night['arrival'],'return_surcharge_cents'=>$night['return'],'total_cents'=>$total);
    }
    public static function decorate(array $rate,array $context,int $partner):array {
        $out=array();foreach(self::services($partner,$context)as $id=>$s){$price=self::price($rate,$context,$s);$out[]=array('service'=>$id,'breakdown'=>$price,'base_cents'=>$price['total_cents']);}return $out;
    }
}
