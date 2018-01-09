<?php

namespace B2TBadges\fns\endpoint;

/**
 * Adds badge portal endpoints
 */
function badge_portal_endpoints(){
    add_rewrite_endpoint( 'classes', EP_PAGES );
    add_rewrite_endpoint( 'certification', EP_PAGES );
}
add_action( 'init', __NAMESPACE__ . '\\badge_portal_endpoints' );