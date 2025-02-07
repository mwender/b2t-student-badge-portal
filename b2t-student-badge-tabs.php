<?php
/**
 * Plugin Name:     B2T Student Badge Tabs
 * Plugin URI:      https://github.com/mwender/wpplugin-update-from-github
 * Description:     Adds Student Badge Portal Tabs to the My Account section inside WooCommerce
 * Author:          Michael Wender
 * Author URI:      https://mwender.com
 * Text Domain:     b2t-student-badge-tabs
 * Domain Path:     /languages
 * Version:         1.3.1
 *
 * @package         B2t_Student_Badge_Tabs
 */

 // We store SalesForce session credentials in $_SESSION['SF_SESSION']
if( ! headers_sent() )
  session_start();

define( 'BADGE_PORTAL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BADGE_PORTAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BADGE_API_NAMESPACE', 'b2tbadges/v1' );
define( 'BADGE_LOCALE', get_locale() );

// Load libraries
require ( BADGE_PORTAL_PLUGIN_PATH . 'lib/typerocket/init.php' );

// Include files
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/badge_cpt.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/content.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/endpoints.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/enqueues.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/inlinestyles.php' );
//require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/salesforce.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/zoho.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/woocommerce.php' );

/**
 * Enhanced logging.
 *
 * @param      string  $message  The log message
 */
if( ! function_exists( 'uber_log' ) ){
  function uber_log( $message = null ){
    static $counter = 1;

    $bt = debug_backtrace();
    $caller = array_shift( $bt );

    if( 1 == $counter )
      error_log( "\n\n" . str_repeat('-', 25 ) . ' STARTING DEBUG [' . date('h:i:sa', current_time('timestamp') ) . '] ' . str_repeat('-', 25 ) . "\n\n" );
    error_log( "\n" . $counter . '. ' . basename( $caller['file'] ) . '::' . $caller['line'] . "\n" . $message . "\n---\n" );
    $counter++;
  }
}