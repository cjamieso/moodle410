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

use report_analytics\report_activities;
use report_analytics\filter;
use report_analytics\analytics_helper;
use report_analytics\averagefilter;
use report_analytics\user_filters;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/report/analytics/tests/report_analytics_testcase.php');

/**
 * Test class for: report_activities.php
 *
 * The following functions are checked:
 * 1) get_events_by_activity()
 * 2) get_monthly_user_activity_data()
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_activities_class_testcase extends report_analytics_testcase {

    /**
     * @var String keep system default timezone.
     */
    protected $systemdefaulttimezone;

    /**
     * Setup a standard test and add the events data as well.
     *
     */
    protected function setUp(): void {

        parent::setUp();
        $this->add_events_data();
        $this->systemdefaulttimezone = date_default_timezone_get();
    }

    /**
     * This function tests the get_events_by_activity() function, which accepts a
     * variety of filters.  Iniitally, no filters are given, then each filter is
     * tried separately, and finally all three are tried at the same time.
     */
    public function test_get_events_by_activity() {

        date_default_timezone_set('America/Edmonton');
        $ac = 'activity_class';
        $a = 'activity';
        $s = 'section';

        $readstr = get_string('reads', 'report_analytics');
        $writestr = get_string('writes', 'report_analytics');
        $filters = new user_filters();
        $filters->action = array('r', 'w');
        $ra = new report_activities($this->courseid, $filters);

        // Test 1: no filters.
        $actual = $ra->get_events_by_activity();
        $expected = array(array('label' => 'All feedbacks', $readstr => 12, $writestr => 8, 'type' => $ac),
            array('label' => 'All files', $readstr => 18, $writestr => 0, 'type' => $ac));
        $this->check_for_results($expected, $actual);

        // Test 2: activity filter.
        $activities = array('mod_quiz', 'mod_feedback', 'core', 's1', 's2', $this->activities[0]->cmid, $this->activities[1]->cmid);
        $filters->activities = $activities;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_events_by_activity();
        $expected = array(array('label' => 'All feedbacks', $readstr => 12, $writestr => 8, 'type' => $ac),
            array('label' => 'System', $readstr => 58, $writestr => 36, 'type' => $ac),
            array('label' => get_section_name($this->courseid, 1), $readstr => 12, $writestr => 8, 'type' => $s),
            array('label' => $this->activities[0]->name, $readstr => 2, $writestr => 0, 'type' => $a),
            array('label' => $this->activities[1]->name, $readstr => 10, $writestr => 8, 'type' => $a));
        $this->check_for_results($expected, $actual);

        // Test 3: user filter.
        $students = array('g' . $this->groups[0]->id, $this->users[6]->id, $this->users[7]->id, $this->users[8]->id);
        $filters->activities = null;
        $filters->students = $students;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_events_by_activity();
        $expected = array(array('label' => 'All feedbacks', $readstr => 4, $writestr => 3, 'type' => $ac),
            array('label' => 'All files', $readstr => 7, $writestr => 0, 'type' => $ac));
        $this->check_for_results($expected, $actual);

        // Test 4: date filter.
        $date = new stdClass();
        $date->from = '2015-05-18 00:00';
        $date->to = '2015-06-08 00:00';
        $filters->activities = null;
        $filters->students = null;
        $filters->date = $date;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_events_by_activity();
        $expected = array(array('label' => 'All feedbacks', $readstr => 12, $writestr => 8, 'type' => $ac),
            array('label' => 'All files', $readstr => 13, $writestr => 0, 'type' => $ac));
        $this->check_for_results($expected, $actual);

        // Test 5: all three filters.
        $filters->activities = $activities;
        $filters->students = $students;
        $filters->date = $date;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_events_by_activity();
        $expected = array(array('label' => 'All feedbacks', $readstr => 4, $writestr => 3, 'type' => $ac),
            array('label' => 'System', $readstr => 19, $writestr => 8, 'type' => $ac),
            array('label' => get_section_name($this->courseid, 1), $readstr => 4, $writestr => 3, 'type' => $s),
            array('label' => $this->activities[0]->name, $readstr => 1, $writestr => 0, 'type' => $a),
            array('label' => $this->activities[1]->name, $readstr => 3, $writestr => 3, 'type' => $a));
        $this->check_for_results($expected, $actual);

        // Test 6: add unique entries option.
        $filters->unique = true;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_events_by_activity();
        $expected = array(array('label' => 'All feedbacks', $readstr => 3, $writestr => 3, 'type' => $ac),
            array('label' => 'System', $readstr => 6, $writestr => 3, 'type' => $ac),
            array('label' => get_section_name($this->courseid, 1), $readstr => 3, $writestr => 3, 'type' => $s),
            array('label' => $this->activities[0]->name, $readstr => 1, $writestr => 0, 'type' => $a),
            array('label' => $this->activities[1]->name, $readstr => 3, $writestr => 3, 'type' => $a));
        $this->check_for_results($expected, $actual);

        // Test 7: test for specific events.
        $filters->unique = false;
        $filters->date = null;
        $filters->action = array('\mod_feedback\event\response_submitted');
        $labelstr = get_string('pluginname', 'mod_feedback') . ' ' . get_string('eventresponsesubmitted', 'mod_feedback');
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_events_by_activity();
        $expected = array(array('label' => 'All feedbacks', $labelstr => 3, 'type' => $ac),
            array('label' => 'System', $labelstr => 0, 'type' => $ac),
            array('label' => get_section_name($this->courseid, 1), $labelstr => 3, 'type' => $s),
            array('label' => $this->activities[0]->name, $labelstr => 0, 'type' => $a),
            array('label' => $this->activities[1]->name, $labelstr => 3, 'type' => $a));
        $this->check_for_results($expected, $actual);
    }

    /**
     * Test the user filter with an empty group.  An exception will be generated.
     */
    public function test_get_events_by_activity_emptygroup() {

        date_default_timezone_set('America/Edmonton');

        $filters = new user_filters();
        $filters->action = array('r', 'w');
        $ra = new report_activities($this->courseid, $filters);
        $filters->students = 'g' . $this->groups[self::NUMBER_OF_GROUPS]->id;
        $this->expectException('Exception', get_string('emptygroup', 'report_analytics'));
        $ra = new report_activities($this->courseid, $filters);
        $ra->get_events_by_activity($filters);
    }

    /**
     * For each expected result, search for the lablled row in the actual results and
     * then verify that the two are the same.
     *
     * @param  array  $expected  expected results
     * @param  array  $actual    actual results
     */
    private function check_for_results($expected, $actual) {

        foreach ($expected as $e) {
            $label = $e['label'];
            foreach ($actual as $a) {
                if ($a['label'] == $label) {
                    $this->assertEquals($e['label'], $a['label']);
                    $this->assertEquals($e['type'], $a['type']);
                    foreach ($a['values'] as $values) {
                        $this->assertEquals($e[$values->name], $values->value);
                    }
                }
            }
        }
    }

    /**
     * This function tests the get_monthly_user_activity_date() function, which
     * accepts up to three filters.  In this case, the user filter is always required,
     * while the activity and date filters are optional.  The various cases are tested
     * below.
     */
    public function test_get_monthly_user_activity_data() {

        $corestr = get_string('allcore', 'report_analytics');
        $filestr = get_string('allfiles', 'report_analytics');
        $feedbackstr = get_string('allfeedbacks', 'report_analytics');

        date_default_timezone_set('America/Edmonton');
        $filters = new user_filters();
        $defaultactivities = array('mod_resource', 'core');
        $filters->action = 'a';
        // Use date filter for tests -> test data is from 2015.
        $filters->date = new stdClass();
        $filters->date->from = '2015-01-01 00:00';
        $filters->date->to = '2015-12-31 00:00';
        $ra = new report_activities($this->courseid, $filters);

        // Test 1: user filter (required).
        $filters->students = 'g' . $this->groups[0]->id;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_monthly_user_activity_data();
        $expected = array("2015-05-17 13:00" => array($filestr => 2), "2015-08-16 13:00" => array($filestr => 3));
        $this->check_timeline_results($expected, $actual);

        // Test 2: activity filter.
        $activities = array('mod_quiz', 'mod_feedback', 'core', 's1', 's2', $this->activities[0]->cmid, $this->activities[1]->cmid);
        $filters->activities = $activities;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_monthly_user_activity_data();
        $expected = array("2015-05-17 13:00" => array($corestr => 13), "2015-08-16 13:00" => array($corestr => 8),
            "2015-10-01 01:00" => array($corestr => 1));
        $this->check_timeline_results($expected, $actual);

        // Test 3: action filters.
        $filters->action = 'r';
        $filters->activities = $defaultactivities;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_monthly_user_activity_data();
        $expected = array("2015-05-17 13:00" => array($corestr => 10, $filestr => 2),
            "2015-08-16 13:00" => array($corestr => 3, $filestr => 3), "2015-10-01 01:00" => array($corestr => 1));
        $this->check_timeline_results($expected, $actual);
        $filters->action = 'w';
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_monthly_user_activity_data();
        $expected = array("2015-05-17 13:00" => array($corestr => 3), "2015-08-16 13:00" => array($corestr => 5));
        $this->check_timeline_results($expected, $actual);
        $filters->action = 'a';

        // Test 4: date filter.
        $date = new stdClass();
        $date->from = '2015-05-01 00:00';
        $date->to = '2015-05-31 00:00';
        $filters->date = $date;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_monthly_user_activity_data();
        $expected = array("2015-05-23 12:00" => array($corestr => 13, $filestr => 2));
        $this->check_timeline_results($expected, $actual);

        // Test 5: all three filters.
        $filters->activities = $activities;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_monthly_user_activity_data();
        $expected = array("2015-05-23 12:00" => array($corestr => 13));
        $this->check_timeline_results($expected, $actual);

        // Test 6: unique entries only.
        $filters->unique = true;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_monthly_user_activity_data();
        $expected = array("2015-05-23 12:00" => array($corestr => 3));
        $this->check_timeline_results($expected, $actual);

        // Test 7: check for events.
        $filters->unique = false;
        $filters->students = array('g' . $this->groups[2]->id);
        $filters->action = '\mod_feedback\event\response_submitted';
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_monthly_user_activity_data();
        $expected = array("2015-05-23 12:00" => array($feedbackstr => 3, get_string('sectionname', 'format_topics') . ' 1' => 3,
          $this->activities[1]->name => 3));
        $this->check_timeline_results($expected, $actual);
    }

    /**
     * Verifies that the expected array matches the actual array for a set of
     * timeline results.
     *
     * The 'expected' array only needs to list the expected non-zero results.
     * Any unspecified results are checked against zero.
     *
     * @param  array  $expected  list of expected non-zero results
     * @param  array  $actual    the full set of actual results
     */
    private function check_timeline_results($expected, $actual) {

        foreach ($actual as $a) {
            if (array_key_exists($a['date'], $expected)) {
                if (array_key_exists($a['label'], $expected[$a['date']])) {
                    $this->assertEquals($expected[$a['date']][$a['label']], $a['count']);
                    continue;
                }
            }
            $this->assertEquals(0, $a['count']);
        }
    }

    /**
     * Test the ability to show average aggregations alongside the regular aggregations.
     */
    public function test_filter_by_average() {

        $this->create_grades();
        date_default_timezone_set('America/Edmonton');

        $ac = 'activity_class';
        $readstr = get_string('reads', 'report_analytics');
        $writestr = get_string('writes', 'report_analytics');
        $averageread = get_string('average', 'report_analytics') . ' ' . get_string('reads', 'report_analytics');
        $averagewrite = get_string('average', 'report_analytics') . ' ' . get_string('writes', 'report_analytics');

        // Test 1: average across all students.
        $filters = new user_filters();
        $filters->action = array('r', 'w');
        $filters->average = averagefilter::ALL;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_events_by_activity();
        // Note: averages do not include admin user, only student values.
        $expected = array(array('label' => 'All feedbacks', $readstr => 12, $writestr => 8,
                $averageread => 9 / self::NUMBER_OF_USERS, $averagewrite => 8 / self::NUMBER_OF_USERS, 'type' => $ac),
            array('label' => 'System', $readstr => 120, $writestr => 112, $averageread => 70 / self::NUMBER_OF_USERS,
                $averagewrite => 48 / self::NUMBER_OF_USERS, 'type' => $ac),
            array('label' => 'All files', $readstr => 18, $writestr => 0, $averageread => 18 / self::NUMBER_OF_USERS,
                $averagewrite => 0 / self::NUMBER_OF_USERS, 'type' => $ac));
        $this->check_for_results($expected, $actual);

        // Test 2: top 15% of class (data for users 13 and 14).
        $filters->average = averagefilter::TOP15;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_events_by_activity();
        $expected = array(array('label' => 'All feedbacks', $readstr => 12, $writestr => 8, $averageread => 0.5,
                $averagewrite => 0.5, 'type' => $ac),
            array('label' => 'System', $readstr => 120, $writestr => 112, $averageread => 2, $averagewrite => 2, 'type' => $ac),
            array('label' => 'All files', $readstr => 18, $writestr => 0, $averageread => 0.5, $averagewrite => 0, 'type' => $ac));
        $this->check_for_results($expected, $actual);

        // Test 3: bottom 15% of class (data for users 3 and 4).
        $filters->average = averagefilter::BOTTOM15;
        $ra = new report_activities($this->courseid, $filters);
        $actual = $ra->get_events_by_activity();
        $expected = array(array('label' => 'All feedbacks', $readstr => 12, $writestr => 8, $averageread => 0, $averagewrite => 0,
                'type' => $ac),
            array('label' => 'System', $readstr => 120, $writestr => 112, $averageread => 5, $averagewrite => 3, 'type' => $ac),
            array('label' => 'All files', $readstr => 18, $writestr => 0, $averageread => 3, $averagewrite => 0, 'type' => $ac));
        $this->check_for_results($expected, $actual);
    }

    /**
     * Set timezone back to default.
     */
    protected function tearDown(): void {
        date_default_timezone_set($this->systemdefaulttimezone);
        parent::tearDown();
    }

}
