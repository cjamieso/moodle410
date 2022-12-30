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
 * This page allows the user to graph a pre/post item/question result.
 *
 * @package    block_skills_group
 * @category   block
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
global $CFG;
require_once($CFG->dirroot . '/blocks/skills_group/locallib.php');

global $OUTPUT, $PAGE, $USER;

$courseid = required_param('courseid', PARAM_INT);
if (!blocks_skills_group_verify_access('block/skills_group:canmanageskillsgroups', true)) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
}
$url = new moodle_url('/blocks/skills_group/graph_results.php', array('id' => $courseid, 'sesskey' => $USER->sesskey));
block_skills_group_setup_page($courseid, $url, get_string('graphresultstitle', BLOCK_SG_LANG_TABLE));


$sgs = new \block_skills_group\skills_group_setting($courseid);
$items = $sgs->get_feedback_items();

set_header();
echo $OUTPUT->header();
$chartrenderable = new \block_skills_group\output\chart_renderable($courseid, $items);
$renderer = $PAGE->get_renderer('block_skills_group', 'chart');
echo $renderer->render($chartrenderable);
echo $renderer->display_entry_data();
echo $OUTPUT->footer();

/**
 * This function set the page header -> JS/CSS includes.
 *
 */
function set_header() {
    global $PAGE;

    // Css.
    $PAGE->requires->css('/blocks/skills_group/css/multiple-select.css');
    // Js files.
    $PAGE->requires->js_call_amd('block_skills_group/chart', 'init');
    // Language strings.
    $PAGE->requires->strings_for_js(array('badrequest', 'count', 'noquestions'), 'block_skills_group');
}