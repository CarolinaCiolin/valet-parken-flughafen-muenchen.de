<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Products {
    public static function parking_types(): array { return array( 'unspecified', 'outdoor', 'covered', 'garage', 'underground' ); }
    public static function label( $value, string $locale = 'de' ): string {
        if ( is_array( $value ) ) { $value = $value[$locale] ?? $value['de'] ?? ''; }
        Config::require( is_string( $value ) && strlen( $value ) <= 600, 'invalid_product_label' ); return sanitize_text_field( $value );
    }
    public static function normalize( array $offer ): array {
        $type = $offer['parking_type'] ?? 'unspecified'; Config::require( in_array( $type, self::parking_types(), true ), 'invalid_parking_type' );
        $attributes = $offer['attributes'] ?? array(); $addons = $offer['addons'] ?? array(); Config::require( is_array( $attributes ) && count( $attributes ) <= 12 && is_array( $addons ) && count( $addons ) <= 15, 'invalid_product_options' );
        $out = array( 'name' => self::label( $offer['product_name'] ?? '' ), 'parking_type' => $type, 'attributes' => array(), 'addons' => array() );
        // Keep both language labels in the stored offer, never translate commercial promises implicitly.
        foreach ( array( 'de', 'en' ) as $locale ) { $out['name_' . $locale] = self::label( $offer['product_name'] ?? '', $locale ); }
        foreach ( $attributes as $a ) { $out['attributes'][] = array( 'de' => self::label( $a, 'de' ), 'en' => self::label( $a, 'en' ) ); }
        $seen = array(); foreach ( $addons as $a ) {
            Config::require( is_array( $a ) && is_string( $a['id'] ?? null ) && preg_match( '/^[a-z0-9_-]{1,50}$/D', $a['id'] ) && ! isset( $seen[$a['id']] ), 'invalid_addon' ); $seen[$a['id']] = true;
            $item = array( 'id' => $a['id'], 'unit_amount_cents' => Config::integer( $a['unit_amount_cents'] ?? null, 0 ), 'max_quantity' => Config::integer( $a['max_quantity'] ?? 1, 1, 1000 ) );
            foreach ( array( 'de', 'en' ) as $locale ) { $item['label_' . $locale] = self::label( $a['label'] ?? '', $locale ); $item['description_' . $locale] = self::label( $a['description'] ?? '', $locale ); Config::require( $item['label_' . $locale] !== '', 'invalid_addon_label' ); }
            $out['addons'][] = $item;
        }
        return $out;
    }
    public static function public_data( array $product, string $locale ): array {
        $out = array( 'product_name' => $product['name_' . $locale], 'parking_type' => $product['parking_type'], 'attributes' => array_column( $product['attributes'], $locale ), 'addons' => array() );
        foreach ( $product['addons'] as $a ) { $out['addons'][] = array( 'id' => $a['id'], 'label' => $a['label_' . $locale], 'description' => $a['description_' . $locale], 'unit_amount_cents' => $a['unit_amount_cents'], 'max_quantity' => $a['max_quantity'] ); }
        return $out;
    }
    public static function selection( int $parking, array $product, $selected, string $locale ): array {
        Config::require( is_array( $selected ) && count( $selected ) <= 15, 'invalid_addon_selection' ); $lines = array(); $total = $parking; $seen = array();
        foreach ( $selected as $s ) {
            Config::require( is_array( $s ) && is_string( $s['id'] ?? null ) && ! isset( $seen[$s['id']] ) && count( array_diff( array_keys( $s ), array( 'id', 'quantity' ) ) ) === 0, 'invalid_addon_selection' );
            $found = null; foreach ( $product['addons'] as $a ) { if ( $a['id'] === $s['id'] ) { $found = $a; break; } } Config::require( $found !== null, 'addon_not_available' );
            $qty = Config::integer( $s['quantity'] ?? null, 1, $found['max_quantity'] ); $seen[$s['id']] = true; $line = $found['unit_amount_cents'] * $qty; $total += $line; Config::integer( $total, 50 );
            $lines[] = array( 'id' => $s['id'], 'label' => $found['label_' . $locale], 'description' => $found['description_' . $locale], 'unit_amount_cents' => $found['unit_amount_cents'], 'quantity' => $qty, 'amount_cents' => $line );
        }
        usort( $lines, function( $a, $b ) { return strcmp( $a['id'], $b['id'] ); } );
        return array( 'product_name' => $product['name_' . $locale], 'parking_type' => $product['parking_type'], 'attributes' => array_column( $product['attributes'], $locale ), 'parking_cents' => $parking, 'addons' => $lines, 'total_cents' => $total );
    }
    public static function catalogue(): array { global $wpdb; $v = get_option( 'vpm_booking_products', array() ); $out = is_array($v)?$v:array(); foreach($wpdb->get_results('SELECT * FROM '.DB::table('product_templates'),ARRAY_A) as $r) { $out[$r['id']]=array('partner_id'=>(int)$r['partner_id'],'offer'=>json_decode($r['payload'],true)); } return $out; }
    public static function save_template( array $d ): void {
        $partner = Operations::number( $d['partner_id'] ?? '', 1 ); Config::require( (bool) DB::row( 'partners', 'id', $partner ), 'partner_not_found' );
        $id = $d['product_id'] ?? ''; Config::require( is_string( $id ) && ( $id === '' || preg_match( '/^product_[a-f0-9]{12}$/D', $id ) ), 'invalid_product_id' );
        $catalogue = self::catalogue(); if ( $id !== '' ) { Config::require( isset( $catalogue[$id] ) && $catalogue[$id]['partner_id'] === $partner, 'catalog_product_partner_mismatch' ); }
        else { Config::require( count( $catalogue ) < 200, 'catalogue_limit' ); $id = 'product_' . bin2hex( random_bytes( 6 ) ); }
        $de = explode( "\n", (string) ( $d['attributes_de'] ?? '' ) ); $en = explode( "\n", (string) ( $d['attributes_en'] ?? '' ) ); $attributes = array();
        foreach ( $de as $i => $value ) { if ( trim( $value ) !== '' ) { $attributes[] = array( 'de' => $value, 'en' => trim( $en[$i] ?? '' ) ?: $value ); } }
        $offer = array( 'product_name' => array( 'de' => $d['name_de'] ?? '', 'en' => $d['name_en'] ?? '' ), 'parking_type' => $d['parking_type'] ?? '', 'attributes' => $attributes, 'addons' => array() );
        foreach ( is_array( $d['addons'] ?? null ) ? $d['addons'] : array() as $code => $a ) {
            if ( ! isset( $a['enabled'] ) ) { continue; }
            $offer['addons'][] = array( 'id' => $code, 'label' => array( 'de' => $a['label_de'] ?? '', 'en' => $a['label_en'] ?? '' ), 'description' => array( 'de' => $a['description_de'] ?? '', 'en' => $a['description_en'] ?? '' ), 'unit_amount_cents' => Operations::number( $a['unit_amount_cents'] ?? '' ), 'max_quantity' => Operations::number( $a['max_quantity'] ?? '', 1, 1000 ) );
        }
        if(!current_user_can('manage_options')){Tenants::authorize($partner,true);Config::require(!preg_match('~(?:https?://|www\.|mailto:|[\p{L}\p{N}._%+\-]+@[\p{L}\p{N}.\-]+\.[\p{L}]{2,})~iu',wp_json_encode($offer,JSON_UNESCAPED_SLASHES)),'external_contact_not_allowed');}
        self::normalize( $offer ); Config::require( self::label( $offer['product_name'], 'de' ) !== '' && self::label( $offer['product_name'], 'en' ) !== '', 'invalid_product_label' );
        global $wpdb; DB::query($wpdb->prepare('INSERT INTO '.DB::table('product_templates').' (id,partner_id,payload) VALUES (%s,%d,%s) ON DUPLICATE KEY UPDATE payload=IF(partner_id=VALUES(partner_id),VALUES(payload),payload)',$id,$partner,wp_json_encode($offer))); Operations::audit( 'product_template.saved', 'partner', $partner );
    }
    public static function enrich( array $offer, int $partner ): array {
        if ( ! isset( $offer['catalog_product_id'] ) ) { return $offer; }
        $catalogue = self::catalogue(); $id = $offer['catalog_product_id']; Config::require( is_string( $id ) && isset( $catalogue[$id] ) && $catalogue[$id]['partner_id'] === $partner, 'catalog_product_partner_mismatch' );
        $template = $catalogue[$id]['offer']; $available = $offer['available_addon_ids'] ?? array(); Config::require( is_array( $available ) && count( $available ) <= 15, 'invalid_addon_availability' );
        foreach ( $available as $code ) { Config::require( is_string( $code ) && in_array( $code, array_column( $template['addons'], 'id' ), true ), 'addon_not_available' ); }
        $template['addons'] = array_values( array_filter( $template['addons'], function( $a ) use ( $available ) { return in_array( $a['id'], $available, true ); } ) );
        return array_merge( $offer, $template );
    }
    public static function scope( string $airport ): string { return $airport . '.' . hash_hmac( 'sha256', 'airport:' . $airport, wp_salt( 'auth' ) ); }
    public static function check_scope( string $scope, string $airport ): void { if ( $scope !== '' ) { Config::require( hash_equals( self::scope( $airport ), $scope ), 'airport_scope_mismatch' ); } }
}
