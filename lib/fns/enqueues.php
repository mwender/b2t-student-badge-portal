<?php

namespace B2TBadges\fns\enqueues;

function enqueue_scripts(){
    if( \b2t_is_endpoint( 'certification' ) || \b2t_is_endpoint( 'classes' )  ){
        wp_register_script( 'open-badges-issuer', 'https://backpack.openbadges.org/issuer.js', null, null, true );
        wp_register_script( 'handlebars', BADGE_PORTAL_PLUGIN_URL . 'lib/js/handlebars-v4.0.5.js', null, filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/handlebars-v4.0.5.js' ), true );

        wp_enqueue_script( 'b2t-badge-portal', BADGE_PORTAL_PLUGIN_URL . 'lib/js/badge-portal.js', ['jquery','handlebars','open-badges-issuer'], filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/badge-portal.js' ), true );

        $current_user = wp_get_current_user();
        wp_localize_script( 'b2t-badge-portal', 'wpvars', [
            'jsonurl' => 'https://b2tpart-b2t.cs77.force.com/certification/services/apexrest/Badge/',
            'user_email' => $current_user->user_email,
            'criteriaurl' => site_url( 'wp-json/' . BADGE_API_NAMESPACE . '/criteria?name=' ),
            'assertionurl' => site_url( 'wp-json/' . BADGE_API_NAMESPACE . '/assertions' ),
        ]);

        wp_enqueue_style( 'b2t-badge-portal', BADGE_PORTAL_PLUGIN_URL . 'lib/css/grid.css', null, filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/css/grid.css' ) );

        add_action('wp_footer', function(){
            $templates = file_get_contents( BADGE_PORTAL_PLUGIN_PATH . 'lib/hbs/handlebars-templates.hbs' );
            echo $templates;
        });
    }
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );