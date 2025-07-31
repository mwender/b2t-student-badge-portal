<?php
namespace B2TBadges\fns\zoho;

/**
 * - https://www.zohoapis.com/crm/v2/functions/getstudentid/actions/execute
 * - https://www.zohoapis.com/crm/v2/functions/getstudentbadges/actions/execute
 * - https://www.zohoapis.com/crm/v2/functions/getstudentclasses/actions/execute
 * - https://www.zohoapis.com/crm/v2/functions/getstudentexams/actions/execute
 */

/**
 * Sets up our Zoho REST API Endpoint
 */
function zoho_endpoint(){
  register_rest_route( BADGE_API_NAMESPACE, '/zh/(?P<action>getStudentId|getStudentBadges|getStudentClasses|getStudentExams)', [
    'methods' => 'GET',
    'callback' => function( \WP_REST_Request $request ){
      if( is_wp_error( $request ) )
        return $request;

      $params = $request->get_params();
      $action = $params['action'];

      $email = '';
      if( isset( $params['email'] ) && is_email( $params['email'] ) )
        $email = $params['email'];

      $student_id = '';
      if( isset( $params['student_id'] ) )
        $student_id = $params['student_id'];

      switch( $action ){

        case 'getStudentId':
          $response = get_student_id(['email' => $email]);
          break;

        case 'getStudentClasses':
          if( empty( $student_id ) )
            return new \WP_Error( 'nocontactid', __('No Contact/Student ID provided.') );
          // Cache Student Data for 24hrs
          $transient_id = 'student-classes_' . $student_id;

          if( false === ( $response = get_transient( $transient_id ) ) ){
            $response = get_student_data([
              'student_id' => $student_id,
              'data_type' => 'classes',
            ]);
            set_transient( $transient_id, $response, 24 * HOUR_IN_SECONDS );
          }
          break;

        case 'getStudentExams':
          if( empty( $student_id ) )
            return new \WP_Error( 'nocontactid', __('No Contact/Student ID provided.') );
          // Cache Student Data for 24hrs
          $transient_id = 'student-exams_' . $student_id;

          if( false === ( $response = get_transient( $transient_id ) ) ){
            $response = get_student_data([
              'student_id' => $student_id,
              'data_type' => 'exams',
            ]);
            set_transient( $transient_id, $response, 24 * HOUR_IN_SECONDS );
          }
          break;

        case 'getStudentBadges': // 10/18/2024 (17:40) - Originally getStudentData
          if( empty( $student_id ) )
            return new \WP_Error( 'nocontactid', __('No Contact/Student ID provided.') );

          // Cache Student Data for 24hrs
          $transient_id = 'student-badges_' . $student_id;
          if( false === ( $response = get_transient( $transient_id ) ) ){
            $response = get_student_badges([
              'student_id' => $student_id,
            ]);
            if( ! is_wp_error( $response ) )
              set_transient( $transient_id, $response, 24 * HOUR_IN_SECONDS );
          }
          break;

        default:
          $response = new \WP_Error( 'noaction', __('No `action` specified in salesforce_endpoints()->callback().') );
          break;
      }

      return $response;
    },
    'permission_callback' => function(){
      if( IS_LOCAL )
        return true;

      check_ajax_referer( 'wp_rest', 'security', false );

      if( ! current_user_can( 'read' ) ){
        return new \WP_Error( 'rest_forbidden', __( 'Permission denied, user does not have `read` permissions.' ), ['status' => 401] );
      }


      return true;
    }
  ]);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\zoho_endpoint' );

/**
 * Retrieves student data from Zoho based on the specified data type.
 *
 * This function fetches either exam or class data for a specific student based on the `student_id`
 * and `data_type` provided in the `$args` array. It requires the constants `B2T_BADGE_PORTAL_ZOHO_EP`
 * and `B2T_BADGE_PORTAL_ZOHO_API_KEY` to be defined in the `wp-config.php` file.
 *
 * @param array $args {
 *     Optional. An array of arguments.
 *
 *     @type string $student_id The ID of the student for whom to retrieve data.
 *     @type string $data_type  The type of data to retrieve ('exams' or 'classes'). Default is 'classes'.
 * }
 * @return stdClass|WP_Error The student data object on success, or a WP_Error object on failure.
 */
function get_student_data( $args = [] ){
  if( ! defined( 'B2T_BADGE_PORTAL_ZOHO_EP' ) || empty( B2T_BADGE_PORTAL_ZOHO_EP ) )
    return new \WP_Error( 'no_zoho_ep', __( 'No Zoho endpoint found. Please define `B2T_BADGE_PORTAL_ZOHO_EP` in your wp-config.php.' ) );

  if( ! defined( 'B2T_BADGE_PORTAL_ZOHO_API_KEY' ) || empty( B2T_BADGE_PORTAL_ZOHO_API_KEY ) )
    return new \WP_Error( 'no_zoho_api_key', __( 'No Zoho API Key found. Please define `B2T_BADGE_PORTAL_ZOHO_API_KEY` in your wp-config.php.' ) );

  if( ! isset( $args['student_id'] ) || empty( $args['student_id'] ) )
    return new \WP_Error( 'nostudentid', __('No Student ID provided.') );

  $query_type = ( 'exams' == $args['data_type'] )? 'exams' : 'classes' ;

  $ep = B2T_BADGE_PORTAL_ZOHO_EP;
  $query_args = array(
    'auth_type'     => 'apikey',
    'zapikey'       => B2T_BADGE_PORTAL_ZOHO_API_KEY,
    'Contact_Id' => $args['student_id'],
  );
  $request_url = add_query_arg( $query_args, $ep . "getstudent{$query_type}/actions/execute" );

  $response = wp_remote_get( $request_url, array(
    'timeout' => 10,
  ));

  $body = wp_remote_retrieve_body( $response );

  if( $body )
    $json_body = json_decode( $body );

  $data = json_decode( $json_body->details->output  );
  return $data;
}

/**
 * Retrieves student badges from Zoho using the provided Zoho endpoint and API key.
 *
 * This function fetches badges for a specific student based on the `student_id` provided
 * in the `$args` array. It requires the constants `B2T_BADGE_PORTAL_ZOHO_EP` and
 * `B2T_BADGE_PORTAL_ZOHO_API_KEY` to be defined in the `wp-config.php` file.
 *
 * @param array $args {
 *     Optional. An array of arguments.
 *
 *     @type string $student_id The ID of the student for whom to retrieve badges.
 * }
 * @return array|WP_Error The badges data array on success, or a WP_Error object on failure.
 */
function get_student_badges( $args = [] ){
  if( ! defined( 'B2T_BADGE_PORTAL_ZOHO_EP' ) || empty( B2T_BADGE_PORTAL_ZOHO_EP ) )
    return new \WP_Error( 'no_zoho_ep', __( 'No Zoho endpoint found. Please define `B2T_BADGE_PORTAL_ZOHO_EP` in your wp-config.php.' ) );

  if( ! defined( 'B2T_BADGE_PORTAL_ZOHO_API_KEY' ) || empty( B2T_BADGE_PORTAL_ZOHO_API_KEY ) )
    return new \WP_Error( 'no_zoho_api_key', __( 'No Zoho API Key found. Please define `B2T_BADGE_PORTAL_ZOHO_API_KEY` in your wp-config.php.' ) );

  if( ! isset( $args['student_id'] ) || empty( $args['student_id'] ) )
    return new \WP_Error( 'nostudentid', __('No Student ID provided.') );

  $ep = B2T_BADGE_PORTAL_ZOHO_EP;
  $query_args = array(
    'auth_type'     => 'apikey',
    'zapikey'       => B2T_BADGE_PORTAL_ZOHO_API_KEY,
    'Contact_Id' => $args['student_id'],
  );
  $request_url = add_query_arg( $query_args, $ep . 'getstudentbadges/actions/execute' );

  $response = wp_remote_get( $request_url, array(
    'timeout' => 10,
  ));

  $body = wp_remote_retrieve_body( $response );

  if( $body )
    $json_body = json_decode( $body );

  $data = json_decode( $json_body->details->output  );
  return $data;
}

/**
 * Gets the student ID from Zoho.
 *
 * @param      array      $args{
 *  @type   string  $email          Zoho contact email
 * }
 *
 * @return     object  Response object or WP Error Object.
 */
function get_student_id( $args = [] ){
  if( ! defined( 'B2T_BADGE_PORTAL_ZOHO_EP' ) || empty( B2T_BADGE_PORTAL_ZOHO_EP ) )
    return new \WP_Error( 'no_zoho_ep', __( 'No Zoho endpoint found. Please define `B2T_BADGE_PORTAL_ZOHO_EP` in your wp-config.php.' ) );

  if( ! defined( 'B2T_BADGE_PORTAL_ZOHO_API_KEY' ) || empty( B2T_BADGE_PORTAL_ZOHO_API_KEY ) )
    return new \WP_Error( 'no_zoho_api_key', __( 'No Zoho API Key found. Please define `B2T_BADGE_PORTAL_ZOHO_API_KEY` in your wp-config.php.' ) );

  if( ! isset( $args['email'] ) || empty( $args['email'] ) )
    return new \WP_Error( 'noemail', __('No Email provided.') );

  if( ! is_email( $args['email'] ) )
    return new \WP_Error( 'invalidemail', __('Invalid email. Please check the syntax of the email you provided.') );

  $ep = B2T_BADGE_PORTAL_ZOHO_EP;
  $query_args = array(
    'zapikey'       => B2T_BADGE_PORTAL_ZOHO_API_KEY,
    'auth_type'     => 'apikey',
    'Contact_Email' => $args['email'],
  );
  $request_url = add_query_arg( $query_args, $ep . 'getstudentid/actions/execute' );

  $response = wp_remote_get( $request_url, array(
    'timeout' => 10,
  ));

  $body = wp_remote_retrieve_body( $response );

  if( $body )
    $json_body = json_decode( $body );

  if( property_exists( $json_body, 'details' ) && ! property_exists( $json_body->details, 'output' ) )
    return new \WP_Error( 'noidreturned', __( 'No Studend ID returned for `' . $args['email'] . '`.' ) );

  $data = json_decode( $json_body->details->output  );

  if( is_wp_error( $response ) ){
    // do nothing
  } else if( 1 < $data->totalSize ){
    $response = new \WP_Error( 'toomanyresults', __('More than one student record was returned for `' . $args['email'] . '`.') );
  } else if( 0 === $data->totalSize ){
    $response = new \WP_Error( 'noresults', __('No student data returned for `' . $args['email'] . '`.') );
  } else {
    $student_id = $data->records[0]->Id;
    $response = new \stdClass();
    $response->student_id = $student_id;
  }

  return $response;

}