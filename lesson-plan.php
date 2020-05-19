<?php
/**
 * accepts uploaded csv and parses to json response
 * -- so basically I need to structure the data into classrooms and assignments
 * -- each assignment needs date, duedate, classroom_id, description, mandatory
 * -- each classroom needs id, school, grade, teacher, subject
 */
namespace Parent_Checklist_REST;

/**
 * 
 */
class Lesson_Factory {
    public $lessonPlan;
    public $files;

    public function __construct($request){
        global $wpdb;
        $files = $request->get_file_params();
        $this->files = $files;
        if($files){
            $fileName = $files["file"]["tmp_name"];
            if ($files["file"]["size"] > 0) {
                
                $file = fopen($fileName, "r");
                $math = array();
                $social_studies = array();
                //begin looping through the csv file values
                while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                    //var_dump($column);
                    if (!empty($column[0])) {
                        if(!empty($column[2])) {
                            $math[] = array(
                                'date'=>$column[0],
                                'description'=>'('.$column[1].') '.$column[2]
                            );
                        }
                        if(!empty($column[4])) {
                            $social_studies[] = array(
                                'date'=>$column[0],
                                'description'=>'('.$column[3].') '.$column[4]
                            );
                        }
                    }  
                } //end looping through the csv values
                unset($math[0]);
                unset($social_studies[0]);
                $math_by_date = array();
                foreach($math as $key=>$item){
                    $math_by_date[$item['date']][] = $item['description'];
                }
                $ss_by_date = array();
                foreach($social_studies as $key=>$item){
                    $ss_by_date[$item['date']][] = $item['description'];
                }
            }
            $classroom = array(
                'school'=>$request['school'],
                'teacher'=>$request['teacher'],
                'grade'=>$request['grade'],
                'subject'=>$request['subject'],
            );
            $class_id = $this->insert_classroom($classroom);
            $lessonPlan = array(
                'classroom'=>$class_id,
                'dueDate'=>$request['dueDate'],
                'assignments'=>$assignments
            );
            $this->lessonPlan = $lessonPlan;
        }
    }// end construct

    private function insert_classroom(){
        global $wpdb;
    }
    
}// end lesson plan class


class Lesson_Plans {
    public $request;
    public $due_dates;
    public $assignments;
    public $sections;

    public function __construct ($request){
        $this->request = $request->get_body_params();
        $this->set_due_dates();
        $this->set_sections();
        //if($request['show_assignments'] == true){
            $this->set_assignments($request->get_body_params());
        //}
    }

    private function set_due_dates(){
        global $wpdb;
        $qry = "
        SELECT DISTINCT (STR_TO_DATE(meta_value, '%Y-%m-%d')) as 'due_date'
        FROM wp_postmeta 
        WHERE meta_key='due_date' 
        ORDER BY due_date DESC";
        $metas = $wpdb->get_results($qry);
        $due_dates = array();
        foreach($metas as $meta){
            $due_dates[] = $meta->due_date;
        }
        $this->due_dates = $due_dates;
    }

    private function set_sections(){
        $sections_object = new Sections();
        $this->sections = $sections_object->sections;
    }

    private function set_class_args(){
        $args = array(
            'post_type'=>'assignment',
            'order'=>'DESC',
            'fields'=>'ids'
        );
        $posts = new \WP_Query($args);
        $this->posts = $posts->posts;
        $class_args = array();
        $class_taxs = array('schools', 'teachers', 'subjects', 'grades');
        foreach($class_taxs as $tax){
            foreach($posts as $post){
                $class_args[$tax][] = get_terms( array(
                    'taxonomy' => $tax,
                    'hide_empty' => true,
                    'fields'=>'names',
                    'object_ids'=>$post_id
                ) );
            }
        }
        $this->class_args = $class_args;
    }

    /* 
    * @param string date 
    */
    public function set_assignments($request){
        $assignments = array();
        unset($request['show_assignments']);
        $relation = (count($request) > 1)
            ? 'AND'
            : 'single';
        //foreach($this->due_dates as $due_date){
            $tax_query = array();
            if((count($request) > 1)){$tax_query['relation'] = 'AND';}
            foreach($request as $tax=>$value){
                $tax_query[] = array(
                    'taxonomy'=>$tax,
                    'field'=>'name',
                    'terms'=>$value
                );
            }
            
            $args = array(
                'posts_per_page'=>'-1',
                'post_type'=>'assignment',
                'order'=>'DESC'
            );
            /*'meta_query' => array(
                array(
                    'key' => 'due_date',
                    'value' => $due_date,
                    'compare' => '=',
                )
                );
                */
             if(!empty($request)){
                 $args['tax_query'] = $tax_query;
             }
            $posts = new \WP_Query($args); 
            //get author photo | query completed assignments
            foreach($posts->posts as $post){
                $post->author_avatar = get_user_meta($post->post_author, 'scholistit_photo', true);
                //get users complete
                global $wpdb;
                $completed = $wpdb->get_results("SELECT user_id FROM wp_completed_assignments WHERE post_id=".$post->ID);
                $users_complete = array();
                foreach($completed as $complete){
                    $users_complete[] = $complete->user_id;
                }
                $post->complete = $users_complete;
                //get mandatory
                $post->mandatory = get_post_meta($post->ID, 'mandatory', true);
                //get due date
                $post->due_date = get_post_meta($post->ID, 'due_date', true);
                //get due date
                $post->assigned_date = get_post_meta($post->ID, 'assigned_date', true);
            }

            $images = $this->get_images($posts);
            $assignments['assignments'] = array(
                'posts'=>$posts->posts,
                'images'=>$images
            ); 
        //}
        $this->assignments = $assignments;
    }

    public function get_images($posts){
        $images = array();
        foreach($posts as $post){
            $post_id = $post->ID;
            $imageUrl = get_the_post_thumbnail_url($post_id);
            if($imageUrl){
                $images[] = array('id'=>$post_id, 'imageUrl'=>$imageUrl);
            }
        }
        return $images;
    }

    


}//end lesson plan

/**
 * @param array associative array of 4 required parameters
 * @param string teacher
 * @param string school
 * @param string grade
 * @param string subject
 */
class Classroom {
    public $id;
    public $classroom;
    public $teacher;
    public $school;
    public $grade;
    public $subject;
    

    /**
     * @param string $key id
     * @param array $key (teacher, grade, school, subject)
     * @param $value would be either the array or the string of the id or the args
     */
    public function __construct($key, $value){
        if(empty(key) || empty($value)){
            trigger_error("you must provide a key and value to instantiate a classroom");
        }
        switch ($key) {
            case 'id':
                $this->instantiate_by_id($value);
            break;
            case 'class':
                $this->instantiate_by_class($value);
            break;
        }        
    }

    private function instantiate_by_id($id){
        global $wpdb;
        $qry = "SELECT * FROM wp_classrooms WHERE id=".$id;
        $classroom = $wpdb->get_row($qry);
        $this->classroom = $classroom;
        if(!empty($classroom)){
            $class_array = explode('_', $classroom->class);
            $this->id = $this->classroom->id;
            $this->schools = str_replace(' ', '-', $class_array[0]);
            $this->teachers = str_replace(' ', '-', $class_array[1]);
            $this->subjects = str_replace(' ', '-', $class_array[2]);
            $this->grades = str_replace(' ', '-', $class_array[3]);
            $this->class_title = ucwords($classroom->class_title);
        } 
    }
    /**
     * @param string "school teacher grade subject"
     */
    private function instantiate_by_class($class_string){
        global $wpdb;
        $qry = "SELECT * FROM wp_classrooms WHERE class='".$class_string."'";
        $classroom = $wpdb->get_row($qry);
        $this->classroom = $classroom;
        if(!empty($classroom)){
            $class_array = explode('_', $classroom->class);
            $this->id = $this->classroom->id;
            $this->schools = str_replace(' ', '-', $class_array[0]);
            $this->teachers = str_replace(' ', '-', $class_array[1]);
            $this->subjects = str_replace(' ', '-', $class_array[2]);
            $this->grades = str_replace(' ', '-', $class_array[3]);
            $this->class_title = ucwords($classroom->class_title);
        } 
    }
}

class Schools {
    public $classrooms;

    public function __construct(){
        global $wpdb;
        $qry = "select * from wp_classes order by school asc";
        if($wpdb->get_results($qry)){
            $this->classrooms = $wpdb->get_results($qry);
        } else {
            $this->classrooms = false;
        }
    }


}

/**
 * assignment
 * this should probably be a custom post type and let the classroom id sit within the post meta
 * in theory, for future or pro accounts, the teachers could use the WP Admin to put very detailed instructions, links, etc
 * and the app could render a full post page for each assignment if neccesary. The excerpt could be the short description and be required. 
 * 
 * for now, we will keep it simple but future proof with a custom post type
 * 
 * so this class is not needed...we just need to do the work to declare the custom post type
 */

 class Assignment extends Classroom {
    public $description; //the custom excerpt
    public $id;
    public $classroom;
    public $dueDate; //custom meta
    public $date; //custom meta


 }






/**
 * future features:
 * -- Google and FB Social login with WordPress
 * -- associating the lesson plan with the user that uploaded it
 * -- notification protocols
 * -- api protocols with other systems?
 */

/**
 * it was hard to resist going down this rabbit hole. But I think it should be handled on the front end.
 * we shall see whether that speeds us up or slows us down. 
 * Here is my thinkning - this is all there it exists
 * --- so if we prove concept on this thing and it works then we can scope this into future features
 */

/**
 * @param array full_name, fname, lname, email
 * @param string full_name is required to construct; all other params are optional
 */
class Teacher {
    public $fullname;
    public $fname;
    public $lname;
    public $email;
    public $class_sections; //array of subjects

    public function __construct($params){
        if (!array($params)){
            trigger_error('Error: teacher params is not an array or is not set', E_USER_ERROR);
        }

    }


}


?>