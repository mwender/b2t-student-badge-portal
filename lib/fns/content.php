<?php

namespace B2TBadges\fns\content;

// More help here: https://iconicwp.com/blog/add-custom-page-account-area-woocommerce/

/**
 * Classes tab content
 */
function classes_endpoint_content() {
    echo 'Classes tab content.';
}
add_action( 'woocommerce_account_classes_endpoint', __NAMESPACE__ . '\\classes_endpoint_content' );

/**
 * Certification tab content
 */
function certification_endpoint_content() {
    echo 'Certification tab content.';
}
add_action( 'woocommerce_account_certification_endpoint', __NAMESPACE__ . '\\certification_endpoint_content' );