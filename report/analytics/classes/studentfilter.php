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
 * studentfilter class
 *
 * This class retrieves data from a particular course and generates the elements
 * necessary for an student filter.  This is composed of two things:
 *
 * 1) A list of all groups
 * 2) A list of all students
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentfilter extends filter {

    /** @var array default settings for options. */
    protected $optionsdefaults = array('gradesort' => false, 'groups' => true);

    /**
     * Set the gradesort option.
     *
     * @param  string  $gradesort  the desired type of grade sorting
     */
    public function set_gradesort($gradesort) {
        $this->options['gradesort'] = $gradesort;
    }

    /**
     * Returns student filter data.
     *
     * @return array  student filter data containing groups and students.
     */
    public function get_filter_data() {

        if ($this->options['groups'] === true) {
            return array(get_string('groups') => $this->get_all_groups(),
                        get_string('students') => $this->get_all_students());
        } else {
            return array(get_string('students') => $this->get_all_students());
        }
    }

    /**
     * Returns a list of all students in a course to be used by the filters.
     *
     * @return array  list of all students in the course ([userid] => name)
     */
    public function get_all_students() {
        $students = $this->parse_student_objects($this->retrieve_students());
        if ($this->options['gradesort'] == true) {
            $students = $this->sort_by_grades($students);
        }
        return $students;
    }

    /**
     * Sorts the list of students by their total course grade.
     *
     * @param  array  $students  array of students with values set to names
     * @return array  sorted array (via highest grades first) of students
     */
    private function sort_by_grades($students) {

        $userids = array_keys($students);
        $temp = grade_get_course_grades($this->courseid, $userids);
        $grades = $temp->grades;
        $gradesarray = array_map(function($o) {
            return $o->grade;
        }, $grades);
        arsort($gradesarray);
        return array_replace($gradesarray, $students);
    }

    /**
     * Retrieve a list of all students from the database, ordered by lastname.
     *
     * @return object  database object containing student {ids, firstnames, and lastnames}
     */
    protected function retrieve_students() {
        global $DB;

        $context = \context_course::instance($this->courseid);
        $params = array($context->id, 'student');
        $query = "SELECT DISTINCT {user}.id as id, firstname, lastname
                  FROM {role_assignments}
                  INNER JOIN {role} on {role}.id={role_assignments}.roleid
                  INNER JOIN {user} on {role_assignments}.userid={user}.id
                  WHERE contextid = ? and {role}.shortname = ?
                  ORDER BY lastname";
        return $DB->get_records_sql($query, $params);
    }

    /**
     * Parse the students object and convert to associative array.
     *
     * @param  object  $students  students object from database with {id, firstname, lastname}
     * @return array  student inforation as array [id] => [name]
     */
    protected function parse_student_objects($students) {
        $studentarray = array();

        foreach ($students as $student) {
            $studentarray["$student->id"] = $student->firstname . ' ' . $student->lastname;
        }
        return $studentarray;
    }

    /**
     * Returns a list of all groups in a course to be used by the filters.
     *
     * @return array  list of all groups in the course ([g + groupid] => name)
     */
    protected function get_all_groups() {
        return $this->parse_group_objects($this->retrieve_groups());
    }

    /**
     * Retrieve a list of all groups from the database.
     *
     * @return object  database object containing groups
     */
    protected function retrieve_groups() {
        global $DB;
        return $DB->get_records('groups', array('courseid' => $this->courseid), 'name');
    }

    /**
     * Parse the groups object and convert to associative array.
     *
     * @param  object $groups  groups object from database
     * @return array  group inforation as array [g + groupid] => [name]
     */
    protected function parse_group_objects($groups) {
        $grouparray = array();

        foreach ($groups as $group) {
            $grouparray["g$group->id"] = $group->name;
        }
        return $grouparray;
    }

    /**
     * This function checks for any groups in the encoded users list.  When a group is found, the users of that
     * group are queried, exploded, and added to the list.
     *
     * @param  int    $courseid  The ID of the course for which the instructor/student lists should be populated.
     * @param  array  $userlist  The list of users, formatted as it is when it comes from javascript.
     * @return array  the combined list of user IDs
     *
     */
    public function parse_groups($userlist) {
        global $DB;

        $newusers = array();
        $i = 0;

        foreach ($userlist as $u) {
            $temp = strval($u);

            if (substr($temp, 0, 1) == 'g') {
                $groupid = substr($temp, 1);
                $groupint = intval($groupid);
                $members = $DB->get_records('groups_members', array('groupid' => $groupint));

                foreach ($members as $mem) {
                    $newusers[$i] = $mem->userid;
                    $i++;
                }
            } else {
                $newusers[$i] = $u;
                $i++;
            }
        }

        $userids = array_unique($newusers);
        // Strip out the keys so they are not stored in the DB.
        return array_values($userids);
    }

}
