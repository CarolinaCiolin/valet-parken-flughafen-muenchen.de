<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
interface PartnerAdapter {
    public function offers( array $partner, array $context, array $credentials ): array;
    public function hold( array $booking, array $credentials, string $key ): array;
    public function confirm( array $booking, array $credentials, string $key ): array;
    public function cancel( array $booking, array $credentials, string $key ): array;
    public function change( array $booking, array $change, array $credentials, string $key ): array;
    public function status( array $booking, array $credentials ): array;
}
final class Adapters {
    public static function has_source(string $airport):bool { global $wpdb,$wp_filter; $hook=$wp_filter['vpm_booking_quote_offers']??null; if($hook)foreach($hook->callbacks as $callbacks)foreach($callbacks as $entry)if($entry['function']!==array(self::class,'offers'))return true;return (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.DB::table('connections').' c INNER JOIN '.DB::table('partners').' p ON p.id=c.partner_id WHERE p.airport=%s AND p.active=1 AND c.mode IN (%s,%s)',$airport,'manual','external'))>0; }
    public static function registry(): array { $drivers = apply_filters( 'vpm_booking_adapters', array() ); $out = array(); foreach ( is_array( $drivers ) ? $drivers : array() as $id => $driver ) { if ( is_string( $id ) && preg_match( '/^[a-z0-9_-]{1,50}$/D', $id ) && $driver instanceof PartnerAdapter ) { $out[$id] = $driver; } } return $out; }
    public static function connection( int $partner ): array { global $wpdb; return (array) $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'connections' ) . ' WHERE partner_id=%d', $partner ), ARRAY_A ); }
    public static function configure( int $partner, array $d ): void {
        Tenants::authorize( $partner, true ); $mode = $d['mode'] ?? ''; Config::require( in_array( $mode, array( 'disabled', 'manual', 'external' ), true ), 'invalid_connection_mode' );
        $adapter = $mode === 'external' ? (string) ( $d['adapter'] ?? '' ) : ''; Config::require( $mode !== 'external' || isset( self::registry()[$adapter] ), 'adapter_not_installed' );
        $old = self::connection( $partner ); $secret = $d['api_key'] ?? ''; Config::require( is_string( $secret ) && strlen( $secret ) <= 4000, 'invalid_credentials' );
        global $wpdb; if($old&&($old['mode']!==$mode||$old['adapter']!==$adapter)) { $open=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.DB::table('bookings')." WHERE partner_id=%d AND state<>'cancelled' AND JSON_EXTRACT(snapshot,'$.fulfilment.mode') IS NOT NULL AND CAST(JSON_EXTRACT(snapshot,'$.travel.return_utc') AS UNSIGNED)>%d",$partner,time()));Config::require($open===0,'connection_has_active_bookings'); }
        $credentials = $mode === 'external' && $old && $old['adapter'] === $adapter && $secret === '' ? $old['credentials'] : Config::seal( $secret !== '' ? array( 'api_key' => $secret ) : array() );
        if ( ! empty( $d['clear_credentials'] ) ) { $credentials = Config::seal( array() ); }
        global $wpdb; DB::query( $wpdb->prepare( 'INSERT INTO ' . DB::table( 'connections' ) . ' (partner_id,mode,adapter,credentials,assistance,updated_at) VALUES (%d,%s,%s,%s,%d,%d) ON DUPLICATE KEY UPDATE mode=VALUES(mode),adapter=VALUES(adapter),credentials=VALUES(credentials),assistance=VALUES(assistance),updated_at=VALUES(updated_at)', $partner, $mode, $adapter, $credentials, empty( $d['assistance'] ) ? 0 : 1, time() ) );
        Operations::audit( 'partner.connection_saved', 'partner', $partner );
    }
    public static function save_rate( int $partner, array $d ): void {
        Tenants::authorize( $partner, true ); $id = (string) ( $d['product_id'] ?? '' ); $catalogue = Products::catalogue(); Config::require( isset( $catalogue[$id] ) && $catalogue[$id]['partner_id'] === $partner, 'product_not_owned' );
        $price = Operations::number( $d['price_cents'] ?? '', 50 ); $capacity = Operations::number( $d['capacity'] ?? '', 0, 100000 ); $pricing = $d['pricing'] ?? ''; Config::require( in_array( $pricing, array( 'stay', '24h' ), true ), 'invalid_pricing' );
        global $wpdb; DB::tx( function() use ( $wpdb, $partner, $id, $price, $capacity, $pricing, $d ) {
            // Lock partner to serialize creation of a previously absent rate; capacity acquisition locks rate too.
            DB::row( 'partners', 'id', $partner, true ); $old = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'rates' ) . ' WHERE partner_id=%d AND product_id=%s FOR UPDATE', $partner, $id ), ARRAY_A );
            if ( $old && $capacity < (int)$old['capacity'] ) { $busy = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . DB::table( 'allocations' ) . " WHERE rate_id=%d AND ends_at>%d AND (state='confirmed' OR (state='held' AND expires_at>%d))", $old['id'], time(), time() ) ); Config::require( $capacity >= $busy, 'capacity_below_reserved_total' ); }
            DB::query( $wpdb->prepare( 'INSERT INTO ' . DB::table( 'rates' ) . ' (partner_id,product_id,price_cents,pricing,capacity,enabled) VALUES (%d,%s,%d,%s,%d,%d) ON DUPLICATE KEY UPDATE price_cents=VALUES(price_cents),pricing=VALUES(pricing),capacity=VALUES(capacity),enabled=VALUES(enabled)', $partner, $id, $price, $pricing, $capacity, empty( $d['enabled'] ) ? 0 : 1 ) ); Operations::audit( 'partner.rate_saved', 'partner', $partner );
        } );
    }
    public static function available( array $rate, array $context, bool $lock = false ): bool {
        global $wpdb; $used = count( $wpdb->get_col( $wpdb->prepare( 'SELECT booking_id FROM ' . DB::table( 'allocations' ) . " WHERE rate_id=%d AND starts_at<%d AND ends_at>%d AND (state='confirmed' OR (state='held' AND expires_at>%d))", $rate['id'], $context['return_utc'], $context['departure_utc'], time() ) . ($lock?' FOR UPDATE':'') ) );
        return (bool) $rate['enabled'] && $used < (int) $rate['capacity'];
    }
    public static function offers( array $existing, array $context ): array {
        global $wpdb; $partners = $wpdb->get_results( $wpdb->prepare( 'SELECT p.* FROM ' . DB::table( 'partners' ) . ' p INNER JOIN ' . DB::table( 'connections' ) . " c ON c.partner_id=p.id WHERE p.airport=%s AND p.active=1 AND c.mode IN ('manual','external')", $context['airport'] ), ARRAY_A );
        foreach ( $partners as $p ) {
            $connection = self::connection( (int) $p['id'] );
            if ( $connection['mode'] === 'manual' ) {
                foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'rates' ) . ' WHERE partner_id=%d AND enabled=1', $p['id'] ), ARRAY_A ) as $rate ) {
                    if ( ! self::available( $rate, $context ) ) { continue; } $catalogue = Products::catalogue(); $template = $catalogue[$rate['product_id']] ?? null; if ( ! $template || $template['partner_id'] !== (int) $p['id'] ) { continue; }
                    $units = $rate['pricing'] === '24h' ? (int) ceil( ( $context['return_utc'] - $context['departure_utc'] ) / DAY_IN_SECONDS ) : 1; $price = (int) $rate['price_cents'] * $units; Config::integer( $price, 50 );
                    $existing[] = array( 'partner_id' => (int) $p['id'], 'base_cents' => $price, 'currency' => 'eur', 'terms' => $rate['pricing'] === '24h' ? 'Abrechnung je angefangene 24 Stunden.' : 'Parkpreis für den angefragten Aufenthalt.', 'catalog_product_id' => $rate['product_id'], 'available_addon_ids' => array_column( $template['offer']['addons'], 'id' ), '_source' => array( 'mode' => 'manual', 'rate_id' => (int) $rate['id'] ) );
                }
            } else {
                try { $drivers = self::registry(); Config::require( isset( $drivers[$connection['adapter']] ), 'adapter_not_installed' );
                $items = $drivers[$connection['adapter']]->offers( $p, $context, Config::open( $connection['credentials'] ) ); Config::require( count( $items ) <= 20, 'invalid_adapter_response' );
                foreach ( $items as $item ) { Config::require( (int) ( $item['partner_id'] ?? 0 ) === (int) $p['id'], 'adapter_partner_mismatch' ); $item['_source'] = array( 'mode' => 'external', 'adapter' => $connection['adapter'] ); $existing[] = $item; } $wpdb->update(DB::table('connections'),array('last_error'=>''),array('partner_id'=>$p['id']));
                } catch(\Throwable $e) { $wpdb->update(DB::table('connections'),array('last_error'=>'provider_unavailable'),array('partner_id'=>$p['id'])); }
            }
        } return $existing;
    }
}
