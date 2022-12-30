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

use report_analytics\activitychart;
use report_analytics\activitytimelinechart;
use report_analytics\forumchart;
use report_analytics\forumtimelinechart;
use report_analytics\userpostschart;
use report_analytics\completionsearchchart;
use report_analytics\scheduledcriteriachart;
use report_analytics\gradechart;
use report_analytics\analytics_helper;
use report_analytics\filter;
use report_analytics\activityfilter;
use report_analytics\user_filters;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: chart.php and associated charts.
 *
 * This includes:
 * 1) activitychart
 * 2) activitytimelinechart
 * 3) forumchart
 * 4) forumtimelinechart
 * 5) userpostschart
 * 6) completionsearchchart
 * 7) scheduledcriteriachart
 * 8) gradechart
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chart_class_testcase extends report_analytics_testcase {

    /**
     * Setup a standard test and add the events data as well.
     *
     */
    protected function setUp(): void {

        parent::setUp();
        $this->add_events_data();
        $this->create_forums();
    }

    /**
     * This tests the ajax_get_data() function of the various charts.  The
     * database retrieval code is tested on its own, so I'm testing only for
     * structure here.
     *
     * Note: forumchart and forumtimelinechart inherit from activitychart and
     * activitytimelinechart, so I have not added a specific test for these two.
     */
    public function test_ajax_get_data() {

        $this->create_grades();
        $this->setAdminUser();

        $chart = new activitychart($this->courseid, array('instructor' => true));
        $filters = new user_filters();
        $filters->action = array('r', 'w');
        $data = $chart->ajax_get_data($filters);
        // 9 total entries for test data.
        $this->assertEquals(count($data), 9);
        // 3 items (label, type, values) for each entry.  'time' is ommitted for phpunit tests.
        $this->assertEquals(count($data[0]), 3);

        $filters = new user_filters();
        $filters->action = 'a';
        $chart = new activitytimelinechart($this->courseid, array('instructor' => true));
        $data = $chart->ajax_get_data($filters);
        // 16 time slots x 9 labels =  144 entries.
        $this->assertEquals(count($data), 144);
        // Each time slot contains 4 fields {label, type, date, count}.
        $this->assertEquals(count($data[0]), 4);

        $filters = new user_filters();
        $filters->students = $this->users[0]->id;
        $chart = new userpostschart($this->courseid, array('instructor' => true));
        $data = $chart->ajax_get_data($filters);
        $this->assertNotEmpty($data);

        $filters = new user_filters();
        $filters->criteria = array($this->create_criterion('grade', $this->gradeitems[0]->id, filter::EQUAL, 4));
        $chart = new completionsearchchart($this->courseid, array('instructor' => true));
        $data = $chart->ajax_get_data($filters);
        // Should give back 3 students.
        $this->assertEquals(count($data), 3);

        $filters = new user_filters();
        $filters->criteria = array($this->create_criterion('grade', $this->gradeitems[0]->id, filter::EQUAL, 4));
        $chart = new scheduledcriteriachart($this->courseid, array('instructor' => true));
        $data = $chart->ajax_get_data($filters);
        // Same chart: should give back 3 students.
        $this->assertEquals(count($data), 3);

        $filters = new user_filters();
        $chart = new gradechart($this->courseid, array('instructor' => true));
        $data = $chart->ajax_get_data($filters);
        // Same chart: should give back students matching the number of users.
        $this->assertEquals(count($data[0]), self::NUMBER_OF_USERS);

    }

    /**
     * This tests the get_chart_title() function of the various charts.
     */
    public function test_get_chart_title() {
        global $USER;

        // Test 1: activitychart -> student view, all students, single student.
        $this->setAdminUser();
        $filters = new user_filters();
        $chart = new activitychart($this->courseid, array('instructor' => false));
        $title = $chart->get_title($filters);
        $username = $USER->firstname . ' ' . $USER->lastname;
        $this->assertEquals($title, get_string('titlestudent', 'report_analytics') . $username);

        $chart = new activitychart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('titleall', 'report_analytics'));
        $filters->students = $this->users[0]->id;
        $chart = new activitychart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('titleselected', 'report_analytics'));

        // Test 2: activitytimelinechart -> student view, all students, student + group.
        $filters = new user_filters();
        $chart = new activitytimelinechart($this->courseid, array('instructor' => false));
        $title = $chart->get_title($filters);
        $username = $USER->firstname . ' ' . $USER->lastname;
        $this->assertEquals($title, get_string('titlestudent', 'report_analytics') . $username);

        $chart = new activitytimelinechart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('titleall', 'report_analytics'));
        $filters->students = array($this->users[0]->id, 'g' . $this->groups[0]->id);
        $chart = new activitytimelinechart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('titleselected', 'report_analytics'));

        // Test 3: forumchart -> student view, all students, single student.
        $this->setAdminUser();
        $filters = new user_filters();
        $chart = new forumchart($this->courseid, array('instructor' => false));
        $title = $chart->get_title($filters);
        $username = $USER->firstname . ' ' . $USER->lastname;
        $this->assertEquals($title, get_string('titlestudent', 'report_analytics') . $username);

        $chart = new forumchart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('titleall', 'report_analytics'));
        $filters->students = $this->users[0]->id;
        $chart = new forumchart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('titleselected', 'report_analytics'));

        // Test 4: forumtimelinechart -> student view, all students, student + group.
        $filters = new user_filters();
        $chart = new forumtimelinechart($this->courseid, array('instructor' => false));
        $title = $chart->get_title($filters);
        $username = $USER->firstname . ' ' . $USER->lastname;
        $this->assertEquals($title, get_string('titlestudent', 'report_analytics') . $username);

        $chart = new forumtimelinechart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('titleall', 'report_analytics'));
        $filters->students = array($this->users[0]->id, 'g' . $this->groups[0]->id);
        $chart = new forumtimelinechart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('titleselected', 'report_analytics'));

        // Test 5: userpostschart -> student view, single student (no groups).
        $filters = new user_filters();
        $chart = new userpostschart($this->courseid, array('instructor' => false));
        $title = $chart->get_title($filters);
        $username = $USER->firstname . ' ' . $USER->lastname;
        $this->assertEquals($title, get_string('titlestudent', 'report_analytics') . $username);

        $filters = new user_filters();
        $chart = new userpostschart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('titleall', 'report_analytics'));
        $filters->students = $this->users[0]->id;
        $chart = new userpostschart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('titleselected', 'report_analytics'));

        // Test 6: completionsearchchart -> only a single title is used.
        $filters = new user_filters();
        $chart = new completionsearchchart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('usercriteriatitle', 'report_analytics'));

        // Test 7: scheduledcriteriachart -> only a single title is used.
        $filters = new user_filters();
        $chart = new scheduledcriteriachart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('usercriteriatitle', 'report_analytics'));

        // Test 8: gradechart -> default titles are used.
        $this->setAdminUser();
        $filters = new user_filters();
        $chart = new gradechart($this->courseid, array('instructor' => false));
        $title = $chart->get_title($filters);
        $username = $USER->firstname . ' ' . $USER->lastname;
        $this->assertEquals($title, get_string('titlestudent', 'report_analytics') . $username);

        $chart = new gradechart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('titleall', 'report_analytics'));
        $filters->students = $this->users[0]->id;
        $chart = new gradechart($this->courseid, array('instructor' => true));
        $title = $chart->get_title($filters);
        $this->assertEquals($title, get_string('titleselected', 'report_analytics'));
    }

    /**
     * This tests the get_chart_info() method that is used to create the graph
     * selector for users.
     */
    public function test_get_chart_info() {

        $this->info_test('ActivityChart');
        $this->info_test('ActivityTimelineChart');
        $this->info_test('ForumChart');
        $this->info_test('ForumTimelineChart');
        $this->info_test('UserPostsChart');
        $this->info_test('CompletionSearchChart');
        $this->info_test('ScheduledCriteriaChart');
        $this->info_test('GradeChart');
    }

    /**
     * Tests the info retrieved by get_chart_info() to see if it matches the
     * standard format.  'graphtype' is the cap-case version, 'id' is in lowercase
     * and the name should be named with the lang string lowercase + name.
     *
     * @param  string  $graphtype  the cap-case version of the graph name
     */
    private function info_test($graphtype) {

        $filters = new user_filters();
        $classname = '\\report_analytics\\' . strtolower($graphtype);;
        $chart = new $classname($this->courseid, array('instructor' => true));
        $info = $chart->get_chart_info($filters);
        $this->assertEquals($info['id'], strtolower($graphtype));
        $this->assertEquals($info['label'], get_string(strtolower($graphtype) . 'name', 'report_analytics'));
        $this->assertEquals($info['value'], $graphtype);
    }

    /**
     * Test the word cloud binning function to ensure that words are counted correctly.
     */
    public function test_word_cloud() {

        $this->setAdminUser();
        $filters = new user_filters();
        $filters->students = $this->users[0]->id;
        $chart = new userpostschart($this->courseid, array('instructor' => true));
        $actual = $chart->word_cloud($filters);
        $expected = array('posting' => 2, 'strangely' => 2, 'six' => 2, 'word' => 2, 'post' => 2, 'message' => 2,
            'discussion' => 2, '1' => 1, '2' => 1);
        $this->assertEquals($expected, $actual);
    }

}
