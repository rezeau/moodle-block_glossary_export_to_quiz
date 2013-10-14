<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details
 *
 * @package    block
 * @subpackage glossary_export_to_quiz
 * @copyright  Joseph Rézeau moodle@rezeau.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_glossary_export_to_quiz extends block_base {
    public function init() {
        global $SESSION;
        $this->title = get_string('pluginname', 'block_glossary_export_to_quiz');
    }

    public function specialization() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        require_once($CFG->libdir . '/filelib.php');
        // Load userdefined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_glossary_export_to_quiz');
        } else {
            $this->title = $this->config->title;
        }
        $course = $this->page->course;
        $this->course = $course;
    }

    public function instance_allow_multiple() {
        // Are you going to allow multiple instances of each block?
        // If yes, then it is assumed that the block WILL USE per-instance configuration.
        return false;
    }

    public function get_content() {
        global $USER, $CFG, $DB, $PAGE, $SESSION;
        $editing = $PAGE->user_is_editing();
        $this->content = new stdClass();
        // Set view block permission to course:mod/glossary:export to prevent students etc to view this block.
        $course = $this->page->course;
        $context = context_course::instance($course->id);
        if (!has_capability('mod/glossary:export', $context)) {
            return;
        }
        // Get list of all current course glossaries.
        $glossaries = $DB->get_records_menu('glossary', array('course' => $this->course->id));

        // No glossary available in current course -> return.
        if (empty($glossaries)) {
            $strglossarys = get_string("modulenameplural", "glossary");
            $this->content->text = get_string('thereareno', 'moodle', $strglossarys);
            $this->content->footer = '';
            return $this->content;
        }
        if (empty($this->config->glossary) || empty($SESSION->block_glossary_export_to_quiz->status) ) {
            if ($editing) {
                    $this->content->text = get_string('notyetconfiguredediting', 'block_glossary_export_to_quiz');
            } else {
                $this->content->text = get_string('notyetconfigured', 'block_glossary_export_to_quiz');
            }

            $this->content->footer = '';
            return $this->content;
        }

        $glossary = explode(",", $this->config->glossary);
        $glossaryid = $glossary[0];
        $categoryid = $glossary[1];

        $cm = get_coursemodule_from_instance("glossary", $glossaryid);
        $cmid = $cm->id;
        $glosssaryname = "<em>$cm->name</em>";

        require_once($CFG->dirroot.'/course/lib.php');
        // Build "content" to be displayed in block.
        // User may have requested a glossary category.
        $categories = explode(",", $this->config->glossary);
        $glossaryid = $categories[0];
        $entriescount = 0;
        $numentries = 0;
        if (isset ($categories[1]) && $categories[1] != 0) {
            $categoryid = $categories[1];
            $category = $DB->get_record('glossary_categories', array('id' => $categoryid));
            $entriescount = $DB->count_records("glossary_entries_categories", array('categoryid'=>$category->id));
            $categoryname = '<b>'.get_string('category', 'glossary').'</b>: <em>'.
                $category->name.'</em>';
        } else {
            $categoryid = '';
            $entriescount = $DB->count_records("glossary_entries", array('glossaryid'=>$glossaryid));
            $categoryname = '<b>'.get_string('category', 'glossary').'</b>: '.
                get_string('allentries', 'block_glossary_export_to_quiz');
        }
        $limitnum = $this->config->limitnum;

        if ($limitnum) {
            $numentries = min($limitnum, $entriescount);
            $limitnum = $numentries;
        } else {
            $numentries = $entriescount;
        }

        $strnumentries = '<br />'.get_string('numentries', 'block_glossary_export_to_quiz', $numentries);

        $sortorder = $this->config->sortingorder;
        $type[0] = get_string('concept', 'block_glossary_export_to_quiz');
        $type[1] = get_string('lastmodified', 'block_glossary_export_to_quiz');
        $type[2] = get_string('firstmodified', 'block_glossary_export_to_quiz');
        $type[3] = get_string('random', 'block_glossary_export_to_quiz');

        $questiontype[0] = 'multichoice_abc';
        $questiontype[1] = 'multichoice_ABCD';
        $questiontype[2] = 'multichoice_123';
        $questiontype[3] = 'multichoice_none';
        $questiontype[4] = 'shortanswer_0'; // Case insensitive.
        $questiontype[5] = 'shortanswer_1'; // Case sensitive.

        $questiontype = $questiontype[$this->config->questiontype];
        $actualquestiontypeparams = explode('_', $questiontype);
        $actualquestiontype = $actualquestiontypeparams[0];
        $actualquestionparam = $actualquestiontypeparams[1];

        $stractualquestiontype = get_string($actualquestiontype, 'block_glossary_export_to_quiz');
        $strsortorder = '<b>'.get_string('sortingorder', 'block_glossary_export_to_quiz').'</b>: '.$type[$sortorder];
        $strquestiontype = '<b>'.get_string('questiontype', 'block_glossary_export_to_quiz').'</b> '.$stractualquestiontype;
        $cm = get_coursemodule_from_instance("glossary", $glossaryid);
        $cmid = $cm->id;
        $glosssaryname = "<em>$cm->name</em>";
        $title = get_string('clicktoexport', 'block_glossary_export_to_quiz');
        $strglossary = get_string('currentglossary', 'glossary');

        $this->content->text   = '<b>'.$strglossary.'</b>: '.$glosssaryname.'<br />'.$categoryname.'<br />'.
            $strsortorder. '<br />'.$strquestiontype;
        $this->content->footer = '<a title="'.$title.'" href='
            .$CFG->wwwroot.'/blocks/glossary_export_to_quiz/export_to_quiz.php?id='
            .$cmid.'&amp;cat='.$categoryid.'&amp;limitnum='.$limitnum.'&amp;questiontype='.$questiontype
            .'&amp;sortorder='.$sortorder.'&amp;entriescount='.$numentries.'>'
            .'<b>'.$strnumentries.'</b></a>';
            return $this->content;
    }
}