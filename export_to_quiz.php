<?php   // $Id: export.php,v 1.32.2.2 2008/11/09 22:55:39 stronk7 Exp $
// modified by Joseph Rézeau NOVEMBER 2010 for exporting glossaries to XML for importing to question bank
	require_once("../../config.php");
    $id = required_param('id', PARAM_INT);      // Course Module ID
    $cat = optional_param('cat',0, PARAM_ALPHANUM);
    $questiontype = optional_param('questiontype',0, PARAM_TEXT);   
    $limitnum = optional_param('limitnum',0, PARAM_ALPHANUM);
    $sortorder = optional_param('sortorder',0, PARAM_ALPHANUM);
    $entriescount = optional_param('entriescount',0, PARAM_ALPHANUM);

	if (! $cm = get_coursemodule_from_id('glossary', $id)) {
        error("Course Module ID was incorrect");
    }
    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }
    if (! $glossary = get_record("glossary", "id", $cm->instance)) {
        error("Course module is incorrect");
    }
    require_login($course->id, false, $cm);  
    
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/glossary:export', $context);

    $strglossary = get_string("modulename", "glossary");
    $strexportfile = get_string("exportfile", "glossary");
    $strexportentries = get_string('exportentriestoxml', 'block_glossary_export_to_quiz');

    $navigation = build_navigation($strexportentries, $cm);
    print_header_simple(format_string($glossary->name), "",$navigation,
        "", "", true, update_module_button($cm->id, $course->id, $strglossary),
        navmenu($course, $cm));

    print_heading($strexportentries);

    print_box_start('glossarydisplay generalbox');
    ?>
    <form action="exportfile_to_quiz.php" method="post">
    <table border="0" cellpadding="6" cellspacing="6" width="100%">
    <tr><td align="center">
        <input type="submit" value="<?php p($strexportfile)?>" />
    </td></tr></table>
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
    print_box_end();
    print_footer($course);
?>
