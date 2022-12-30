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
 * Entry page for analytics report.
 *
 * @package    report_analytics
 * @category   report
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');

$courseid = required_param('id', 'int');
if (!$course = get_course($courseid)) {
    print_error('nocourseid');
}
require_login($course, false);
$instructor = false;
$context = context_course::instance($courseid);
if (has_capability('report/analytics:view', $context)) {
    $instructor = true;
} else if ($courseid != SITEID) {
    require_capability('report/analytics:studentview', $context);
}
$PAGE->set_context($context);
$PAGE->set_url('/report/analytics/index.php', array('id' => $courseid));
$PAGE->set_title($course->shortname .': '. get_string('pluginname', 'report_analytics'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report');
\report_analytics\analytics_helper::set_header();
echo $OUTPUT->header();
$selectorrenderable = new \report_analytics\output\selector_renderable($courseid, array('instructor' => $instructor));
$renderer = $PAGE->get_renderer('report_analytics', 'selector');
echo $renderer->render($selectorrenderable);
echo $OUTPUT->footer();
