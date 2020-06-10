<?php
/**
 * Plugin Name: SchooListIt Api
 * Plugin URI: http://SchooListIt.com/
 * Description: Rest API for parent checklist
 * Author: megphillips91
 * Author URI: http://msp-media.org/
 * Version: 1.1.3
 * License: GPL2+
 * http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

 /*
 Parent Checklise is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 2 of the License, or
 any later version.

 Parent Checklist is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Charter Boat Bookings. If not, see http://www.gnu.org/licenses/gpl-3.0.html.
 */
namespace Parent_Checklist_REST;

 // Exit if accessed directly.
 if ( ! defined( 'ABSPATH' ) ) {
 	exit;
 }

 /**
  * Include plugin files
  */
 require_once plugin_dir_path( __FILE__ ) . 'lesson-plan.php';
 require_once plugin_dir_path( __FILE__ ) . 'translate_blocks.php';
 require_once plugin_dir_path( __FILE__ ) . 'endpoints.php';
 require_once plugin_dir_path( __FILE__ ) . 'post-types.php';


 //create post type
 $post_type = new Assignment_Post_Type_Factory();

 
?>