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

class block_glossary_export_to_quiz_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $SESSION;
        $SESSION->block_glossary_export_to_quiz = new stdClass();
        $SESSION->block_glossary_export_to_quiz->status = 'defined';
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

            // TODO check if no glossaries available.
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
                            $numentries = $DB->count_records('glossary_entries_categories', array('categoryid' => $category->id));
                            $categoriesarray[$glossarystring][$key.','.$cid] = $glossarystring.' :: '.
                                $category->name.' ('.$numentries.')';
                            $numentriesincategory[$key][$cid] = $numentries;
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
                $mform->addElement('text', 'config_limitnum',
                                get_string('limitnum', 'block_glossary_export_to_quiz'), array('size' => 5));
                $mform->addHelpButton('config_limitnum', 'limitnum', 'block_glossary_export_to_quiz');
                $mform->setDefault('config_limitnum', 0);
                $mform->setType('config_limitnum', PARAM_INTEGER);

                // And select question types to put in dropdown box.
                $types = array(
                    0 => get_string('multichoice', 'block_glossary_export_to_quiz').
                                ' ('.get_string('answernumberingabc', 'qtype_multichoice').')',
                    1 => get_string('multichoice', 'block_glossary_export_to_quiz').' ('.
                                get_string('answernumberingABCD', 'qtype_multichoice').')',
                    2 => get_string('multichoice', 'block_glossary_export_to_quiz').' ('.
                                get_string('answernumbering123', 'qtype_multichoice').')',
                    3 => get_string('multichoice', 'block_glossary_export_to_quiz').' ('.
                                get_string('answernumberingnone', 'qtype_multichoice').')',
                    4 => get_string('shortanswer_0', 'block_glossary_export_to_quiz'),
                    5 => get_string('shortanswer_1', 'block_glossary_export_to_quiz')
                );
                $mform->addElement('select', 'config_questiontype',
                                get_string('questiontype', 'block_glossary_export_to_quiz'), $types);
                $mform->addHelpButton('config_questiontype', 'questiontype', 'block_glossary_export_to_quiz');
            }
        }
    }

    public function validation($data, $files) {
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
        $maxentries = $data['config_limitnum'];

        if ($questiontype < 4 && ($maxentries < 4 || $glossarynumentries < 4)) {
            if ($maxentries < 4) {
                $errormsg = 'notenoughentriesselected';
                $numentries = $maxentries;
            }
            if ($glossarynumentries < 4) {
                $errormsg = 'notenoughentriesavailable';
                $numentries = $glossarynumentries;
            }
            $errors['config_limitnum'] = get_string($errormsg, 'block_glossary_export_to_quiz', $numentries);
        }

        return $errors;
    }
}
