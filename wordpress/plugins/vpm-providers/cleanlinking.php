<?php
/** Consent-gated TradeTracker integration. No requests before marketing consent. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_footer', function () {
    if ( ! get_option( 'vpm_cleanlinking_enabled', false ) ) { return; }
    // Fail closed when consent management is absent; do not track previews/admins.
    if ( ! defined( 'cmplz_free' ) || is_admin() || is_preview() || ! is_singular() || current_user_can( 'manage_options' ) ) { return; }
    $page = get_queried_object();
    if ( ! $page instanceof WP_Post ) { return; }
    $content = $page->post_content;
    if ( ! preg_match( '~https?://(?:www\.)?(?:parkos\.de|parkvia\.com)/|\[vpm_providers\b~i', $content ) ) { return; }
    ?>
<script id="vpm-tradetracker-cleanlinking" type="text/plain" data-category="marketing">
(function () {
    if (window.vpmTradeTrackerLoaded) { return; }
    window.vpmTradeTrackerLoaded = true;
    window._viewTagOptions = {type: 'a', site: 518003, version: 1};
    var script = document.createElement('script');
    script.async = true;
    script.src = 'https://tm.tradetracker.net/public/viewTag.js';
    document.body.appendChild(script);
})();
</script>
    <?php
}, 20 );
