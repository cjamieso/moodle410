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

use report_analytics\actionfilter;
use report_analytics\studentfilter;
use report_analytics\user_filters;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: actionfilter.php
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_analytics_actionfilter_testcase extends report_analytics_testcase {

    /**
     * This function tests the data retrieval function.
     */
    public function test_get_filter_data() {

        // Default -> standard actions not needed.
        $actionfilter = new actionfilter($this->courseid);
        $this->assertEquals(10, count($actionfilter->get_filter_data()));
        // Single select -> all options come back.
        $actionfilter = new actionfilter($this->courseid, array('action' => 'single'));
        $this->assertEquals(13, count($actionfilter->get_filter_data()));
        // Forum -> forum events only.
        $actionfilter = new actionfilter($this->courseid, array('types' => 'forum'));
        $this->assertEquals(3, count($actionfilter->get_filter_data()));
    }

    /**
     * This function tests the action label retrieval function.
     */
    public function test_get_action_label() {

        $actionfilter = new actionfilter($this->courseid, array('types' => 'forum'));
        // Test retrieval.
        $this->assertEquals(get_string('allactions', 'report_analytics'), $actionfilter->get_action_label('a'));
        $this->assertEquals(get_string('eventpostcreated', 'mod_forum'),
            $actionfilter->get_action_label('\mod_forum\event\post_created'));
        $this->assertEquals(get_string('eventattemptviewed', 'mod_quiz'),
            $actionfilter->get_action_label('\mod_quiz\event\attempt_viewed'));
        $this->expectException('Exception', get_string('actionnotfound', 'report_analytics'));
        $actionfilter->get_action_label('zzz');
    }

    /**
     * This function tests to see if the correct number of operators is returned.
     */
    public function test_get_operator_data() {

        // Test: the filter operators.
        $actionfilter = new actionfilter($this->courseid);
        $operators = $actionfilter->get_operator_data();
        $this->assertEquals(3, count($operators));
    }

    /**
     * This function tests the get_users_by_condition() function.  This function
     * can be given two types of conditions: activity conditions or grade conditions
     * (or both).  We test each of these two cases, along with the combined case.
     */
    public function test_filter_userids_by_condition() {

        $this->add_events_data();
        $filters = new user_filters();
        $actionfilter = new actionfilter($this->courseid);
        $studentfilter = new studentfilter($this->courseid);
        $students = array_keys($studentfilter->get_all_students());

        // Test 1: action condition (all).
        $condition = $this->create_criterion('action', array('cmid' => 'core', 'actionid' => 'a'), actionfilter::EQUAL, 2);
        $actual = $actionfilter->filter_userids_by_condition($condition, $students, $filters);
        sort($actual);
        $expected = $this->create_userids_from_keys(array(7, 8, 11, 12));
        $this->assertEquals($expected, $actual);

        // Test 2: event condition.
        $condition = $this->create_criterion('action', array('cmid' => $this->activities[1]->cmid,
            'actionid' => '\mod_feedback\event\response_submitted'), actionfilter::GREATER_THAN, 0);
        $actual = $actionfilter->filter_userids_by_condition($condition, $students, $filters);
        sort($actual);
        $expected = $this->create_userids_from_keys(array(6, 7, 8, 9, 10, 11, 12, 13));
        $this->assertEquals($expected, $actual);

        // Test 3: event condition w/ smaller list of users.
        $users = $this->create_userids_from_keys(array(0, 1, 2, 3, 4, 5, 6, 7, 8));
        $actual = $actionfilter->filter_userids_by_condition($condition, $users, $filters);
        sort($actual);
        $expected = $this->create_userids_from_keys(array(6, 7, 8));
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test the format_condition() function.  This function takes a condition
     * in the javascript format and generates it in a human readable format.
     */
    public function test_format_condition() {

        $actionfilter = new actionfilter($this->courseid);

        // Test 1: formatting of core/system.
        $condition = $this->create_criterion('action', array('cmid' => 'core', 'actionid' => 'a'), actionfilter::EQUAL, 2);
        $expected = get_string('allcore', 'report_analytics') . ', ' . get_string('allactions', 'report_analytics') . ' ' .
            get_string('equal', 'report_analytics') . ' ' . '2';
        $actual = $actionfilter->format_condition($condition);
        $this->assertEquals($expected, $actual);

        // Test 2: individual activity.
        $condition = $this->create_criterion('action', array('cmid' => $this->activities[1]->cmid,
            'actionid' => '\mod_feedback\event\response_submitted'), actionfilter::GREATER_THAN, 0);
        $expected = $this->activitynames[1] . ', ' . get_string('pluginname', 'mod_feedback') . ' ' .
            get_string('eventresponsesubmitted', 'mod_feedback') . ' ' . get_string('greaterthan', 'report_analytics') . ' ' . '0';
        $actual = $actionfilter->format_condition($condition);
        $this->assertEquals($expected, $actual);
    }

}
