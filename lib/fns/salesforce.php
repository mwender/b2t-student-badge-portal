<?php
namespace B2TBadges\fns\salesforce;

/**
 * Sets up our SalesForce REST API Endpoint
 */
function salesforce_endpoint(){
  register_rest_route( BADGE_API_NAMESPACE, '/sf/(?P<action>login|getStudentId|getStudentData|getStudentClasses)', [
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
        case 'login':
          $response = login();
          break;

        case 'getStudentId':
          if( ! isset( $_SESSION['SF_SESSION'] ) )
            login();

          $response = get_student_id([
            'access_token' => $_SESSION['SF_SESSION']->access_token,
            'instance_url' => $_SESSION['SF_SESSION']->instance_url,
            'email' => $email,
          ]);
          $student_info;
          break;

        case 'getStudentClasses':
          if( empty( $student_id ) )
            return new \WP_Error( 'nocontactid', __('No Contact/Student ID provided.') );
          // Cache Student Data for 24hrs
          $transient_id = 'student-classes_' . $student_id;

          if( false === ( $response = get_transient( $transient_id ) ) ){
            if( ! isset( $_SESSION['SF_SESSION'] ) )
              login();

            $response = get_student_data([
              'access_token' => $_SESSION['SF_SESSION']->access_token,
              'instance_url' => $_SESSION['SF_SESSION']->instance_url,
              'contact_id' => $student_id,
              'data_type' => 'classes',
            ]);
            set_transient( $transient_id, $response, 24 * HOUR_IN_SECONDS );
          }
          break;

        case 'getStudentData':
          if( empty( $student_id ) )
            return new \WP_Error( 'nocontactid', __('No Contact/Student ID provided.') );

          // Cache Student Data for 24hrs
          $transient_id = 'student-badges_' . $student_id;
          if( false === ( $response = get_transient( $transient_id ) ) ){
            if( ! isset( $_SESSION['SF_SESSION'] ) )
              login();

            $response = get_student_data([
              'access_token' => $_SESSION['SF_SESSION']->access_token,
              'instance_url' => $_SESSION['SF_SESSION']->instance_url,
              'contact_id' => $student_id,
              'data_type' => 'badges',
            ]);
            set_transient( $transient_id, $response, 24 * HOUR_IN_SECONDS );
          }
          break;

        default:
          $response = new \WP_Error( 'noaction', __('No `action` specified in salesforce_endpoints()->callback().') );
          break;
      }

      return $response;
    }/*,
    'permission_callback' => function(){
      if( ! current_user_can( 'read' ) ){
        return new \WP_Error( 'rest_forbidden', __('Permission denied'), ['status' => 401] );
      }

      return true;
    }
    /**/
  ]);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\salesforce_endpoint' );

/**
 * Logs into SalesForce using Session ID Authentication
 *
 * @return    object      Login Response or WP Error.
 */
function login(){

  // Verify we have credentials we need to attempt an authentication:
  $check_const = [
    'B2T_BADGE_PORTAL_CLIENT_ID',
    'B2T_BADGE_PORTAL_CLIENT_SECRET',
    'B2T_BADGE_PORTAL_USERNAME',
    'B2T_BADGE_PORTAL_PASSWORD',
    'B2T_BADGE_PORTAL_SECURITY_TOKEN',
  ];
  foreach ($check_const as $const) {
    if( ! defined( $const ) ){
      return new \WP_Error( 'missingconst', __('Please make sure the following constants are defined in your `wp-config.php`: ' . implode( ', ', $check_const ) . '.') );
    }
  }

  // Login to SalesForce
  $response = wp_remote_post( 'https://login.salesforce.com/services/oauth2/token', [
    'method' => 'POST',
    'timeout' => 30,
    'redirection' => 5,
    'body' => [
      'grant_type' => 'password',
      'client_id' => B2T_BADGE_PORTAL_CLIENT_ID,
      'client_secret' => B2T_BADGE_PORTAL_CLIENT_SECRET,
      'username' => B2T_BADGE_PORTAL_USERNAME,
      'password' => B2T_BADGE_PORTAL_PASSWORD . B2T_BADGE_PORTAL_SECURITY_TOKEN
    ],
  ]);

  if( ! is_wp_error( $response ) ){
    $data = json_decode( wp_remote_retrieve_body( $response ) );
    $_SESSION['SF_SESSION'] = $data;
    $response = new \stdClass();
    $response->data = $data;
  }

  return $response;
}

/**
 * Gets Student Data from SalesForce.
 *
 * @param      array  $args{
 *  @type   string  $access_token   SalesForce access token
 *  @type   string  $instance_url   SalesForce instance URL
 *  @type   string  $contact_id     SalesForce Contact ID
 *  @type   string  $data_type      Selects the
 * }
 *
 * @return     object      Student Data or WP Error.
 */
function get_student_data( $args = [] ){
  if( ! isset( $args['access_token'] ) || empty( $args['access_token'] ) ){
    return new \WP_Error( 'noaccesstoken', __('No Access Token provided.') );
  }
  if( ! isset( $args['instance_url'] ) || empty( $args['instance_url'] ) ){
    return new \WP_Error( 'noinstanceurl', __('No Instance URL provided.') );
  }
  if( ! isset( $args['contact_id'] ) || empty( $args['contact_id'] ) ){
    return new \WP_Error( 'nocontactid', __('No Contact ID provided.') );
  }

  switch ( $args['data_type'] ) {
    case 'classes':
      $query = 'select contact__c, contact__r.name, Class__c, Class__r.Name, Start_Date__c, Class__r.End_Date__C from Non_LMS_Course_Enrollment__c
where Contact__c = \'' . $args['contact_id'] . '\'';
      $request_url = $args['instance_url'] . '/services/data/v42.0/query/?q=' . urlencode( $query );
      break;

    default:
      $request_url = $args['instance_url'] . '/services/apexrest/Badge/' . $args['contact_id'];
      break;
  }

  $response = wp_remote_get( $request_url, [
    'method' => 'GET',
    'timeout' => 30,
    'redirection' => 5,
    'headers' => [
      'Authorization' => 'Bearer ' . $args['access_token']
    ],
  ]);

  if( ! is_wp_error( $response ) ){
    $data = json_decode( wp_remote_retrieve_body( $response ) );
    $response = new \stdClass();
    $response->data = $data;
  }

  $response->instance_url = $args['instance_url'];

  return $response;
}

/**
 * Gets the student identifier.
 *
 * @param      array      $args{
 *  @type   string  $access_token   SalesForce access token
 *  @type   string  $instance_url   SalesForce instance URL
 *  @type   string  $email          SalesForce contact email
 * }
 *
 * @return     object  Response object or WP Error Object.
 */
function get_student_id( $args = [] ){
  if( ! isset( $args['access_token'] ) || empty( $args['access_token'] ) ){
    return new \WP_Error( 'noaccesstoken', __('No Access Token provided.') );
  }
  if( ! isset( $args['instance_url'] ) || empty( $args['instance_url'] ) ){
    return new \WP_Error( 'noinstanceurl', __('No Instance URL provided.') );
  }
  if( ! isset( $args['email'] ) || empty( $args['email'] ) ){
    return new \WP_Error( 'noemail', __('No Email provided.') );
  }

  $response = wp_remote_get( $args['instance_url'] . '/services/data/v42.0/query/?q=SELECT+Id+FROM+Contact+WHERE+Contact.Email=\'' . $args['email'] . '\'+AND+student__c=true', [
    'method' => 'GET',
    'timeout' => 30,
    'redirection' => 5,
    'headers' => [
      'Authorization' => 'Bearer ' . $args['access_token']
    ],
  ]);

  if( isset( $response['body'] ) )
    $data = json_decode( wp_remote_retrieve_body( $response ) );

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