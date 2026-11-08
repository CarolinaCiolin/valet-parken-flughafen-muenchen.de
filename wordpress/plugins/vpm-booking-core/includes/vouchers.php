<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Vouchers {
    // Preview never consumes/reserves. Core::create calls this again while holding the coupon row lock.
    public static function evaluate( array $coupon, array $partner, int $base, string $email, ?int $now = null ): int {
        global $wpdb; $now = $now ?? time(); Config::require( (bool) $coupon, 'coupon_unknown' );
        Config::require( $now < (int) $coupon['ends_at'], 'coupon_expired' ); Config::require( $now >= (int) $coupon['starts_at'], 'coupon_not_started' );
        $hash = hash_hmac( 'sha256', Config::email( $email ), wp_salt( 'auth' ) );
        Config::require( ! $coupon['customer_hash'] || hash_equals( $coupon['customer_hash'], $hash ), 'coupon_wrong_customer' );
        Config::require( ( ! $coupon['airport'] || $coupon['airport'] === $partner['airport'] ) && ( ! $coupon['partner_id'] || (int) $coupon['partner_id'] === (int) $partner['id'] ), 'coupon_wrong_offer' );
        $used = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . DB::table( 'coupon_uses' ) . " WHERE coupon_id=%d AND customer_hash=%s AND state='consumed'", $coupon['id'], $hash ) );
        Config::require( $used < (int) $coupon['max_per_customer'] && (int) $coupon['used_uses'] < (int) $coupon['max_uses'], 'coupon_redeemed' );
        $held = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . DB::table( 'coupon_uses' ) . " WHERE coupon_id=%d AND customer_hash=%s AND state='reserved'", $coupon['id'], $hash ) );
        Config::require( $used + $held < (int) $coupon['max_per_customer'] && (int) $coupon['used_uses'] + (int) $coupon['held_uses'] < (int) $coupon['max_uses'], 'coupon_in_progress' );
        $discount = $coupon['kind'] === 'fixed' ? (int) $coupon['value'] : intdiv( $base * (int) $coupon['value'] + 5000, 10000 );
        Config::require( (int) $coupon['used_cents'] + (int) $coupon['held_cents'] + $discount <= (int) $coupon['budget_cents'], 'coupon_budget_exhausted' );
        return $discount;
    }
}
final class Loyalty {
    public static function grant( int $contact_id ): ?array {
        if ( ! get_option( 'vpm_booking_loyalty_enabled', false ) ) { return null; } global $wpdb;
        $contact = DB::row( 'contacts', 'id', $contact_id ); if ( ! $contact || $contact['state'] !== 'confirmed' ) { return null; }
        $b = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'bookings' ) . " WHERE customer_email=%s AND (payment_state IN ('paid','partially_refunded','refunded') OR stripe_pi<>'') ORDER BY created_at,id LIMIT 1", $contact['email'] ), ARRAY_A );
        if ( ! $b ) { return null; }
        return DB::tx( function() use ( $wpdb, $contact_id, $b ) {
            $b = Core::booking( $b['public_id'], true ); $c = DB::row( 'contacts', 'id', $contact_id, true );
            if ( ! $c || $c['state'] !== 'confirmed' || $b['state'] !== 'confirmed' || $b['payment_state'] !== 'paid' || (int) $b['refunded_cents'] > 0 || (int) $b['created_at'] < (int) get_option( 'vpm_booking_loyalty_starts', PHP_INT_MAX ) ) { return null; }
            if ( $wpdb->get_var( $wpdb->prepare( 'SELECT contact_id FROM ' . DB::table( 'loyalty' ) . ' WHERE contact_id=%d', $contact_id ) ) ) { return null; }
            $issued = time(); $expires = ( new \DateTimeImmutable( '@' . $issued ) )->add( new \DateInterval( 'P2Y' ) )->getTimestamp(); $hash = hash_hmac( 'sha256', $c['email'], wp_salt( 'auth' ) );
            $coupon = Core::coupon( array( 'code' => 'RETURN-' . strtoupper( bin2hex( random_bytes( 10 ) ) ), 'kind' => 'percent', 'value' => 500, 'starts_at' => $issued, 'ends_at' => $expires, 'max_uses' => 1, 'max_per_customer' => 1, 'budget_cents' => 5000000, 'customer_hash' => $hash ) );
            DB::insert( 'loyalty', array( 'contact_id' => $contact_id, 'coupon_id' => $coupon['id'], 'booking_id' => $b['id'], 'issued_at' => $issued ) );
            Automation::event( 'loyalty:' . $contact_id, 'loyalty.coupon_issued', 'marketing', array( 'recipient' => $c['email'], 'coupon_code' => $coupon['code'], 'discount_bps' => 500, 'expires_at' => $expires, 'consent_revision' => (int) $c['revision'], 'consent_version' => $c['consent_version'], 'unsubscribe_url' => Consent::unsubscribe_url( $c ) ), $contact_id );
            Operations::audit( 'loyalty.coupon_issued', 'coupon', (int) $coupon['id'] ); return $coupon;
        } );
    }
    public static function run(): void {
        if ( ! get_option( 'vpm_booking_loyalty_enabled', false ) ) { return; } global $wpdb; $cursor = (int) get_option( 'vpm_booking_loyalty_cursor', 0 );
        $ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . DB::table( 'contacts' ) . " WHERE state='confirmed' AND id>%d ORDER BY id LIMIT 20", $cursor ) );
        foreach ( $ids as $id ) { self::grant( (int) $id ); $cursor = (int) $id; }
        update_option( 'vpm_booking_loyalty_cursor', count( $ids ) < 20 ? 0 : $cursor, false );
    }
}
