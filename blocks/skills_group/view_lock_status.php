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
 * Displays page that lets an instructor view and toggle lock status for all students
 * in the course.
 *
 * @package    block_skills_group
 * @category   block
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/skills_group/locallib.php');
global $USER, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
if (!blocks_skills_group_verify_access('block/skills_group:canmanageskillsgroups', true)) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
}
$url = new moodle_url('/blocks/skills_group/view_lock_status.php', array('courseid' => $courseid,
                      'sesskey' => $USER->sesskey));
block_skills_group_setup_page($courseid, $url, get_string('togglelockstatus', BLOCK_SG_LANG_TABLE));

$error = null;

echo $OUTPUT->header();
if ($error == null) {
    // Complete any toggles BEFORE drawing page.
    $action = optional_param('action', 'none', PARAM_TEXT);
    if ($action != 'none') {
        update_lock_status($action);
    }
    display_lock_table();
}
echo $OUTPUT->footer();

/**
 * This function updates a student's lock status to lock or unlock their choice.
 */
function update_lock_status($action) {

    $courseid = required_param('courseid', PARAM_INT);
    $userid = required_param('userid', PARAM_INT);
    if ($action == 'lock') {
        $status = true;
    } else if ($action == 'unlock') {
        $status = false;
    } else {
        return;
    }
    $student = new \block_skills_group\skills_group_student($courseid, $userid);
    $student->set_lock_choice($status);
}

/**
 * This function draws the lock status table.
 */
function display_lock_table() {
    global $DB;

    $courseid = required_param('courseid', PARAM_INT);

    echo html_writer::start_div('yui3-skin-sam', array('id' => 'viewgroup'));
    echo html_writer::start_tag('table', array('class' => 'yui3-datatable-table'));
    draw_table_header();

    $context = context_course::instance($courseid);
    $userfields = 'u.id, u.firstname, u.lastname';
    $orderby = 'u.lastname, u.firstname';
    $members = get_enrolled_users($context, 'block/skills_group:cancreateorjoinskillsgroups', 0, $userfields, $orderby);
    draw_table_results($members, $courseid);

    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}

/**
 * This function draws the table header - Student + Lock Status.
 */
function draw_table_header() {

    echo html_writer::start_tag('thead', array('class' => 'yui3-datatable-columns'));
    echo html_writer::start_tag('tr');
    echo html_writer::nonempty_tag('th', get_string('student', BLOCK_SG_LANG_TABLE),
                                   array('class' => 'yui3-datatable-header'));
    echo html_writer::nonempty_tag('th', get_string('lockstatus', BLOCK_SG_LANG_TABLE),
                                   array('class' => 'yui3-datatable-header'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
}

/**
 * This function draws the main table contents - name -> {lock|unlock}.
 *
 * @param object $members List of group members
 * @param int $courseid The ID for the course we're currently in
 */
function draw_table_results($members, $courseid) {
    global $USER, $OUTPUT;

    foreach ($members as $m) {
        echo html_writer::start_tag('tr');
        $name = $m->firstname . ' ' . $m->lastname;
        echo html_writer::nonempty_tag('td', $name, array('class' => 'yui3-datatable-cell'));
        $student = new \block_skills_group\skills_group_student($courseid, $m->id);
        if ($student->get_lock_choice() === true) {
            $opposite = 'unlock';
            $image = $OUTPUT->pix_icon('i/lock', get_string($opposite, BLOCK_SG_LANG_TABLE));
        } else {
            $opposite = 'lock';
            $image = $OUTPUT->pix_icon('i/unlock', get_string($opposite, BLOCK_SG_LANG_TABLE));
        }
        $url = new moodle_url('/blocks/skills_group/view_lock_status.php', array('courseid' => $courseid,
                  'userid' => $m->id, 'action' => $opposite, 'sesskey' => $USER->sesskey));
        $link = html_writer::link($url, $image, array('title' => get_string($opposite, BLOCK_SG_LANG_TABLE)));
        echo html_writer::nonempty_tag('td', $link, array('class' => 'yui3-datatable-cell tdcenter'));
        echo html_writer::end_tag('tr');
    }
}