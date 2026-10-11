<?php
/** Local-only hook and synthetic consent fixture. Not HFCM and not distributed. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Fail fast for unavailable external admin APIs in this isolated, offline test.
add_filter('pre_http_request',function($result,$args,$url){
 if(wp_parse_url(home_url(),PHP_URL_HOST)==='127.0.0.1' && wp_parse_url($url,PHP_URL_HOST)!=='127.0.0.1'){return new WP_Error('vpm_local_offline','External APIs are unavailable in this local compatibility test.');}
 return $result;
},10,3);
add_action('init',function(){if(wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1' || !get_option('vpm_local_compatibility_fixture')){return;}
add_action('wp_head',function(){echo '<meta name="vpm-hook-check" content="head">';});
add_action('wp_body_open',function(){echo '<span id="vpm-body-hook-check" hidden></span>';});
add_action('wp_footer',function(){echo '<span id="vpm-footer-hook-check" hidden></span><script type="text/plain" class="cmplz-script" data-category="statistics">window.vpmSyntheticConsentRuns=(window.vpmSyntheticConsentRuns||0)+1;</script>';});
});
