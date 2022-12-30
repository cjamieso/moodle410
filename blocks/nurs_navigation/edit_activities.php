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
$blockid = required_param('blockid', PARAM_INT);

if (!$course = get_course($courseid)) {
    print_error('invalidcourse', 'block_nurs_navigation', $courseid);
}
require_login($course, false);
if (!has_capability('block/nurs_navigation:caneditnursnavigation', context_course::instance($courseid))) {
    echo get_string('noaccess', BNN_LANG_TABLE);
    return;
}

$PAGE->set_url('/blocks/nurs_navigation/edit_activities.php', array('id' => $courseid));
$PAGE->set_context(context_course::instance($COURSE->id));
$PAGE->set_title(get_string('editactivitiestitle', BNN_LANG_TABLE));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('editactivitiestitle', BNN_LANG_TABLE));

$form = new \block_nurs_navigation\edit_activities_form($courseid);
// Saving the blockid and courseid allows the page to continue upon post.
$toform['courseid'] = $courseid;
$toform['blockid'] = $blockid;
$form->set_data($toform);

if ($form->is_cancelled()) {
    // Cancelled forms redirect to the course main page.
    $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($courseurl);
} else if ($fromform = $form->get_data()) {
    // Process form and redirect on completion.
    $form->process_form($fromform);
    $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($courseurl);
} else {
    // Form didn't validate or this is the first display.
    $site = get_site();
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
