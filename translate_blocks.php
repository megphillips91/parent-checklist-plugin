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
    
}

 ?>