<?php

namespace B2TBadges\fns\enqueues;

function enqueue_scripts(){
    if( \b2t_is_endpoint( 'certification' ) || \b2t_is_endpoint( 'classes' )  ){
        wp_dequeue_script( 'divi-custom-script' );
        wp_register_script( 'handlebars', BADGE_PORTAL_PLUGIN_URL . 'lib/js/handlebars-v4.0.5.js', null, filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/handlebars-v4.0.5.js' ), true );

        $current_user = wp_get_current_user();
        $student_id = get_user_meta( $current_user->ID, 'sf_student_id', true );

        if( empty( $student_id ) ){
            if( ! isset( $_SESSION['SF_SESSION'] ) )
                \B2TBadges\fns\salesforce\login();

            $student = \B2TBadges\fns\salesforce\get_student_id([
                'access_token'  => $_SESSION['SF_SESSION']->access_token,
                'instance_url'  => $_SESSION['SF_SESSION']->instance_url,
                'email'         => $current_user->user_email,
            ]);

            if( ! is_wp_error( $student ) ){
                add_user_meta( $current_user->ID, 'sf_student_id', $student->student_id, true );
                $student_id = $student->student_id;
            } else {
                $student_id = '';
            }
        }

        add_action('wp_footer', function(){
            $templates = file_get_contents( BADGE_PORTAL_PLUGIN_PATH . 'lib/hbs/handlebars-templates.hbs' );
            echo $templates;
        });
    }
    if( \b2t_is_endpoint( 'certification' ) ){
        wp_register_script( 'open-badges-issuer', 'https://backpack.openbadges.org/issuer.js', null, null, true );
        wp_enqueue_script( 'b2t-badges-tab', BADGE_PORTAL_PLUGIN_URL . 'lib/js/badges-tab.js', ['jquery','handlebars','open-badges-issuer','jquery-ui-tabs'], filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/badges-tab.js' ), true );

        wp_localize_script( 'b2t-badges-tab', 'wpvars', [
            'jsonurl' => site_url( 'wp-json/' . BADGE_API_NAMESPACE . '/sf/' ),
            'student_id' => $student_id,
            'student_email' => $current_user->user_email,
            'criteriaurl' => site_url( 'wp-json/' . BADGE_API_NAMESPACE . '/criteria?name=' ),
            'assertionurl' => site_url( 'wp-json/' . BADGE_API_NAMESPACE . '/assertions' ),
            'default_badge' => BADGE_PORTAL_PLUGIN_URL . '/lib/img/b2t-default-badge.png',
            'nonce' => wp_create_nonce( 'wp_rest' )
        ]);


    }
    if( \b2t_is_endpoint( 'classes' ) ){
        wp_register_script( 'handlebars-intl', BADGE_PORTAL_PLUGIN_URL . 'lib/js/handlebars-intl.min.js', ['handlebars'], filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/handlebars-intl.min.js'), true );
        wp_enqueue_script( 'b2t-classes-tab', BADGE_PORTAL_PLUGIN_URL . 'lib/js/classes-tab.js', ['jquery','handlebars','handlebars-intl'], filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/classes-tab.js' ), true );

        wp_localize_script( 'b2t-classes-tab', 'wpvars', [
            'jsonurl' => site_url( 'wp-json/' . BADGE_API_NAMESPACE . '/sf/' ),
            'student_id' => $student_id,
            'student_email' => $current_user->user_email,
            'nonce' => wp_create_nonce( 'wp_rest' )
        ]);
    }
    wp_enqueue_style( 'b2t-badge-portal', BADGE_PORTAL_PLUGIN_URL . 'lib/css/main.css', null, filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/css/main.css' ) );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts', 20 );