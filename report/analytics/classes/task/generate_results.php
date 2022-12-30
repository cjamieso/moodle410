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

namespace report_analytics\task;

use report_analytics\scheduled_results_record;
use report_analytics\scheduledcriteriachart;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Task to run all pre-setup queries by the user and email the results.
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_results extends \core\task\scheduled_task {

    /**
     * Sets the name of the task (as it appears in the admin -> task space)
     *
     * @return string  the name of the task
     */
    public function get_name() {
        return get_string('generateresults', 'report_analytics');
    }

    /**
     * For each set of conditions in an active course, apply the condition to the
     * students and record the results in the database.
     */
    public function execute() {
        global $DB;

        $records = $DB->get_records('report_analytics_results');
        foreach ($records as $record) {
            $scheduledrecord = new scheduled_results_record($record);
            $course = get_course($scheduledrecord->get_courseid());
            // Process for active courses with a list of recipients only.
            if ($course->visible && count($scheduledrecord->get_userids()) > 0) {
                $results = array();
                $filters = $scheduledrecord->get_filters();
                if (is_array($filters)) {
                    foreach ($filters as $filter) {
                        $chart = new scheduledcriteriachart($scheduledrecord->get_courseid());
                        $results[] = $chart->ajax_get_data($filter);
                    }
                    $scheduledrecord->set_results($results);
                    $scheduledrecord->set_results_time(time());
                    $scheduledrecord->save();
                }
            }
        }
    }

}
