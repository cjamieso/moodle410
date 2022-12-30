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
require_once($CFG->dirroot . '/grade/lib.php');

/**
 * gradefilter class
 *
 * This class retrieves all grade items from a course so that they can be
 * displayed in a dropdown.
 *
 * It is also responsible for returning a list of operators that can be
 * applied to the grade items.
 *
 * Additionally, a grade condition can be applied to all students in the
 * class.
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradefilter extends filter {

    /** @var string characters to replace in grade filter strings. */
    protected $replace = "/[^\w+\- ']/";

    /**
     * Returns grade filter data.
     *
     * @return array  an array containing a list of grades used in the course
     */
    public function get_filter_data() {
        return $this->get_course_grades();
    }

    /**
     * Retrieve the grades tree structure (used by grade related reports) and
     * iterate through it to retrieve a list of grades and their associated IDs
     * suitable for presentation in a drop-down menu.
     *
     * @return array  an array containing a list of grades used in the course
     */
    protected function get_course_grades() {

        $grades = array();

        $gs = new \grade_seq($this->courseid, true);
        foreach ($gs->elements as $id => $gradeitem) {
            $grades[$id] = $this->get_grade_name($gradeitem['object']);
        }
        return $grades;
    }

    /**
     * Gets the name of a grade item.
     *
     * @param  object  $gradeitem  the grade item
     * @return string  the name of the grade item
     */
    protected function get_grade_name($gradeitem) {

        if ($gradeitem->is_category_item()) {
            $container = $gradeitem->load_item_category();
            $name = $container->get_name();
        } else {
            $name = $gradeitem->get_name();
        }
        return $name;
    }

    /**
     * Apply a given grade condition to students in the course.  By default, the
     * condition is checked against all students, but a list of students may
     * optionally be passed to narrow the search.
     *
     * @param  array       $condition  the grade condition {operand, operator, value}
     * @param  array       $userids    a list of user IDs for the condition to be checked against
     * @throws \Exception  invalid scale text
     * @return array  list of users satisfying given grade condition
     */
    public function filter_userids_by_condition($condition, $userids) {

        $users = array();
        if (is_array($userids) && count($userids) == 0) {
            return $users;
        }
        $gi = new \grade_item(array('id' => $condition->operand, 'courseid' => $this->courseid));
        $value = $this->get_grade_value($condition, $gi);
        $grades = \grade_grade::fetch_users_grades($gi, $userids);

        foreach ($grades as $userid => $grade) {
            $uservalue = ($gi->gradetype == GRADE_TYPE_TEXT) ? strip_tags($grade->feedback) : $grade->finalgrade;
            if ($this->check_condition($uservalue, $condition->operator, $value) === true) {
                $users[] = $userid;
            }
        }
        return $users;
    }

    /**
     * Retrieves the grade value from the given grade item.
     *
     * @param  array       $condition  the grade condition {operand, operator, value}
     * @param  object      $gi         the grade item to retrieve the value for
     * @throws \Exception  if scale entry text not found, throw error
     * @return string      the value of the grade item
     */
    protected function get_grade_value($condition, $gi) {

        if ($gi->gradetype == GRADE_TYPE_SCALE) {
            $scale = $gi->load_scale();
            $temp = preg_replace($this->replace, "", trim($condition->value));
            $value = array_search($temp, $scale->scale_items);
            if ($value === false) {
                throw new \Exception(get_string('invalidscaletext', 'report_analytics'));
            } else {
                // Moodle offsets the saved scale values by 1, accommodate for that after checking for false.
                $value++;
            }
        } else {
            $value = preg_replace($this->replace, "", trim($condition->value));
        }
        return $value;
    }

    /**
     * Format a grade condition in a manner that is suitable to display to a user.
     *
     * @param  array  $condition  the grade condition {operand, operator, value}
     * @return string  grade condition represented as a string
     */
    public function format_condition($condition) {

        $gi = new \grade_item(array('id' => $condition->operand, 'courseid' => $this->courseid));
        $operators = $this->get_operator_data();
        $operatorstring = $operators[$condition->operator];
        return $this->get_grade_name($gi) . ' ' . $operatorstring . ' ' . $this->get_grade_value($condition, $gi);
    }

}
