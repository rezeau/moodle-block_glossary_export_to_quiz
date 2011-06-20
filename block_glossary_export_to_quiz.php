<?php // $Id: block_glossary_export_to_quiz.php,v 1.0. 2010/11/27 01:01:01 rezeau Exp $

class block_glossary_export_to_quiz extends block_base {
    function init() {
    	$this->title = get_string('blockname','block_glossary_export_to_quiz');
        $this->version = 2011012900;
    }

    function specialization() {
        global $CFG, $COURSE, $editing;
        $this->course = $COURSE;
		// make editing icon available even when course edit mode is off
        $editingiconlink = '';
        $title = get_string('configuration');
        $glossaries = get_records_select_menu('glossary', 'course='.$this->course->id,'name','id,name');        	
		if (!$editing && !empty($glossaries)) {
        	$editingiconlink = ' <a title="'.$title.'" href="'.$CFG->wwwroot.'/course/view.php?id='.$COURSE->id.
            	'&instanceid='.$this->instance->id.'&sesskey='.sesskey().'&blockaction=config">'.
            	'<img src="'.$CFG->pixpath.'/i/edit.gif" class="icon" alt="" /></a>';
        }        
        $this->title = get_string('blockname','block_glossary_export_to_quiz').$editingiconlink;
    }

    function instance_allow_multiple() {
        return false;
    }

    function instance_config_print() {
        global $CFG, $SESSION;;

        if (!isset($this->config)) {
            // ... teacher has not yet configured the block, let's put some default values here to explain things
            $this->config->title = get_string('pluginname','block_glossary_export_to_quiz');
        }
        
        // select glossaries to put in dropdown box ...
        $glossaries = get_records_select_menu('glossary', 'course='.$this->course->id,'name','id,name');        

		// build dropdown list array for choose_from_menu_nested () in config_instance file
        $categoriesarray = array();
        foreach($glossaries as $key => $value) {
			$glossaries[$key] = strip_tags(format_string($value,true));
            $categories = get_records("glossary_categories", "glossaryid", $key ,"name ASC");
			$glossarystring = $value;
			$numentries = count_records("glossary_entries","glossaryid",$key);
			if ($numentries) {
				$categoriesarray[$glossarystring][$key] = '* '.get_string('allentries','block_glossary_export_to_quiz').' ('.$numentries.')';
                if(!empty($categories)) {
                	foreach ($categories as $category) {
                		$cid = $category->id;
                		$numentries = count_records("glossary_entries_categories","categoryid",$category->id);               	
                		$categoriesarray[$glossarystring][$key.','.$cid] = $category->name.' ('.$numentries.')';
                	}
                }
			}
		}
		// and select entry types to put in dropdown box
        $sortorder[0] = get_string('concept','block_glossary_export_to_quiz');
        $sortorder[1] = get_string('lastmodified','block_glossary_export_to_quiz');	
		$sortorder[2] = get_string('firstmodified','block_glossary_export_to_quiz');        
		$sortorder[3] = get_string('random','block_glossary_export_to_quiz');

        $questiontype[0] = get_string('multichoice','block_glossary_export_to_quiz').' ('.get_string('answernumberingabc', 'qtype_multichoice').')';
        $questiontype[1] = get_string('multichoice','block_glossary_export_to_quiz').' ('.get_string('answernumberingABCD', 'qtype_multichoice').')';
        $questiontype[2] = get_string('multichoice','block_glossary_export_to_quiz').' ('.get_string('answernumbering123', 'qtype_multichoice').')';
        $questiontype[3] = get_string('multichoice','block_glossary_export_to_quiz').' ('.get_string('answernumberingnone', 'qtype_multichoice').')';
        $questiontype[4] = get_string('shortanswer_0','block_glossary_export_to_quiz'); // case insensitive
        $questiontype[5] = get_string('shortanswer_1','block_glossary_export_to_quiz'); // case sensitive
        // display the form in config_instance file
        if (is_file($CFG->dirroot .'/blocks/'. $this->name() .'/config_instance.html')) {
            print_simple_box_start('center', '', '', 5, 'blockconfigglobal');
            include($CFG->dirroot .'/blocks/'. $this->name() .'/config_instance.html');
            print_simple_box_end();
        } else {
            notice(get_string('blockconfigbad'), str_replace('blockaction=', 'dummy=', qualified_me()));
        }
		$SESSION->block_glossary_export_to_quiz->glossary = $this->config->glossary;
        return true;		
    }

    function get_content() {
        global $USER, $CFG, $COURSE, $SESSION;
        $this->content->footer = '';
        // set view block permission to course:mod/glossary:export to prevent students etc to view this block
        $course = get_record('course', 'id', optional_param('id',1)); 
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
		if (!has_capability('mod/glossary:export', $context)) {
  			return;
		}
		
		// get list of all current course glossaries
		$glossaries = get_records_select_menu('glossary', 'course='.$this->course->id,'name','id,name');        
		
		// no glossary available in current course -> return
		if(empty($glossaries)) {
        	$strglossarys = get_string("modulenameplural", "glossary");
        	$this->content->text = get_string('thereareno', 'moodle', $strglossarys);
			return $this->content;
		}
        if (empty($this->config->glossary) 
        		|| !isset($SESSION->block_glossary_export_to_quiz->glossary) 
        		|| $SESSION->block_glossary_export_to_quiz->glossary == 'exported') {
        	$this->content->text   = get_string('notyetconfigured','block_glossary_export_to_quiz');
            return $this->content;
        }
                
        // build "content" to be displayed in block
        // user may have requested a glossary category
        $categories = explode(",",$this->config->glossary);
        $glossaryid = $categories[0];
        $entriescount = 0;
        $numentries = 0;
        if (isset ($categories[1])) {
        	$categoryid = $categories[1];
        	$category = get_record('glossary_categories', 'id', $categoryid);
			$entriescount = count_records("glossary_entries_categories","categoryid",$category->id);        	
        	$categoryname = '<b>'.get_string('category','glossary').'</b>: <em>'.$category->name.'</em>';        	
        } else {
        	$categoryid = '';
        	$entriescount = count_records("glossary_entries","glossaryid",$glossaryid);
        	$categoryname = '<b>'.get_string('category','glossary').'</b>: '.get_string('allentries','block_glossary_export_to_quiz');
        }
		$limitnum = $this->config->limitnum;        
		
		if ($limitnum) {
			$numentries = min($limitnum, $entriescount);
			$limitnum = $numentries;			
		} else {
			$numentries = $entriescount;
		}

		$strnumentries = '<br />'.get_string('numentries', 'block_glossary_export_to_quiz', $numentries);
		
        $sortorder = $this->config->sortorder;
        $type[0] = get_string('concept','block_glossary_export_to_quiz');  
        $type[1] = get_string('lastmodified','block_glossary_export_to_quiz');
        $type[2] = get_string('firstmodified','block_glossary_export_to_quiz');        
        $type[3] = get_string('random','block_glossary_export_to_quiz');
 
        $questiontype[0] = 'multichoice_abc';
        $questiontype[1] = 'multichoice_ABCD';
        $questiontype[2] = 'multichoice_123';
        $questiontype[3] = 'multichoice_none';
        $questiontype[4] = 'shortanswer_0'; // case insensitive
        $questiontype[5] = 'shortanswer_1'; // case sensitive
        
        $questiontype = $questiontype[$this->config->questiontype];
        $actualquestiontype_params = explode('_', $questiontype);
        $actualquestiontype = $actualquestiontype_params[0];
        $actualquestionparam = $actualquestiontype_params[1];
        
        $stractualquestiontype = get_string($actualquestiontype, 'block_glossary_export_to_quiz'); 
        $strsortorder = '<b>'.get_string('sortorder','block_glossary_export_to_quiz').'</b>: '.$type[$sortorder];
        $strquestiontype = '<b>'.get_string('questiontype','block_glossary_export_to_quiz').'</b> '.$stractualquestiontype;
        $cm = get_coursemodule_from_instance("glossary", $glossaryid);
        $cmid = $cm->id;
        $glosssaryname = "<em>$cm->name</em>"; 
        $title = get_string('clicktoexport','block_glossary_export_to_quiz');
        $strglossary = get_string('currentglossary','glossary');
        if (($actualquestiontype == 'multichoice') && $numentries < 4) {
            $varnotenough = $glosssaryname.' | '.$categoryname;
            $a->numentries = $numentries;
            $a->varnotenough = $varnotenough; 
            $this->content->text = get_string('notenoughentries', 'block_glossary_export_to_quiz', $a);;
            return $this->content;
        }
        $this->content->text = '<b>'.$strglossary.'</b>: '.$glosssaryname.'<br />'.$categoryname.'<br />'.
            $strsortorder. '<br />'.$strquestiontype;
        $this->content->footer = '<a title="'.$title.'" href='
            .$CFG->wwwroot.'/blocks/glossary_export_to_quiz/export_to_quiz.php?id='
            .$cmid.'&amp;cat='.$categoryid.'&amp;limitnum='.$limitnum.'&amp;questiontype='.$questiontype
            .'&amp;sortorder='.$sortorder.'&amp;entriescount='.$numentries.'>'
            .'<b>'.$strnumentries.'</b></a>';
            return $this->content;
		
    }

    /*function hide_header() {
        return false;
    }*/

    /**
     * Executed after block instance has been created, we use it to recode
     * the glossary config setting to point to the new (restored) one
     */
/*    function after_restore($restore) {
    /// We need to transform the glossary->id from the original one to the restored one
        if ($rec = backup_getid($restore->backup_unique_code, 'glossary', $this->config->glossary)) {
            $this->config->glossary = $rec->new_id;
            $this->instance_config_commit();
        }
    }
*/	
    function instance_allow_config() {
	/// do not allow config if there are no glossaries available in current course    	
        $glossaries = get_records_select_menu('glossary', 'course='.$this->course->id,'name','id,name');        
        if($glossaries) {
			return true;
        }
        return false;
	}
	
}
?>