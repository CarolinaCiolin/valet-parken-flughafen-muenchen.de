<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Tenants {
    public static function role( int $partner ): string {
        if ( current_user_can( 'manage_options' ) ) { return 'platform'; } global $wpdb;
        return (string) $wpdb->get_var( $wpdb->prepare( 'SELECT role FROM ' . DB::table( 'memberships' ) . ' WHERE partner_id=%d AND user_id=%d', $partner, get_current_user_id() ) );
    }
    public static function authorize( int $partner, bool $write = false ): void {
        Config::require( is_user_logged_in() && $partner > 0 && (bool) DB::row( 'partners', 'id', $partner ), 'portal_forbidden' );
        $role = self::role( $partner ); Config::require( in_array( $role, $write ? array( 'platform', 'owner', 'manager', 'support' ) : array( 'platform', 'owner', 'manager', 'support', 'viewer' ), true ), 'portal_forbidden' );
    }
    public static function assign( int $partner, int $user, string $role ): void {
        Config::require( current_user_can( 'manage_options' ), 'portal_forbidden' ); Config::require( DB::row( 'partners', 'id', $partner ) && get_userdata( $user ), 'membership_target_missing' );
        Config::require( in_array( $role, array( 'owner', 'manager', 'support', 'viewer', 'remove' ), true ), 'invalid_portal_role' ); global $wpdb;
        if ( $role === 'remove' ) { $wpdb->delete( DB::table( 'memberships' ), array( 'partner_id' => $partner, 'user_id' => $user ) ); }
        else { DB::query( $wpdb->prepare( 'INSERT INTO ' . DB::table( 'memberships' ) . ' (partner_id,user_id,role,created_at) VALUES (%d,%d,%s,%d) ON DUPLICATE KEY UPDATE role=VALUES(role)', $partner, $user, $role, time() ) ); }
        Operations::audit( 'portal.membership_' . $role, 'partner', $partner );
    }
    public static function partners(): array {
        global $wpdb; if ( current_user_can( 'manage_options' ) ) { return $wpdb->get_results( 'SELECT id,name,airport FROM ' . DB::table( 'partners' ) . ' ORDER BY name', ARRAY_A ); }
        return $wpdb->get_results( $wpdb->prepare( 'SELECT p.id,p.name,p.airport FROM ' . DB::table( 'partners' ) . ' p INNER JOIN ' . DB::table( 'memberships' ) . ' m ON m.partner_id=p.id WHERE m.user_id=%d ORDER BY p.name', get_current_user_id() ), ARRAY_A );
    }
    public static function bookings( int $partner, int $page = 1 ): array {
        self::authorize( $partner ); global $wpdb; $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'bookings' ) . ' WHERE partner_id=%d ORDER BY id DESC LIMIT 25 OFFSET %d', $partner, ( min(100000,max( 1, $page )) - 1 ) * 25 ), ARRAY_A ); $out = array();
        foreach ( $rows as $b ) { $q = Core::snapshot( $b ); $out[] = array( 'reference' => $b['public_id'], 'email' => $b['customer_email'], 'state' => $b['state'], 'payment_state' => $b['payment_state'], 'checkout_started' => (bool) $b['stripe_session'], 'partner_expected_cents' => $q['partner'], 'travel' => $q['travel'] ?? array(), 'selection' => $q['offer_selection'] ?? array(), 'created_at' => (int) $b['created_at'] ); } return $out;
    }
    public static function stats( ?int $partner ): array {
        if ( $partner !== null ) { self::authorize( $partner ); } else { Config::require( current_user_can( 'manage_options' ), 'portal_forbidden' ); } global $wpdb;
        $scope = $partner !== null ? $wpdb->prepare( ' AND b.partner_id=%d', $partner ) : '';
        $where = ' WHERE NOT EXISTS (SELECT 1 FROM ' . DB::table( 'demo' ) . ' d WHERE d.booking_id=b.id)' . $scope;
        $row = $wpdb->get_row( 'SELECT COUNT(*) enquiries,COUNT(DISTINCT b.customer_email) customers,COALESCE(SUM(b.stripe_session IS NOT NULL),0) checkouts,COALESCE(SUM(b.payment_state IN (\'paid\',\'partially_refunded\',\'refunded\')),0) paid,COALESCE(SUM(b.stripe_session IS NOT NULL AND b.payment_state IN (\'paid\',\'partially_refunded\',\'refunded\')),0) checkout_paid FROM ' . DB::table( 'bookings' ) . ' b' . $where, ARRAY_A );
        $out = array_map( 'intval', $row ); $out['paid_checkout_ratio_percent'] = $out['checkouts'] ? round( 100 * $out['checkout_paid'] / $out['checkouts'], 2 ) : null;
        // Signed accounting entries reconcile refunds; these are ledger entitlements, not bank payouts.
        $ledger = $wpdb->get_results( 'SELECT l.account,SUM(l.cents) cents FROM ' . DB::table( 'ledger' ) . ' l INNER JOIN ' . DB::table( 'bookings' ) . ' b ON b.id=l.booking_id' . $where . ' GROUP BY l.account', ARRAY_A ); $sums = array_column( $ledger, 'cents', 'account' );
        $out['partner_entitlement_cents'] = -(int) ( $sums['partner_payable'] ?? 0 );
        if ( $partner === null ) { $out['commission_cents'] = -(int) ( $sums['commission_revenue'] ?? 0 ); $out['discount_cost_cents'] = (int) ( $sums['promotion_expense'] ?? 0 ); $out['net_commission_cents'] = $out['commission_cents'] - $out['discount_cost_cents']; $out['newsletter_confirmed'] = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . DB::table( 'contacts' ) . " WHERE state='confirmed'" ); }
        return $out;
    }
}
