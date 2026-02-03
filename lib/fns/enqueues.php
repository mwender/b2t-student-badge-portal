<?php

namespace BadgePortal\fns\enqueues;
use function B2TBadges\fns\zoho\get_student_id;

function enqueue_scripts(){
    if( \BadgePortal\fns\endpoint\is_endpoint( 'certification' ) || \BadgePortal\fns\endpoint\is_endpoint( 'classes' )  ){

        wp_register_script( 'handlebars', BADGE_PORTAL_PLUGIN_URL . 'lib/js/handlebars-v4.0.5.js', null, filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/handlebars-v4.0.5.js' ), true );
        wp_register_script( 'handlebars-intl', BADGE_PORTAL_PLUGIN_URL . 'lib/js/handlebars-intl.min.js', ['handlebars'], filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/handlebars-intl.min.js'), true );

        $current_user = wp_get_current_user();
        $student_id = get_user_meta( $current_user->ID, 'zh_student_id', true );

        if( empty( $student_id ) ){
            $data = get_student_id( array( 'email' => $current_user->user_email ) );

            if( ! is_wp_error( $data ) ){
                update_user_meta( $current_user->ID, 'zh_student_id', $data->student_id );
                $student_id = $data->student_id;
            } else {
                uber_log( '[ERROR] get_student_id(' . $current_user->user_email . ') returned: ' . $data->get_error_message() . ' with code `' . $data->get_error_code() . '`.' );
                $student_id = '';
            }
        }

        add_action('wp_footer', function(){
            $templates = file_get_contents( BADGE_PORTAL_PLUGIN_PATH . 'lib/hbs/handlebars-templates.hbs' );
            echo $templates;
        });
    }
    if( \BadgePortal\fns\endpoint\is_endpoint( 'certification' ) ){
        wp_enqueue_script( 'b2t-badges-tab', BADGE_PORTAL_PLUGIN_URL . 'lib/js/badges-tab.js', ['jquery','handlebars','handlebars-intl','jquery-ui-tabs'], filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/badges-tab.js' ), true );

        wp_localize_script( 'b2t-badges-tab', 'wpvars', [
            'jsonurl' => rest_url( BADGE_API_NAMESPACE . '/zh/' ),
            'student_id' => $student_id,
            'student_email' => $current_user->user_email,
            'criteriaurl' => rest_url( BADGE_API_NAMESPACE . '/criteria?name=' ),
            'assertionurl' => rest_url( BADGE_API_NAMESPACE . '/assertions' ),
            'issueassertionurl' => rest_url( BADGE_API_NAMESPACE . '/issue-assertion' ),
            'default_badge' => BADGE_PORTAL_PLUGIN_URL . 'lib/img/b2t-default-badge.png',
            'nonce' => wp_create_nonce( 'wp_rest' )
        ]);


    }
    if( \BadgePortal\fns\endpoint\is_endpoint( 'classes' ) ){

        wp_enqueue_script( 'b2t-student-resources-popup', BADGE_PORTAL_PLUGIN_URL . 'lib/js/student-resources-popup.js', ['jquery'], filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/student-resources-popup.js' ) );
        
        // Get the Elementor popup ID from ACF Options (fall back to empty string if not set)
        $elementor_popup_id = get_field( 'student_resources_popup', 'option' );
        if ( empty( $elementor_popup_id ) ) {
          $elementor_popup_id = '';
        }
        wp_localize_script( 'b2t-student-resources-popup', 'popupvars', [
            'elementor_popup_id' => $elementor_popup_id,
        ]);

        wp_enqueue_script( 'b2t-classes-tab', BADGE_PORTAL_PLUGIN_URL . 'lib/js/classes-tab.js', ['jquery','handlebars','handlebars-intl'], filemtime( BADGE_PORTAL_PLUGIN_PATH . 'lib/js/classes-tab.js' ), true );

        wp_localize_script( 'b2t-classes-tab', 'wpvars', [
            'jsonurl' => site_url( 'wp-json/' . BADGE_API_NAMESPACE . '/zh/' ),
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