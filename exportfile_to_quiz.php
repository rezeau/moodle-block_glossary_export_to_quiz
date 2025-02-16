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

// File mod/glossary/exportfile.php modified by Joseph Rézeau
// for exporting glossaries to XML for importing to question bank.

require_once("../../config.php");
require_once("../../lib/filelib.php");

global $SESSION, $DB, $mediafiles;
$SESSION->block_glossary_export_to_quiz->status = '';
$mediafiles = [];

$id = required_param('id', PARAM_INT);      // Course Module ID.
$cat = optional_param('cat', 0, PARAM_ALPHANUM);
$limitnum = optional_param('limitnum', '', PARAM_ALPHANUM);
$nbchoices = optional_param('nbchoices', '', PARAM_ALPHANUM);
$usecase = optional_param('usecase', '', PARAM_ALPHANUM);
$nbmaxtrieswordle = optional_param('nbmaxtrieswordle', '', PARAM_ALPHANUM);
$answernumbering = optional_param('answernumbering', '', PARAM_ALPHANUM);
$shuffleanswers = optional_param('shuffleanswers', '', PARAM_ALPHANUM);
$answerdisplay = optional_param('answerdisplay', '', PARAM_ALPHANUM);
$numquestions = optional_param('numquestions', '', PARAM_ALPHANUM);
$sortorder = optional_param('sortorder', 0, PARAM_ALPHANUM);
$entriescount = optional_param('entriescount', 0, PARAM_ALPHANUM);
$questiontype = optional_param('questiontype', 0, PARAM_ALPHANUMEXT);
$exportmediafiles = optional_param('exportmediafiles', '', PARAM_ALPHANUM);
$extrawronganswer = optional_param('extrawronganswer', '', PARAM_ALPHANUM);
$maskconceptindefinitions = optional_param('maskconceptindefinitions', '', PARAM_ALPHANUM);

if (! $cm = get_coursemodule_from_id('glossary', $id)) {
    error("Course Module ID was incorrect");
}

if (! $course = $DB->get_record("course", ['id' => $cm->course])) {
    error("Course is misconfigured");
}

if (! $glossary = $DB->get_record("glossary", ['id' => $cm->instance])) {
    error("Course module is incorrect");
}

require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/glossary:export', $context);
define('BGETQ_CONCEPT',      '0');
define('BGETQ_FIRSTMODIFIED', '2');
define('BGETQ_LASTMODIFIED', '1');
define('BGETQ_RANDOMLY',     '3');

switch ($sortorder) {
    case BGETQ_RANDOMLY:
        $sortorder = 'ORDER BY RAND()';
        // May be slow on a very large glossary, see
        // http://www.titov.net/2005/09/21/do-not-use-order-by-rand-or-how-to-get-random-rows-from-table/ please.
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
}

$catfrom = "";
$catwhere = "";
$giftcategoryname = $glossary->name;
if ($cat) {
    $category = $DB->get_record('glossary_categories', ['id' => $cat]);
    $categoryname = $category->name;
    $giftcategoryname .= '_'.$categoryname;
    $catfrom = ", mdl_glossary_entries_categories c ";
    $catwhere = "and ge.id = c.entryid and c.categoryid = $cat";
}

$sql = "SELECT ge.id, ge.concept, ge.definition "
." FROM ".$CFG->prefix."glossary_entries ge $catfrom "
. "WHERE ge.glossaryid = $glossary->id "
. "AND ge.approved = 1 "
. "$catwhere "
. "$sortorder "
. "$limit";

// Build XML file - based on moodle/question/xml/format.php.
// Add opening tag.
$expout = "";
$questionscounter = 0;
$questiontypeparams = explode("_", $questiontype);
$questiontype = $questiontypeparams[0];

switch ($questiontype) {
    case 'multichoice':
        $questiontypeabbr = ' MCQ';
        break;
    case 'shortanswer':
        $questiontypeabbr = ' SA';
        break;
    case 'matching':
        $questiontypeabbr = ' MATCHING';
        break;
    case 'ddwtos':
        $questiontypeabbr = ' DRAGDROPTEXT';
        break;
    case 'gapfill':
        $questiontypeabbr = ' GAPFILL';
        break;
    case 'guessit':
        $questiontypeabbr = ' GUESSIT_WORDLE';
        break;
}

$giftcategoryname .= ' '.$numquestions.$questiontypeabbr;
$filename = clean_filename(format_string($giftcategoryname, true).' questions.xml');
$expout .= "\n\n<!-- question: $questionscounter  -->\n";

$categorypath = writetext( $giftcategoryname );
$expout .= "  <question type=\"category\">\n";
$expout .= "    <category>\n";
$expout .= "        $categorypath\n";
$expout .= "    </category>\n";
$expout .= "  </question>\n";
$context = context_module::instance($cm->id);

if ( $entries = $DB->get_records_sql($sql) ) {
    $instructions = '';
    switch ($questiontype) {
        case 'multichoice':
            $concepts = [];
            foreach ($entries as $entry) {
                $concepts[] = $entry->concept;
            }
            break;
        case 'matching':
            $instructions = get_string('matchinstructions', 'block_glossary_export_to_quiz');
            break;
        case 'ddwtos': $instructions = get_string('ddwtosinstructions', 'block_glossary_export_to_quiz');
        case 'gapfill':
            switch ($answerdisplay) {
                case 'dragdrop':
                    $instructions = get_string('ddwtosinstructions', 'block_glossary_export_to_quiz');
                    break;
                case 'dropdown':
                    $instructions = get_string('gapfillddinstructions', 'block_glossary_export_to_quiz');
                    break;
            }
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
            if ($subquestionscounter === 0) { // Start new matching question.
                $concept = $entry->concept;
                $nametext = writetext( $concept.' etc.' );
                $expout .= "\n\n<!-- question: $questionscounter  -->\n";
                $qtformat = "html";
                $expout .= "  <question type=\"$questiontype\">\n";
                $expout .= "    <name>$nametext</name>\n";
                $expout .= "    <questiontext format=\"$qtformat\">\n";
                $expout .= writetext( '<p>'.$instructions.'</p><hr />' );
                $expout .= "    </questiontext>\n";
                $expout .= "    <shuffleanswers>" .$shuffleanswers . "</shuffleanswers>\n";
                $questionscounter++;
            }
            $concept = $entry->concept;
            $definition = strip_text($entry->definition, $concept, $maskconceptindefinitions, $exportmediafiles);
            $expout .= "    <subquestion format=\"$qtformat\">\n";
            if ($subquestionscounter < $nbchoices - $extrawronganswer) {
                $expout .= writetext($definition);
                if ($exportmediafiles) {
                    $fs = get_file_storage();
                    $entryfiles = $fs->get_area_files($context->id, 'mod_glossary', 'entry', $entry->id);
                    $expout .= write_files($entryfiles, false);
                }
            }
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
        $dragboxconcept = [];

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
            if ($choicescounter == 0) { // Start a new ddwtos question.
                $questiontext = '';
                // Write the question name constructed from the text of the first concept plus etc.
                $concept = $entry->concept;
                $nametext = writetext( $concept.' etc.' );
                $expout .= "\n\n<!-- question: $questionscounter  -->\n";
                $qtformat = "html";
                $expout .= "  <question type=\"$questiontype\">\n";
                $expout .= "    <name>$nametext</name>\n";
                $expout .= "    <questiontext format=\"$qtformat\">\n";
                $questiontext .= '<p>'.$instructions.'</p><hr />';
                $questionscounter++;
            }
            $dragboxconcept[$choicescounter] = $entry->concept;
            if ($choicescounter < $nbchoices - $extrawronganswer) {
                $concept = $entry->concept;
                $definition = strip_text($entry->definition,
                    $concept, $maskconceptindefinitions, $exportmediafiles);
                $questiontext .= '<p>[['. ($choicescounter + 1). ']]'. $definition.'</p>';
                if ($exportmediafiles) {
                    $fs = get_file_storage();
                    $entryfiles = $fs->get_area_files($context->id, 'mod_glossary', 'entry', $entry->id);
                    $expout .= write_files($entryfiles, $short = false);
                }
            }
            $choicescounter++;
        }
        // Write the final question text and dragboxes!
        $expout .= writetext($questiontext, 3);
        $expout .= "    </questiontext>\n";
        $expout .= "    <shuffleanswers>" .$shuffleanswers . "</shuffleanswers>\n";
        for ($j = 0; $j < $nbchoices; $j++) {
            $expout .= "      <dragbox>\n";
            $expout .= writetext($dragboxconcept[$j], $nbchoices);
            $expout .= "      </dragbox>\n";
        }
        $expout .= "</question>\n";

    } else if ($questiontype == 'gapfill') {
        $choicescounter = 0;
        $questionscounter++;
        $questiontext = '';
        $dragboxconcept = [];

        foreach ($entries as $entry) {
            if ($choicescounter == $nbchoices) {
                $choicescounter = 0;
                // Write question text and dragboxes.
                $expout .= writetext($questiontext, 3);
                $expout .= "    </questiontext>\n";
                $expout .= "    <answerdisplay>" .$answerdisplay . "</answerdisplay>\n";
                $expout .= "    <delimitchars>[]</delimitchars>\n";
                $expout .= "    <fixedgapsize>1</fixedgapsize>\n";
                $expout .= "    <optionsaftertext>1</optionsaftertext>\n";

                for ($j = 0; $j < $nbchoices; $j++) {
                    $score = '"100"';
                    if ($j == $nbchoices - $extrawronganswer) {
                        $score = '"0"';
                    }
                    $expout .= "      <answer fraction=".$score.">\n";
                    $expout .= writetext($dragboxconcept[$j], $nbchoices);
                    $expout .= "      </answer>\n";
                }

                $expout .= "</question>\n";
            }
            if ($choicescounter == 0) { // Start a new question.
                $questiontext = '';
                // Write the question name constructed from the text of the first concept plus etc.
                $concept = $entry->concept;
                $nametext = writetext( $concept.' etc.' );
                $expout .= "\n\n<!-- question: $questionscounter  -->\n";
                $qtformat = "html";
                $expout .= "  <question type=\"$questiontype\">\n";
                $expout .= "    <name>$nametext</name>\n";
                $expout .= "    <questiontext format=\"$qtformat\">\n";
                $questiontext .= '<p>'.$instructions.'</p><hr />';
                $questionscounter++;
            }
            $dragboxconcept[$choicescounter] = $entry->concept;
            if ($choicescounter < $nbchoices - $extrawronganswer) {
                $concept = $entry->concept;
                $definition = strip_text($entry->definition,
                    $concept, $maskconceptindefinitions, $exportmediafiles);
                $questiontext .= '<p>['.$dragboxconcept[$choicescounter]. ']&nbsp;'. $definition.'</p>';
                if ($exportmediafiles) {
                    $fs = get_file_storage();
                    $entryfiles = $fs->get_area_files($context->id, 'mod_glossary', 'entry', $entry->id);
                    $expout .= write_files($entryfiles, $short = false);
                }
            }
            $choicescounter++;
        }
        // Write the final question text and answers!
        $expout .= writetext($questiontext, 3);
        $expout .= "    </questiontext>\n";
        $expout .= "    <answerdisplay>" .$answerdisplay . "</answerdisplay>\n";
        $expout .= "    <delimitchars>[]</delimitchars>\n";
        $expout .= "    <fixedgapsize>1</fixedgapsize>\n";
        $expout .= "    <optionsaftertext>1</optionsaftertext>\n";
        for ($j = 0; $j < $nbchoices; $j++) {
            $score = '"100"';
            if ($j == $nbchoices - $extrawronganswer) {
                $score = '"0"';
            }
            $expout .= "      <answer fraction=".$score.">\n";
            $expout .= writetext($dragboxconcept[$j], $nbchoices);
            $expout .= "      </answer>\n";
        }
        $expout .= "</question>\n";

    } else {
        foreach ($entries as $entry) { // Question types multichoice or  shortanswer or guessitwordle.
            $questionscounter++;
            $concept = $entry->concept;
            $definition = strip_text($entry->definition, $concept, $maskconceptindefinitions, $exportmediafiles);
            $expout .= "\n\n<!-- question: $questionscounter  -->\n";
            $nametext = writetext( $concept );
            $qtformat = "html";
            $expout .= "  <question type=\"$questiontype\">\n";
            $expout .= "    <name>$nametext</name>\n";
            $expout .= "    <questiontext format=\"$qtformat\">\n";
            $expout .= writetext($definition, 0);
            if ($exportmediafiles) {
                $fs = get_file_storage();
                $entryfiles = $fs->get_area_files($context->id, 'mod_glossary', 'entry', $entry->id);
                $expout .= write_files($entryfiles);
            }
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
                    $randkeys = array_rand($concepts2, $nbchoices);
                    for ($i = 0; $i < $nbchoices + 1; $i++) {
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
                            $distracter = $concepts2[$randkeys[$i - 1]];
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
                case 'guessit':
                    $expout .= "    <nbmaxtrieswordle>$nbmaxtrieswordle</nbmaxtrieswordle>\n ";
                    $percent = 100;
                    $expout .= "    <answer fraction=\"$percent\">\n";
                    $expout .= writetext( $concept, 3, false );
                    $expout .= "    </answer>\n";
                    $expout .= "</question>\n";
                    break;
            }
        }
        // Close the question tag.
    }
}
    // Initial string.
    // Add the xml headers and footers.
    $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                   "<quiz>\n" .
                   $expout . "\n" .
                   "</quiz>";
?>
<?php
/**
 * Exports glossary entries to a quiz format.
 *
 * This file handles the functionality of exporting glossary entries into a quiz format
 * within the Glossary Export to Quiz block.
 *
 * @package   block_glossary_export_to_quiz
 * @copyright 2024 Joseph Rézeau moodle@rezeau.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Reset glossary export.
$SESSION->block_glossary_export_to_quiz->status = '';

send_file($content, $filename, 0, 0, true, true);

/**
 * Generates <text></text> tags, processing raw text therein
 * @param string $raw the content to output.
 * @param int $indent the current indent level.
 * @param bool $short stick it on one line.
 * @return string formatted text.
 * @package block_glossary_export_to_quiz
 */
function writetext($raw, $indent = 0, $short = true) {
    $indent = str_repeat('  ', $indent);
    // If required add CDATA tags.
    if (!empty($raw) & (htmlspecialchars($raw) != $raw)) {
        $raw = "<![CDATA[$raw]]>";
    }

    if ($short) {
        $xml = "$indent<text>$raw</text>";
    } else {
        $xml = "$indent<text>\n$raw\n$indent</text>\n";
    }

    return $xml;
}

/**
 * Generate the XML to represent some files.
 * @param array $files stored_file objects.
 * @return string $string the XML.
 * @package block_glossary_export_to_quiz
 */
function write_files($files) {
    global $mediafiles;
    if (empty($files)) {
        return '';
    }
    $string = '';
    foreach ($files as $file) {
        if ($file->is_directory()) {
            continue;
        }
        // Avoid potential duplicate files in XML.
        if (in_array($file->get_filename() . $file->get_filepath(), $mediafiles)) {
            return '';
        } else {
            $mediafiles[] = $file->get_filename() . $file->get_filepath();
        }
        $string .= '<file name="' . $file->get_filename() . '" path="' . $file->get_filepath() . '" encoding="base64">';
        $string .= base64_encode($file->get_content());
        $string .= '</file>';
    }
    return $string;
}

/**
 * Remove all links, paragraph, line break, horizontal line and div tags from string
 * except paragraphs containing an image 19 APRIL 2020.
 * @param string $text
 * @param string $concept
 * @param string $maskconceptindefinitions
 * @param string $exportmediafiles
 * @return string cleaned text
 * @package block_glossary_export_to_quiz
 */
function strip_text ($text, $concept, $maskconceptindefinitions, $exportmediafiles) {
    $pattern = '/(<p[^>]*>|<\/p>|<div[^>]*>|<\/div>|<[bh]r ?\/?>|<a.*<\/a>)/';
    $text = preg_replace ($pattern, ' ', $text);

    // Find images in text (if any).
    // Enclose images in paragraph tags.
    $pattern = '/(<img.*?>)/';
    $replacement = '<p>$1</p>';
    $text = preg_replace($pattern, $replacement, $text);

    $pattern = '/\n/';
    $text = preg_replace ($pattern, ' ', $text);

    if (!$exportmediafiles) {
        $pattern = '/<img.*?>/';
        $replacement = '';
        $text = preg_replace($pattern, $replacement, $text);
    }

    // If replace concept string in definition with asterisks ***.
    // Do NOT replace within image link !!!
    // Only replace if full word, not part of word. 27/04/2024.
    if ($maskconceptindefinitions) {
        $pattern = '/\b' . preg_quote($concept, '/') . '\b(?![[:punct:]])|(?<![[:punct:]])\b' . preg_quote($concept, '/') . '\b/i';
        $replacement = '***';
        $text = preg_replace($pattern, $replacement, $text);
    }

    return $text;
}
