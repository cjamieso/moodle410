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

use \report_analytics\user_filters;
use \report_analytics\averagefilter;
use \report_analytics\gradefilter;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: user_filters.php
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_analytics_user_filters_testcase extends report_analytics_testcase {

    /**
     * Test the constructor.  The constructor can create the object with either
     * an object-based representation or an array-based representation.
     */
    public function test_constructor() {

        // Test 1: object-based representation.
        $filters = new stdClass();
        $filters->students = array(1, 2, 3);
        $filters->activities = array(4, 5, 6);
        $userfilters = new user_filters($filters, array('instructor' => true));
        $this->assertEquals(array(1, 2, 3), $userfilters->students);
        $this->assertEquals(array(4, 5, 6), $userfilters->activities);

        // Test 2: array-based representation.
        $filters = array('students' => array(1, 2, 3), 'activities' => array(4, 5, 6));
        $userfilters = new user_filters($filters, array('instructor' => true));
        $this->assertEquals(array(1, 2, 3), $userfilters->students);
        $this->assertEquals(array(4, 5, 6), $userfilters->activities);
    }

    /**
     * Test the set_activities function.
     */
    public function test_set_activities() {

        $filters = new user_filters();
        $this->assertNull($filters->activities);

        $filters->set_activities(array(1, 2, 3));
        $this->assertEquals(array(1, 2, 3), $filters->activities);
    }

    /**
     * Test the set_students function.  In addition to basic function, the students
     * variable gets re-mapped to the user's ID if they are a student.
     */
    public function test_set_students() {
        global $USER;

        $filters = new user_filters();
        $this->assertNull($filters->students);

        // Test 1: on creation, should be set to user's ID.
        $filters = new user_filters(null, array('instructor' => false));
        $this->assertEquals($USER->id, $filters->students);
        // Test 2: attempt to change should not change.
        $filters->set_students(1);
        $this->assertEquals($USER->id, $filters->students);
        // Test 3: instructor and setting field.
        $filters = new user_filters(null, array('instructor' => true));
        $filters->set_students(array(1, 2, 3));
        $this->assertEquals(array(1, 2, 3), $filters->students);
    }

    /**
     * Test the set_grade function.
     */
    public function test_set_grade() {

        $this->create_grades();
        $filters = new user_filters();
        $this->assertNull($filters->grade);

        $grade = $this->create_criterion('grade', $this->gradeitems[0]->id, gradefilter::EQUAL, 4);
        $filters->set_grade($grade);
        $this->assertEquals($grade, $filters->grade);
    }

    /**
     * Test the set_date function.
     */
    public function test_set_date() {

        $filters = new user_filters();
        $this->assertNull($filters->date);

        // Test 1: single date.
        $date = new stdClass();
        $date->to = '2015-11-06 06:30';
        $filters->set_date($date);
        $this->assertEquals($date, $filters->date);

        // Test 2: date set.
        $date = new stdClass();
        $date->to = '2015-11-06 06:30';
        $date->from = '2015-12-06 08:30';
        $filters->set_date($date);
        $this->assertEquals($date, $filters->date);

        // Test 3: invalid string.
        $date->to = 'spaghetti';
        $this->expectException('Exception', get_string('baddate', 'report_analytics'));
        $filters->set_date($date);
    }

    /**
     * Test trying to set the date to an invalid day of the calendar.
     */
    public function test_set_date_invalid() {

        $filters = new user_filters();
        // Setting date to Feb. 31st.
        $date = new stdClass();
        $date->to = '2015-02-31 06:30';
        $this->expectException('Exception', get_string('baddate', 'report_analytics'));
        $filters->set_date($date);
    }

    /**
     * Test trying to set the date using invalid formatting '/' instead of '-'.
     */
    public function test_set_date_bad_format() {

        $filters = new user_filters();
        $date = new stdClass();
        $date->to = '2015/11/06 06:30';
        $this->expectException('Exception', get_string('baddate', 'report_analytics'));
        $filters->set_date($date);
    }

    /**
     * Test the set_average function.
     */
    public function test_set_average() {

        $filters = new user_filters();
        $this->assertNull($filters->average);

        $filters->set_average(averagefilter::TOP15);
        $this->assertEquals(averagefilter::TOP15, $filters->average);
    }

    /**
     * Test the set_unique function.
     */
    public function test_set_unique() {

        $filters = new user_filters();
        $this->assertFalse($filters->unique);

        $filters->set_unique(true);
        $this->assertTrue($filters->unique);
    }

    /**
     * Test the set_bins function.  The bins field defaults to DEFAULT_BINS.
     */
    public function test_set_bins() {

        $filters = new user_filters();
        $this->assertEquals(user_filters::DEFAULT_BINS, $filters->bins);

        $filters->set_bins(128);
        $this->assertEquals(128, $filters->bins);
    }

    /**
     * Test the set_action function.
     */
    public function test_set_action() {

        $filters = new user_filters();
        $this->assertNull($filters->action);

        $filters->set_action(array('r', 'w'));
        $this->assertEquals(array('r', 'w'), $filters->action);
    }

    /**
     * Test the set_words function.
     */
    public function test_set_words() {

        $filters = new user_filters();
        $this->assertNull($filters->words);

        // Test 1: no value present, defaults to 0, 9999.
        $filters->set_words(null);
        $words = new stdClass();
        $words->minwords = 0;
        $words->maxwords = 9999;
        $this->assertEquals($words, $filters->words);

        // Test 2: change min, max.
        $words->minwords = 5;
        $words->maxwords = 50;
        $filters->set_words($words);
        $this->assertEquals($words, $filters->words);
    }

    /**
     * Test the set_criteria function.
     */
    public function test_set_criteria() {

        $this->create_grades();
        $filters = new user_filters();
        $this->assertNull($filters->criteria);

        $criteria = array($this->create_criterion('grade', $this->gradeitems[0]->id, gradefilter::EQUAL, 4));
        $filters->set_criteria($criteria);
        $this->assertEquals($criteria, $filters->criteria);
    }

}
