<?php 
$string['allentries'] = 'All entries';
$string['clicktoexport'] = 'Click to export this glossary\'s entries to quiz (XML)';
$string['concept'] = 'Alphabetical order';
$string['emptyglossaries'] = 'This course glossaries are empty (no entries)';
$string['emptyglossary'] = 'This course glossary is empty (no entries)';
$string['exportentriestoxml'] = 'Export entries to Quiz (XML)';
$string['firstmodified'] = 'Oldest entries first';
$string['lastmodified'] = 'Most recent entries first';
$string['limitnum'] = 'Maximum number of entries to export';
$string['limitnum_help'] = 'Leave empty to export all entries from selected Glossary or Category. 
This option can be useful for exporting a limited number of entries from very large glossaries.';
$string['multichoice'] = 'Multiple Choice';
$string['notenoughentries'] = 'Not enough entries (<b>{$a->numentries}</b>) in <b>Glossary</b> <em>{$a->varnotenough}</em> for Multichoice questions.'; 
$string['numentries'] = 'Export {$a} entries';
$string['noglossaries'] = 'No glossaries in this course';
$string['nolink'] = 'Remove glossary autolinks';
$string['notyetconfigured'] = 'Please <b>Turn editing on</b> to configure this block.';
$string['notyetconfiguredediting'] = 'Please configure this block using the edit icon.';
$string['pluginname'] = 'Export Glossary to Quiz';
$string['questiontype'] = 'Question type:';
$string['questiontype_help'] = 'Glossary entries can be exported to the Quiz Questions bank either as multiple choice or short answer questions.
Multiple choice questions will consist of the following elements:

* question text = glossary entry definition
* correct answer = glossary entry concept
* distracters = 3 glossary entry concepts randomly selected from the glossary (or glossary category) that you have selected.

Short answer questions

* Case insensitive. Student responses will be accepted as correct regardless of the original glossary entry concept case (uppercase or lowercase).
** Example: original entry "Moodle". Accepted correct responses: "Moodle", "moodle".
* Case sensitive. Student responses will be only be accepted as correct it the case of the original glossary entry concept is used..
** Example: original entry "Moodle". Accepted correct response: "Moodle".';
$string['random'] = 'Randomly';
$string['selectglossary'] = 'Select glossary to export from';
$string['selectglossary_help'] = 'Use the dropdown list to select the glossary that you want to use to export its entries to the quiz questions bank. 
If that glossary contains categories, you can select only one category to export its entries.
To cancel your choice or to reset the block, simply leave the dropdown list on the Choose... position.';
$string['shortanswer'] = 'Short answer';
$string['shortanswer_0'] = 'Short answer (Case insensitive)';
$string['shortanswer_1'] = 'Short answer (Case sensitive)';
$string['sortingorder'] = 'Sorting Order';
$string['sortingorder_help'] = 'Use this setting to determine how the exported glossary entries will be ordered when you import them to your questions data bank.
This can be used, in combination with the Maximum number of entries, for creating a quiz to test the latest entries to your glossary (especially a fairly large one). ';
?>
