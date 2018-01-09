<?php
/**
 * Plugin Name:     B2T Student Badge Tabs
 * Plugin URI:      https://github.com/mwender/wpplugin-update-from-github
 * Description:     Adds Student Badge Portal Tabs to the My Account section inside WooCommerce
 * Author:          Michael Wender
 * Author URI:      https://mwender.com
 * Text Domain:     b2t-student-badge-tabs
 * Domain Path:     /languages
 * Version:         1.0.2
 *
 * @package         B2t_Student_Badge_Tabs
 */

// Initialize Plugin Updates
require_once ( plugin_dir_path( __FILE__ ) . 'lib/classes/plugin-updater.php' );
if( is_admin() ){
    add_action( 'init', function(){
        // If you're experiencing GitHub API rate limits while testing
        // plugin updates, create a `Personal access token` under your
        // GitHub profile's `Developer Settings`. Then add
        // `define( 'GITHUB_ACCESS_TOKEN', your_access_token );` to
        // your site's `wp-config.php`.
        new GitHub_Plugin_Updater( __FILE__, 'mwender', 'wpplugin-update-from-github', GITHUB_ACCESS_TOKEN );
    } );
}

// Include files
require_once( plugin_dir_path( __FILE__ ) . 'lib/fns/content.php' );
require_once( plugin_dir_path( __FILE__ ) . 'lib/fns/endpoints.php' );
require_once( plugin_dir_path( __FILE__ ) . 'lib/fns/woocommerce.php' );