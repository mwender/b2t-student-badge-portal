<?php 
namespace B2TBadges\restapi;

use function B2TBadges\utilities\human_filesize;

/**
 * Registers a custom REST API route to retrieve published post data by ID.
 *
 * This route is registered under the namespace 'studentresources/v1'
 * with the route '/post/(?P<id>\d+)' and supports GET requests.
 *
 * Example request:
 * GET /wp-json/studentresources/v1/post/123
 *
 * The callback retrieves the specified post and returns its title and
 * processed content if the post exists and is published. If not, it
 * returns a WP_Error with a 404 status.
 *
 * @see register_rest_route() For registering custom REST API routes.
 * @see get_post()             Retrieves post data given a post ID.
 * @see get_the_title()        Retrieves the post title.
 * @see apply_filters()        Applies filters to the post content.
 *
 * @return array|WP_Error Returns an associative array with 'post_title' and
 *                        'content' keys on success, or WP_Error if not found.
 */
add_action( 'rest_api_init', function () {
  register_rest_route( 'studentresources/v1', '/post/(?P<id>\d+)', [
    'methods'  => 'GET',
    'callback' => function( $request ) {
      $post_id = $request['id'];
      $post    = get_post( $post_id );

      if ( ! $post || 'publish' !== $post->post_status ) {
        return new WP_Error( 'not_found', 'Post not found', [ 'status' => 404 ] );
      }

      $resources = [];

      if ( have_rows( 'resources', $post_id ) ) {
        while ( have_rows( 'resources', $post_id ) ) {
          the_row();
          $download = get_sub_field( 'download', $post_id );
          if ( $download ) {
            $resources[] = $download;
          }
        }
      }      

      $resources_html = '';

      if ( ! empty( $resources ) ) {
        $resources_html .= '<table>';
        $resources_html .= '<thead><tr><th>Resource</th><th>Filesize</th><th>Download</th></tr></thead>';
        $resources_html .= '<tbody>';

        foreach ( $resources as $resource ) {
          $resources_html .= '<tr>';
          $resources_html .= '<td>' . esc_html( $resource['title'] ) . '</td>';
          $resources_html .= '<td>' . esc_html( human_filesize( $resource['filesize'] ) ) . '</td>';
          //$resources_html .= '<td><a href="' . esc_url( $resource['url'] ) . '">' . esc_html( $resource['filename'] ) . '</a></td>';
          $resources_html .= '<td>'.do_shortcode( '[download id="'.$resource['ID'].'"]' ).'</td>';
          $resources_html .= '</tr>';
        }

        $resources_html .= '</tbody></table>';
      } 

      return [
        'post_title'   => get_the_title( $post ),
        'content' => apply_filters( 'the_content', $post->post_content ) . $resources_html,
      ];
    },
    'permission_callback' => '__return_true',
  ] );
} );

