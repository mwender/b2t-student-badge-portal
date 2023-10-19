<?php

namespace BadgePortal\fns\enqueues;

function enqueue_scripts(){
    if( \BadgePortal\fns\endpoint\is_endpoint( 'certification' ) || \BadgePortal\fns\endpoint\is_endpoint( 'classes' )  ){
        wp_dequeue_script( 'divi-custom-script' );

        wp_register_script( 'handlebars', BADGE_PORTAL_PLUGIN_URL . 'lib/js/handlebars-v4.0.5.js', null, filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/handlebars-v4.0.5.js' ), true );
        wp_register_script( 'handlebars-intl', BADGE_PORTAL_PLUGIN_URL . 'lib/js/handlebars-intl.min.js', ['handlebars'], filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/handlebars-intl.min.js'), true );

        $current_user = wp_get_current_user();
        $student_id = get_user_meta( $current_user->ID, 'sf_student_id', true );

        if( empty( $student_id ) ){
            if( ! isset( $_SESSION['SF_SESSION'] ) )
                \B2TBadges\fns\salesforce\login();

            if( array_key_exists( 'SF_SESSION', $_SESSION ) && is_object( $_SESSION['SF_SESSION'] ) && property_exists( $_SESSION['SF_SESSION'], 'access_token') && property_exists( $_SESSION['SF_SESSION'], 'instance_url') ){
                $student = \B2TBadges\fns\salesforce\get_student_id([
                    'access_token'  => $_SESSION['SF_SESSION']->access_token,
                    'instance_url'  => $_SESSION['SF_SESSION']->instance_url,
                    'email'         => $current_user->user_email,
                ]);
            } else {
                $student = new \WP_Error( 'noconnection', __( 'SalesForce session variable missing either `access_token` or `instance_url`.' ) );
            }

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
    if( \BadgePortal\fns\endpoint\is_endpoint( 'certification' ) ){
        wp_register_script( 'open-badges-issuer', 'https://backpack.openbadges.org/issuer.js', null, null, true );
        wp_enqueue_script( 'b2t-badges-tab', BADGE_PORTAL_PLUGIN_URL . 'lib/js/badges-tab.js', ['jquery','handlebars','handlebars-intl','open-badges-issuer','jquery-ui-tabs'], filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/badges-tab.js' ), true );

        wp_localize_script( 'b2t-badges-tab', 'wpvars', [
            'jsonurl' => site_url( 'wp-json/' . BADGE_API_NAMESPACE . '/sf/' ),
            'student_id' => $student_id,
            'student_email' => $current_user->user_email,
            'criteriaurl' => site_url( 'wp-json/' . BADGE_API_NAMESPACE . '/criteria?name=' ),
            'assertionurl' => site_url( 'wp-json/' . BADGE_API_NAMESPACE . '/assertions' ),
            'default_badge' => BADGE_PORTAL_PLUGIN_URL . 'lib/img/b2t-default-badge.png',
            'nonce' => wp_create_nonce( 'wp_rest' )
        ]);


    }
    if( \BadgePortal\fns\endpoint\is_endpoint( 'classes' ) ){

        wp_enqueue_script( 'b2t-classes-tab', BADGE_PORTAL_PLUGIN_URL . 'lib/js/classes-tab.js', ['jquery','handlebars','handlebars-intl'], filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/classes-tab.js' ), true );

        wp_localize_script( 'b2t-classes-tab', 'wpvars', [
            'jsonurl' => site_url( 'wp-json/' . BADGE_API_NAMESPACE . '/sf/' ),
            'student_id' => $student_id,
            'student_email' => $current_user->user_email,
            'nonce' => wp_create_nonce( 'wp_rest' )
        ]);
    }

    if( \is_account_page() )
        wp_enqueue_style( 'b2t-badge-portal', BADGE_PORTAL_PLUGIN_URL . 'lib/css/main.css', null, filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/css/main.css' ) );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts', 20 );

function enqueue_badge_scripts(){
    global $wp_query, $wp_styles, $wp_scripts, $post;

    // Don't do anything if we're not on a badge page
    if( is_admin() || 'badge' != $wp_query->query_vars['post_type'] )
        return;

    remove_action( 'wp_footer', 'b2t_divi_smooth_scroll_anchors' );

    foreach( $wp_styles->registered as $handle => $data ){
        if( ! stristr( $handle, 'admin' ) ){
            wp_deregister_style( $handle );
            wp_dequeue_style( $handle );
        }
    }

    foreach( $wp_scripts->registered as $handle => $data ){
        if( ! stristr( $handle, 'admin' ) ){
            wp_deregister_script( $handle );
            wp_dequeue_script( $handle );
        }
    }
    wp_enqueue_style( 'b2t-badge-portal', BADGE_PORTAL_PLUGIN_URL . 'lib/css/main.css', null, filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/css/main.css' ) );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_badge_scripts', 9999999 );