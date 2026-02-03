<?php
/**
 * Open Badges (OB 2.0) REST endpoints for Badge Portal
 *
 * @see https://www.imsglobal.org/sites/default/files/Badges/OBv2p0Final/index.html
 */

namespace BadgePortal\fns\endpoint;

/**
 * Adds badge portal endpoints.
 *
 * @return void
 */
function badge_portal_endpoints() {
  add_rewrite_endpoint( 'classes', EP_PAGES );
  add_rewrite_endpoint( 'certification', EP_PAGES );
}
add_action( 'init', __NAMESPACE__ . '\\badge_portal_endpoints' );

/**
 * Check whether the currently viewed page is an endpoint.
 *
 * @param string $endpoint Endpoint name.
 * @return bool
 */
function is_endpoint( $endpoint = false ) {
  global $wp_query;

  if ( ! $wp_query ) {
    return false;
  }

  return isset( $wp_query->query[ $endpoint ] );
}

/**
 * Register Open Badges REST API endpoints.
 *
 * @return void
 */
function student_portal_endpoints() {

  /**
   * Returns an Open Badges 2.0 Assertion.
   *
   * Example:
   * https://example.com/wp-json/b2tbadges/v1/assertions
   *   ?email=user@example.com
   *   &badge=agile-analysis
   *   &completed=2017-04-21
   */
  register_rest_route(
    BADGE_API_NAMESPACE,
    '/assertions',
    [
      'methods'  => 'GET',
      'callback' => function( \WP_REST_Request $request ) {

        $email     = strtolower( trim( $request['email'] ) );
        $slug      = sanitize_title( $request['badge'] );
        $completed = $request['completed'];

        $issued_on = gmdate( 'Y-m-d', strtotime( $completed ) );

        // Canonical Assertion URL (hosted verification).
        $assertion_id = add_query_arg(
          [
            'email'     => $email,
            'badge'     => $slug,
            'completed' => $completed,
          ],
          site_url( 'wp-json/b2tbadges/v1/assertions' )
        );

        /**
         * Hash recipient identity.
         *
         * Assertions are publicly accessible, so recipient email
         * should not be exposed in clear text.
         */
        $recipient_hash = hash(
          'sha256',
          $email . wp_salt( 'auth' )
        );

        $assertion = [
          '@context'     => 'https://w3id.org/openbadges/v2',
          'type'         => 'Assertion',
          'id'           => $assertion_id,
          'recipient'    => [
            'type'     => 'email',
            'hashed'   => true,
            'identity' => 'sha256$' . $recipient_hash,
          ],
          'issuedOn'     => $issued_on,
          'badge'        => site_url(
            'wp-json/b2tbadges/v1/badge-class?name=' . $slug
          ),
          'verification' => [
            'type' => 'HostedBadge',
          ],
        ];

        return rest_ensure_response( $assertion );
      },
      'args'     => [
        'badge'     => [
          'validate_callback' => function( $param ) {
            if ( ! get_page_by_path( $param, OBJECT, 'badge' ) ) {
              return new \WP_Error(
                'b2t_badge_not_found',
                'No badge found with slug: ' . $param,
                [ 'status' => 404 ]
              );
            }
            return true;
          },
          'required' => true,
        ],
        'completed' => [
          'validate_callback' => function( $param ) {
            if ( ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $param ) ) {
              return new \WP_Error(
                'b2t_invalid_completed',
                '`completed` must be in format YYYY-MM-DD: ' . $param,
                [ 'status' => 400 ]
              );
            }
            return true;
          },
          'required' => true,
        ],
        'email'     => [
          'validate_callback' => function( $param ) {
            if ( ! is_email( $param ) ) {
              return new \WP_Error(
                'b2t_invalid_email',
                'Invalid email: ' . $param,
                [ 'status' => 400 ]
              );
            }
            return true;
          },
          'required' => true,
        ],
      ],
      'permission_callback' => '__return_true',
    ]
  );

  /**
   * Returns an Open Badges 2.0 BadgeClass.
   */
  register_rest_route(
    BADGE_API_NAMESPACE,
    '/badge-class',
    [
      'methods'  => 'GET',
      'callback' => function( \WP_REST_Request $request ) {

        $name  = sanitize_title( $request['name'] );
        $badge = get_page_by_path( $name, OBJECT, 'badge' );

        setup_postdata( $badge );

        $badge_class_id = site_url(
          'wp-json/b2tbadges/v1/badge-class?name=' . $name
        );

        $data = [
          '@context'    => 'https://w3id.org/openbadges/v2',
          'type'        => 'BadgeClass',
          'id'          => $badge_class_id,
          'name'        => $badge->post_title,
          'description' => get_post_meta(
            $badge->ID,
            'description',
            true
          ),
          'image'       => wp_get_attachment_url(
            get_post_meta( $badge->ID, 'badge_image', true )
          ),
          'criteria'    => get_permalink( $badge->ID ),
          'issuer'      => site_url( '/wp-json/b2tbadges/v1/issuer' ),
        ];

        return rest_ensure_response( $data );
      },
      'args'     => [
        'name' => [
          'validate_callback' => function( $param ) {
            $param = sanitize_title( $param );
            if ( ! get_page_by_path( $param, OBJECT, 'badge' ) ) {
              return new \WP_Error(
                'b2t_badge_not_found',
                'No badge found with slug: ' . $param,
                [ 'status' => 404 ]
              );
            }
            return true;
          },
          'required' => true,
        ],
      ],
      'permission_callback' => '__return_true',
    ]
  );

  /**
   * Returns Badge criteria (HTML + optional image).
   */
  register_rest_route(
    BADGE_API_NAMESPACE,
    '/criteria',
    [
      'methods'  => 'GET',
      'callback' => function( \WP_REST_Request $request ) {

        $name      = sanitize_title( $request['name'] );
        $completed = $request['completed'];
        $badge     = get_page_by_path( $name, OBJECT, 'badge' );

        $image_url = wp_get_attachment_url(
          get_post_meta( $badge->ID, 'badge_image', true )
        );

        if ( 'false' === $completed || empty( $completed ) ) {
          $grayscale_id = get_post_meta(
            $badge->ID,
            'badge_image_grayscale',
            true
          );
          if ( $grayscale_id ) {
            $image_url = wp_get_attachment_url( $grayscale_id );
          }
        }

        $data = [
          'slug'     => $name,
          'criteria' => apply_filters(
            'the_content',
            $badge->post_content
          ),
        ];

        if ( ! empty( $image_url ) ) {
          $data['image'] = $image_url;
        }

        return rest_ensure_response(
          [
            'success' => true,
            'data'    => $data,
          ]
        );
      },
      'args'     => [
        'name' => [
          'validate_callback' => function( $param ) {
            $param = sanitize_title( $param );
            if ( ! get_page_by_path( $param, OBJECT, 'badge' ) ) {
              return new \WP_Error(
                'b2t_badge_not_found',
                'No badge found with slug: ' . $param,
                [ 'status' => 404 ]
              );
            }
            return true;
          },
          'required' => true,
        ],
      ],
      'permission_callback' => '__return_true',
    ]
  );

  /**
   * Returns an Open Badges 2.0 Issuer profile.
   */
  register_rest_route(
    BADGE_API_NAMESPACE,
    '/issuer',
    [
      'methods'  => 'GET',
      'callback' => function() {

        $issuer_id = site_url( '/wp-json/b2tbadges/v1/issuer' );

        $data = [
          '@context' => 'https://w3id.org/openbadges/v2',
          'type'     => 'Issuer',
          'id'       => $issuer_id,
          'name'     => get_bloginfo( 'name' ),
          'url'      => get_bloginfo( 'url' ),
          'image'    => get_site_icon_url(),
        ];

        return rest_ensure_response( $data );
      },
      'permission_callback' => '__return_true',
    ]
  );
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\student_portal_endpoints' );
