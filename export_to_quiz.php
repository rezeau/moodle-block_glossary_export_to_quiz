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

// Original file: mod/glossary/export.php.
// Modified by JR 17 JAN 2011.

require_once("../../config.php");

$id = required_param('id', PARAM_INT);      // Course Module ID.
$cat = optional_param('cat', 0, PARAM_ALPHANUM);
$questiontype = optional_param('questiontype', 0, PARAM_ALPHANUM);
$limitnum = optional_param('limitnum', '', PARAM_ALPHANUM);
$addidnumber = optional_param('addidnumber', '', PARAM_ALPHANUM);
$sortorder = optional_param('sortorder', 0, PARAM_ALPHANUM);
$nbchoices = optional_param('nbchoices', '', PARAM_ALPHANUM);
$usecase = optional_param('usecase', '', PARAM_ALPHANUM);
$nbmaxtrieswordle = optional_param('nbmaxtrieswordle', '', PARAM_ALPHANUM);
$nbmaxletterswordle = optional_param('nbmaxletterswordle', '', PARAM_ALPHANUM);
$answernumbering = optional_param('answernumbering', '', PARAM_ALPHANUM);
$shuffleanswers = optional_param('shuffleanswers', '', PARAM_ALPHANUM);
$answerdisplay = optional_param('answerdisplay', '', PARAM_ALPHANUM);
$numquestions = optional_param('numquestions', '', PARAM_ALPHANUM);
$questiontype = optional_param('questiontype', 0, PARAM_ALPHANUMEXT);
$exportmediafiles = optional_param('exportmediafiles', '', PARAM_ALPHANUM);
$maskconceptindefinitions = optional_param('maskconceptindefinitions', '', PARAM_ALPHANUM);
$extrawronganswer = optional_param('extrawronganswer', '', PARAM_ALPHANUM);
$url = new moodle_url('/mod/glossary/export.php', ['id' => $id]);
if ($cat !== 0) {
    $url->param('cat', $cat);
}

$PAGE->set_url($url);

if (! $cm = get_coursemodule_from_id('glossary', $id)) {
    throw new moodle_exception('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", ["id" => $cm->course])) {
    throw new moodle_exception('coursemisconf');
}

if (! $glossary = $DB->get_record("glossary", ["id" => $cm->instance])) {
    throw new moodle_exception('invalidid', 'glossary');
}

require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/glossary:export', $context);


$strexportfile = get_string("exportfile", "glossary");
$strexportentries = get_string('exportentriestoxml', 'block_glossary_export_to_quiz');

echo $OUTPUT->header();
echo $OUTPUT->heading($strexportentries);
echo $OUTPUT->box_start('glossarydisplay generalbox');

echo ('
    <form action="exportfile_to_quiz.php" method="post">
    <table border="0" cellpadding="6" cellspacing="6" width="100%">
    <tr><td align="center">
        <input type="submit" value='.$strexportfile.' />
    </td></tr></table>
    <div>
    </div>
    <div>
    <input type="hidden" name="id" value='.$id.' />
    <input type="hidden" name="cat" value='.$cat.' />
    <input type="hidden" name="limitnum" value='.$limitnum.' />
    <input type="hidden" name="questiontype" value='.$questiontype.' />
    <input type="hidden" name="sortorder" value='.$sortorder.' />
    <input type="hidden" name="addidnumber" value='.$addidnumber.' />
    <input type="hidden" name="nbchoices" value='.$nbchoices.' />
    <input type="hidden" name="usecase" value='.$usecase.' />
    <input type="hidden" name="nbmaxtrieswordle" value='.$nbmaxtrieswordle.' />
    <input type="hidden" name="nbmaxletterswordle" value='.$nbmaxletterswordle.' />
    <input type="hidden" name="answernumbering" value='.$answernumbering.' />
    <input type="hidden" name="shuffleanswers" value='.$shuffleanswers.' />
    <input type="hidden" name="answerdisplay" value='.$answerdisplay.' />
    <input type="hidden" name="numquestions" value='.$numquestions.' />
    <input type="hidden" name="exportmediafiles" value='.$exportmediafiles.' />
    <input type="hidden" name="maskconceptindefinitions" value='.$maskconceptindefinitions.' />
    <input type="hidden" name="extrawronganswer" value='.$extrawronganswer.' />
    </div>
    </form>
');

    $courseurl = new moodle_url("/course/view.php", ['id' => $course->id]);
    echo html_writer::start_tag('div', ['class' => 'buttons']);
    echo $OUTPUT->single_button($courseurl, get_string('returntocourse', 'block_completionstatus'), 'get');
    echo html_writer::end_tag('div');
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
