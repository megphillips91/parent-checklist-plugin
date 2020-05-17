<?php
/**
 * Create wp rest api namespace and endpoint to post new csv files
 */
namespace Parent_Checklist_REST;

add_action( 'rest_api_init', function () {

    register_rest_route( 'parent-checklist-rest/v2', '/uploads', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\uploads_endpoint',
    ) );

    register_rest_route( 'parent-checklist-rest/v2', '/assignments', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\post_assignments',
    ) );

    register_rest_route( 'parent-checklist/v2', '/lesson-plans', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\rest_get_lesson_plans',
    ) );
    //classrooms
    register_rest_route( 'parent-checklist/v2', '/classrooms', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\rest_get_classrooms',
    ) );

    register_rest_route( 'parent-checklist-rest/v2', '/registration', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\register_user',
      'validate_callback'=> __NAMESPACE__.'\\check_JWT',
    ) );

  } 
); //end add action
  
  function uploads_endpoint(\WP_REST_Request $request){
    $lesson = new Lesson_Factory($request);
    return $lesson->lessonPlan;
  }
  
  function rest_get_lesson_plans(\WP_REST_Request $request){
    $lesson_plans = new Lesson_Plans($request);
    return $lesson_plans;
  }

  function rest_get_classrooms(\WP_REST_Request $request){
    global $wpdb;
    $qry = "select id from wp_classrooms";
    $classrooms = $wpdb->get_results($qry);
    $classes = array();
    foreach($classrooms as $classroom){
      $classes[] = new Classroom('id', $classroom->id);
    }
    return $classes;
  }


  function register_user(\WP_REST_Request $request){
    $auth_response = authenticated($request);
    if($auth_response['authenticated'] === true){
      $username =$request->get_param('username');
      $email = $request->get_param('email');
      $token = $request->get_param('password');
      $wpusername = explode('@', $email);
      $wpusername = $wpusername[0];
      $user = array(
        'user_pass' => $token,
        'user_login' =>$wpusername,
        'user_email' =>$email,
        'display_name' => $username,
        'role'=>'author'
      );
      $user_id = wp_insert_user($user);
      $success= (is_int($user_id)) ? true : false ;
      //save photourl to usermeta
      if($status){
        update_user_meta($user_id, 'scholistit_photo', $request->get_param('photoUrl'));
      }
      $response = array(
        'user'=>$user,
        'registration'=>$success,
        'wp_user'=>get_user_by('id', $user_id)
      );
      return $response;
    } else {
      return $auth_response;
    }
  }

  function post_assignments(\WP_REST_Request $request){
    $auth_response = authenticated($request);
    if($auth_response['authenticated'] === true){
      //log in the current user by email and password
      $params = $request->get_params();
      $wp_user =   get_user_by('email', sanitize_email($params['post_author']));
      $post_array = array(
        'post_author' => $wp_user->get('id'),
        'post_title' => sanitize_text_field($params['post_title']),
        'post_excerpt' => sanitize_text_field($params['post_excerpt']),
        'post_content' => '',
        'post_date_gmt' => sanitize_text_field($params['post_date']),
        'post_status' => 'publish',
        'post_type' => 'assignment',
        'meta_input' => array(
            'due_date' => sanitize_text_field($params['due_date']),
            'mandatory' => sanitize_text_field($params['mandatory'])
        )
      );
      $post_id = wp_insert_post($post_array);
      //handle the terms
      $tax_fields = array('grades', 'teachers', 'schools', 'subjects', 'keywords');
      $tax_input = array();
      foreach($tax_fields as $tax) {
        $term_names = explode(',', $params[$tax]);
        foreach($term_names as $name){
          $slug = str_replace(' ', '-', strtolower($name));
          $name = $slug;
          $tax_fields[] = wp_set_object_terms( $post_id, $term_names, $tax );
        }
      }
      $response = array(
        'params'=>$post_array,
        'post_id' => $post_id,
        'tax_fields'=>$tax_fields
      );
      return $response;
    } else {
      return $auth_response;
    }
  }
    
  /**
   * homeade security for now it will suffice. 
   * two calls - 
   * 1. get a nonce for scholistic_registration
   * 2. return with 
   * --- username
   * --- email
   * --- password (token from google/fb)
   * --- nonce
   * --- salt
   * 3. this script will check the nonce against wp_check_nonce. if pass, 
   * 4. Then check the secret_key_salt encryption. 
   * 
   */
  function authenticated($request) {
    $headers = $request->get_headers();
    $nonce_key_header = $headers['x_scholistit_auth'][0];
    if(empty($nonce_key_header)){
      $auth_response = array(
        'salt'=>wp_create_nonce("scholistit_registration"),
        'authenticated'=>false,
        'message'=> 'The properly named auth header is missing. Please send a valid authentication header',
      );
      return $auth_response;
    } else {
      $response = array();
      $nonce_key = $nonce_key_header;
      //$nonce_key = base64_decode($nonce_key);
      $valid_key = SCHOLIST_IT_SECRET_KEY;
      $auth_array = explode('_', $nonce_key);
      $supplied_key = $auth_array[1];
      $supplied_nonce = $auth_array[0];
      $response['key_check'] = ($supplied_key === $valid_key) ? true : 'fail';
      $response['nonce_check'] = (wp_verify_nonce($supplied_nonce, "scholistit_registration")) ? true : 'fail';
      if($response['key_check'] === true && $response['nonce_check'] === true ){
        $response['authenticated'] = true;
        return $response;
      } else {
        $response['authenticated'] = false;
        $response['salt'] = wp_create_nonce("scholistit_registration");
        return $response;
      }
    } 
  }

?>