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
 * Entry page for scheduled report designer.
 *
 * @package    report_analytics
 * @category   report
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
global $CFG, $USER;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');

$courseid = required_param('id', 'int');
if (!$course = get_course($courseid)) {
    print_error('nocourseid');
}
require_login($course, false);
$context = context_course::instance($courseid);
require_capability('report/analytics:view', $context);
$PAGE->set_context($context);
$PAGE->set_url('/report/analytics/scheduled.php', array('id' => $courseid));
$PAGE->set_title($course->shortname .': '. get_string('pluginname', 'report_analytics'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report');
\report_analytics\analytics_helper::set_header();
$record = new \report_analytics\scheduled_results_record($courseid, $USER->id);
$PAGE->requires->js_call_amd('report_analytics/scheduled_report', 'scheduledReportCriteriaInit', $record->get_filters());
$PAGE->requires->js_call_amd('report_analytics/scheduled_report', 'scheduledReportUserIDsInit', $record->get_userids());
echo $OUTPUT->header();
$renderable = new \report_analytics\output\scheduledcriteriachart_renderable($courseid, array('instructor' => true));
$renderer = $PAGE->get_renderer('report_analytics', 'scheduledcriteriachart');
echo $renderer->render($renderable);
echo $OUTPUT->footer();
