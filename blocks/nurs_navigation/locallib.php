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
 * This is the locallib.php file for the project.  Any functions that are
 * used across several different modules are here.
 *
 * @package    block_nurs_navigation
 * @category   block
 * @copyright  2012 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
/* Moodle Bug -> course/format/lib.php should include course/lib.php because it uses its function
 * but it does not, so I have to manually include it. */
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/course/format/lib.php');

/** hex is base 16 */
define("BNN_BASE_HEX", 16);
/** decimal is base 10 */
define("BNN_BASE_DECIMAL", 10);
/** length of fileid */
define("BNN_ITEMID_LENGTH", 9);
/** component under which the files will be saved */
define("BNN_BLOCK_SAVE_COMPONENT", 'block_nurs_navigation');
/** file save area */
define("BNN_BLOCK_SAVE_AREA", 'nursing_image');
/** max number of bytes for a file */
define('BNN_MAX_BYTES', 100000000);
/** max number of files that can be choosen */
define('BNN_MAX_FILES', 1);
/** name of the plugin */
define('BNN_LANG_TABLE', 'block_nurs_navigation');

/**
 * This is method grabs an unused file ID from the {files} table and returns it to the user.
 *
 * @return int Unused file ID.
 *
 */
function get_unused_file_id() {
    global $DB;

    do {
        // 1) Use uniqid to generate random string based on time.
        // 2) Compute its md5 hash (returned as 32 character hex).
        // 3) Convert to decimal (base 36 -> base 10).
        // 4) Select the first 9 digits (itemids are 9 digits long).
        $attachmentid = substr(base_convert(md5(uniqid('', true)), BNN_BASE_HEX, BNN_BASE_DECIMAL), 0, BNN_ITEMID_LENGTH);
    } while ($DB->count_records('files', array('itemid' => $attachmentid)) > 0);

    return $attachmentid;
}

/**
 * This method checks a draft ID in the files table to see if there is at least one valid file
 * associated with it.
 *
 * @return bool T/F indicating whether a draft file exists with that ID.
 *
 */
function check_draft_id($draftid) {
    global $DB;

    $params = array($draftid);
    $query = "SELECT * FROM {files} WHERE itemid = ? AND filename <> '.'";
    $records = $DB->get_records_sql($query, $params);

    return (count($records) > 0);
}

/**
 * This method returns the number of active sections in a course.
 *
 * @param int $courseid This is the course ID of the course to check.
 * @return int Total number of active sections.
 *
 */
function get_number_of_sections($courseid) {
    global $DB;

    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    return course_get_format($course)->get_last_section_number();
}

/**
 * This is method returns the section titles for all active sections within a course.  The data
 * is appened to the passed second parameter.
 *
 * We skip section 0, since we're only interested in actual sections in the course.
 *
 * @param int $courseid This is the course ID of the course to check.
 * @param array $sectionheaders The section headers will be appended here.
 * @return int Total number of active sections.
 *
 */
function get_section_titles($courseid,  &$sectionheaders) {
    global $DB;

    $numberofsections = get_number_of_sections($courseid);
    for ($i = 1; $i <= $numberofsections; $i++) {
        $sectionheaders[$i] = get_section_name($courseid, $i);
    }
    return $numberofsections;
}

/**
 * Check to see if a custom title is specified.  If not, fall back on the activity
 * name.
 *
 * @param  string  $type  the activity type
 * @return string  the title to use for the activity
 */
function get_activity_title($type) {

    if (get_string_manager()->string_exists($type . 'title', BNN_LANG_TABLE)) {
        return get_string($type . 'title', BNN_LANG_TABLE);
    } else if (get_string_manager()->string_exists('modulenameplural', $type)) {
        return get_string('modulenameplural', $type);
    } else {
        return get_string('missingtitle', BNN_LANG_TABLE);
    }
}
