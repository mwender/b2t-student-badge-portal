<?php

namespace B2TBadges\fns\woocommerce;

/**
 * Adds tabs to My Account nav.
 *
 * @param      array  $items  The nav items
 *
 * @return     array  Ammended nav items array
 */
function add_tabs( $items ){
    $items = array_slice( $items, 0, 2, true ) + [ 'classes' => __( 'Classes', 'b2t-student-badge-tabs' ) ] + [ 'certification' => __( 'Certification Program', 'b2t-student-badge-tabs' ) ] + array_slice( $items, 2, null, true ) ;

    foreach ($items as $key => $value) {
        $items[$key] = ucwords( $value );
    }

    return $items;
}
add_filter( 'woocommerce_account_menu_items', __NAMESPACE__ . '\\add_tabs' );
