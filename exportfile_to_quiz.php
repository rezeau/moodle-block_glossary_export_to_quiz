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

// File mod/glossary/exportfile.php modified by Joseph Rézeau NOVEMBER 2010
// for exporting glossaries to XML for importing to question bank.

define('BGETQ_CONCEPT',      '0');
define('BGETQ_FIRSTMODIFIED', '2');
define('BGETQ_LASTMODIFIED', '1');
define('BGETQ_RANDOMLY',     '3');

global $SESSION, $DB;

require_once("../../config.php");
require_once("../../lib/filelib.php");

$id = required_param('id', PARAM_INT);      // Course Module ID.
$cat = optional_param('cat', 0, PARAM_ALPHANUM);
$limitnum = optional_param('limitnum', 0, PARAM_ALPHANUM);
$sortorder = optional_param('sortorder', 0, PARAM_ALPHANUM);
$entriescount = optional_param('entriescount', 0, PARAM_ALPHANUM);
$questiontype =  optional_param('questiontype', 0, PARAM_ALPHANUMEXT);
if (! $cm = get_coursemodule_from_id('glossary', $id)) {
    error("Course Module ID was incorrect");
}

if (! $course = $DB->get_record("course", array('id' => $cm->course))) {
    error("Course is misconfigured");
}

if (! $glossary = $DB->get_record("glossary", array('id'=>$cm->instance))) {
    error("Course module is incorrect");
}

require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/glossary:export', $context);

$filename = clean_filename(strip_tags(format_string($glossary->name, true)).'.xml');
$giftcategoryname = $glossary->name;

$limitfrom = 0;

switch ($sortorder) {
    case BGETQ_RANDOMLY:
        if (!isset($limitnum)) {
            $limitnum = 0;
        }
        $i = rand(1, $entriescount - $limitnum);
        $limitfrom = $i-1;
        $sortorder = '';
        break;
    case BGETQ_CONCEPT:
            $sortorder = 'ORDER BY concept ASC';
            break;
    case BGETQ_FIRSTMODIFIED:
        $sortorder = 'ORDER BY timemodified ASC';
        break;
    case BGETQ_LASTMODIFIED:
        $sortorder = 'ORDER BY timemodified DESC';
        break;
}

if ($limitnum) {
    $limit = "LIMIT  $limitnum OFFSET $limitfrom ";
} else {
    $limit = '';
}

$catfrom = "";
$catwhere = "";

if ($cat) {
    $category = $DB->get_record('glossary_categories', array('id'=>$cat));
    $categoryname = $category->name;
    $giftcategoryname .= '_'.$categoryname;
    $catfrom = ", mdl_glossary_entries_categories c ";
    $catwhere = "and ge.id = c.entryid and c.categoryid = $cat";
}
$sql = "SELECT * FROM ".$CFG->prefix."glossary_entries ge $catfrom "
. "WHERE ge.glossaryid = $glossary->id "
. "AND ge.approved = 1 "
. "$catwhere "
. "$sortorder "
. "$limit";

// Build XML file - based on moodle/question/xml/format.php.
// Add opening tag.
$expout = "";
$counter=0;

$expout .= "\n\n<!-- question: $counter  -->\n";

$categorypath = writetext( $giftcategoryname );
$expout .= "  <question type=\"category\">\n";
$expout .= "    <category>\n";
$expout .= "        $categorypath\n";
$expout .= "    </category>\n";
$expout .= "  </question>\n";
$context = context_module::instance($cm->id);

if ( $entries = $DB->get_records_sql($sql) ) {
    $questiontypeparams = explode("_", $questiontype);
    $questiontype = $questiontypeparams[0];
    if ($questiontype == 'multichoice') {
        $answernumbering = $questiontypeparams[1];
        $concepts = array();
        foreach ($entries as $entry) {
            $concepts[] = $entry->concept;
        }
    } else {
        $usecase = $questiontypeparams[1];
    }
    foreach ($entries as $entry) {
        $counter++;
        $definition = trusttext_strip($entry->definition);
        $fs = get_file_storage();
        $entryfiles = $fs->get_area_files($context->id, 'mod_glossary', 'entry', $entry->id);
        $concept = trusttext_strip($entry->concept);
        $expout .= "\n\n<!-- question: $counter  -->\n";
        $nametext = writetext( $concept );
        $qtformat = "html";
        $expout .= "  <question type=\"$questiontype\">\n";
        $expout .= "    <name>$nametext</name>\n";
        $expout .= "    <questiontext format=\"$qtformat\">\n";
        $expout .= writetext( $definition );;
        $expout .= writefiles($entryfiles);
        $expout .= "    </questiontext>\n";

        if ( $questiontype == 'multichoice') {
            $expout .= "    <shuffleanswers>true</shuffleanswers>\n";
            $expout .= "    <answernumbering>".$answernumbering."</answernumbering>\n";
            $concepts2 = $concepts;
            foreach ($concepts2 as $key => $value) {
                if ($value == $concept) {
                    unset($concepts2[$key]);
                }
            }
            $randkeys = array_rand($concepts2, 3);
            for ($i=0; $i<4; $i++) {
                if ($i === 0) {
                    $percent = 100;
                    $expout .= "      <answer fraction=\"$percent\">\n";
                    $expout .= writetext( $concept, 3, false )."\n";
                    $expout .= "      <feedback>\n";
                    $expout .= "      <text>\n";
                    $expout .= "      </text>\n";
                    $expout .= "      </feedback>\n";
                    $expout .= "    </answer>\n";
                } else {
                    $percent = 0;
                    $distracter = $concepts2[$randkeys[$i-1]];
                    $expout .= "      <answer fraction=\"$percent\">\n";
                    $expout .= writetext( $distracter, 3, false )."\n";
                    $expout .= "      <feedback>\n";
                    $expout .= "      <text>\n";
                    $expout .= "      </text>\n";
                    $expout .= "      </feedback>\n";
                    $expout .= "    </answer>\n";
                }
            }
        } else { // Shortanswer.
            $expout .= "    <usecase>$usecase</usecase>\n ";
            $percent = 100;
            $expout .= "    <answer fraction=\"$percent\">\n";
            $expout .= writetext( $concept, 3, false );
            $expout .= "    </answer>\n";
        }
        // Close the question tag.
        $expout .= "</question>\n";
    }
}
    // Initial string.
    // Add the xml headers and footers.
    $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                   "<quiz>\n" .
                   $expout . "\n" .
                   "</quiz>";

// Make the xml look nice.
$content = xmltidy( $content );
// Reset glossary export.
$SESSION->block_glossary_export_to_quiz->status = '';

send_file($content, $filename, 0, 0, true, true);

/**
 * generates <text></text> tags, processing raw text therein
 * @param int ilev the current indent level
 * @param boolean short stick it on one line
 * @return string formatted text
 */
function writetext($raw, $ilev = 0, $short = true) {
    $indent = str_repeat('  ', $ilev);

    // If required add CDATA tags.
    if (!empty($raw) and (htmlspecialchars($raw) != $raw)) {
        $raw = "<![CDATA[$raw]]>";
    }

    if ($short) {
        $xml = "$indent<text>$raw</text>";
    } else {
        $xml = "$indent<text>\n$raw\n$indent</text>\n";
    }

    return $xml;
}

function writefiles($files, $encoding='base64') {
    if (empty($files)) {
        return '';
    }
    $string = '';
    foreach ($files as $file) {
        if ($file->is_directory()) {
            continue;
        }
        $string .= '<file name="' . $file->get_filename() . '" encoding="' . $encoding . '">';
        $string .= base64_encode($file->get_content());
        $string .= '</file>';
    }
    return $string;
}

function xmltidy( $content ) {
    // Can only do this if tidy is installed.
    if (extension_loaded('tidy')) {
        $config = array( 'input-xml'=>true, 'output-xml'=>true, 'indent'=>true, 'wrap'=>0 );
        $tidy = new tidy;
        $tidy->parseString($content, $config, 'utf8');
        $tidy->cleanRepair();
        return $tidy->value;
    } else {
        return $content;
    }
}
