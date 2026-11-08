<?php
/** Copy the defines above WordPress's stop-editing line, never replace wp-config.php. */
// Only on the separate test installation; not the existing Munich site.
define('VPM_BOOKING_DEMO_MODE', true);
// Placeholder for future relay setup. This does not configure DNS or a mailbox.
// define('VPM_BOOKING_RELAY_DOMAIN', 'messages.YOUR-TEST-DOMAIN.example');
// No Stripe live keys, production mailing lists or real customer data on this installation.
