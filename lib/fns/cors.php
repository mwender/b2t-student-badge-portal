<?php

add_filter(
  'rest_pre_serve_request',
  function ( $served, $result, $request, $server ) {

    $route = $request->get_route();

    // Only apply to your badge namespace.
    if ( 0 !== strpos( $route, '/' . BADGE_API_NAMESPACE . '/' ) ) {
      return $served;
    }

    header( 'Access-Control-Allow-Origin: *' );
    header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
    header( 'Access-Control-Allow-Headers: Content-Type, X-WP-Nonce' );
    header( 'Access-Control-Expose-Headers: Link' );

    // Helps with some embedded / cross-origin fetch contexts.
    header( 'Cross-Origin-Resource-Policy: cross-origin' );

    return $served;
  },
  10,
  4
);
