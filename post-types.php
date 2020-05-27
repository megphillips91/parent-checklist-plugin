<?php
/**
 * Assignment Custom Post Type
 * this is the creation and details of the custom post type for Group Assignments.
 * This class will serve all of the custom meta, front end forms, display, etc for custom Assignments. 
 * it will interface with the Youtube_Oauth class to 
 * * upload a preview to youtube and
 * * set the description, seo, and link backs from youtube to the site. 
 */
namespace Parent_Checklist_REST;






class Assignment_Post_Type_Factory {

    public function __construct(){
        $this->classroom_args = array(
            'schools'=>array('singular'=>'school', 'plural'=>'schools'),
            'subjects'=>array('singular'=>'subject', 'plural'=>'subjects'),
            'seachers'=>array('singular'=>'teacher', 'plural'=>'teachers'),
            'grades'=>array('singular'=>'grade', 'plural'=>'grades'),
            'keywords'=>array('singular'=>'keyword', 'plural'=>'keywords'),
        );
        
        add_action('init', array($this, 'create_post_types'));
        add_action('add_meta_boxes', array($this, 'add_custom_meta'));
        add_action('save_post', array($this, 'save_custom_meta'));
        add_action('init', array($this, 'create_taxonomies'));
        add_filter('manage_edit-assignment_columns', array($this, 'add_menu_columns'));
        add_filter('manage_assignment_posts_custom_column', array($this, 'manage_custom_columns'));
        add_action( 'rest_api_init', array($this, 'api_custom_fields'));
    }
    

    public function create_post_types(){
        $args = array(
            'labels' => array(
                'name'               => __('Assignments', 'ParentChecklist'),
                'singular_name'      => __('Assignment', 'ParentChecklist'),
                'add_new_item'       => __('Add New Assignment', 'ParentChecklist'),
                'edit_item'          => __('Edit Assignment', 'ParentChecklist'),
                'new_item'           => __('New Assignment', 'ParentChecklist'),
                'view_item'          => __('View Assignment', 'ParentChecklist'),
                'search_items'       => __('Search Studies', 'ParentChecklist'),
                'not_found'          => __('No Assignment Found', 'ParentChecklist'),
                'not_found_in_trash' => __('No studies found in Trash', 'ParentChecklist')
            ),
            
            'description'          => 'Assignments',
            'hierarchical'         => false,
            'menu_icon'            => 'dashicons-book',
            'menu_position'        => 5,
            'public'               => true,
            'has_archive' => 'assignments',
            'show_in_rest'       => true,
            'rest_base'          => 'assignments',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'taxonomies'=>array('subjects', 'teachers', 'grades', 'schools', 'keywords'),
            'supports'             => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'comments', 'page-attributes', 'due_date')
            );
        register_post_type( 'assignment', $args);

    } //end create post type

    function api_custom_fields() {
        register_rest_field( 'assignment', 'due_date', array(
        'get_callback' => __NAMESPACE__.'\\get_due_date',
        'schema' => null,
        )
        );
        
        register_rest_field( 'assignment', 'imageUrl', array(
              'get_callback'    => __NAMESPACE__.'\\get_rest_featured_image',
              'update_callback' => null,
              'schema'          => null,
          )
        );

        register_rest_field( 'assignment', 'author_avatar', array(
            'get_callback'    => __NAMESPACE__.'\\get_author_avatar',
            'update_callback' => null,
            'schema'          => null,
        )
      );

        register_rest_field( 'assignment', 'class_terms', array(
            'get_callback' => __NAMESPACE__.'\\get_the_class_array',
            'schema' => null,
            )
        );

        register_rest_field( 'assignment', 'assigned_date', array(
            'get_callback' => __NAMESPACE__.'\\get_assigned_date',
            'schema' => null,
            )
            );
        
        register_rest_field( 'assignment', 'complete', array(
                'get_callback' => __NAMESPACE__.'\\get_completed_assignments',
                'schema' => null,
                )
                );   

        register_rest_field( 'comment', 'author_avatar', array(
            'get_callback' => __NAMESPACE__.'\\get_author_avatar',
            'schema' => null,
            )
            );   
            
           
    }    

    public function create_taxonomies(){
        
        foreach($this->classroom_args as $key=>$arg){
            // Add Assignment keywords
            $labels = array(
                'name' => _x( ucwords(ucwords($arg['plural'])), 'taxonomy general name' ),
                'singular_name' => _x( ucwords($arg['singular']), 'taxonomy singular name' ),
                'search_items' =>  __( 'Search '.ucwords($arg['plural']) ),
                'popular_items' => __( 'Popular '.ucwords($arg['plural']) ),
                'all_items' => __( 'All '.ucwords($arg['plural']) ),
                'parent_item' => null,
                'parent_item_colon' => null,
                'edit_item' => __( 'Edit '.ucwords($arg['singular']) ), 
                'update_item' => __( 'Update '.ucwords($arg['singular']) ),
                'add_new_item' => __( 'Add New '.ucwords($arg['singular']) ),
                'new_item_name' => __( 'New '.ucwords($arg['singular']) ),
                'separate_items_with_commas' => __( 'Separate '.ucwords($arg['plural']).' with commas' ),
                'add_or_remove_items' => __( 'Add or remove '.ucwords($arg['plural']) ),
                'choose_from_most_used' => __( 'Choose from the most used '.ucwords($arg['plural']) ),
                'menu_name' => __( ucwords($arg['plural']) ),
            ); 

            register_taxonomy($arg['plural'],'assignment',array(
                'hierarchical' => false,
                'labels' => $labels,
                'show_ui' => true,
                'update_count_callback' => '_update_post_term_count',
                'query_var' => true,
                'show_in_rest'=>true
            ));
        }
        
    }// end create taxonomies
    
    public function add_menu_columns($columns){
        $new_columns['cb'] = '<input type="checkbox" />';
        $new_columns['title'] = _x('Assignment', 'column name');
        $new_columns['author'] = __('Author');
        $new_columns['school'] = __('Schools');
        $new_columns['grade'] = __('Grade');
        $new_columns['teachers'] = __('Teachers');
        $new_columns['subject'] = __('Subjects');
        $new_columns['assigned_date'] = __('Assigned Date');
        $new_columns['due_date'] = __('Due Date');
        $new_columns['date'] = _x('Date', 'column name');
        return $new_columns;
    }

    public function manage_custom_columns($column){
        global $post;
        switch ($column){
            case 'school':
                $terms = wp_get_post_terms($post->ID, 'schools');
                $this->echo_term_list($terms);
            break;
            case 'grade':
                $terms = wp_get_post_terms($post->ID, 'grades');
                $this->echo_term_list($terms);
            break;
            case 'teachers':
                $terms = wp_get_post_terms($post->ID, 'teachers');
                $this->echo_term_list($terms);
            break;
            case 'subject':
                $terms = wp_get_post_terms($post->ID, 'subjects');
                $this->echo_term_list($terms);
            break;
            case 'due_date':
                $terms = get_post_meta($post->ID, 'due_date', true);
                echo $terms;
            break;
            case 'assigned_date':
                $terms = get_post_meta($post->ID, 'assigned_date', true);
                echo $terms;
            break;
        }
    }

    private function echo_term_list($terms){
        $content = '';
        foreach($terms as $key=>$term){
            $content .= '<a href="'.get_term_link($term->term_id).'">'.ucwords($term->name).'</a>';
            if($key < (count($terms) - 1)){
                $content .= ', ';
            }
        }
        echo $content;       
    }

    public function register_custom_metas(){
        $args = array( 
          'object_subtype'=>  'assignment',
          'type'=> 'string',
          'description'=>'Due Date',
          'single'=>'true',
          'show_in_rest'=>true
        );
        register_meta(
            'post',
            'due_date',
            $args
        );
    }

    public function add_custom_meta(){
        add_meta_box( 'due_date',
         __( 'Due Date', 'textdomain' ), 
         __NAMESPACE__.'\\due_date_callback', 
         'assignment',
        'side'
        );

        add_meta_box( 'assigned_date',
         __( 'Assigned Date', 'textdomain' ), 
         __NAMESPACE__.'\\assigned_date_callback', 
         'assignment',
        'side'
        );

    }
    public function save_custom_meta($post_id){
        if(isset($_POST['due_date']) && !empty($_POST['due_date'])){
            //TODO: check nonce
            update_post_meta($post_id, 'due_date', sanitize_text_field($_POST['due_date']));
        }
        if(isset($_POST['assigned_date']) && !empty($_POST['assigned_date'])){
            //TODO: check nonce
            update_post_meta($post_id, 'assigned_date', sanitize_text_field($_POST['assigned_date']));
        }
        $this->get_class($post_id);
    }    
    /**
     * loops through the taxonomies and gets the terms for each taxonnomy
     */
    public function get_classroom(){
        $class_taxs = array('schools', 'teachers', 'subjects', 'grades');
        $class_args = [];
        foreach($class_taxs as $tax){
            $class_args[$tax] = get_the_terms($post_id, $tax);
        }
        return $class_args;
    }
    /**
     * gets or sets class
     * @return object class with params id, class string of school teacher subject grade
     * @return int id
     * @return string class->class is space separated string "school teacher subject grade"
     */
    public function get_class($post_id){
        $class_taxs = array('schools', 'teachers', 'subjects', 'grades');
        $class_args = [];
        $tax_ids = [];
        foreach($class_taxs as $tax){
            $wp_terms = get_the_terms($post_id, $tax);
            if(!empty($wp_terms[0])){
                $class_args[$tax] = $wp_terms[0]->slug;
                $tax_ids[$tax]=$wp_terms[0]->term_id;
            }
        }
        if(!empty($class_args)){
            $class = strtolower(implode('_', $class_args));
        global $wpdb;
        $class_id = $wpdb->get_row("select id, class from wp_classrooms where class='".$class."'");
        if(empty($class_id)){
            $insert_response = $wpdb->insert(
                'wp_classrooms',
                array(
                    'class' => $class,
                    'class_title'  =>ucwords(str_replace('-', ' ', str_replace('_', ' ', $class))),
                    'term_ids' => implode('_', $tax_ids)
                )
            );
            return $wpdb->class;
        } else {
            return $class_id->class;
        }
        }
    }

} //end class declaration



/**
 * the admin interface meta box to copy link to preview on youtube
 */
function due_date_callback(){
    $metabox = '<input type="date" id="due_date" name="due_date" value="'.get_post_meta(get_the_id(), 'due_date', true).'"/>';
    echo $metabox;
}

function assigned_date_callback(){
    $metabox = '<input type="date" id="assigned_date" name="assigned_date" value="'.get_post_meta(get_the_id(), 'assigned_date', true).'"/>';
    echo $metabox;
}

function get_due_date( $object ) {
    //get the id of the post object array
    $post_id = $object['id'];
    //return the post meta
    return get_post_meta( $post_id, 'due_date', true );
}

function get_completed_assignments( $object ) {
    $post_id = $object['id'];
    global $wpdb;
    $completed = $wpdb->get_results("SELECT user_id FROM wp_completed_assignments WHERE post_id=".$post_id);
    $users_complete = array();
    foreach($completed as $complete){
        $users_complete[] = $complete->user_id;
    }
    return $users_complete;
}

function get_assigned_date( $object ) {
    //get the id of the post object array
    $post_id = $object['id'];
    //return the post meta
    return get_post_meta( $post_id, 'assigned_date', true );
}

function get_the_class_array( $object ){
    $post_id = $object['id'];
    $class_taxs = array('schools', 'teachers', 'subjects', 'grades');
    return $class_taxs;
}


class Sections {
    public $sections;
    public $assignments;

    public function __construct($show_assignments = false){
        $this->set_assignments();
        $this->get_post_sections();
        $this->remove_dups();
    }

    private function set_assignments(){
        $args = array(
            'posts_per_page'=>'-1',
            'post_type'=>'assignment',
            'order'=>'DESC',
            
        );
            $args['fields'] = 'ids';
            $assignments = new \WP_Query($args);
            $this->assignments = $assignments->posts;
        
    }

    private function get_post_sections(){
        $this->sections = array();
        foreach($this->assignments as $assignment){
            $assignment_sections = new Assignment_Sections($assignment);
            foreach($assignment_sections->sections as $section){
                $this->sections[] = $section;
            }
        } 
    }

    private function remove_dups(){
        $this->sections = array_map("unserialize", array_unique(array_map("serialize", $this->sections)));
    }

} //end Sections

function get_rest_featured_image( $object, $field_name, $request ){
    if( $object['featured_media'] ){
      $img = wp_get_attachment_image_src( $object['featured_media'], 'app-thumb' );
      return $img[0];
    } else {
      return false;
    }
}

function get_author_avatar( $object, $field_name, $request ){
    if($object['type'] == 'comment'){
        $author_id = (int) $object['author'];
    } else {
        $author_id = (int) $object['post_author'];
    }
   
   $response = get_user_meta($author_id, 'scholistit_photo', true);
   return $response; 
}



function get_the_classroom(){
   $sections = new Sections();
   //echo '<pre>'; var_dump($sections); echo '</pre>';
}

class Assignment_Sections {
    public $sections;
    public $section_taxonomies;
    public $assignment_terms;
    

    public function __construct($post_id){
        $this->section_taxonomies = array('schools', 'teachers', 'subjects', 'grades');
        $this->get_assignment_terms($post_id);
        $this->set_section_0();
        $this->handle_multidimensional_sections();
    }

    private function get_assignment_terms($post_id){
        $sections = array();
        $sections['multidimensional'] = false;
        $sections['multidimensional_taxonomies'] = array();
        //loop
        foreach($this->section_taxonomies as $tax){
            $sections[$tax] = get_terms( array(
                'taxonomy' => $tax,
                'hide_empty' => true,
                'fields'=>'names',
                'object_ids'=>$post_id
            ) );
            if(count($sections[$tax]) > 1){
                $sections['multidimensional'] = true;
                $sections['multidimensional_taxonomies'][] = $tax;
            }
        } // ends the original loop
        $this->assignment_terms = $sections;
    }

    private function set_section_0(){
        $this->sections = array();//this is what we are after.
        $section_0 = array();
        foreach($this->section_taxonomies as $tax_name){
            $section_0[$tax_name] = $this->assignment_terms[$tax_name][0];
        }
        $this->sections[]=$section_0;
    }

    private function handle_multidimensional_sections(){
        if($this->assignment_terms['multidimensional'] == true){
            $num_multis = count($this->assignment_terms['multidimensional_taxonomies']);
            for ($i = 0; $i <= $num_multis; $i++) {
                //for as many taxonomies as have multiple terms, create another section
                //then set it equal to the section in position 0
                $this->sections[$i] = $this->sections[0];

                foreach($this->assignment_terms['multidimensional_taxonomies'] as $multitax){
                    //loop through that new section changing only the needed taxonomies to the new position term
                    $this->sections[$i][$multitax] = $this->assignment_terms[$multitax][$i];
                }
            } 
        }
    }

} //end class assignment_sections

  

?>