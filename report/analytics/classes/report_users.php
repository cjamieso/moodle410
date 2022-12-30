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

namespace report_analytics;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');

/**
 * report_users class
 *
 * This class generates data for users in a course based by returning their current
 * final grade and total number of actions.
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_users {

    /** @var int the course id. */
    protected $courseid;
    /** @var array filters to be used to generate the report */
    protected $filters;

    /**
     * Users report constructor.  Save courseid and filters for later use.
     *
     * @param  int    $courseid  the ID of the course
     * @param  array  $filters   the various filters from javascript (student, activity, etc).
     */
    public function __construct($courseid, $filters) {

        $this->courseid = $courseid;
        $this->filters = $filters;
    }

    /**
     * Get the data for the selected users.
     *
     * @return array  {user scatter points, range for events, range for grades}
     */
    public function get_user_data() {

        $userpoints = $this->generate_user_points();
        $studentfilter = new studentfilter($this->courseid);
        $students = $studentfilter->get_all_students();
        $eventrange = $this->get_course_event_range(array_keys($students));
        $graderange = $this->get_course_grade_range(array_keys($students));
        return array($userpoints, $eventrange, $graderange);
    }

    /**
     * Generate a point for each requested user to be plotted on a scatter plot.
     *
     * @return array  {name, grade, actions}
     */
    protected function generate_user_points() {
        global $CFG;

        $oldstudents = $this->filters->students;
        $studentfilter = new studentfilter($this->courseid);
        if (!isset($this->filters->students)) {
            $students = $studentfilter->get_all_students();
            $this->filters->students = array_keys($students);
            asort($this->filters->students);
        } else {
            if (!is_array($this->filters->students)) {
                $this->filters->students = array($this->filters->students);
            }
            $this->filters->students = $studentfilter->parse_groups($this->filters->students);
        }
        $grades = $this->get_course_grades_by_user($this->filters->students);
        $events = $this->get_course_events_by_user($this->filters->students);
        require_once($CFG->dirroot . "/user/lib.php");
        $users = user_get_users_by_id($this->filters->students);
        $userpoints = array();
        foreach ($this->filters->students as $studentid) {
            $username = $users[$studentid]->firstname . ' ' . $users[$studentid]->lastname;
            $grade = (isset($grades[$studentid]->grade)) ? floatval($grades[$studentid]->grade) : 0;
            $actions = (isset($events[$studentid]->count)) ? intval($events[$studentid]->count) : 0;
            $userpoints[] = array('name' => $username, 'grade' => $grade, 'actions' => $actions);
        }
        $this->filters->students = $oldstudents;
        return $userpoints;
    }

    /**
     * Gets the range of events (by user) in the course.
     *
     * @throws \Exception  if no students in course, generate exception
     * @return array  The course event range (min, max)
     */
    protected function get_course_event_range($userids) {

        $results = $this->get_course_events_by_user($userids);
        if (count($results) == 0) {
            throw new \Exception('no students in course');
        }
        $range = array('min' => null, 'max' => null);
        foreach ($results as $result) {
            $this->update_range($result->count, $range);
        }
        $range['min'] = intval($range['min']);
        $range['max'] = intval($range['max']);
        return $range;
    }

    /**
     * Gets the total number of events (per user) in the course.
     *
     * @param  array  $userids  the userids to fetch events for
     * @return object  the total number of events per user
     */
    protected function get_course_events_by_user($userids) {
        global $DB;

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userid');
        $userparams['courseid'] = $this->courseid;
        $query = "SELECT userid, count(*)
                  FROM {logstore_standard_log}
                  WHERE courseid = :courseid AND userid " . $usersql .
                  "GROUP BY userid";

        return $DB->get_records_sql($query, $userparams);
    }

    /**
     * Gets the range of final grades (by user) in the course.
     *
     * @param  array  $userids  the userids to fetch grades for
     * @return array  the course grade range (min, max)
     */
    protected function get_course_grade_range($userids) {
        $grades = $this->get_course_grades_by_user($userids);
        $range = array('min' => null, 'max' => null);
        foreach ($grades as $grade) {
            $this->update_range($grade->grade, $range);
        }
        $range['min'] = floatval($range['min']);
        $range['max'] = floatval($range['max']);
        return $range;
    }

    /**
     * Gets the final grades (by user) in the course.
     *
     * @param  array  $userids  the userids to fetch grades for
     *
     * @return array  course final grades by user ([userid]->grade has the result)
     */
    protected function get_course_grades_by_user($userids) {
        $temp = grade_get_course_grades($this->courseid, $userids);
        return $temp->grades;
    }

    /**
     * Update the range parameter based on the new set of values.
     *
     * @param  integer  $value  the new value to inspect
     * @param  integer  $range  the existing value range (passed by reference)
     */
    protected function update_range($value, &$range) {

        if ($range['min'] === null) {
            $range['min'] = $value;
        }
        if ($range['max'] === null) {
            $range['max'] = $value;
        }
        if ($value < $range['min']) {
            $range['min'] = $value;
        } else if ($value > $range['max']) {
            $range['max'] = $value;
        }
    }
}
