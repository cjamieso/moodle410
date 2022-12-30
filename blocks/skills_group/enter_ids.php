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
 * This is ID mapping page for skills_group block instructors.
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
    redirect(new \moodle_url('/course/view.php', array('id' => $courseid)));
}
$url = new \moodle_url('/blocks/skills_group/enter_ids.php', array('id' => $courseid, 'sesskey' => $USER->sesskey));
block_skills_group_setup_page($courseid, $url, get_string('identrytitle', BLOCK_SG_LANG_TABLE));

$idsform = new \block_skills_group\enter_ids_form($courseid);
$toform['courseid'] = $courseid;
// Retrieve any previously used settings.
$sgm = new \block_skills_group\skills_group_mapping($courseid);
$mappings = $sgm->get_all_mappings();
foreach ($mappings as $mapping) {
    $toform['position' . $mapping->position] = $mapping->accreditationid;
}
$idsform->set_data($toform);

if ($idsform->is_cancelled()) {
    $courseurl = new \moodle_url('/course/view.php', array('id' => $courseid));
    redirect($courseurl);
} else if ($fromform = $idsform->get_data()) {
    if (isset($fromform->submitupload) && $fromform->submitupload == get_string('submitupload', BLOCK_SG_LANG_TABLE)) {
        $idsform->process_upload($idsform->get_file_content('uploadids'));
    } else {
        $idsform->process_form($fromform);
    }
    $courseurl = new \moodle_url('/course/view.php', array('id' => $courseid));
    redirect($courseurl);
} else {
    $site = get_site();
    echo $OUTPUT->header();
    $idsform->display();
    echo $OUTPUT->footer();
}
