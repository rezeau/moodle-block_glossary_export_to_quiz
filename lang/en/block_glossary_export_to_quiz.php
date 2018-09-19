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
 * Strings for component 'glossary_export_to_quiz', language 'en', branch 'MOODLE_25_STABLE'
 *
 * @package    block_glossary_export_to_quiz
 * @copyright  Joseph Rézeau - moodle@rezeau.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allentries'] = 'All entries';
$string['glossary_export_to_quiz:addinstance'] = 'Add a new glossary_export_to_quiz block';
$string['glossary_export_to_quiz:myaddinstance'] = 'Add a new glossary_export_to_quiz block to the My Moodle page';
$string['clicktoexport'] = 'Click to export this glossary\'s entries to quiz (XML)';
$string['concept'] = 'Alphabetical order';
$string['emptyglossaries'] = 'This course glossaries are empty (no entries)';
$string['emptyglossary'] = 'This course glossary is empty (no entries)';
$string['exportentriestoxml'] = 'Export entries to Quiz (XML)';
$string['firstmodified'] = 'Oldest entries first';
$string['generalhelp'] = 'Block Help';
$string['lastmodified'] = 'Most recent entries first';
$string['limitnum'] = 'Maximum number of entries to export';
$string['limitnum_help'] = 'Leave this field at its default "0" value to export ALL entries from selected Glossary or Category.
This option can be useful for exporting a limited number of entries from very large glossaries.';
$string['multichoice'] = 'Multiple Choice';
$string['notenoughentriesavailable'] = 'Not enough entries available ({$a->numentries}) for Multichoice questions (minimum {$a->nbchoices} entries needed).';
$string['notenoughentriesselected'] = 'Not enough entries selected ({$a->numentries}) for Multichoice questions (minimum {$a->nbchoices} entries needed).';
$string['numentries'] = 'Export {$a} entries';
$string['numquestions'] = ' and create {$a} questions';
$string['noglossaries'] = 'No glossaries in this course';
$string['notyetconfigured'] = 'Please <b>Turn editing on</b> to configure this block.';
$string['notyetconfiguredediting'] = 'Please click the Actions icon to configure this block.';
$string['pluginname'] = 'Export Glossary to Quiz';
$string['pluginname_help'] = 'Right-click the <b>More Help</b> link to view the Moodle Documentation Wiki.';
$string['pluginname_link'] = 'block/glossary_export_to_quiz/edit';
$string['questiontype'] = 'Question type:';
$string['questiontype_help'] = 'Select which question type you want to export the glossary\'s entries to.';
$string['random'] = 'Randomly';
$string['selectglossary'] = 'Select glossary to export from';
$string['selectglossary_help'] = 'Use the dropdown list to select the glossary that you want to use to export its entries to the quiz questions bank.
If that glossary contains categories, you can select only one category to export its entries.
To cancel your choice or to reset the block, simply leave the dropdown list on the Choose... position.';
$string['sortingorder'] = 'Sorting Order';
$string['sortingorder_help'] = 'Use this setting to determine how the exported glossary entries will be ordered when you import them to your questions data bank.
This can be used, in combination with the Maximum number of entries, for creating a quiz to test the latest entries to your glossary (especially a fairly large one). ';
$string['matchinstructions'] = 'Match the definitions and the concepts';
$string['ddwtos'] = 'Drag and drop into text';
$string['ddwtosinstructions'] = 'Drag each concept label to match its definition';
$string['nbchoices'] = 'Number of choices';
$string['nbchoices_help'] = 'Select how many choices/answers you want to make available.

Does not apply to the shortanswer question type.';
$string['shuffleanswers'] = 'Shuffle answers';
$string['shuffleanswers_help'] = 'If enabled, the order of the choices/answers is randomly shuffled for each attempt.

Does not apply to the shortanswer question type.';
$string['usecase'] = 'Case sensitivity';
$string['usecase_help'] = 'Applies to the shortanswer question type only.';
$string['answernumbering'] = 'Number the choices?';
$string['answernumbering_help'] = 'Applies to the multichoice question type only.';
$string['privacy:metadata'] = 'The Export Glossary to Quiz block does not store any personal data.';