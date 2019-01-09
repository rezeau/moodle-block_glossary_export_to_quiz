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
 * @copyright  Joseph Rézeau <moodle@rezeau.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Form for editing glossary_export_to_quiz block instances.
 *
 * @copyright  Joseph Rézeau <moodle@rezeau.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_glossary_export_to_quiz_edit_form extends block_edit_form {

    /**
     * The definition of the fields to use.
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        global $CFG, $DB, $SESSION;
        $SESSION->block_glossary_export_to_quiz = new stdClass();
        $SESSION->block_glossary_export_to_quiz->status = 'defined';
        // Needed for getting available question types.
        require_once($CFG->libdir . '/questionlib.php');

        // Fields for editing HTML block title and contents.
        $mform->addElement('static', 'generalhelp', get_string('pluginname', 'block_glossary_export_to_quiz') .
            ' ('.get_string('help').')' );
        $mform->addHelpButton('generalhelp', 'pluginname', 'block_glossary_export_to_quiz');

        // Select glossaries to put in dropdown box ...
        $glossaries = $DB->get_records_menu('glossary', array('course' => $this->block->course->id), 'name', 'id,name');
        if (!$glossaries) {
            $mform->addElement('header', 'config_noglossaries', get_string('noglossaries', 'block_glossary_export_to_quiz'));
        } else {
            $numglossaries = $DB->count_records('glossary', array('course' => $this->block->course->id));
            foreach ($glossaries as $key => $value) {
                $glossaries[$key] = strip_tags(format_string($value, true));
            }

            // Build dropdown list array for choose_from_menu_nested () in config_instance file.
            $categoriesarray = array();
            $categoriesarray[''][0] = get_string('choosedots');

            // Number of entries available in glossary/category.
            $numentriesincategory = array();
            $totalnumentries = 0;
            foreach ($glossaries as $key => $value) {
                $glossarystring = $value;
                $numentries = $DB->count_records('glossary_entries', array('glossaryid' => $key, 'approved' => 1));
                $totalnumentries += $numentries;
                if ($numentries) {
                    $categoriesarray[$glossarystring][$key.',0'] = $glossarystring.' * '.
                        get_string('allentries', 'block_glossary_export_to_quiz').' ('.$numentries.')';
                    $numentriesincategory[$key][0] = $numentries;
                    $select = 'glossaryid='.$key;
                    $categories = $DB->get_records_select('glossary_categories', $select, null, 'name ASC');
                    if (!empty ($categories)) {
                        foreach ($categories as $category) {
                            $cid = $category->id;
                            $sql = "SELECT COUNT(*) "
                                ." FROM mdl_glossary_entries ge , mdl_glossary_entries_categories c "
                                . " WHERE ge.glossaryid = $key "
                                . " AND ge.approved = 1 AND ge.id = c.entryid "
                                . " AND c.categoryid = $cid";
                            $numentries = $DB->count_records_sql($sql);
                            // Do not show empty categories in the list!
                            if ($numentries) {
                                $categoriesarray[$glossarystring][$key.','.$cid] = $glossarystring.' :: '.
                                    $category->name.' ('.$numentries.')';
                                $numentriesincategory[$key][$cid] = $numentries;
                            }
                        }
                    }
                }
            }

            if ($totalnumentries === 0) {
                if ($numglossaries == 1) {
                    $emptyglossaries = 'emptyglossary';
                } else {
                    $emptyglossaries = 'emptyglossaries';
                }
                $mform->addElement('header', 'configheader', get_string($emptyglossaries, 'block_glossary_export_to_quiz'));
            } else {
                $this->categoriesarray = $categoriesarray;
                $this->numentriesincategory = $numentriesincategory;
                $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

                $group = array($mform->createElement('selectgroups', 'config_glossary', '', $categoriesarray) );
                $mform->addGroup($group, 'selectglossary',
                    get_string('selectglossary', 'block_glossary_export_to_quiz'), '', false);

                // And select sortorder types to put in dropdown box.
                $mform->addHelpButton('selectglossary', 'selectglossary', 'block_glossary_export_to_quiz');

                $types = array(
                    0 => get_string('concept', 'block_glossary_export_to_quiz'),
                    1 => get_string('lastmodified', 'block_glossary_export_to_quiz'),
                    2 => get_string('firstmodified', 'block_glossary_export_to_quiz'),
                    3 => get_string('random', 'block_glossary_export_to_quiz')
                );
                $mform->addElement('select', 'config_sortingorder',
                                get_string('sortingorder', 'block_glossary_export_to_quiz'), $types);
                $mform->addHelpButton('config_sortingorder', 'sortingorder', 'block_glossary_export_to_quiz');
                $mform->hideIf('config_sortingorder', 'config_glossary', 'eq', 0);

                $mform->addElement('text', 'config_limitnum',
                                get_string('limitnum', 'block_glossary_export_to_quiz'), array('size' => 5));
                $mform->addHelpButton('config_limitnum', 'limitnum', 'block_glossary_export_to_quiz');
                $mform->setDefault('config_limitnum', '');
                $mform->setType('config_limitnum', PARAM_INTEGER);
                $mform->hideIf('config_limitnum', 'config_glossary', 'eq', 0);

                // And select question types to put in dropdown box.
                $strquestiontypes = array(
                    0 => get_string('choosedots'),
                    1 => get_string('pluginname', 'qtype_shortanswer'),
                    2 => get_string('pluginname', 'qtype_multichoice'),
                    3 => get_string('pluginname', 'qtype_match'),
                    4 => get_string('pluginname', 'qtype_ddwtos')
                );

                // JR DECEMBER 2018 todo maybe add more question types.
                $gapfillinstalled = false;
                $createabletypes = question_bank::get_creatable_qtypes();
                if (array_key_exists('gapfill', $createabletypes)) {
                    $gapfillinstalled = true;
                    $strquestiontypes[5] = get_string('pluginname', 'qtype_gapfill');
                };

                $mform->addElement('select', 'config_questiontype',
                    get_string('selectquestiontype', 'quiz'), $strquestiontypes);
                $mform->setDefault('config_questiontype', 0);
                $mform->hideIf('config_questiontype', 'config_glossary', 'eq', 0);

                $nbchoices = array(
                    3 => 3,
                    4 => 4,
                    5 => 5,
                    6 => 6,
                    7 => 7,
                    8 => 8,
                    9 => 9,
                    10 => 10
                );
                if ($gapfillinstalled) {
                    $answerdisplaytypes = array(
                        "gapfill" => get_string('displaygapfill', 'qtype_gapfill'),
                        "dragdrop" => get_string('displaydragdrop', 'qtype_gapfill'),
                        "dropdown" => get_string('displaydropdown', 'qtype_gapfill'));

                    $mform->addElement('select', 'config_answerdisplay', get_string('pluginname', 'qtype_gapfill').' '
                        .get_string('answerdisplay', 'qtype_gapfill'), $answerdisplaytypes);
                    $mform->setDefault('config_answerdisplay', 1);
                    $mform->addHelpButton('config_answerdisplay', 'answerdisplay', 'qtype_gapfill');
                    $mform->hideIf('config_answerdisplay', 'config_questiontype', 'neq', 5);
                    $mform->hideIf('config_answerdisplay', 'config_glossary', 'eq', 0);

                    // Sets all gaps to the size of the largest gap, avoids giving clues to the correct answer.
                    $mform->addElement('advcheckbox', 'config_fixedgapsize', get_string('fixedgapsize', 'qtype_gapfill'));
                    $mform->addHelpButton('config_fixedgapsize', 'fixedgapsize', 'qtype_gapfill');
                    $mform->hideIf('config_fixedgapsize', 'config_answerdisplay', 'eq', 'dropdown');
                    $mform->hideIf('config_fixedgapsize', 'config_questiontype', 'neq', 5);
                    $mform->hideIf('config_fixedgapsize', 'config_glossary', 'eq', 0);
                }

                $mform->addElement('select', 'config_nbchoices',
                    get_string('nbchoices', 'block_glossary_export_to_quiz'), $nbchoices);
                $mform->addHelpButton('config_nbchoices', 'nbchoices', 'block_glossary_export_to_quiz');
                // Disable my control unless a dropdown has value 42.
                $mform->hideIf('config_nbchoices', 'config_questiontype', 'eq', 0);
                $mform->hideIf('config_nbchoices', 'config_questiontype', 'eq', 1);
                $mform->hideIf('config_nbchoices', 'config_glossary', 'eq', 0);

                // Matching & drag&drop text add an extra wrong answer.
                $menu = array(
                    get_string('no'),
                    get_string('yes')
                );
                $mform->addElement('select', 'config_extrawronganswer',
                    get_string('extrawronganswer', 'block_glossary_export_to_quiz'), $menu);
                $mform->addHelpButton('config_extrawronganswer', 'extrawronganswer', 'block_glossary_export_to_quiz');
                // Disable this control for shortanswer (0) and multichoice (2) question types.
                $mform->hideIf('config_extrawronganswer', 'config_questiontype', 'eq', 0);
                $mform->hideIf('config_extrawronganswer', 'config_questiontype', 'eq', 2);
                $mform->hideIf('config_extrawronganswer', 'config_questiontype', 'eq', 1);
                $mform->hideIf('config_extrawronganswer', 'config_glossary', 'eq', 0);

                // Answer numbering for multichoice questions.
                $answernumbering = array(
                    0 => get_string('answernumberingabc', 'qtype_multichoice'),
                    1 => get_string('answernumberingABCD', 'qtype_multichoice'),
                    2 => get_string('answernumbering123', 'qtype_multichoice'),
                    3 => get_string('answernumberingiii', 'qtype_multichoice'),
                    4 => get_string('answernumberingIIII', 'qtype_multichoice'),
                    5 => get_string('answernumberingnone', 'qtype_multichoice')
                );
                $mform->addElement('select', 'config_answernumbering',
                    get_string('answernumbering', 'qtype_multichoice'), $answernumbering);
                $mform->hideIf('config_answernumbering', 'config_questiontype', 'neq', 2);
                $mform->hideIf('config_answernumbering', 'config_glossary', 'eq', 0);

                // Shuffle within questions.
                $mform->addElement('selectyesno', 'config_shuffleanswers',
                    get_string('shufflewithin', 'quiz'));
                $mform->addHelpButton('config_shuffleanswers', 'shufflewithin', 'quiz');
                $mform->setDefault('config_shuffleanswers', 1);
                $mform->hideIf('config_shuffleanswers', 'config_questiontype', 'eq', 0);
                $mform->hideIf('config_shuffleanswers', 'config_questiontype', 'eq', 1);
                $mform->hideIf('config_shuffleanswers', 'config_questiontype', 'eq', 5);

                // Short answer usecase.
                $menu = array(
                    get_string('caseno', 'qtype_shortanswer'),
                    get_string('caseyes', 'qtype_shortanswer')
                );
                $mform->addElement('select', 'config_usecase',
                    get_string('casesensitive', 'qtype_shortanswer'), $menu);
                $mform->hideIf('config_usecase', 'config_questiontype', 'neq', 1);
                $mform->hideIf('config_usecase', 'config_glossary', 'eq', 0);

                // Export media files.
                $menu = array(
                    get_string('no'),
                    get_string('yes')
                );
                $mform->addElement('select', 'config_exportmediafiles',
                    get_string('exportmediafiles', 'block_glossary_export_to_quiz'), $menu);
                $mform->addHelpButton('config_exportmediafiles', 'exportmediafiles', 'block_glossary_export_to_quiz');
                $mform->hideIf('config_exportmediafiles', 'config_glossary', 'eq', 0);
                $mform->hideIf('config_exportmediafiles', 'config_questiontype', 'eq', 0);
            }
        }
    }

    /**
     * Validate the submitted form data.
     *
     * @param array $data
     * @param array $files
     * @return array $errors
     */
    public function validation($data, $files) {
        $errors = array();
        if (!isset($data['config_glossary'])) {
            return;
        }
        $glossaryid = $data['config_glossary'];
        if ($glossaryid == 0) {
            return;
        }
        $errors = parent::validation($data, $files);
        $glossary = explode(",", $glossaryid);
        $glossaryid = $glossary[0];
        $categoryid = $glossary[1];
        $glossarynumentries = $this->numentriesincategory[$glossaryid][$categoryid];
        $questiontype = $data['config_questiontype'];
        if ($data['config_limitnum']) {
            $maxentries = $data['config_limitnum'];
        } else {
            $maxentries = $glossarynumentries;
        }
        if ($questiontype > 1) {
            $data['config_nbchoices'] += $data['config_extrawronganswer'];
            $nbchoices = $data['config_nbchoices'];
            if ($questiontype > 1) { // Multichoice / matching / draganddrop.
                if ($maxentries < $nbchoices || $glossarynumentries < $nbchoices) {
                    if ($maxentries < $nbchoices ) {
                        $errormsg = 'notenoughentriesselected';
                        $numentries = $maxentries;
                    }
                    if ($glossarynumentries < $nbchoices) {
                        $errormsg = 'notenoughentriesavailable';
                        $numentries = $glossarynumentries;
                    }
                    $errors['config_limitnum'] = get_string($errormsg, 'block_glossary_export_to_quiz',
                        ['numentries' => $numentries, 'nbchoices' => $nbchoices]);
                }
            }
        }
        if ($maxentries > $glossarynumentries) {
            $errors['config_limitnum'] = '<b>'.get_string('warning', 'moodle').'</b>: '
                .get_string('limitnum', 'block_glossary_export_to_quiz').' ('.$maxentries.') > '
                .get_string('numberofentries', 'glossary').' ('.$glossarynumentries.') !';
        }

        return $errors;
    }
}
