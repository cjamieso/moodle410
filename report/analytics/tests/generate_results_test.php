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
 * Test class for: generate_results.php (task)
 *
 * This task is fairly straightforward and has only the two base methods:
 * get_name() [skipped, simple get_string()]
 * execute()
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_results_class_testcase extends report_analytics_testcase {

    /**
     * Test the task execution function.  More extensive testing on conditions (criteria)
     * is performed in the report_conditions_test.php file.  Here, we test only
     * that the task can retrieve simple conditions and save the results in the
     * database.
     */
    public function test_execute() {

        $this->add_events_data();
        $this->create_grades();
        $this->login_admin_user();

        // Create criteria to check.
        $filters = array();
        $temp = new user_filters();
        $temp->criteria = array($this->create_criterion('grade', $this->gradeitems[0]->id, filter::GREATER_THAN, 3),
            $this->create_criterion('action', array('cmid' => 'core', 'actionid' => 'a'), filter::GREATER_THAN, 6));
        $filters[] = $temp;
        $temp = new user_filters();
        $temp->criteria = array($this->create_criterion('action', array('cmid' => 'core', 'actionid' => 'a'),
            filter::GREATER_THAN, 10), $this->create_criterion('action', array('cmid' => 'core', 'actionid' => 'a'),
            filter::LESS_THAN, 25));
        $filters[] = $temp;

        // Test 1: user field is empty.
        $record = new scheduled_results_record($this->courseid);
        $record->set_filters(json_encode($filters));
        $record->save();

        // Execute task.
        $task = \core\task\manager::get_scheduled_task('report_analytics\task\generate_results');
        $task->set_disabled(false);
        \core\task\manager::configure_scheduled_task($task);
        $task->execute();
        $record = new scheduled_results_record($this->courseid);
        $actual = $record->get_results();

        // Result: no results generated.
        $this->assertEquals(0, count($actual));

        // Test 2: valid recipient.
        $record = new scheduled_results_record($this->courseid);
        $record->set_filters(json_encode($filters));
        $record->set_userids(array(2));
        $record->save();

        // Execute task.
        $now = time();
        sleep(1);
        $task = \core\task\manager::get_scheduled_task('report_analytics\task\generate_results');
        $task->set_disabled(false);
        \core\task\manager::configure_scheduled_task($task);
        $task->execute();
        $record = new scheduled_results_record($this->courseid);
        $actual = $record->get_results();

        // Results: list of users correctly returned + timestamp updated.
        $this->assertGreaterThan($now, $record->get_results_time());
        $expected1 = $this->create_userids_from_keys(array(2, 6, 10, 13));
        $this->verify_users_array($expected1, $actual[0]);
        $expected2 = $this->create_userids_from_keys(array(2, 3, 5, 10));
        $this->verify_users_array($expected2, $actual[1]);
    }

}
