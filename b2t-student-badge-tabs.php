<?php
/**
 * Plugin Name:     B2T Student Badge Tabs
 * Plugin URI:      https://github.com/mwender/wpplugin-update-from-github
 * Description:     Adds Student Badge Portal Tabs to the My Account section inside WooCommerce
 * Author:          Michael Wender
 * Author URI:      https://mwender.com
 * Text Domain:     b2t-student-badge-tabs
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         B2t_Student_Badge_Tabs
 */
define( 'BADGE_PORTAL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BADGE_PORTAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BADGE_API_NAMESPACE', 'b2tbadges/v1' );

// We store SalesForce session credentials in $_SESSION['SF_SESSION']
session_start();

// Load libraries
require ( BADGE_PORTAL_PLUGIN_PATH . 'lib/typerocket/init.php' );

// Initialize Plugin Updates
/*
if( is_admin() ){
    require_once ( plugin_dir_path( __FILE__ ) . 'lib/classes/plugin-updater.php' );
    add_action( 'init', function(){
        // If you're experiencing GitHub API rate limits while testing
        // plugin updates, create a `Personal access token` under your
        // GitHub profile's `Developer Settings`. Then add
        // `define( 'GITHUB_ACCESS_TOKEN', your_access_token );` to
        // your site's `wp-config.php`.
        new GitHub_Plugin_Updater( __FILE__, 'mwender', 'b2t-student-badge-portal', GITHUB_ACCESS_TOKEN );
    } );
}
*/

/**
 * Check whether the currently viewed page is an endpoint
 *
 * @param      string  $endpoint  The endpoint
 *
 * @return     boolean  Returns TRUE if is an endpoint.
 */
function b2t_is_endpoint( $endpoint = false ){
    global $wp_query;

    if( ! $wp_query )
        return false;

    return isset( $wp_query->query[ $endpoint ] );
}

// Include files
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/badge_cpt.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/content.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/endpoints.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/enqueues.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/inlinestyles.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/salesforce.php' );
require_once( BADGE_PORTAL_PLUGIN_PATH . 'lib/fns/woocommerce.php' );