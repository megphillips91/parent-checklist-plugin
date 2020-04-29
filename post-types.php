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


//add_shortcode('the_classroom', __NAMESPACE__.'\\get_the_classroom');

function get_the_classroom(){
    $post_id = 41;
    $class_taxs = array('schools', 'teachers', 'subjects', 'grades');
    $class_args = [];
    foreach($class_taxs as $tax){
        $wp_terms = get_the_terms($post_id, $tax);
        $class_args[$tax] = $wp_terms[0]->name;
    }
    $class = strtolower(implode('_', $class_args));
    $class_title = ucwords(implode(' ', $class_args));

    global $wpdb;
    $class_id = $wpdb->get_row("select id, class from wp_classrooms where class='".$class."'");
    if(empty($class_id)){
        $insert_response = $wpdb->insert(
            'wp_classrooms',
            array(
                'class' => $class,
                'class_title'=>$class_title
            )
        );
        return $wpdb->insert_id;
    } else {
        return $class_id->id;
    }
}



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
        add_action( 'rest_api_init', array($this, 'api_due_date_field'));

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
            'supports'             => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes', 'due_date')
            );
        register_post_type( 'assignment', $args);

    } //end create post type

    function api_due_date_field() {
        register_rest_field( 'assignments', 'due_date', array(
        'get_callback' => __NAMESPACE__.'\\get_due_date',
        'schema' => null,
        )
        );

        register_rest_field( 'assignments', 'class_args', array(
            'get_callback' => __NAMESPACE__.'\\get_class_args',
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

    }
    public function save_custom_meta($post_id){
        if(isset($_POST['due_date']) && !empty($_POST['due_date'])){
            //TODO: check nonce
            update_post_meta($post_id, 'due_date', sanitize_text_field($_POST['due_date']));
        }
        $this->get_class($post_id);
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
        foreach($class_taxs as $tax){
            $wp_terms = get_the_terms($post_id, $tax);
            if(!empty($wp_terms[0])){
                $class_args[$tax] = $wp_terms[0]->name;}
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
                    'class_title'  =>ucwords(str_replace('_', ' ', $class))
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

function get_due_date( $object ) {
    //get the id of the post object array
    $post_id = $object['id'];
    //return the post meta
    return get_post_meta( $post_id, 'due_date', true );
   }

  

?>