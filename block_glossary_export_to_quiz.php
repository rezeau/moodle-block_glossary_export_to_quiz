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
 * @package    block_glossary_export_to_quiz
 * @copyright  Joseph Rézeau moodle@rezeau.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Block glossary_export_to_quiz definition.
 *
 * This block can be added to a course page to enable a teacher to export
 * glossary entries to various question types.
 *
 * @package    block_glossary_export_to_quiz
 * @copyright  Joseph Rézeau <moodle@rezeau.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_glossary_export_to_quiz extends block_base {

    /**
     * Core function used to initialize the block.
     */
    public function init() {
        global $SESSION;
        $this->title = get_string('pluginname', 'block_glossary_export_to_quiz');
    }

     /**
      * Core function, specifies where the block can be used.
      * @return array
      */
    public function applicable_formats() {
        return array('all' => true);
    }

    /**
     * This function is called on your subclass right after an instance is loaded
     * Use this function to act on instance data just after it's loaded and before anything else is done
     * For instance: if your block will have different title's depending on location (site, course, blog, etc)
     */
    public function specialization() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        require_once($CFG->libdir . '/filelib.php');
        // Needed for getting available question types.
        require_once($CFG->libdir . '/questionlib.php');
        // Load userdefined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_glossary_export_to_quiz');
        } else {
            $this->title = $this->config->title;
        }
        $course = $this->page->course;
        $this->course = $course;
    }
    /**
     * Allows the block to be added multiple times to a single page
     * @return boolean
     */
    public function instance_allow_multiple() {
        // Are you going to allow multiple instances of each block?
        // If yes, then it is assumed that the block WILL USE per-instance configuration.
        return false;
    }

    /**
     * Parent class version of this function simply returns NULL
     * This should be implemented by the derived class to return
     * the content object.
     *
     * @return stdObject
     */
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
        if (empty($this->config->glossary) || empty($this->config->questiontype)
            || empty($SESSION->block_glossary_export_to_quiz->status) ) {
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
            $sql = "SELECT COUNT(*) "
                ." FROM mdl_glossary_entries ge , mdl_glossary_entries_categories c "
                . " WHERE ge.glossaryid = $glossaryid "
                . " AND ge.approved = 1 AND ge.id = c.entryid "
                . " AND c.categoryid = $categoryid";
            $entriescount = $DB->count_records_sql($sql);
            $categoryname = '<b>'.get_string('category', 'glossary').'</b>: <em>'.
                $category->name.'</em>';
        } else {
            $categoryid = '';
            $entriescount = $DB->count_records("glossary_entries", array('glossaryid' => $glossaryid));
            $categoryname = '<b>'.get_string('category', 'glossary').'</b>: '.
                get_string('allentries', 'block_glossary_export_to_quiz');
        }
        $limitnum = $this->config->limitnum;

        $qtype = $this->config->questiontype;
        // Initialize options.
        $usecase = '';
        $exportmediafiles = $this->config->exportmediafiles;
        $answerdisplay = '';
        $extrawronganswer = $this->config->extrawronganswer;
        $shuffleanswers = '';
        $answernumbering = '';
        $nbchoices = '';

        switch ($qtype) {
            case 1:     // Type shortanswer.
                $usecase = $this->config->usecase;

                break;
            case 2:     // Type multichoice.
                $stranswernumbering = array(
                    0 => 'abc',
                    1 => 'ABCD',
                    2 => '123',
                    3 => 'iii',
                    4 => 'IIII',
                    5 => 'none'
                );
                $nbchoices = $this->config->nbchoices - 1;
                $answernumbering = $stranswernumbering[$this->config->answernumbering];
                $shuffleanswers = $this->config->shuffleanswers;
                break;
            case 3:      // Type matching.
            case 4:      // Type drag and drop into text.
                $nbchoices = $this->config->nbchoices;
                $shuffleanswers = $this->config->shuffleanswers;
            case 5:     // Type gapfill.
                $nbchoices = $this->config->nbchoices;
                $shuffleanswers = $this->config->shuffleanswers;
                $answerdisplay = $this->config->answerdisplay;
            break;
        }

        if ($limitnum) {
            $numentries = min($limitnum, $entriescount);
            $limitnum = $numentries;
        } else {
            $numentries = $entriescount;
        }
        if ($qtype > 2) { // Matching or drag&drop question.
            $nbchoices += $extrawronganswer;
            $limitnum = floor ($numentries / $nbchoices ) * $nbchoices;
            $numentries = $limitnum;
            $numquestions = $limitnum / $nbchoices;
        } else {
            $numquestions = $numentries;
        }

        $strnumentries = '<br />'.get_string('numentries', 'block_glossary_export_to_quiz',
            $numentries).get_string('numquestions', 'block_glossary_export_to_quiz', $numquestions);

        $sortorder = $this->config->sortingorder;
        $type[0] = get_string('concept', 'block_glossary_export_to_quiz');
        $type[1] = get_string('lastmodified', 'block_glossary_export_to_quiz');
        $type[2] = get_string('firstmodified', 'block_glossary_export_to_quiz');
        $type[3] = get_string('random', 'block_glossary_export_to_quiz');

        $questiontype[1] = 'shortanswer';
        $questiontype[2] = 'multichoice';
        $questiontype[3] = 'matching';
        $questiontype[4] = 'ddwtos';

        $strquestiontypes = array(
            1 => get_string('pluginname', 'qtype_shortanswer'),
            2 => get_string('pluginname', 'qtype_multichoice'),
            3 => get_string('pluginname', 'qtype_match'),
            4 => get_string('pluginname', 'qtype_ddwtos')
        );
        // JR DECEMBER 2018 added the gapfill question type.
        $createabletypes = question_bank::get_creatable_qtypes();
        if (array_key_exists('gapfill', $createabletypes)) {
            $questiontype[5] = 'gapfill';
            $strquestiontypes[5] = get_string('pluginname', 'qtype_gapfill');
        };

        // Just in case a new question type has been removed after creating an export glossary.
        if (!$questiontype[$this->config->questiontype]) {
            $this->content->footer = '';
            return $this->content;
        }

        $questiontype = $questiontype[$this->config->questiontype];
        $stractualquestiontype = $strquestiontypes[$this->config->questiontype];
        $strsortorder = '<b>'.get_string('sortingorder', 'block_glossary_export_to_quiz').'</b>: '.$type[$sortorder];
        $strquestiontype = '<b>'.get_string('questiontype', 'quiz', '</b>'.$stractualquestiontype);
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
            .'&amp;sortorder='.$sortorder.'&amp;usecase='.$usecase.'&amp;exportmediafiles='.$exportmediafiles
            .'&amp;nbchoices='.$nbchoices.'&amp;extrawronganswer='.$extrawronganswer
            .'&amp;numquestions='.$numquestions.'&amp;answernumbering='.$answernumbering
            .'&amp;shuffleanswers='.$shuffleanswers.'&amp;answerdisplay='.$answerdisplay.'>'
            .'<b>'.$strnumentries.'</b></a>';
        return $this->content;
    }
}