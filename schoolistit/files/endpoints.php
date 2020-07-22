<?php
/**
 * Create wp rest api namespace and endpoint to post new csv files
 */
namespace Parent_Checklist_REST;
use \DateTime;


add_action( 'rest_api_init', function () {

    register_rest_route( 'schoolistit-rest/v2', '/uploads', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\uploads_endpoint',
    ) );

    register_rest_route( 'schoolistit-rest/v2', '/assignments', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\post_assignments',
    ) );

    register_rest_route( 'schoolistit/v2', '/lesson-plans', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\rest_get_lesson_plans',
    ) );
    //classrooms
    register_rest_route( 'schoolistit/v2', '/classrooms', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\rest_get_classrooms',
    ) );

    register_rest_route( 'schoolistit/v2', '/follows', array(
      'methods' => 'GET',
      'callback' => __NAMESPACE__.'\\rest_get_follows',
    ) );

    register_rest_route( 'schoolistit-rest/v2', '/registration', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\register_user',
    ) );

    register_rest_route( 'schoolistit-rest/v2', '/mark_complete', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\mark_complete',
    ) );

    //get_scholistit_user_data
    register_rest_route( 'schoolistit-rest/v2', '/user_data', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\get_scholistit_user_data',
    ) );

    //get_scholistit_user_data
    register_rest_route( 'schoolistit-rest/v2', '/comments/post', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\save_comment',
    ) );

    //get_scholistit_user_data
    register_rest_route( 'schoolistit-rest/v2', '/follow', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\post_follow',
    ) );

    //get_scholistit_user_data
    register_rest_route( 'schoolistit-rest/v2', '/post-content', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\post_content',
    ) );

    //get_scholistit_user_data
    register_rest_route( 'schoolistit-rest/v2', '/post-image', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\post_image',
    ) );

    //get_scholistit_user_data
    register_rest_route( 'schoolistit/v2', '/translate-gutenberg', array(
      'methods' => 'GET, POST',
      'callback' => __NAMESPACE__.'\\translate_gutenberg',
    ) );

  }
); //end add action

function post_image(\WP_REST_Request $request){
  $auth_response = authenticated($request);
  if($auth_response['authenticated'] === true){
    $params = $request->get_params();
    if($_FILES['file']){
      if (!function_exists('wp_handle_upload'))
        {
          // These files need to be included as dependencies when on the front end.
          require_once( ABSPATH . 'wp-admin/includes/image.php' );
          require_once( ABSPATH . 'wp-admin/includes/file.php' );
          require_once( ABSPATH . 'wp-admin/includes/media.php' );
        }
      foreach($_FILES as $key=>$file){
        $media_id = media_handle_upload($key, $params['post_id'], array('test_form'=>false));
        $post_id = wp_update_post(array(
          'ID'=>$media_id,
          'post_title'=>$params['alt']
        ));
        update_post_meta($media_id, '_wp_attachment_image_alt', $params['alt']);
        $attachment_post = get_post($media_id);
      }

    }
    $response = array(
      'happenening'=>"happeneing",
      'params' => $params,
      'image_src'=>$attachment_post->guid,
      'media_post'=>$attachment_post
    );
  return $response;
} else {
  return $auth_response;
}
}


function translate_gutenberg(\WP_REST_Request $request){
  $auth_response = authenticated($request);
  if($auth_response['authenticated'] === true){
  $params = $request->get_params();
  $megadraft = new Translate_Gutenberg_Blocks($params);
  $blocks = $megadraft->blocks;
  $response = array(
    'translation' => $megadraft,
    'params' => $params,
  );
  return $response;
} else {
  return $auth_response;
}
}


function post_content(\WP_REST_Request $request){
  $auth_response = authenticated($request);
  if($auth_response['authenticated'] === true){
  $params = $request->get_params();
  $request_blocks = \json_decode($params['blocks']);
  $gutenBlocks = new Translate_Megadraft_Blocks($request_blocks);
  $blocks = $gutenBlocks->blocks;
  $string_content = '';
  foreach($blocks as $block){
    $string_content .= $block->guten_block;
  }
  $post_id = (int) $params['post_id'];

  update_post_meta($post_id, 'draft_js_content', $params['rawContent']); //store the draft.js raw blocks into metadata for retrieval later.
  update_post_meta($post_id, 'link_external', $params['linkEternal']); //store the draft.js raw blocks into metadata for retrieval later.

  $postarr = array(
    'ID'=> $post_id,
    'post_content'=>$string_content
  );
  $post_response = wp_update_post($postarr, true);
  $response = array(
    'success' => $post_response,
    'string_content' =>$string_content,
    'translation' => $gutenBlocks,
    'params' => $params,
  );
  return $response;
} else {
  return $auth_response;
}
}

  function post_follow(\WP_REST_Request $request){
    $auth_response = authenticated($request);
    if($auth_response['authenticated'] === true){
    $params = $request->get_params();
    $section = (array) json_decode($params['object']);
    $profile = ($params['student'] == 'no') ? 'self' : $params['student'] ;
    save_follow($params['user_id'], $profile, $section, 'section');
    //then if first timer, change to not first timer and update profile.
    update_user_meta($params['user_id'], 'scholistit_firstTimer', 'false');
    return get_follows($params['user_id']);
  } else {
    return $auth_response;
  }
  }

  function save_follow($user_id, $profile, $terms, $type){
    global $wpdb;
    $insert = $wpdb->insert(
      'wp_following',
      array(
        'user_id'=>$user_id,
        'profile'=>$profile,
        'the_object'=>serialize($terms),
        'object_type'=>$type
      )
      );
  }

  function get_follows($user_id, $profile = NULL){
    global $wpdb;
    $user_id = (int) $user_id;
    if($profile != NULL){
      $query = "select * from wp_following where user_id=".$user_id;
      $query .= " and profile = '".$profile."'";
    } else {
      $query = "SELECT DISTINCT the_object, object_type FROM wp_following WHERE user_id=".$user_id;
    }
    $result = $wpdb->get_results($query);

    $response = array();
    foreach($result as $key=>$follow){
      $follow->section = unserialize($follow->the_object);
      $response[] = $follow->section;
    }
    return $response;
  }

  function rest_get_follows(\WP_REST_Request $request){
      $params = $request->get_params();
      if(!empty($params['userID'])){
        $user_id = (int) $params['userID'];
          $following = get_follows($user_id);
          return $following;
      } else {
        return 'there was an error. userID is required parameter';
      }
  }

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


  function save_comment(\WP_REST_Request $request){
    $auth_response = authenticated($request);
    if($auth_response['authenticated'] === true){
      $params = $request->get_params();
      $args = array(
        'comment_content'=>$params['comment'],
        'comment_post_ID'=>(int)$params['post'],
        'user_id'=>$params['author'],
        'comment_meta'=>array('comment_section' => serialize($params['section']))
      );
      $comment_ID = wp_insert_comment($args);
      $response = array(
        'comment_id' => $comment_ID,
        'comment' => get_comment($comment_ID)
      );
      return $response;
    } else {
      return $auth_response;
    }
  }




  function register_user(\WP_REST_Request $request){
    $auth_response = authenticated($request);
    if($auth_response['authenticated'] === true){
      $params = $request->get_params();
      $username =$request->get_param('username');
      $email = $request->get_param('email');
      $token = $request->get_param('password');
      $wpusername = explode('@', $email);
      $wpusername = $wpusername[0];
      $display_name = str_replace('_', ' ', $username);
      $user = array(
        'user_pass' => $token,
        'user_login' =>$wpusername,
        'user_email' =>$email,
        'display_name' => $username,
        'role'=>'author'
      );
      $user_id = wp_insert_user($user);
      if(!is_int($user_id) && $user_id->errors){
        $user = get_user_by('email', $email);
        $user_id = $user->ID;
        update_user_meta($user_id, 'scholistit_firstTimer', 'false');
      } else {
        update_user_meta($user_id, 'scholistit_firstTimer', 'true');
        //add a follow to the schoolistit section
        $user = get_user_by('ID', $user_id);
      }

      update_user_meta($user_id, 'scholistit_photo', $params['photo']);
      $students = json_decode($params['students']);
      update_user_meta($user_id, 'students', serialize($students));
      $terms = array(
        'schools'=>'SchooListIt',
        'teachers'=>'Meg Phillips',
        'grades'=>'All Grades',
        'subjects'=>'Home Feed'
      );

      save_follow($user_id, NULL, $terms, 'section');
      //get completed assignments
      $completed = get_user_completed_assignments($user_id);
      $firstTimer = get_user_meta($user_id, 'scholistit_firstTimer', true);
      $following = get_follows($user_id);
      $students = unserialize(get_user_meta($user_id, 'students', true));
      foreach($students as $student){
        $student->following = get_follows($user_id, $student->name);
      }

      $response = array(
        'userID'=>$user_id,
        'user'=>$user->data,
        'name'=>$user->data->display_name,
        'photo'=>$params['photo'],
        'email'=>$user->data->user_email,
        'first_time' => $firstTimer,
        'students'=> $students,
        'completed'=>$completed,
        'following'=>$following,
        'registration'=>$success,
      );
      return $response;
    } else {
      return $auth_response;
    }
  }

  function get_scholistit_user_data(\WP_REST_Request $request){
    $params = $request->get_params();
    $completed_assignments = get_user_completed_assignments($params['user_id']);
    $userData = array(
      'completed'=>$completed_assignments
    );
    return $userData;
  }

  function get_user_completed_assignments($user_id){
    global $wpdb;
    $completed = $wpdb->get_results("SELECT post_id FROM wp_completed_assignments WHERE user_id=".$user_id);
    $posts_complete = array();
    foreach($completed as $complete){
        $posts_complete[] = $complete->post_id;
    }
    $response = array(
      //'params' => $user_id,
      //'completed' => $completed,
      'posts_complete' => $posts_complete
    );
    return $response;
  }

  function post_assignments(\WP_REST_Request $request){
    $auth_response = authenticated($request);
    if($auth_response['authenticated'] === true){
      $params = $request->get_params();
      if(!empty($params['post_id'])){
        //then edit or delete
        $response = edit_assignment($params);
        return $response;
      } else {
          //log in the current user by email and password
      $today = date('Y-m-d');
      $wp_user =   get_user_by('email', sanitize_email($params['post_author']));
      $post_array = array(
        'post_author' => $wp_user->get('id'),
        'post_title' => sanitize_text_field($params['post_title']),
        'post_excerpt' => sanitize_text_field($params['post_excerpt']),
        'post_content' => '',
        'post_date_gmt' => $today,
        'post_status' => 'publish',
        'post_type' => 'assignment',
        'meta_input' => array(
            'due_date' => sanitize_text_field($params['due_date']),
            'mandatory' => sanitize_text_field($params['mandatory']),
            'assigned_date' => sanitize_text_field($params['post_date']),
            'post_link'=>esc_url_raw($params['post_link'])
        )
      );
      $post_id = wp_insert_post($post_array);
      update_post_meta($post_id, 'assigned_date', sanitize_text_field($params['post_date']));
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
      //set up author avatar
      $response = array(
        'params'=>$params,
        'post_array'=>$post_array,
        'post_id' => $post_id,
        'tax_fields'=>$tax_fields,
        'post'=>get_post($post_id)
      );
      return $response;
      } //else create post

    } else {
      return $auth_response;
    }
  }

  function edit_assignment($params){
    $changed_fields = (!empty($params['changed_fields'])) ? explode(',', $params['changed_fields']): null ;
    if(in_array('delete_post', $changed_fields)){
      //MEGDO we need to deal with SEO here. Eventually when we have an SEO strategy to show lessons publically
      $delete = wp_trash_post($params['post_id']);
      $status = ($delete) ? true : false;
      $response = array(
        'return' => $delete,
        'deleted'=> $status
      );
      return $response;
    } else {
      $post_args = array(
        'ID'           => $params['post_id'],
        'post_title'   => $params['post_title'],
        'post_excerpt' => $params['post_excerpt'],
      );
      $result = wp_update_post($post_args);
      update_post_meta($params['post_id'], 'due_date', sanitize_text_field($params['due_date']));
      update_post_meta($params['post_id'], 'assigned_date', sanitize_text_field($params['assigned_date']));
      update_post_meta($params['post_id'], 'mandatory', sanitize_text_field($params['mandatory']));
      update_post_meta($params['post_id'], 'post_link', esc_url_raw($params['post_link']));

      //wp-json/wp/v2/assignments?.$params['post_id]
      $request = new \WP_REST_Request( 'GET', '/wp/v2/assignments/'.$params['post_id']);
      $response = rest_do_request( $request );
      $server = rest_get_server();
      $data = $server->response_to_data( $response, false );
      $post = $data ;
      $response = array(
        'post'=>$post,
        'edit'=>true,
        'params'=>$params

      );
      return $response;
    } return "there was an error. please try again";

  }

  function mark_complete(\WP_REST_Request $request){
    $auth_response = authenticated($request);
    if($auth_response['authenticated'] === true){
      global $wpdb;
      $params = $request->get_params();
      if(intval($params['post_id']) && intval($params['user_id'])){
          $datetime = new DateTime($params['check_date']);
          $mysqlDate = $datetime->format("Y-m-d H:i:s");
          //insert
          if($params['action'] == 'insert'){
            $insert = $wpdb->insert(
              'wp_completed_assignments',
              array(
                'post_id'=>$params['post_id'],
                'user_id'=>$params['user_id'],
                'check_date'=>$mysqlDate
              )
            );
            if($insert){
              $response = array(
                'insert_id' => $wpdb->insert_id,
                'status'=>'whoohoo'
              );
              return $response;
            }
          }
          //delete
        if($params['action'] == 'delete'){
          $delete = $wpdb->delete(
            'wp_completed_assignments',
            array(
              'post_id'=>$params['post_id'],
              'user_id'=>$params['user_id'],
            )
            );
            if($delete){
              $response = array(
                'delete'=>$delete,
                'status'=>'success'
              );
              return $response;
            } else {
              $response = array(
                'delete'=>$delete,
                'status'=>'the assignment was not in the db to delete'
              );
              return $response;
            }
        }
        $response = array(
          'message'=>"not a valid action",
          'params'=>$params
        );
        return $response;
      } else {
        return 'validation_error';
      }
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