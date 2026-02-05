<?php
/**
 * Plugin Name:     B2T Student Badge Tabs
 * Plugin URI:      https://github.com/mwender/wpplugin-update-from-github
 * Description:     Adds Student Badge Portal Tabs to the My Account section inside WooCommerce
 * Author:          Michael Wender
 * Author URI:      https://mwender.com
 * Text Domain:     b2t-student-badge-tabs
 * Domain Path:     /languages
 * Version:         1.6.0
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
if ( ! defined( 'IS_LOCAL' ) )
  define( 'IS_LOCAL', false );


// Load libraries
require ( BADGE_PORTAL_PLUGIN_PATH . 'lib/typerocket/init.php' );

// Include files
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/badge_cpt.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/cors.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/content.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/endpoints.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/enqueues.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/inlinestyles.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/student-resources.rest-api.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/utilities.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/woocommerce.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/zoho.php' );

/**
 * Create the badge assertions table.
 *
 * @return void
 */
function b2t_create_badge_assertions_table() {
  global $wpdb;

  $table_name      = $wpdb->prefix . 'b2t_badge_assertions';
  $charset_collate = $wpdb->get_charset_collate();

  $sql = "
    CREATE TABLE {$table_name} (
      assertion_id CHAR(64) NOT NULL,
      recipient_hash CHAR(64) NOT NULL,
      badge_slug VARCHAR(190) NOT NULL,
      issued_on DATE NOT NULL,
      assertion_json LONGTEXT NOT NULL,
      created_at DATETIME NOT NULL,
      revoked_at DATETIME NULL,
      PRIMARY KEY (assertion_id),
      KEY badge_slug (badge_slug),
      KEY issued_on (issued_on)
    ) {$charset_collate};
  ";

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta( $sql );
}

register_activation_hook(
  __FILE__,
  'b2t_create_badge_assertions_table'
);
