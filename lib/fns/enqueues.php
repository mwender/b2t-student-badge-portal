<?php

namespace B2TBadges\fns\enqueues;

function enqueue_scripts(){
    if( \b2t_is_endpoint( 'certification' ) || \b2t_is_endpoint( 'classes' )  ){
        wp_enqueue_script( 'handlebars', BADGE_PORTAL_PLUGIN_URL . 'lib/js/handlebars-v4.0.5.js', null, filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/handlebars-v4.0.5.js' ) );

        wp_enqueue_script( 'b2t-badge-portal', BADGE_PORTAL_PLUGIN_URL . 'lib/js/badge-portal.js', ['jquery','handlebars'], filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/badge-portal.js' ), true );
        // TODO: dynamically insert user's email address
        wp_localize_script( 'b2t-badge-portal', 'wpvars', ['jsonurl' => 'https://b2tpart-b2t.cs77.force.com/certification/services/apexrest/Badge/', 'user_email' => 'jabbott@sfgmembers.com'] );
        add_action('wp_footer', function(){
            $templates = file_get_contents( BADGE_PORTAL_PLUGIN_PATH . 'lib/hbs/handlebars-templates.hbs' );
            echo $templates;
        });
    }
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );