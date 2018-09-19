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

// File mod/glossary/exportfile.php modified by Joseph Rézeau NOVEMBER 2010
// for exporting glossaries to XML for importing to question bank.

define('BGETQ_CONCEPT',      '0');
define('BGETQ_FIRSTMODIFIED', '2');
define('BGETQ_LASTMODIFIED', '1');
define('BGETQ_RANDOMLY',     '3');

global $SESSION, $DB;

require_once("../../config.php");
require_once("../../lib/filelib.php");
        /*$usecase = $this->config->usecase;
        $answernumbering = $this->config->answernumbering;
        $shuffleanswers = $this->config->shuffleanswers;*/

$id = required_param('id', PARAM_INT);      // Course Module ID.
$cat = optional_param('cat', 0, PARAM_ALPHANUM);
$limitnum = optional_param('limitnum', '', PARAM_ALPHANUM);
$nbchoices = optional_param('nbchoices', '', PARAM_ALPHANUM);
$usecase = optional_param('usecase', '', PARAM_ALPHANUM);
$answernumbering = optional_param('answernumbering', '', PARAM_ALPHANUM);
$shuffleanswers = optional_param('shuffleanswers', '', PARAM_ALPHANUM);
$numquestions = optional_param('numquestions', '', PARAM_ALPHANUM);
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

switch ($sortorder) {
    case BGETQ_RANDOMLY:
        $sortorder = 'ORDER BY RAND()';
        // May be slow on a very large glossary, see http://www.titov.net/2005/09/21/do-not-use-order-by-rand-or-how-to-get-random-rows-from-table/
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
    $limit = "LIMIT  $limitnum ";
} else {
    $limit = '';
    $limitnum = $entriescount;
}

$catfrom = "";
$catwhere = "";
$giftcategoryname = $glossary->name;
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
$questionscounter=0;
$questiontypeparams = explode("_", $questiontype);
$questiontype = $questiontypeparams[0];

switch ($questiontype) {
    case 'multichoice':
        $questiontype_abbr = ' MCQ';
        break;
    case 'shortanswer':
        $questiontype_abbr = ' SA';
        break;
    case 'matching':
        $questiontype_abbr = ' MATCHING';
        break;
    case 'ddwtos':
        $questiontype_abbr = ' DRAGDROPTEXT';
        break;
}

$giftcategoryname .= ' '.$numquestions.$questiontype_abbr;
$filename = clean_filename(strip_tags(format_string($giftcategoryname, true)).' questions.xml');
$expout .= "\n\n<!-- question: $questionscounter  -->\n";

$categorypath = writetext( $giftcategoryname );
$expout .= "  <question type=\"category\">\n";
$expout .= "    <category>\n";
$expout .= "        $categorypath\n";
$expout .= "    </category>\n";
$expout .= "  </question>\n";
$context = context_module::instance($cm->id);

if ( $entries = $DB->get_records_sql($sql) ) {
    switch ($questiontype) {
        case 'multichoice':
            $concepts = array();
            foreach ($entries as $entry) {
                $concepts[] = $entry->concept;
            }
            break;
        case 'matching':
            $questiontext = get_string('matchinstructions', 'block_glossary_export_to_quiz');
            break;
        case 'ddwtos':
            $instructions = get_string('ddwtosinstructions', 'block_glossary_export_to_quiz');
            break;
    }

    if ($questiontype == 'matching') {        
        $subquestionscounter = 0;
        $questionscounter++;
        foreach ($entries as $entry) {            
            if ($subquestionscounter == $nbchoices) {
                $subquestionscounter = 0;
                // Close the question tag.
                $expout .= "</question>\n";
            }             
            if ($subquestionscounter === 0) { // Start new matching question
                $concept = trusttext_strip($entry->concept);
                $nametext = writetext( $concept.' etc.' );
                $expout .= "\n\n<!-- question: $questionscounter  -->\n";                
                $qtformat = "html";
                $expout .= "  <question type=\"$questiontype\">\n";
                $expout .= "    <name>$nametext</name>\n";
                $expout .= "    <questiontext format=\"$qtformat\">\n";
                $expout .= writetext( $questiontext );
                $expout .= "    </questiontext>\n";
                $expout .= "    <shuffleanswers>" .$shuffleanswers . "</shuffleanswers>\n";
                $questionscounter++;                
            }                      
            $concept = trusttext_strip($entry->concept);
            $definition = trusttext_strip($entry->definition);
            $fs = get_file_storage();
            $entryfiles = $fs->get_area_files($context->id, 'mod_glossary', 'entry', $entry->id);
            $expout .= "    <subquestion format=\"$qtformat\">\n";            
            $expout .= writetext($definition, 3);
            $expout .= writefiles($entryfiles);
            $expout .= "      <answer>\n";
            $expout .= writetext($concept, 4);
            $expout .= "      </answer>\n";
            $expout .= "    </subquestion>\n";            
            $subquestionscounter++;          
        }
        // Close the last question tag.
        $expout .= "</question>\n";   
    } else if ($questiontype == 'ddwtos') {
        $choicescounter = 0;
        $questionscounter++;
        $questiontext = '';        
        $dragboxconcept = array();     
        
        foreach ($entries as $entry) {                           
            
            if ($choicescounter == $nbchoices) {
                $choicescounter = 0;
                // Write question text and dragboxes.
                $expout .= writetext($questiontext, 3);
                $expout .= "    </questiontext>\n"; 
                $expout .= "    <shuffleanswers>" .$shuffleanswers . "</shuffleanswers>\n";
                for ($j = 0; $j < $nbchoices; $j++) {
                    $expout .= "      <dragbox>\n";
                    $expout .= writetext($dragboxconcept[$j], $nbchoices);
                    $expout .= "      </dragbox>\n";
                }
                $expout .= "</question>\n";                
            }             
            if ($choicescounter == 0) { // Start new matching question            
                $questiontext = '';
                $concept = trusttext_strip($entry->concept);
                $nametext = writetext( $concept.' etc.' );
                $expout .= "\n\n<!-- question: $questionscounter  -->\n";                
                $qtformat = "html";
                $expout .= "  <question type=\"$questiontype\">\n";
                $expout .= "    <name>$nametext</name>\n";
                $expout .= "    <questiontext format=\"$qtformat\">\n";                
                $questiontext .= '<p>'.$instructions.'</p>';
                $questionscounter++;                
            }            
            $dragboxconcept[$choicescounter] = trusttext_strip($entry->concept);
            $definition = trusttext_strip($entry->definition);
            $questiontext .= '<p>[['. ($choicescounter + 1). ']]'. $definition.'</p>';
            $fs = get_file_storage();
            $entryfiles = $fs->get_area_files($context->id, 'mod_glossary', 'entry', $entry->id);
            $expout .= writefiles($entryfiles);
            $choicescounter++;                                                                                       
        }
        // Write the final question text and dragboxes!
        $expout .= writetext($questiontext, 3);
        $expout .= "    </questiontext>\n";   
        for ($j = 0; $j < $nbchoices; $j++) {
            $expout .= "      <dragbox>\n";
            $expout .= writetext($dragboxconcept[$j], $nbchoices);
            $expout .= "      </dragbox>\n";
        }
        $expout .= "    <shuffleanswers>" .$shuffleanswers . "</shuffleanswers>\n";
        $expout .= "</question>\n";                   
        
    } else {
        foreach ($entries as $entry) {
            $questionscounter++;
            $definition = trusttext_strip($entry->definition);
            $fs = get_file_storage();
            $entryfiles = $fs->get_area_files($context->id, 'mod_glossary', 'entry', $entry->id);
            $concept = trusttext_strip($entry->concept);
            $expout .= "\n\n<!-- question: $questionscounter  -->\n";
            $nametext = writetext( $concept );
            $qtformat = "html";
            $expout .= "  <question type=\"$questiontype\">\n";
            $expout .= "    <name>$nametext</name>\n";
            $expout .= "    <questiontext format=\"$qtformat\">\n";
            $expout .= writetext( $definition );
            $expout .= writefiles($entryfiles);
            $expout .= "    </questiontext>\n";
    
            switch ($questiontype) {               
                case 'multichoice':
                    $expout .= "    <shuffleanswers>true</shuffleanswers>\n";
                    $expout .= "    <answernumbering>".$answernumbering."</answernumbering>\n";
                    $concepts2 = $concepts;
                    foreach ($concepts2 as $key => $value) {
                        if ($value == $concept) {
                            unset($concepts2[$key]);
                        }
                    }
                    $randkeys = array_rand($concepts2, 3);
                    for ($i = 0; $i < 4; $i++) {
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
                    $expout .= "</question>\n";
                    break;   
                case 'shortanswer':
                    $expout .= "    <usecase>$usecase</usecase>\n ";
                    $percent = 100;
                    $expout .= "    <answer fraction=\"$percent\">\n";
                    $expout .= writetext( $concept, 3, false );
                    $expout .= "    </answer>\n";
                    $expout .= "</question>\n";
                    break;
            }
        }
        // Close the question tag.
        //$expout .= "</question>\n";
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
