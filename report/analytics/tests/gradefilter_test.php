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

use report_analytics\gradefilter;
use report_analytics\studentfilter;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: gradefilter.php
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_analytics_gradefilter_testcase extends report_analytics_testcase {

    /**
     * This function tests that the correct grade item information for the test
     * course is returned.
     */
    public function test_get_filter_data() {

        $this->create_grades();
        $gradefilter = new gradefilter($this->courseid);

        // Test: retrieving grade items.
        $allitems = $gradefilter->get_filter_data();
        $gradeitems = array_slice($allitems, 0, count($this->gradeitems));
        foreach ($gradeitems as $id => $gradeitem) {
            $this->assertEquals($this->gradeitems[$id]->itemname, $gradeitem);
        }
        $this->assertEquals('Course total', end($allitems));
    }

    /**
     * This function tests to see if the correct number of operators is returned.
     */
    public function test_get_operator_data() {

        // Test: the filter operators.
        $gradefilter = new gradefilter($this->courseid);
        $operators = $gradefilter->get_operator_data();
        $this->assertEquals(3, count($operators));
    }

    /**
     * This function tests applying grade conditions.
     */
    public function test_filter_userids_by_condition() {

        $this->create_grades();
        $gradefilter = new gradefilter($this->courseid);
        $studentfilter = new studentfilter($this->courseid);
        $students = array_keys($studentfilter->get_all_students());

        // Test 1: grade item (equal).
        $condition = $this->create_criterion('grade', $this->gradeitems[0]->id, gradefilter::EQUAL, 4);
        $actual = $gradefilter->filter_userids_by_condition($condition, $students);
        sort($actual);
        $expected = $this->create_userids_from_keys(array(0, 1, 2));
        $this->assertEquals($expected, $actual);

        // Test 2: grade item (less than).
        $condition->operator = gradefilter::LESS_THAN;
        $condition->value = 3;
        $actual = $gradefilter->filter_userids_by_condition($condition, $students);
        sort($actual);
        $expected = $this->create_userids_from_keys(array(3, 4, 5));
        $this->assertEquals($expected, $actual);

        // Test 3: scale item (equal).
        $condition->operand = $this->gradeitems[1]->id;
        $condition->operator = gradefilter::EQUAL;
        $condition->value = 'Bad';
        $actual = $gradefilter->filter_userids_by_condition($condition, $students);
        sort($actual);
        $expected = $this->create_userids_from_keys(array(0, 3, 6, 9, 12));
        $this->assertEquals($expected, $actual);

        // Test 4: scale item (greater than).
        $condition->operator = gradefilter::GREATER_THAN;
        $actual = $gradefilter->filter_userids_by_condition($condition, $students);
        sort($actual);
        $expected = $this->create_userids_from_keys(array(1, 2, 4, 5, 7, 8, 10, 11, 13, 14));
        $this->assertEquals($expected, $actual);

        // Test 5: text item (equal).
        $condition->operand = $this->gradeitems[2]->id;
        $condition->operator = gradefilter::EQUAL;
        $condition->value = 'bad';
        $actual = $gradefilter->filter_userids_by_condition($condition, $students);
        sort($actual);
        $expected = $this->create_userids_from_keys(array(0, 1, 6));
        $this->assertEquals($expected, $actual);

        // Test 6: test trim + regex replacement -> should produce the same results as test 5.
        $condition->value = '   bad&^% ';
        $actual = $gradefilter->filter_userids_by_condition($condition, $students);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test the format_condition() function.  This function takes a condition
     * in the javascript format and generates it in a human readable format.
     */
    public function test_format_condition() {
        global $DB;

        $this->create_grades();
        $gradefilter = new gradefilter($this->courseid);

        // Test 1: regular grade item.
        $condition = $this->create_criterion('grade', $this->gradeitems[0]->id, gradefilter::EQUAL, 4);
        $expected = $this->gradeitems[0]->itemname . ' ' . get_string('equal', 'report_analytics') . ' ' . '4';
        $actual = $gradefilter->format_condition($condition);
        $this->assertEquals($expected, $actual);

        // Test 2: course total column.
        $total = $DB->get_record('grade_items', array('courseid' => $this->courseid, 'itemtype' => 'course'));
        $condition = $this->create_criterion('grade', $total->id, gradefilter::GREATER_THAN, 10);
        $expected = 'Course total ' . get_string('greaterthan', 'report_analytics') . ' ' . '10';
        $actual = $gradefilter->format_condition($condition);
        $this->assertEquals($expected, $actual);
    }

}
