<?php
/// original file: mod/glossary/export.php
/// modified by JR 17 JAN 2011

require_once("../../config.php");
//require_once("lib.php");

$id = required_param('id', PARAM_INT);      // Course Module ID

$cat = optional_param('cat',0, PARAM_ALPHANUM);
$questiontype = optional_param('questiontype',0, PARAM_ALPHANUM);   
$limitnum = optional_param('limitnum',0, PARAM_ALPHANUM);
$sortorder = optional_param('sortorder',0, PARAM_ALPHANUM);
$entriescount = optional_param('entriescount',0, PARAM_ALPHANUM);
$questiontype =  optional_param('questiontype',0, PARAM_ALPHANUMEXT);  
$url = new moodle_url('/mod/glossary/export.php', array('id'=>$id));
if ($cat !== 0) {
    $url->param('cat', $cat);
}

$PAGE->set_url($url);

if (! $cm = get_coursemodule_from_id('glossary', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

if (! $glossary = $DB->get_record("glossary", array("id"=>$cm->instance))) {
    print_error('invalidid', 'glossary');
}

require_login($course->id, false, $cm);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/glossary:export', $context);

$strglossary = get_string("modulename", "glossary");
$strexportfile = get_string("exportfile", "glossary");
$strexportentries = get_string('exportentriestoxml', 'block_glossary_export_to_quiz');

// not needed here
/*
$PAGE->set_url('/blocks/block_glossary_export_to_quiz/export_to_quiz.php', array('id'=>$cm->id));
$PAGE->navbar->add($strexportentries);
$PAGE->set_title(format_string($glossary->name));
$PAGE->set_heading($course->fullname);
*/

echo $OUTPUT->header();
echo $OUTPUT->heading($strexportentries);
echo $OUTPUT->box_start('glossarydisplay generalbox');

?>
    <form action="exportfile_to_quiz.php" method="post">
    <table border="0" cellpadding="6" cellspacing="6" width="100%">
    <tr><td align="center">
        <input type="submit" value="<?php p($strexportfile)?>" />
    </td></tr></table>
    <div>
    </div>
        <div>
    <input type="hidden" name="id" value="<?php p($id)?>" />
    <input type="hidden" name="cat" value="<?php p($cat)?>" />
    <input type="hidden" name="limitnum" value="<?php p($limitnum)?>" />
    <input type="hidden" name="questiontype" value="<?php p($questiontype)?>" />
    <input type="hidden" name="sortorder" value="<?php p($sortorder)?>" />
    <input type="hidden" name="entriescount" value="<?php p($entriescount)?>" />
    </div>
    
    </form>
<?php
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
?>
