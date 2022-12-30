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

use report_analytics\scheduled_results_record;
use report_analytics\filter;
use report_analytics\user_filters;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: email_results.php (task)
 *
 * This task is a bit more complex, since the task to generate results must run first.
 * Much of the content if the email is tested in other functions (e.g., format_condition()
 * in the various filter files), so here we simply test to ensure that the email
 * is generated and sent.  Moodle recommends redirecting the emails to a sink.
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class email_results_class_testcase extends report_analytics_testcase {

    /**
     * Test the task execution function.
     */
    public function test_execute() {
        global $USER;

        $this->add_events_data();
        $this->create_grades();
        $this->login_admin_user();

        // Create criteria to check.
        $filters = array();
        $temp = new user_filters();
        $temp->criteria = array($this->create_criterion('action', array('cmid' => 'core', 'actionid' => 'a'),
            filter::GREATER_THAN, 10), $this->create_criterion('action', array('cmid' => 'core', 'actionid' => 'a'),
            filter::LESS_THAN, 25));
        $filters[] = $temp;

        // Save for task.
        $record = new scheduled_results_record($this->courseid);
        $record->set_filters($filters);
        $record->set_userids(array(2));
        $record->save();

        // Generate results.
        $task = \core\task\manager::get_scheduled_task('report_analytics\task\generate_results');
        $task->set_disabled(false);
        \core\task\manager::configure_scheduled_task($task);
        $task->execute();

        // Test 1: execute task -> will email.
        $now = time();
        sleep(1);
        $task = \core\task\manager::get_scheduled_task('report_analytics\task\email_results');
        $task->set_disabled(false);
        \core\task\manager::configure_scheduled_task($task);
        unset_config('noemailever');
        $sink = $this->redirectEmails();
        $task->execute();

        $record = new scheduled_results_record($this->courseid);
        $this->assertGreaterThan($now, $record->get_email_time());
        $this->assertEquals($sink->count(), 1);
        $sink->clear();

        // Test 2: execute task again -> no email.
        $task->execute();
        $this->assertEquals($sink->count(), 0);
        $sink->clear();
    }

}
