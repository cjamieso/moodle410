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

use report_analytics\report_conditions;
use report_analytics\filter;
use report_analytics\user_filters;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: report_conditions.php
 *
 * The class has a single public entry point: get_users_by_condition()
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_conditions_class_testcase extends report_analytics_testcase {

    /**
     * This function tests the get_users_by_condition() function.  This function
     * can be given two types of conditions: activity conditions or grade conditions
     * (or both).  We test each of these two cases, along with the combined case.
     */
    public function test_get_users_by_condition() {

        $this->add_events_data();
        $this->create_grades();
        $this->login_admin_user();
        $filters = new user_filters();

        // Test 1: grade condition.
        $filters->criteria = array($this->create_criterion('grade', $this->gradeitems[0]->id, filter::EQUAL, 4));
        $rc = new report_conditions($this->courseid, $filters);
        $actual = $rc->get_users_by_condition();
        $expected = $this->create_userids_from_keys(array(0, 1, 2));
        $this->verify_users_array($expected, $actual);

        // Test 2: multiple grade conditions.
        $filters->criteria = array($this->create_criterion('grade', $this->gradeitems[0]->id, filter::GREATER_THAN, 3),
            $this->create_criterion('grade', $this->gradeitems[0]->id, filter::LESS_THAN, 10));
        $rc = new report_conditions($this->courseid, $filters);
        $actual = $rc->get_users_by_condition();
        $expected = $this->create_userids_from_keys(array(0, 1, 2, 6, 7, 8));
        $this->verify_users_array($expected, $actual);

        // Test 3: activity condition.
        $filters->criteria = array($this->create_criterion('action', array('cmid' => 'core', 'actionid' => 'a'), filter::EQUAL, 2));
        $rc = new report_conditions($this->courseid, $filters);
        $actual = $rc->get_users_by_condition();
        $expected = $this->create_userids_from_keys(array(7, 8, 11, 12));
        $this->verify_users_array($expected, $actual);

        // Test 4: multiple activity conditions.
        $filters->criteria = array($this->create_criterion('action', array('cmid' => 'core', 'actionid' => 'a'),
            filter::GREATER_THAN, 10), $this->create_criterion('action', array('cmid' => 'core', 'actionid' => 'a'),
            filter::LESS_THAN, 25));
        $rc = new report_conditions($this->courseid, $filters);
        $actual = $rc->get_users_by_condition();
        $expected = $this->create_userids_from_keys(array(2, 3, 5, 10));
        $this->verify_users_array($expected, $actual);

        // Test 5: grade + activity condition.
        $filters->criteria = array($this->create_criterion('grade', $this->gradeitems[0]->id, filter::GREATER_THAN, 3),
            $this->create_criterion('action', array('cmid' => 'core', 'actionid' => 'a'), filter::GREATER_THAN, 6));
        $rc = new report_conditions($this->courseid, $filters);
        $actual = $rc->get_users_by_condition();
        $expected = $this->create_userids_from_keys(array(2, 6, 10, 13));
        $this->verify_users_array($expected, $actual);
    }

}
