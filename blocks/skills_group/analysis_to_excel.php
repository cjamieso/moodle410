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
 * This is a copy of the file mod/feedback/analysis_to_excel.php with
 * some a lot of content removed and cleaned up.
 *
 * In this exported version, we also add the accreditation ID to the column.
 *
 * @package    block_skills_group
 * @category   block
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
global $CFG;
require_once($CFG->dirroot . '/mod/feedback/lib.php');
require_once($CFG->libdir . '/excellib.class.php');

feedback_load_feedback_items();

$id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

if (!confirm_sesskey(required_param("sesskey", PARAM_TEXT))) {
    print_error('badsesskey', 'skills_group');
}
if (!$feedback = $DB->get_record("feedback", array("id" => $id))) {
    print_error('invalidcoursemodule');
}

$params = array('feedback' => $feedback->id, 'hasvalue' => 1);
if (!$items = $DB->get_records('feedback_item', $params, 'position')) {
    print_error('no_items_available_yet',
                'feedback',
                $CFG->wwwroot.'/mod/feedback/view.php?id='.$id);
    exit;
}

$filename = "coursefeedback.xls";
$workbook = new \MoodleExcelWorkbook('-');
$workbook->send($filename);

$xlsformats = new stdClass();
$xlsformats->head2 = $workbook->add_format(array(
                        'align' => 'left',
                        'bold' => 1,
                        'bottum' => 2));

$xlsformats->default = $workbook->add_format(array(
                        'align' => 'left',
                        'v_align' => 'top'));

$worksheet = $workbook->add_worksheet('detailed');
$completeds = feedback_get_completeds_group($feedback);
$rowoffset = 0;
$rowoffset = feedback_excelprint_detailed_head($feedback, $worksheet, $xlsformats, $items, $rowoffset, $courseid);
if ($rowoffset == null) {
    $workbook->close();
}

if (is_array($completeds)) {
    foreach ($completeds as $completed) {
        $rowoffset = feedback_excelprint_detailed_items($worksheet, $xlsformats, $completed, $items, $rowoffset);
        if ($rowoffset == null) {
            $workbook->close();
        }
    }
}

$workbook->close();
exit;

/**
 * This function was taken from the original file with a minor addition to determine the
 * "label" variable below.  The user information columns have been removed.
 *
 * @param      object   $feedback    The feedback
 * @param      object   $worksheet   The worksheet
 * @param      object   $xlsformats  The formats object for cell styles
 * @param      array    $items       The items/questions on the feedback
 * @param      int      $rowoffset   The offset of the row to start writing at
 * @param      int      $courseid    The ID of the course being used
 * @return     int|null  The next row to write to (or null if the feedback activity is empty)
 */
function feedback_excelprint_detailed_head($feedback, &$worksheet, $xlsformats, $items, $rowoffset, $courseid) {

    // Added by Craig: to retrieve mapping.
    $sgm = new \block_skills_group\skills_group_mapping($courseid);

    if (!$items) {
        return null;
    }
    $coloffset = 0;

    foreach ($items as $item) {
        // Modified by Craig: retrieve the mapping and display.
        $accreditationid = $sgm->get_accreditation_id($item->position);
        $label = isset($accreditationid) ? $accreditationid : '';
        $worksheet->write_string($rowoffset, $coloffset, $label, $xlsformats->head2);
        $worksheet->write_string($rowoffset + 1, $coloffset, $item->name, $xlsformats->head2);
        $coloffset++;
    }

    $worksheet->write_string($rowoffset + 1, $coloffset, get_string('courseid', 'feedback'), $xlsformats->head2);
    $coloffset++;

    $worksheet->write_string($rowoffset + 1, $coloffset, get_string('course'), $xlsformats->head2);
    $coloffset++;

    return $rowoffset + 2;
}

/**
 * This is an exact copy of the original function with the user information removed.
 *
 * @param      object   $worksheet   The worksheet
 * @param      object   $xlsformats  The formats object for cell styles
 * @param      object   $completed   The entry from the feedback_completed table in the database
 * @param      array    $items       The items/questions on the feedback
 * @param      int      $rowoffset   The offset of the row to start writing at
 * @return     int|null  The next row to write to (or null if the feedback activity is empty)
 */
function feedback_excelprint_detailed_items(&$worksheet, $xlsformats,
                                            $completed, $items, $rowoffset) {
    global $DB;

    if (!$items) {
        return null;
    }
    $coloffset = 0;
    $courseid = 0;

    $feedback = $DB->get_record('feedback', array('id' => $completed->feedback));

    foreach ($items as $item) {
        $params = array('item' => $item->id, 'completed' => $completed->id);
        $value = $DB->get_record('feedback_value', $params);

        $itemobj = feedback_get_item_class($item->typ);
        $printval = $itemobj->get_printval($item, $value);
        $printval = trim($printval);

        if (is_numeric($printval)) {
            $worksheet->write_number($rowoffset, $coloffset, $printval, $xlsformats->default);
        } else if ($printval != '') {
            $worksheet->write_string($rowoffset, $coloffset, $printval, $xlsformats->default);
        }
        $printval = '';
        $coloffset++;
        $courseid = isset($value->course_id) ? $value->course_id : 0;
        if ($courseid == 0) {
            $courseid = $feedback->course;
        }
    }
    $worksheet->write_number($rowoffset, $coloffset, $courseid, $xlsformats->default);
    $coloffset++;
    if (isset($courseid) AND $course = $DB->get_record('course', array('id' => $courseid))) {
        $coursecontext = context_course::instance($courseid);
        $shortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $worksheet->write_string($rowoffset, $coloffset, $shortname, $xlsformats->default);
    }
    return $rowoffset + 1;
}
