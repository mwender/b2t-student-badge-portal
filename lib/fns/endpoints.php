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
 * Build a deterministic, opaque assertion token.
 *
 * Used as the canonical assertion ID.
 *
 * @param string $email
 * @param string $slug
 * @param string $completed YYYY-MM-DD
 * @return string
 */
function build_assertion_token( $email, $slug, $completed ) {
  $email     = strtolower( trim( $email ) );
  $slug      = sanitize_title( $slug );
  $completed = trim( $completed );

  $raw = implode(
    '|',
    [
      $email,
      $slug,
      $completed,
      wp_salt( 'auth' ),
    ]
  );

  return hash( 'sha256', $raw );
}

/**
 * Build a hashed recipient identity for OB2 assertions.
 *
 * @param string $email
 * @return string
 */
function build_recipient_identity_hash( $email ) {
  $email = strtolower( trim( $email ) );

  return hash( 'sha256', $email );
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

        $issued_on = gmdate( 'c', strtotime( $completed . ' 00:00:00' ) );


        // Canonical Assertion URL (hosted verification).
        $assertion_token = build_assertion_token( $email, $slug, $completed );
        $assertion_id = site_url(
          'wp-json/b2tbadges/v1/assertions/' . $assertion_token
        );        

        /**
         * Hash recipient identity.
         *
         * Assertions are publicly accessible, so recipient email
         * should not be exposed in clear text.
         */
        $recipient_hash = build_recipient_identity_hash( $email );

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
        $email     = apply_filters( 'b2t_badges_issuer_email', get_option( 'admin_email' ) );

        $data = [
          '@context'  => 'https://w3id.org/openbadges/v2',
          'type'      => 'Issuer',
          'id'        => $issuer_id,
          'name'      => get_bloginfo( 'name' ),
          'url'       => get_bloginfo( 'url' ),
          'email'     => $email,
          'image'     => get_site_icon_url(),
        ];

        return rest_ensure_response( $data );
      },
      'permission_callback' => '__return_true',
    ]
  );

  /**
   * Returns a canonical Open Badges 2.0 Assertion by assertion_id.
   *
   * Example:
   * https://example.com/wp-json/b2tbadges/v1/assertions/{assertion_id}
   */
  register_rest_route(
    BADGE_API_NAMESPACE,
    '/assertions/(?P<assertion_id>[a-f0-9]{64})',
    [
      'methods'  => 'GET',
      'callback' => function( \WP_REST_Request $request ) {
        global $wpdb;

        $assertion_id = $request['assertion_id'];
        $table_name   = $wpdb->prefix . 'b2t_badge_assertions';

        $row = $wpdb->get_row(
          $wpdb->prepare(
            "SELECT assertion_json
             FROM {$table_name}
             WHERE assertion_id = %s
               AND revoked_at IS NULL
             LIMIT 1",
            $assertion_id
          )
        );

        if ( ! $row ) {
          return new \WP_Error(
            'b2t_assertion_not_found',
            'Assertion not found.',
            [ 'status' => 404 ]
          );
        }

        $assertion = json_decode( $row->assertion_json, true );

        if ( empty( $assertion ) ) {
          return new \WP_Error(
            'b2t_assertion_invalid',
            'Stored assertion is invalid.',
            [ 'status' => 500 ]
          );
        }

        return rest_ensure_response( $assertion );
      },
      'permission_callback' => '__return_true',
    ]
  );
/////
  /**
   * Issues (creates and stores) an Open Badges 2.0 Assertion.
   *
   * Example:
   * POST https://example.com/wp-json/b2tbadges/v1/issue-assertion
   * {
   *   "email": "user@example.com",
   *   "badge": "agile-analysis",
   *   "completed": "2017-04-21"
   * }
   */
  register_rest_route(
    BADGE_API_NAMESPACE,
    '/issue-assertion',
    [
      'methods'  => 'POST',
      'callback' => function( \WP_REST_Request $request ) {
        global $wpdb;

        $email     = strtolower( trim( $request['email'] ) );
        $slug      = sanitize_title( $request['badge'] );
        $completed = $request['completed'];

        if ( ! is_email( $email ) ) {
          return new \WP_Error(
            'b2t_invalid_email',
            'Invalid email address.',
            [ 'status' => 400 ]
          );
        }

        if ( ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $completed ) ) {
          return new \WP_Error(
            'b2t_invalid_completed',
            '`completed` must be in format YYYY-MM-DD.',
            [ 'status' => 400 ]
          );
        }

        if ( ! get_page_by_path( $slug, OBJECT, 'badge' ) ) {
          return new \WP_Error(
            'b2t_badge_not_found',
            'No badge found with slug: ' . $slug,
            [ 'status' => 404 ]
          );
        }

        $assertion_id   = build_assertion_token( $email, $slug, $completed );
        $recipient_hash = build_recipient_identity_hash( $email );
        $issued_on = gmdate( 'c', strtotime( $completed . ' 00:00:00' ) );

        $canonical_url = site_url(
          'wp-json/b2tbadges/v1/assertions/' . $assertion_id
        );

        $table_name = $wpdb->prefix . 'b2t_badge_assertions';

        // If assertion already exists, return it.
        $existing = $wpdb->get_row(
          $wpdb->prepare(
            "SELECT assertion_json
             FROM {$table_name}
             WHERE assertion_id = %s
               AND revoked_at IS NULL
             LIMIT 1",
            $assertion_id
          )
        );

        if ( $existing ) {
          return rest_ensure_response(
            json_decode( $existing->assertion_json, true )
          );
        }

        // Build OB 2.0 assertion.
        $assertion = [
          '@context'     => 'https://w3id.org/openbadges/v2',
          'type'         => 'Assertion',
          'id'           => $canonical_url,
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

        $inserted = $wpdb->insert(
          $table_name,
          [
            'assertion_id'   => $assertion_id,
            'recipient_hash' => $recipient_hash,
            'badge_slug'     => $slug,
            'issued_on'      => $issued_on,
            'assertion_json' => wp_json_encode( $assertion ),
            'created_at'     => current_time( 'mysql', true ),
            'revoked_at'     => null,
          ],
          [
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
          ]
        );

        if ( false === $inserted ) {
          return new \WP_Error(
            'b2t_assertion_insert_failed',
            'Failed to store assertion.',
            [ 'status' => 500 ]
          );
        }

        return rest_ensure_response( $assertion );
      },
      'permission_callback' => '__return_true',
    ]
  );

/////
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\student_portal_endpoints' );
