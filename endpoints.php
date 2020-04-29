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

  } ); //end add action
  
  function uploads_endpoint(\WP_REST_Request $request){
    $lesson = new Lesson_Factory($request);
    return $lesson->lessonPlan;
  }
  
  function rest_get_lesson_plans(\WP_REST_Request $request){
    
    $lesson_plans = new Lesson_Plans($request->get_params());
    return $lesson_plans;
  }

  function rest_get_classrooms(){
    global $wpdb;
    $qry = "select id from wp_classrooms";
    $classrooms = $wpdb->get_results($qry);
    $classes = array();
    foreach($classrooms as $classroom){
      $classes[] = new Classroom('id', $classroom->id);
    }
    return $classes;
  }



  //add lesson plans endpoint

?>