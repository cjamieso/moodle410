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
 * This is a hidden page that permits a category manager to delete icons.
 *
 * @package    block_skills_group
 * @category   block
 * @copyright  2018 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/nurs_navigation/locallib.php');
global $USER, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
require_login();
if (!has_capability('block/nurs_navigation:caneditnursnavigation', context_course::instance($courseid))) {
    echo get_string('noaccess', BNN_LANG_TABLE);
    return;
}

$PAGE->set_url('/blocks/nurs_navigation/delete_global_icon.php');
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_title(get_string('deletepagetitle', BNN_LANG_TABLE));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('deletepagetitle', BNN_LANG_TABLE));

echo $OUTPUT->header();
// Complete any deletions BEFORE drawing page.
$id = optional_param('delete', null, PARAM_TEXT);
if ($id) {
    delete_icon($id);
}
display_icon_table();

echo $OUTPUT->footer();

/**
 * This function deletes a record from the table.
 *
 * @param  $id  the id of the record to delete
 */
function delete_icon($id) {
    global $DB;

    $DB->delete_records('nurs_navigation', array('id' => $id));
}

/**
 * This function draws the icon listsings table.
 */
function display_icon_table() {
    global $DB;

    echo html_writer::start_div('yui3-skin-sam');
    echo html_writer::start_tag('table', array('class' => 'yui3-datatable-table'));
    draw_table_header();

    $records = $DB->get_records('nurs_navigation', array('courseid' => 1));
    draw_table_results($records);

    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}

/**
 * This function draws the table header - section name + icon.
 */
function draw_table_header() {

    echo html_writer::start_tag('thead', array('class' => 'yui3-datatable-columns'));
    echo html_writer::start_tag('tr');
    echo html_writer::nonempty_tag('th', get_string('section', BNN_LANG_TABLE),
                                   array('class' => 'yui3-datatable-header'));
    echo html_writer::nonempty_tag('th', get_string('existingimage', BNN_LANG_TABLE),
                                   array('class' => 'yui3-datatable-header'));
    echo html_writer::nonempty_tag('th', get_string('delete', BNN_LANG_TABLE),
                                   array('class' => 'yui3-datatable-header'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
}

/**
 * This function draws the main table contents - name -> {lock|unlock}.
 *
 * @param object $records list of all global records
 */
function draw_table_results($records) {
    global $OUTPUT;

    $courseid = required_param('courseid', PARAM_INT);
    foreach ($records as $r) {
        echo html_writer::start_tag('tr');
        echo html_writer::nonempty_tag('td', $r->sectionname, array('class' => 'yui3-datatable-cell'));
        $icon = new \block_nurs_navigation\section_icon($r->courseid, $r->sectionname);
        $tag = get_image_tag($icon);
        echo html_writer::nonempty_tag('td', $tag, array('class' => 'yui3-datatable-cell tdcenter', 'align' => 'center'));
        $url = new moodle_url('/blocks/nurs_navigation/delete_global_icon.php', array('delete' => $r->id, 'courseid' => $courseid));
        $deleteicon = $OUTPUT->pix_icon('i/delete', get_string('deleteicon', BNN_LANG_TABLE));
        $link = html_writer::link($url, $deleteicon, array('title' => get_string('deleteicon', BNN_LANG_TABLE)));
        echo html_writer::nonempty_tag('td', $link, array('class' => 'yui3-datatable-cell tdcenter'));
        echo html_writer::end_tag('tr');
    }
}

function get_image_tag($sectionicon) {

    $height = get_config('nurs_navigation', 'Image_Height');
    $width = get_config('nurs_navigation', 'Image_Width');
    $image = $sectionicon->get_image(true);
    return "<img src='$image' height='$height' width='$width' />";
}

