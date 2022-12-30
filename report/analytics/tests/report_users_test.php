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

use report_analytics\report_users;
use report_analytics\filter;
use report_analytics\user_filters;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: report_users.php
 *
 * The class has a single public entry point: get_user_data()
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_users_class_testcase extends report_analytics_testcase {

    /**
     * This function tests the get_user_data() function.  With an empty student
     * filter, the function will return all users.  We also test with a specific
     * student to ensure that the student version will work.
     */
    public function test_get_user_data() {

        $this->add_events_data();
        $this->create_grades();
        $this->login_admin_user();
        $filters = new user_filters();

        // Test 1: retrieve all user data.
        $ru = new report_users($this->courseid, $filters);
        list($points, $eventrange, $graderange) = $ru->get_user_data();
        $expectedeventrange = array('min' => 4, 'max' => 15);
        $expectedgraderange = array('min' => 2, 'max' => 16);
        $this->assertEquals($expectedeventrange, $eventrange);
        $this->assertEquals($expectedgraderange, $graderange);
        $expectedpoints = array(
            array('grade' => 5, 'actions' => 5),
            array('grade' => 6, 'actions' => 8),
            array('grade' => 7, 'actions' => 14),
            array('grade' => 2, 'actions' => 15),
            array('grade' => 3, 'actions' => 7),
            array('grade' => 4, 'actions' => 13),
            array('grade' => 8, 'actions' => 15),
            array('grade' => 9, 'actions' => 4),
            array('grade' => 10, 'actions' => 4),
            array('grade' => 11, 'actions' => 5),
            array('grade' => 12, 'actions' => 15),
            array('grade' => 13, 'actions' => 4),
            array('grade' => 14, 'actions' => 4),
            array('grade' => 15, 'actions' => 11),
            array('grade' => 16, 'actions' => 0)
        );

        for ($i = 0; $i < self::NUMBER_OF_USERS; $i++) {
            $this->assertEquals($expectedpoints[$i]['grade'], $points[$i]['grade']);
            $this->assertEquals($expectedpoints[$i]['actions'], $points[$i]['actions']);
        }

        // Test 2: retrieve one user.
        $filters->students = $this->users[0]->id;
        $ru = new report_users($this->courseid, $filters);
        list($points, $eventrange, $graderange) = $ru->get_user_data();
        // Ranges will stay the same.
        $this->assertEquals($expectedeventrange, $eventrange);
        $this->assertEquals($expectedgraderange, $graderange);
        $this->assertEquals(5, $points[0]['grade']);
        $this->assertEquals(5, $points[0]['actions']);
    }

}
