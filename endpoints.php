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

  } ); //end add action
  
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
    /*if( !check_JWT($request->get_param('bearer')) ) {
      $response = array(
        'response'=> 'bad token'
      );
      return ($response);
    } else {
      /*$username =$request->get_param('username');
      $email = $request->get_param('email');
      $token = $request->get_param('token');
      $wpusername = explode('@', $email);
      $wpusername = $wpusername[0];
      $user_id = wp_create_user($wpusername, $token, $email);
      $status = (is_int($user_id)) ? 'success' : 'failure';
      $response = array(
        'username'=>$wpusername,
        'registration'=>$status,
        'user_id' =>$user_id,
        'user'=>get_user_by('id', $user_id),
        'JWT_Auth' => check_JWT($request->bearer)
      );
    }
    */
    $bearer = $request->get_param('Bearer');
      $response = array(
        'jwt'=>check_JWT($bearer)
      );
      return $response;
  }

  function check_JWT($bearer){
    //THIS NEEDS TO BE PERFECTED somehow
    $url = content_url('/wp-json/simple-jwt-authentication/v1/token/validate');
    $args = array(
      'headers'=>array(
        'Authorization'=>$bearer
      )
      );
    //$response = wp_remote_post($url, $args);
    //$return = wp_remote_retrieve_body($response);
    return TRUE;
  }



  //add lesson plans endpoint

?>