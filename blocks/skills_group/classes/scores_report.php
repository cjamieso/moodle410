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

namespace block_skills_group;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/mod/feedback/lib.php');

/**
 * scores_report class.
 *
 * This class holds the results of a particular student.  The results need to
 * be retrieved from a specified feedback activity.
 *
 * @package    block_skills_group
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scores_report {

    /** ID of the course */
    private $courseid;
    /** skills_group_settings object to hold feedback information */
    private $settings;
    /** Item scores */
    private $classscores = array();

    /**
     * This function stores the needed variables and computes the total skill
     * scores for a class.
     *
     * @param int $courseid The ID of the course.
     *
     */
    public function __construct($courseid) {

        $this->courseid = $courseid;
        $this->settings = new skills_group_setting($courseid);
    }

    /**
     * Gets the class scores for a particular set of items.  If multiple items are
     * specified, the results are added together.
     *
     * @param  int|array  $items  the item(s) to gather and return
     */
    public function get_class_scores($items) {

        if (!is_array($items)) {
            $items = array($items);
        }
        $this->bin_class_scores();
        $levels = $this->settings->get_feedback_levels();
        $offset = $this->settings->get_feedback_offset();
        $data = array();
        foreach ($levels as $l) {
            $data[] = array('value' => intval($l), 'label' => rtrim($l), get_string('pre', 'block_skills_group') => 0,
                get_string('post', 'block_skills_group') => 0);
        }
        foreach ($items as $item) {
            $this->format_scores(get_string('pre', 'block_skills_group'), $this->classscores['pre'][$item], $data);
            // Items are based on pre-course feedback -> ensure that it exists on post-course feedback.
            if (isset($this->classscores['post'][$item])) {
                $this->format_scores(get_string('post', 'block_skills_group'), $this->classscores['post'][$item], $data);
            }
        }
        return $data;
    }

    /**
     * Format a set of scores {'pre'|'post'} in a data structure that matches what
     * the graphing libraries expect.
     *
     * @param  string  $type    the type of scores to format (key in the data array)
     * @param  array   $scores  the scores to format
     * @param  array   $data    the data to give to d3js
     */
    private function format_scores($type, $scores, &$data) {

        foreach ($scores as $key => $score) {
            foreach ($data as &$d) {
                if ($d['value'] == $key) {
                    $d[$type] += $score;
                }
            }
        }
    }

    /**
     * Add two arrays and store the result in the second array.
     *
     * @param  array  $array1  the array containing the results to add
     * @param  array  $array2  the totals array to add the values into
     */
    private function add_to_array($array, &$totalsarray) {

        foreach ($array as $key => $count) {
            $totalsarray[$key] += $count;
        }
    }

    /**
     * Retrieve the scores for the entire class.
     */
    public function bin_class_scores() {

        // Wipe any old scores in case students have entered scores since last checking.
        if (!empty($this->classscores)) {
            $this->classscores = array();
        }
        $prefeedbackid = $this->settings->get_feedback_id('pre');
        $postfeedbackid = $this->settings->get_feedback_id('post');
        $students = $this->retrieve_students();
        foreach ($students as $student) {
            $sgstudent = new skills_group_student($this->courseid, $student->id);
            if (isset($prefeedbackid)) {
                $this->bin_scores('pre', $sgstudent);
            }
            if (isset($postfeedbackid)) {
                $this->bin_scores('post', $sgstudent);
            }
        }
        return $this->classscores;
    }

    /**
     * Parse the students object and convert to associative array.
     *
     * @param  object  $students  students object from database with {id, firstname, lastname}
     * @return array  student inforation as array [id] => [name]
     */
    private function parse_student_objects($students) {
        $studentarray = array();

        foreach ($students as $student) {
            $studentarray["$student->id"] = $student->firstname . ' ' . $student->lastname;
        }
        return $studentarray;
    }

    /**
     * Retrieve a list of all students from the database, ordered by lastname.
     *
     * @return array  database object containing student {ids, firstnames, and lastnames}
     */
    private function retrieve_students() {
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
     * Bins a set of scores according to their values.
     *
     * @param  string  $type       the type of scores to retrieve
     * @param  object  $sgstudent  the current student being processed
     */
    private function bin_scores($type, $sgstudent) {

        $newscores = $sgstudent->get_scores($type);
        if (!array_key_exists($type, $this->classscores)) {
            $this->classscores[$type] = array();
        }
        $levels = $this->settings->get_feedback_levels();
        foreach ($newscores as $key => $score) {
            if (empty($this->classscores[$type][$key])) {
                $this->classscores[$type][$key] = array_fill(intval($levels[0]), count($levels), 0);
            }
            // This acts as a safeguard against non-symmetric feedbacks.
            if ($score !== null && isset($this->classscores[$type][$key][intval($score)])) {
                $this->classscores[$type][$key][intval($score)]++;
            }
        }
    }

}