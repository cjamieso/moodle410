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

use report_analytics\studentfilter;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: studentfilter.php
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_analytics_studentfilter_testcase extends report_analytics_testcase {

    /**
     * This function tests the students filter.
     */
    public function test_get_filter_data() {

        $studentfilter = new studentfilter($this->courseid);

        // Build the expected answers.
        $groups = array();
        foreach ($this->groups as $g) {
            $groups['g' . $g->id] = $g->name;
        }
        $students = array();
        foreach ($this->users as $u) {
            $students[$u->id] = $u->firstname . ' ' . $u->lastname;
        }
        // Grab data and check.
        $data = $studentfilter->get_filter_data();
        // Reorder keys so that the two sets match.
        $groups = $this->reorder_array($groups, $data[get_string('groups')]);
        $students = $this->reorder_array($students, $data[get_string('students')]);
        $this->assertEquals($groups, $data[get_string('groups')]);
        $this->assertEquals($students, $data[get_string('students')]);
        // Verify that get_all_students() also works directly.
        $this->assertEquals($students, $studentfilter->get_all_students());

        // Test with grade sorting.
        $this->create_grades();
        $studentfilter->set_gradesort(true);
        $data = $studentfilter->get_filter_data();
        $expected = $this->create_userids_from_keys(array(8, 7, 6, 2, 1, 0, 5, 4, 3));
        $expected = $this->create_userids_from_keys(array(14, 13, 12, 11, 10, 9, 8, 7, 6, 2, 1, 0, 5, 4, 3));
        $gradedresults = array_keys($data[get_string('students')]);
        $this->assertEquals($expected, $gradedresults);
    }

    /**
     * This function tests the parse_groups() function.  Simple cases consisting of
     * only user IDs are tested first.  Group IDs are added afterwards for more complex
     * tests.
     */
    public function test_parse_groups() {

        $studentfilter = new studentfilter($this->courseid);

        // Test 1: single user ID.
        $ids = array($this->users[0]->id);
        $actual = $studentfilter->parse_groups($ids);
        $this->assertEquals($ids, $actual);

        // Test 2: multiple user IDs.
        $ids = array($this->users[0]->id, $this->users[self::NUMBER_OF_USERS - 1]->id);
        $actual = $studentfilter->parse_groups($ids);
        $this->assertEquals($ids, $actual);

        // Test 3: single group.
        $ids = array("g" . $this->groups[0]->id);
        $actual = $studentfilter->parse_groups($ids);
        $expected = $this->get_users_in_group(0);
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);

        // Test 4: single group + user outside of group.
        $ids = array("g" . $this->groups[0]->id, $this->users[self::NUMBER_OF_USERS - 1]->id);
        $actual = $studentfilter->parse_groups($ids);
        $expected = $this->get_users_in_group(0);
        $expected[] = $this->users[self::NUMBER_OF_USERS - 1]->id;
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);

        // Test 5: single group + user inside of group.
        $ids = array("g" . $this->groups[0]->id, $this->users[0]->id);
        $actual = $studentfilter->parse_groups($ids);
        $expected = $this->get_users_in_group(0);
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);

        // Test 6: two groups + user outside of group.
        if (self::NUMBER_OF_GROUPS > 1 && self::NUMBER_OF_USERS > 2 * self::USERS_PER_GROUP) {
            $ids = array("g" . $this->groups[0]->id, "g" . $this->groups[1]->id, $this->users[self::NUMBER_OF_USERS - 1]->id);
            $actual = $studentfilter->parse_groups($ids);
            $expected = $this->get_users_in_group(0);
            $expected = array_merge($expected, $this->get_users_in_group(1));
            $expected[] = $this->users[self::NUMBER_OF_USERS - 1]->id;
            sort($expected);
            sort($actual);
            $this->assertEquals($expected, $actual);
        }

        // Test 7: two groups + user inside of group.
        if (self::NUMBER_OF_GROUPS > 1) {
            $ids = array("g" . $this->groups[0]->id, "g" . $this->groups[1]->id, $this->users[0]->id);
            $actual = $studentfilter->parse_groups($ids);
            $expected = $this->get_users_in_group(0);
            $expected = array_merge($expected, $this->get_users_in_group(1));
            sort($expected);
            sort($actual);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * Reorder the keys in array a1 according to the keys in array a2 and
     * return the result.
     *
     * @param      array  $a1     array to be reordered
     * @param      array  $a2     array with set of keys in desired order
     * @return     array  array a1 reordered according to keys in a2
     */
    private function reorder_array($a1, $a2) {
        $temp = array();
        foreach ($a2 as $key => $value) {
            if (isset($a1[$key])) {
                $temp[$key] = $a1[$key];
            }
        }
        return $temp;
    }

}
