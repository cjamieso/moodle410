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
 * This page creates the form that lets the user select whether a quiz/assignment
 * is a quiz, assignment, or quest in the course.
 *
 * @package    block_nurs_navigation
 * @category   block
 * @copyright  2018 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/nurs_navigation/locallib.php');

global $DB, $OUTPUT, $PAGE;

$courseid = required_param('courseid', PARAM_INT);
$type = required_param('type', PARAM_TEXT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', BNN_LANG_TABLE, $courseid);
}
require_login($course);

$PAGE->set_url('/blocks/nurs_navigation/activity_table.php', array('id' => $courseid, 'type' => $type));
$PAGE->set_pagelayout('incourse');
$title = get_activity_title($type);
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
$renderable = new \block_nurs_navigation\output\activity_table_renderable($courseid, $type);
$renderer = $PAGE->get_renderer('block_nurs_navigation', 'activity_table');
echo $renderer->render($renderable);
echo $OUTPUT->footer();
