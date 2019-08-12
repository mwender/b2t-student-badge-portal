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
  $items = array_slice( $items, 0, 2, true ) + [ 'classes' => __( 'Classes/Exams', 'b2t-student-badge-tabs' ) ] + [ 'certification' => __( 'Certification Program', 'b2t-student-badge-tabs' ) ] + array_slice( $items, 2, null, true ) ;

  foreach ($items as $key => $value) {
    $items[$key] = ucwords( $value );
  }

  $new_items = [
    'dashboard' => $items['dashboard'],
    'certification' => $items['certification'],
    'classes' => $items['classes'],
    'orders' => $items['orders'],
    /*'subscriptions' => $items['subscriptions'],*/
    'downloads' => $items['downloads'],
    'edit-account' => $items['edit-account'],
    'edit-address' => $items['edit-address'],
    'payment-methods' => $items['payment-methods'],
    'customer-logout' => $items['customer-logout'],
  ];

  return $new_items;
}
add_filter( 'woocommerce_account_menu_items', __NAMESPACE__ . '\\add_tabs', 20 );

/**
 * Adds an additional message to login errors
 *
 * @param      string  $error  The error
 *
 * @return     string  Modified login error message.
 */
function login_error_notice( $error ){

  if( stristr( $error, 'The password you entered' )
      || stristr( $error, 'Invalid username' ) )
    $error.= '<br /><br /><strong>IMPORTANT:</strong> If you’ve never purchased anything on our website, you will not have an account, even if you’ve taken a class with us. Please choose the “Register” option to create an account so that you can view your progress in our Certification Program, your class/exam history, etc.';

  return $error;
}
add_filter( 'login_errors', __NAMESPACE__ . '\\login_error_notice' );

/**
 * Adds a mobile menu showhide.
 */
function add_mobile_menu_showhide(){
?>
<label for="show-menu" class="show-menu">Show Menu &darr;</label>
<input type="checkbox" id="show-menu" role="button" />
<?php
}
add_action('woocommerce_before_account_navigation', __NAMESPACE__ . '\\add_mobile_menu_showhide');