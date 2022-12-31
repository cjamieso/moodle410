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
 * Question type class for the calculated question type.
 *
 * @package    qtype
 * @subpackage calculated
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/multichoice/questiontype.php');
require_once($CFG->dirroot . '/question/type/calculated/questiontype.php');
require_once($CFG->dirroot . '/question/type/calculatedmulti/questiontype.php');


/**
 * The calculated (with minimal decimals) question type.
 * A single function is changed to use the new datasetitems_form
 *
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_calculatedmedical extends qtype_calculatedmulti {

    /**
     * We override this function to have it return the mindecimal question types.
     *
     * @param  object  $questiondata  The questiondata
     * @return question  the newly created question.
     */
    protected function make_question_instance($questiondata) {
        question_bank::load_question_definition_classes($this->name());
        if ($questiondata->options->single) {
            $class = 'qtype_calculatedmedical_single_question';
        } else {
            $class = 'qtype_calculatedmedical_multi_question';
        }
        return new $class();
    }

    /**
     * We override this function to have it reference the datasetitems_form using the
     * minimum decimal specification.  Otherwise the function is the same.
     *
     * This gets called by question2.php after the standard question is saved.
     */
    public function &next_wizard_form($submiturl, $question, $wizardnow) {
        global $CFG, $SESSION, $COURSE;

        // Catch invalid navigation & reloads.
        if (empty($question->id) && empty($SESSION->calculated)) {
            redirect('edit.php?courseid=' . $COURSE->id,
                    'The page you are loading has expired. Cannot get next wizard form.', 3);
        }
        if (empty($question->id)) {
            $question = $SESSION->calculated->questionform;
        }

        // See where we're coming from.
        switch($wizardnow) {
            case 'datasetdefinitions':
                require("{$CFG->dirroot}/question/type/calculated/datasetdefinitions_form.php");
                $mform = new question_dataset_dependent_definitions_form(
                        "{$submiturl}?wizardnow=datasetdefinitions", $question);
                break;
            case 'datasetitems':
                require("{$CFG->dirroot}/question/type/calculatedmedical/datasetitems_form.php");
                $regenerate = optional_param('forceregeneration', false, PARAM_BOOL);
                $mform = new question_dataset_min_decimals_dependent_items_form(
                        "{$submiturl}?wizardnow=datasetitems", $question, $regenerate);
                break;
            default:
                print_error('invalidwizardpage', 'question');
                break;
        }

        return $mform;
    }

    /**
     * This function exists in the base class, but it has hardcoded the area to
     * "calculatedmulti", so we re-map it here to the correct area.
     *
     * @param  int  $questionid    the question ID
     * @param  int  $oldcontextid  the old context ID
     * @param  int  $newcontextid  the new context ID
     */
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        $fs = get_file_storage();

        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid, true);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);

        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'qtype_calculatedmedical', 'correctfeedback', $questionid);
        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'qtype_calculatedmedical', 'partiallycorrectfeedback', $questionid);
        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'qtype_calculatedmedical', 'incorrectfeedback', $questionid);
    }

    /**
     * This function exists in the base class, but it has hardcoded the area to
     * "calculatedmulti", so we re-map it here to the correct area.
     *
     * @param  int  $questionid the question ID
     * @param  int  $contextid  the context ID
     */
    protected function delete_files($questionid, $contextid) {
        $fs = get_file_storage();

        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid, true);
        $this->delete_files_in_hints($questionid, $contextid);

        $fs->delete_area_files($contextid, 'qtype_calculatedmedical',
                'correctfeedback', $questionid);
        $fs->delete_area_files($contextid, 'qtype_calculatedmedical',
                'partiallycorrectfeedback', $questionid);
        $fs->delete_area_files($contextid, 'qtype_calculatedmedical',
                'incorrectfeedback', $questionid);
    }

    /**
     * Imports question from the Moodle XML format.
     *
     * Moodle has done a terrible job on the xml import/export.  All of the base
     * question types are hardcoded outside of the question type folder.
     * (in question/format/xml/format.php)
     * Since they aren't part of the question itself, I can't re-use content in
     * the existing classes and have to copy/paste from format.php.
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        $qo = $format->import_calculated($data);
        $qo->qtype = 'calculatedmedical';
        return $qo;
    }

    /**
     * Exports question from the Moodle XML format.
     *
     * Moodle has done a terrible job on the xml import/export.  All of the base
     * question types are hardcoded outside of the question type folder.
     * (in question/format/xml/format.php)
     * Since they aren't part of the question itself, I can't re-use content in
     * the existing classes and have to copy/paste from format.php.
     */
    public function export_to_xml($question, qformat_xml $format, $extra=null) {

        $expout = '';
        $contextid = $question->contextid;
        $fs = get_file_storage();
        $expout .= "    <synchronize>{$question->options->synchronize}</synchronize>\n";
        $expout .= "    <single>{$question->options->single}</single>\n";
        $expout .= "    <answernumbering>" . $question->options->answernumbering .
                "</answernumbering>\n";
        $expout .= "    <shuffleanswers>" . $question->options->shuffleanswers .
                "</shuffleanswers>\n";

        $component = 'qtype_' . $question->qtype;
        $files = $fs->get_area_files($contextid, $component,
                'correctfeedback', $question->id);
        $expout .= "    <correctfeedback>\n";
        $expout .= $format->writetext($question->options->correctfeedback, 3);
        $expout .= $format->write_files($files);
        $expout .= "    </correctfeedback>\n";

        $files = $fs->get_area_files($contextid, $component,
                'partiallycorrectfeedback', $question->id);
        $expout .= "    <partiallycorrectfeedback>\n";
        $expout .= $format->writetext($question->options->partiallycorrectfeedback, 3);
        $expout .= $format->write_files($files);
        $expout .= "    </partiallycorrectfeedback>\n";

        $files = $fs->get_area_files($contextid, $component,
                'incorrectfeedback', $question->id);
        $expout .= "    <incorrectfeedback>\n";
        $expout .= $format->writetext($question->options->incorrectfeedback, 3);
        $expout .= $format->write_files($files);
        $expout .= "    </incorrectfeedback>\n";

        foreach ($question->options->answers as $answer) {
            $percent = 100 * $answer->fraction;
            $expout .= "<answer fraction=\"{$percent}\">\n";
            // The "<text/>" tags are an added feature, old files won't have them.
            $expout .= "    <text>{$answer->answer}</text>\n";
            $expout .= "    <tolerance>{$answer->tolerance}</tolerance>\n";
            $expout .= "    <tolerancetype>{$answer->tolerancetype}</tolerancetype>\n";
            $expout .= "    <correctanswerformat>" .
                    $answer->correctanswerformat . "</correctanswerformat>\n";
            $expout .= "    <correctanswerlength>" .
                    $answer->correctanswerlength . "</correctanswerlength>\n";
            $expout .= "    <feedback {$format->format($answer->feedbackformat)}>\n";
            $files = $fs->get_area_files($contextid, $component,
                    'instruction', $question->id);
            $expout .= $format->writetext($answer->feedback);
            $expout .= $format->write_files($answer->feedbackfiles);
            $expout .= "    </feedback>\n";
            $expout .= "</answer>\n";
        }
        if (isset($question->options->unitgradingtype)) {
            $expout .= "    <unitgradingtype>" .
                    $question->options->unitgradingtype . "</unitgradingtype>\n";
        }
        if (isset($question->options->unitpenalty)) {
            $expout .= "    <unitpenalty>" .
                    $question->options->unitpenalty . "</unitpenalty>\n";
        }
        if (isset($question->options->showunits)) {
            $expout .= "    <showunits>{$question->options->showunits}</showunits>\n";
        }
        if (isset($question->options->unitsleft)) {
            $expout .= "    <unitsleft>{$question->options->unitsleft}</unitsleft>\n";
        }

        if (isset($question->options->instructionsformat)) {
            $files = $fs->get_area_files($contextid, $component,
                    'instruction', $question->id);
            $expout .= "    <instructions " .
                    $format->format($question->options->instructionsformat) . ">\n";
            $expout .= $format->writetext($question->options->instructions, 3);
            $expout .= $format->write_files($files);
            $expout .= "    </instructions>\n";
        }

        if (isset($question->options->units)) {
            $units = $question->options->units;
            if (count($units)) {
                $expout .= "<units>\n";
                foreach ($units as $unit) {
                    $expout .= "  <unit>\n";
                    $expout .= "    <multiplier>{$unit->multiplier}</multiplier>\n";
                    $expout .= "    <unit_name>{$unit->unit}</unit_name>\n";
                    $expout .= "  </unit>\n";
                }
                $expout .= "</units>\n";
            }
        }

        // The tag $question->export_process has been set so we get all the
        // data items in the database from the function
        // qtype_calculated::get_question_options calculatedsimple defaults
        // to calculated.
        if (isset($question->options->datasets) && count($question->options->datasets)) {
            $expout .= "<dataset_definitions>\n";
            foreach ($question->options->datasets as $def) {
                $expout .= "<dataset_definition>\n";
                $expout .= "    <status>".$format->writetext($def->status)."</status>\n";
                $expout .= "    <name>".$format->writetext($def->name)."</name>\n";
                if ($question->qtype == 'calculated') {
                    $expout .= "    <type>calculated</type>\n";
                } else {
                    $expout .= "    <type>calculatedsimple</type>\n";
                }
                $expout .= "    <distribution>" . $format->writetext($def->distribution) .
                        "</distribution>\n";
                $expout .= "    <minimum>" . $format->writetext($def->minimum) .
                        "</minimum>\n";
                $expout .= "    <maximum>" . $format->writetext($def->maximum) .
                        "</maximum>\n";
                $expout .= "    <decimals>" . $format->writetext($def->decimals) .
                        "</decimals>\n";
                $expout .= "    <itemcount>{$def->itemcount}</itemcount>\n";
                if ($def->itemcount > 0) {
                    $expout .= "    <dataset_items>\n";
                    foreach ($def->items as $item) {
                          $expout .= "        <dataset_item>\n";
                          $expout .= "           <number>".$item->itemnumber."</number>\n";
                          $expout .= "           <value>".$item->value."</value>\n";
                          $expout .= "        </dataset_item>\n";
                    }
                    $expout .= "    </dataset_items>\n";
                    $expout .= "    <number_of_items>" . $def->number_of_items .
                            "</number_of_items>\n";
                }
                $expout .= "</dataset_definition>\n";
            }
            $expout .= "</dataset_definitions>\n";
        }
        return $expout;
    }

}
