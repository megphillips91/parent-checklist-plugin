<?php
/**
 * Lesson Content 
 * this file should hold classes responsible for taking blocks from megadraft and converting them into gutenberg blocks. 
 * the idea here is that in theory, I can roll this into a plugin for the repo and therein
 * gain credibility as a developer working on modern front end 
 * 
 * other than personal self interest, what this will do that is pretty cool, is to "translate" gutenberg into draft.js
 * with the end net effect being that the lesson content will be available on both side - within the WP editoe
 * and within the app. which could prove needed and useful.
 */
namespace Parent_Checklist_REST;
use \DateTime;

/**
 * Translates megadraft blocks into gutenberg blocks 
 * take in megadraft blocks translate into gutenberg blocks which can be saved into the DB and then used in the WP Admin
 * @param string $JSON_blocks -  raw result of megadraft function editorStateToJSON; send it over red rover ;)
 * @return string the formatted and wrapped gutenberg block to be stored into the db
 */
class Translate_Megadraft_Blocks {
    public $raw;
    public $guten_blocks;

    /* this takes in the raw result of megadraft function editorStateToJSON */
    public function __construct ($JSON_blocks) {
        $this->raw = $JSON_blocks;
        $this->blocks = array();
        foreach($JSON_blocks as $block){
           $this->blocks[] = new Gutenberg_Block(json_decode($block));
        }
        return $this;        
    }
    
}

class Gutenberg_Block {
    public $megadraft_type;
    public $guten_type;
    public $guten_block;

    public function __construct ($block_raw){
        $this->megadraft_type = $block_raw->type;
        switch ($this->megadraft_type) {
            case 'unstyled' :
                $this->guten_type = 'paragraph';
                $this->guten_block = $this->translate_paragraph($block_raw->text);
            break;
            default:
                $this->guten_block = '';
                $this->guten_type = 'not recognized';
        }
    }

    private function translate_paragraph($content){
        $block = '<!-- wp:paragraph --><p>'.$content.'</p><!-- /wp:paragraph -->';
        return $block;
    }
}


class Translate_Gutenberg_Blocks {
    public $blocks;

    public function __construct ($post_content) {
        //$this->guten_blocks = parse_blocks($post_content);
        $megadraft_blocks = array();
        
         if ( has_blocks( $post_content ) ) {
            $guten_blocks = parse_blocks($post_content);
            foreach($guten_blocks as $block){
                if($block['blockName'] != NULL){
                    $megadraft = new Megadraft_block($block);
                    $megadraft_blocks[] = $megadraft->megadraft_block;
                }
            }
        } 
        $this->blocks = $megadraft_blocks;     
    }
    
}

class Megadraft_block {
    public $megadraft_block;
   
    public function __construct ($guten_block){
       // $this->blockvartype = ($guten_block['blockName'] == 'core/paragraph') ? true : false;
       //$this->guten_block = $guten_block;
        
        switch ($guten_block['blockName']){
            case 'core/paragraph':
                $this->megadraft_block = $this->create_paragraph($guten_block);
            break;
            case 'core/heading':
                $this->megadraft_block = $this->create_heading($guten_block);
            break;
            case 'core/image':
                $this->megadraft_block = $this->create_image($guten_block);
            break;
            default:  
                $this->megadraft_block = 'error: blocktype not recognized'; 
        }
    }

    private function create_image($guten_block){
        $display = "medium";
        if($guten_block['attrs']['align'] === 'left'){
            $display = 'small';
        } 
        //$image_src = $guten_block['innerHTML'];
        $image_arr = explode("src=", $guten_block['innerHTML']);
        $image_arr = explode('"', $image_arr[1]);
        $image_src = $image_arr[1];
        if(filter_var($image_src, FILTER_VALIDATE_URL) === false){
            $image_src  = "error: the src was not extracted from the gutenberg block properly";
        }

        $data = array(
            'src' => $image_src,
            'type' => "image",
            'display'=>$display,
        );
        return array(
            'key'=> rand(),
            'text'=> wp_strip_all_tags($guten_block['innerHTML'], true),
            'type'=>'atomic',
            "depth" => 0,
            "inlineStyleRanges" => array(),
            "entityRanges" => array(),
            "data" => (object) $data
        );
    }

    private function create_paragraph($guten_block){
        
        return array(
            'key'=> rand(),
            'text'=> wp_strip_all_tags($guten_block['innerHTML'], true),
            'type'=>'unstyled',
            "depth" => 0,
            "inlineStyleRanges" => array(),
            "entityRanges" => array(),
            "data" => (object) array()
        );
    }

    private function create_heading($guten_block){
        
        return array(
            'key'=> rand(),
            'text'=> wp_strip_all_tags($guten_block['innerHTML'], true),
            'type'=>"header-two",
            "depth" => 0,
            "inlineStyleRanges" => array(),
            "entityRanges" => array(),
            "data" => (object) array()
        );
    }
}

 ?>