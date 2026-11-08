<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Core {
    public static function partner( array $input ): array {
        $airport = strtoupper( (string) ( $input['airport'] ?? '' ) ); $account = (string) ( $input['stripe_account'] ?? '' );
        Config::require( (bool) preg_match( '/^[A-Z]{3}$/D', $airport ) && ( $account === '' || (bool) preg_match( '/^acct_[a-zA-Z0-9]{5,70}$/D', $account ) ), 'invalid_partner' );
        $name = sanitize_text_field( $input['name'] ?? '' ); Config::require( $name !== '' && strlen( $name ) <= 180, 'invalid_partner_name' );
        $id = DB::insert( 'partners', array( 'airport' => $airport, 'name' => $name, 'stripe_account' => $account, 'commission_bps' => Config::integer( $input['commission_bps'] ?? null, 0, 10000 ), 'active' => Config::integer( $input['active'] ?? 1, 0, 1 ) ) );
        return DB::row( 'partners', 'id', $id );
    }
    public static function coupon( array $input ): array {
        $code = strtoupper( trim( (string) ( $input['code'] ?? '' ) ) ); $kind = $input['kind'] ?? '';
        Config::require( (bool) preg_match( '/^[A-Z0-9_-]{3,40}$/D', $code ) && in_array( $kind, array( 'fixed', 'percent' ), true ), 'invalid_coupon' );
        $start = Config::integer( $input['starts_at'] ?? null, 0, PHP_INT_MAX ); $end = Config::integer( $input['ends_at'] ?? null, 1, PHP_INT_MAX ); Config::require( $end > $start, 'invalid_coupon_dates' );
        $airport = strtoupper( (string) ( $input['airport'] ?? '' ) ); Config::require( '' === $airport || (bool) preg_match( '/^[A-Z]{3}$/D', $airport ), 'invalid_airport' );
        $hash = $input['customer_hash'] ?? ''; Config::require( is_string( $hash ) && ( $hash === '' || preg_match( '/^[a-f0-9]{64}$/D', $hash ) ), 'invalid_coupon_customer' );
        $id = DB::insert( 'coupons', array( 'code' => $code, 'kind' => $kind, 'customer_hash' => $hash, 'value' => Config::integer( $input['value'] ?? null, 1, 'percent' === $kind ? 10000 : 100000000 ), 'starts_at' => $start, 'ends_at' => $end, 'max_uses' => Config::integer( $input['max_uses'] ?? null, 1, 1000000 ), 'max_per_customer' => Config::integer( $input['max_per_customer'] ?? 1, 1, 1000000 ), 'budget_cents' => Config::integer( $input['budget_cents'] ?? null, 1 ), 'airport' => $airport, 'partner_id' => Config::integer( $input['partner_id'] ?? 0, 0 ) ) );
        return DB::row( 'coupons', 'id', $id );
    }
    public static function booking( string $public, bool $lock = false ): array { $b = DB::row( 'bookings', 'public_id', $public, $lock ); Config::require( ! empty( $b ), 'booking_not_found' ); return $b; }
    public static function snapshot( array $booking ): array { return (array) json_decode( $booking['snapshot'], true ); }
    public static function create( array $input, string $key ): array {
        global $wpdb; Config::key( $key ); $email = Config::email( (string) ( $input['email'] ?? '' ) );
        $partner_id = Config::integer( $input['partner_id'] ?? null, 1 ); $base = Config::integer( $input['base_cents'] ?? null, 50 );
        $locale = $input['locale'] ?? 'de'; Config::require( in_array( $locale, array( 'de', 'en' ), true ), 'invalid_locale' );
        $code = strtoupper( trim( (string) ( $input['coupon'] ?? '' ) ) );
        $travel = $input['travel'] ?? array(); $terms = sanitize_text_field( $input['offer_terms'] ?? '' );
        Config::require( is_array( $travel ) && strlen( $terms ) <= 1200, 'invalid_offer_snapshot' );
        if ( $travel ) { Config::require( isset( $travel['airport'], $travel['timezone'], $travel['departure_utc'], $travel['return_utc'] ) && is_string( $travel['airport'] ) && in_array( $travel['timezone'], \DateTimeZone::listIdentifiers(), true ) && is_int( $travel['departure_utc'] ) && is_int( $travel['return_utc'] ) && $travel['departure_utc'] > 0 && $travel['return_utc'] > $travel['departure_utc'], 'invalid_travel_snapshot' ); $travel = array_intersect_key( $travel, array_flip( array( 'airport', 'timezone', 'departure_utc', 'return_utc' ) ) ); }
        $selection = $input['offer_selection'] ?? array(); Config::require( is_array( $selection ), 'invalid_offer_selection' ); if ( $selection ) { Config::require( ( $selection['total_cents'] ?? null ) === $base && is_array( $selection['addons'] ?? null ), 'invalid_offer_selection' ); }
        $fulfilment = $input['fulfilment'] ?? array(); $demo = !empty($input['sandbox_demo']); Config::require(!$demo || (defined('VPM_BOOKING_DEMO_MODE') && VPM_BOOKING_DEMO_MODE === true),'demo_disabled');
        $fingerprint_data = array( $email, $partner_id, $base, $locale, $code ); if ( $travel || $terms ) { $fingerprint_data[] = $travel; $fingerprint_data[] = $terms; }
        if ( $selection ) { $fingerprint_data[] = $selection; }
        if($fulfilment)$fingerprint_data[]=$fulfilment; if($demo)$fingerprint_data[]='sandbox_demo';
        $fingerprint = hash( 'sha256', wp_json_encode( $fingerprint_data ) );
        return DB::tx( function() use ( $wpdb, $key, $email, $partner_id, $base, $locale, $code, $fingerprint, $travel, $terms, $selection, $fulfilment, $demo ) {
            // Stable request keys are unique at database level; a collision cannot create two bookings.
            $old = DB::row( 'bookings', 'request_key', $key, true );
            if ( $old ) { Config::require( hash_equals( $old['request_hash'], $fingerprint ), 'idempotency_conflict' ); return $old; }
            $partner = DB::row( 'partners', 'id', $partner_id ); Config::require( ! empty( $partner ), 'partner_not_found' ); Config::require( (int) $partner['active'] === 1, 'partner_inactive' ); if ( $travel ) { Config::require( $travel['airport'] === $partner['airport'], 'travel_partner_mismatch' ); }
            $coupon = array(); $discount = 0; $customer_hash = hash_hmac( 'sha256', $email, wp_salt( 'auth' ) );
            if ( $code !== '' ) {
                $coupon = DB::row( 'coupons', 'code', $code, true );
                $discount = Vouchers::evaluate( $coupon, $partner, $base, $email );
            }
            $q = Finance::quote( $base, (int) $partner['commission_bps'], $discount ); if($fulfilment)$q['fulfilment']=$fulfilment; if($demo)$q['sandbox_demo']=true; $q['partner_service']=Logistics::snapshot($partner); $q['stripe_account'] = $partner['stripe_account']; $q['airport'] = $partner['airport']; $q['travel'] = $travel; $q['offer_terms'] = $terms; if ( $selection ) { $q['offer_selection'] = $selection; }
            $id = DB::insert( 'bookings', array( 'public_id' => wp_generate_uuid4(), 'request_key' => $key, 'request_hash' => $fingerprint, 'partner_id' => $partner_id, 'customer_email' => $email, 'locale' => $locale, 'snapshot' => wp_json_encode( $q ), 'state' => 'requested', 'payment_state' => 'unpaid', 'created_at' => time() ) );
            if($demo) DB::insert('demo',array('booking_id'=>$id,'state'=>'created','created_at'=>time()));
            if ( $coupon ) {
                DB::insert( 'coupon_uses', array( 'coupon_id' => $coupon['id'], 'booking_id' => $id, 'customer_hash' => $customer_hash, 'amount_cents' => $discount, 'state' => 'reserved' ) );
                DB::update( 'coupons', array( 'held_uses' => (int) $coupon['held_uses'] + 1, 'held_cents' => (int) $coupon['held_cents'] + $discount ), array( 'id' => $coupon['id'] ) );
            }
            $booking = DB::row( 'bookings', 'id', $id ); Automation::booking_event( $booking, 'booking.requested' ); return $booking;
        } );
    }
    public static function confirm( string $public ): array {
        return DB::tx( function() use ( $public ) { $b = self::booking( $public, true ); if(Flow::managed($b))Config::require(Flow::confirmation_ready($b),'partner_confirmation_not_ready'); Config::require( in_array( $b['state'], array( 'requested', 'confirmed' ), true ), 'invalid_booking_transition' ); DB::update( 'bookings', array( 'state' => 'confirmed' ), array( 'id' => $b['id'] ) ); $b['state'] = 'confirmed'; if ( $b['payment_state'] === 'paid' ) { Automation::booking_event( $b, 'booking.confirmed' ); Logistics::document_required($b); } return $b; } );
    }
    public static function coupon_state( array $booking, string $target ): void {
        global $wpdb; $use = (array) $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'coupon_uses' ) . ' WHERE booking_id=%d FOR UPDATE', $booking['id'] ), ARRAY_A );
        if ( ! $use || 'reserved' !== $use['state'] ) { return; }
        $c = DB::row( 'coupons', 'id', $use['coupon_id'], true );
        $data = array( 'held_uses' => (int) $c['held_uses'] - 1, 'held_cents' => (int) $c['held_cents'] - (int) $use['amount_cents'] );
        if ( 'consumed' === $target ) { $data['used_uses'] = (int) $c['used_uses'] + 1; $data['used_cents'] = (int) $c['used_cents'] + (int) $use['amount_cents']; }
        DB::update( 'coupons', $data, array( 'id' => $c['id'] ) ); DB::update( 'coupon_uses', array( 'state' => $target ), array( 'id' => $use['id'] ) );
    }
    public static function cancel( string $public ): array {
        return DB::tx( function() use ( $public ) { $b = self::booking( $public, true ); if ( 'cancelled' === $b['state'] ) { return $b; } DB::update( 'bookings', array( 'state' => 'cancelled' ), array( 'id' => $b['id'] ) ); $b['state'] = 'cancelled'; global $wpdb; DB::query($wpdb->prepare('UPDATE '.DB::table('changes')." SET state='completed',reviewed_at=%d WHERE booking_id=%d AND kind='cancellation'",time(),$b['id'])); self::coupon_state( $b, 'released' ); if ( in_array( $b['payment_state'], array( 'paid', 'partially_refunded' ), true ) ) { global $wpdb; $pending = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(cents),0) FROM ' . DB::table( 'refunds' ) . " WHERE booking_id=%d AND status IN ('pending','submitted','attention')", $b['id'] ) ); $remaining = self::snapshot( $b )['charged'] - (int) $b['refunded_cents'] - $pending; if ( $remaining > 0 ) { self::refund_row( $b, $remaining, 'confirmed-cancellation' ); } } Automation::booking_event( $b, 'booking.cancelled' ); return $b; } );
    }
    // Caller must hold booking row lock. Pending refunds reserve their amount too.
    public static function refund_row( array $b, int $cents, string $key ): array {
        global $wpdb; Config::key( $key ); Config::integer( $cents, 1 );
        $old = (array) $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'refunds' ) . ' WHERE booking_id=%d AND request_key=%s', $b['id'], $key ), ARRAY_A );
        if ( $old ) { Config::require( (int) $old['cents'] === $cents, 'idempotency_conflict' ); return $old; }
        Config::require( in_array( $b['payment_state'], array( 'paid', 'partially_refunded' ), true ), 'payment_not_refundable' );
        $pending = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(cents),0) FROM ' . DB::table( 'refunds' ) . " WHERE booking_id=%d AND status IN ('pending','submitted','attention')", $b['id'] ) );
        Config::require( (int) $b['refunded_cents'] + $pending + $cents <= self::snapshot( $b )['charged'], 'refund_exceeds_payment' );
        $id = DB::insert( 'refunds', array( 'public_id' => wp_generate_uuid4(), 'booking_id' => $b['id'], 'request_key' => $key, 'cents' => $cents, 'status' => 'pending' ) );
        Automation::booking_event( $b, 'refund.requested', array( 'refund_id' => $id, 'amount_cents' => $cents ), 'refund:' . $id ); return DB::row( 'refunds', 'id', $id );
    }
    public static function refund( string $public, int $cents, string $key ): array { return DB::tx( function() use ( $public, $cents, $key ) { return self::refund_row( self::booking( $public, true ), $cents, $key ); } ); }
}
