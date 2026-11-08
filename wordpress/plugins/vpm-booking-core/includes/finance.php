<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Finance {
    public static function quote( int $base, int $bps, int $discount ): array {
        Config::integer( $base, 50 ); Config::integer( $bps, 0, 10000 ); Config::integer( $discount, 0 );
        $commission = intdiv( $base * $bps + 5000, 10000 );
        Config::require( $discount <= $commission, 'subsidy_exceeds_commission_requires_funded_model' );
        $charged = $base - $discount; Config::require( $charged >= 50, 'stripe_minimum_or_free_booking_unsupported' );
        return array( 'base' => $base, 'commission' => $commission, 'discount' => $discount, 'charged' => $charged, 'partner' => $base - $commission, 'platform' => $commission - $discount, 'currency' => 'eur' );
    }
    public static function paid_entries( array $q ): array { return array( 'customer_funds' => $q['charged'], 'partner_payable' => -$q['partner'], 'commission_revenue' => -$q['commission'], 'promotion_expense' => $q['discount'] ); }
    private static function allocated( int $amount, int $refund, int $paid ): int { return intdiv( $amount * $refund + intdiv( $paid, 2 ), $paid ); }
    public static function refund_entries( array $q, int $before, int $after ): array {
        Config::require( $before >= 0 && $after > $before && $after <= $q['charged'], 'invalid_refund' );
        $partner = self::allocated( $q['partner'], $after, $q['charged'] ) - self::allocated( $q['partner'], $before, $q['charged'] );
        $coupon = self::allocated( $q['discount'], $after, $q['charged'] ) - self::allocated( $q['discount'], $before, $q['charged'] );
        $cash = $after - $before;
        return array( 'customer_funds' => -$cash, 'partner_payable' => $partner, 'commission_revenue' => $cash + $coupon - $partner, 'promotion_expense' => -$coupon );
    }
    public static function journal( array $booking, string $event, array $entries ): void {
        Config::require( array_sum( $entries ) === 0, 'unbalanced_journal' );
        foreach ( $entries as $account => $cents ) { DB::insert( 'ledger', array( 'booking_id' => $booking['id'], 'entry_key' => $event . ':' . $account, 'account' => $account, 'cents' => $cents, 'currency' => 'eur', 'created_at' => time() ) ); }
    }
}
