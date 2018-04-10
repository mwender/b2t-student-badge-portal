<?php
/*
 * Open Badges Docs: https://github.com/mozilla/openbadges-backpack/wiki/Using-the-Issuer-API
 */
namespace B2TBadges\fns\endpoint;

/**
 * Adds badge portal endpoints
 */
function badge_portal_endpoints(){
    add_rewrite_endpoint( 'classes', EP_PAGES );
    add_rewrite_endpoint( 'certification', EP_PAGES );
}
add_action( 'init', __NAMESPACE__ . '\\badge_portal_endpoints' );

/**
 * Open Badges endpoint definitions
 */
function student_portal_endpoints(){

  /**
   * Returns Open Badges assertion
   *
   * Example request: https://example.com/wp-json/b2tbadges/v1/assertions?email=webmaster@example.com&badge=agile-analysis&completed=2017-04-21
   */
  register_rest_route( BADGE_API_NAMESPACE, '/assertions', [
    'methods' => 'GET',
    'callback' => function( \WP_REST_Request $request ){
      $email = $request['email'];
      $slug = sanitize_title( $request['badge'] );
      $completed = $request['completed'];

      $assertion = [
          'uid' => hash( 'sha256', $email ),
          'recipient' => [
              'type' => 'email',
              'identity' => $email,
              'hashed' => false
          ],
          'issuedOn' => date('Y-m-d', strtotime( $completed ) ),
          'badge' => site_url( 'wp-json/b2tbadges/v1/badge-class?name=' . $slug ),
          'verify' => [
              'type' => 'hosted',
              'url' => site_url( 'wp-json/b2tbadges/v1/assertions?email=' . $email . '&badge=' . $slug . '&completed=' . $completed )
          ]
      ];

      return $assertion;
    },
    'args' => [
      'badge' => [
        'validate_callback' => function($param, $request, $key){
          if( ! get_page_by_path( $param, OBJECT, 'badge' ) )
            return wp_send_json_error( 'No badge found with slug: ' . $param );

          return true;
        },
        'required' => true
      ],
      'completed' => [
        'validate_callback' => function($param, $request, $key){
          if( ! preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $param ) )
            return wp_send_json_error( '`completed` must be in format YYYY-MM-DD: ' . $param );

          return true;
        },
        'required' => true
      ],
      'email' => [
        'validate_callback' => function($param, $request, $key){
          if( ! is_email( $param ) )
            return wp_send_json_error( 'Invalid email: ' . $param );

          return true;
        },
        'required' => true
      ]
    ]
  ]);

  /**
   * Returns an Open Badges class definition
   */
  register_rest_route( BADGE_API_NAMESPACE, '/badge-class', [
    'methods' => 'GET',
    'callback' => function( \WP_REST_Request $request ){

      // Returns an Open Badges badge class definition
      $name = $request['name'];
      $badge = get_page_by_path( $name, OBJECT, 'badge' );

      setup_postdata( $badge );

      $data = [];
      $data['name'] = $badge->post_title;
      $data['description'] = get_post_meta( $badge->ID, 'description', true );
      $data['image'] = wp_get_attachment_url( get_post_meta( $badge->ID, 'badge_image', true ) );
      $data['criteria'] = get_permalink( $badge->ID );
      $data['issuer'] = site_url( '/wp-json/b2tbadges/v1/issuer' );

      return $data;
    },
    'args' => [
      'name' => [
        'validate_callback' => function($param, $request, $key){
          if( ! get_page_by_path( $param, OBJECT, 'badge' ) )
            return wp_send_json_error( 'No badge found with slug: ' . $param );

          return true;
        },
        'required' => true
      ]
    ]
  ]);

  /**
   * Returns the Badge criteria
   */
  register_rest_route( BADGE_API_NAMESPACE, '/criteria', [
    'methods' => 'GET',
    'callback' => function( \WP_REST_Request $request ){
      $name = $request['name'];
      $name = sanitize_title( $name );
      $badge = get_page_by_path( $name, OBJECT, 'badge' );

      $data = [];
      $data['slug'] = $name;
      $data['criteria'] = apply_filters( 'the_content', $badge->post_content );

      $image_url = wp_get_attachment_url( get_post_meta( $badge->ID, 'badge_image', true ) );
      if( ! empty( $image_url ) )
        $data['image'] = $image_url;

      return ['success' => true, 'data' => $data];
    },
    'args' => [
      'name' => [
        'validate_callback' => function($param, $request, $key){
          $param = sanitize_title( $param );
          if( ! get_page_by_path( $param, OBJECT, 'badge' ) )
            wp_send_json_error( ['slug' => $param, 'message' => 'No badge found with slug: ' . $param] );

          return true;
        },
        'required' => true
      ]
    ]
  ]);

  /**
   * Returns an Open Badges Issuer definition
   */
  register_rest_route( BADGE_API_NAMESPACE, '/issuer', [
    'methods' => 'GET',
    'callback' => function(){

      // Return Open Badges Issuer definition
      $data = [];
      $data['name'] = get_bloginfo( 'name' );
      $data['url'] = get_bloginfo( 'url' );
      $data['image'] = get_site_icon_url();

      return $data;
    }
  ]);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\student_portal_endpoints' );