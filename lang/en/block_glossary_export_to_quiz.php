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
 * Strings for component 'glossary_export_to_quiz', language 'en', branch 'MOODLE_35_STABLE'
 *
 * @package    block_glossary_export_to_quiz
 * @copyright  Joseph Rézeau - moodle@rezeau.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addidnumber'] = 'Add IDNUMBER';
$string['addidnumber_help'] = 'Add an IDNUMBER based on the Glossary name to the exported category and use the questions number to add their IDNUMBER.';
$string['allentries'] = 'All entries';
$string['clicktoexport'] = 'Click to export this glossary\'s entries to quiz (XML)';
$string['concept'] = 'Alphabetical order';
$string['ddwtosinstructions'] = 'Drag each concept label to match its definition';
$string['emptyglossaries'] = 'This course glossaries are empty (no entries)';
$string['emptyglossary'] = 'This course glossary is empty (no entries)';
$string['exportentriestoxml'] = 'Export entries to Quiz (XML)';
$string['exportmediafiles'] = 'Export images and audio/video files?';
$string['exportmediafiles_help'] = 'Do you want to export the images/audio/video which may have been inserted into this glossary\'s definitions?
Some question types are not really compatible with some media elements.';
$string['exporttoquessit'] = 'Export entries to Guess It question(s)';
$string['extrawronganswer'] = 'Add one extra wrong answer?';
$string['extrawronganswer_help'] = 'Do you want to add an extra wrong answer/distracter to each of your questions?';
$string['firstmodified'] = 'Oldest entries first';
$string['gapfillddinstructions'] = 'Select from each dropdown list the concept which matches its definition';
$string['generalhelp'] = 'Block Help';
$string['glossary_export_to_quiz:addinstance'] = 'Add a new glossary_export_to_quiz block';
$string['glossary_export_to_quiz:myaddinstance'] = 'Add a new glossary_export_to_quiz block to the My Moodle page';
$string['lastmodified'] = 'Most recent entries first';
$string['limitnum'] = 'Maximum number of entries to export';
$string['limitnum_help'] = 'Leave this field at its default "0" value to export ALL entries from selected Glossary or Category.
This option can be useful for exporting a limited number of entries from very large glossaries.';
$string['maskconceptindefinitions'] = 'Mask concept words in definition texts?';
$string['maskconceptindefinitions_help'] = 'If concept words appear in the text of their definitions, do you want to mask them (with 3 asterisks)?';
$string['matchinstructions'] = 'Match the definitions and the concepts';
$string['nbchoices'] = 'Number of choices';
$string['nbchoices_help'] = 'Select how many choices/answers you want to make available.';
$string['nbmaxletterswordle'] = 'Number of letters in wordle';
$string['nbmaxletterswordle_help'] = 'Select the maximum length of concepts to select for wordle export. The minimum is 4';
$string['noglossaries'] = 'No glossaries in this course';
$string['notenoughentriesavailable'] = 'Not enough entries available ({$a->numentries}) for this question type (minimum {$a->nbchoices} entries needed).';
$string['notenoughentriesselected'] = 'Not enough entries selected ({$a->numentries}) for this question type (minimum {$a->nbchoices} entries needed).';
$string['notyetconfigured'] = 'Please <b>Turn editing on</b> to configure this block.';
$string['notyetconfiguredediting'] = 'Please click the Actions icon to configure this block.';
$string['numentries'] = 'Export {$a} entries';
$string['numquestions'] = ' and create {$a} questions';
$string['pluginname'] = 'Export Glossary to Quiz';
$string['pluginname_help'] = 'Right-click the <b>More Help</b> link to view the Moodle Documentation Wiki.';
$string['pluginname_link'] = 'block/glossary_export_to_quiz/edit';
$string['privacy:metadata'] = 'The Export Glossary to Quiz block does not store any personal data.';
$string['questiontype_help'] = 'Select which question type you want to export the glossary\'s entries to.';
$string['random'] = 'Random';
$string['selectglossary'] = 'Select glossary to export from';
$string['selectglossary_help'] = 'Use the dropdown list to select the glossary that you want to use to export its entries to the quiz questions bank.
If that glossary contains categories, you can select only one category to export its entries.
To cancel your choice or to reset the block, simply leave the dropdown list on the Choose... position.';
$string['selectquestiontype'] = 'Select question type';
$string['selectquestiontype_help'] = 'You can use the Moodle default question types: Short answer, Multiplechoice, Matching and Drag and Drop into text. If they are installed on your site you can also use Gapfill and Guess It (Wordle Guess a word).';
$string['shuffleanswers'] = 'Shuffle answers';
$string['shuffleanswers_help'] = 'If enabled, the order of the choices/answers is randomly shuffled for each attempt.';
$string['sortingorder'] = 'Sorting Order';
$string['sortingorder_help'] = 'Use this setting to determine how the exported glossary entries will be ordered when you import them to your questions data bank.
This can be used, in combination with the Maximum number of entries, for creating a quiz to test the latest entries to your glossary (especially a fairly large one). ';
$string['validnumber'] = 'Enter a valid number';