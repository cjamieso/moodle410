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
use report_analytics\user_filters;

defined('MOODLE_INTERNAL') || die();

/**
 * chart class
 *
 * This class is a base class used to add charts to the analytics main page.
 * It defines the base interface that is used.
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class chart {

    /** @var int the ID of the course that the filter should apply to. */
    protected $courseid;
    /** @var array default settings for options. */
    protected $optionsdefaults = array('instructor' => false, 'modtypes' => 'all');
    /** @var array settings needed to create the chart (for example: student vs. instructor). */
    protected $options;

    /**
     * Store the course ID for later use.
     *
     * @param  int    $courseid  the ID of the course to work with
     * @param  array  $options   options given to create the chart
     */
    public function __construct($courseid, $options = array()) {
        $this->courseid = $courseid;
        $this->options = array_merge($this->optionsdefaults, $options);
    }

    /** Retrieve chart data via ajax call */
    abstract public function ajax_get_data($filters);

    /**
     * Retrieve information about chart:
     * {id, paramname, label, description}
     *
     * Derived classes should update 'value' and 'sort' if desired
     *
     * @return array  the chart information
     */
    public function get_chart_info() {

        $classname = (new \ReflectionClass($this))->getShortName();
        return array('id' => $classname, 'paramname' => get_string($classname . 'name', 'report_analytics'),
            'label' => get_string($classname . 'name', 'report_analytics'), 'value' => $classname,
            'description' => get_string($classname . '_help', 'report_analytics'), 'sort' => 0);
    }

    /**
     * Returns the title of the graph based on the filters that were provided.
     *
     * @param  object  $filters  the filters to use to generate the chart
     * @return string the correct title for the graph
     */
    public function get_title($filters) {
        global $USER;

        $filters = $this->set_empty_filters_to_default($filters);
        if ($this->options['instructor']) {
            $filter = new studentfilter($this->courseid);
            $allstudents = $filter->get_all_students();
            if (!isset($filters->students)) {
                return get_string('titleall', 'report_analytics');
            }
            if (!is_array($filters->students)) {
                $filters->students = (array)$filters->students;
            }
            if (empty($filters->students) || (count($filters->students) == count($allstudents))) {
                return get_string('titleall', 'report_analytics');
            } else {
                return get_string('titleselected', 'report_analytics');
            }
        } else {
            return get_string('titlestudent', 'report_analytics') . $USER->firstname . ' ' . $USER->lastname;
        }
    }

    /**
     * Check the filters array and set to safe defaults if no value exists.
     *
     * An empty activity filter should be updated with a sensible default
     * (may depend on the chart).  If a grade condition is supplied, then
     * the list of students should be updated.  The action filter should be
     * updated based on the chart type that is being used.
     *
     * @param  object  $filters  the filters to use to generate the chart
     * @return object  the updated filters object
     */
    protected function set_empty_filters_to_default($filters) {

        if (isset($filters->grade)) {
            $filters->students = $this->get_students_with_gradecondition($filters->grade);
        }
        if (isset($filters) && empty($filters->activities)) {
            $activityfilter = new activityfilter($this->courseid, array('types' => $this->options['modtypes']));
            $filters->activities = $activityfilter->get_default_activities();
        }
        if (isset($filters) && empty($filters->action)) {
            if ($this->options['modtypes'] === 'all') {
                $filters->action = array('r', 'w');
            } else {
                $af = new actionfilter($this->courseid, array('types' => $this->options['modtypes']));
                $filters->action = array_keys($af->get_filter_data());
            }
        }
        return $filters;
    }

    /**
     * Gets an array of students matching the specified grade condition
     *
     * @param  array       $condition  the grade condition {operand, operator, value}
     * @throws \Exception  if no students found, generate an exception
     * @return the students matching the grade condition
     */
    protected function get_students_with_gradecondition($condition) {

        $gradefilter = new gradefilter($this->courseid);
        $studentfilter = new studentfilter($this->courseid);
        $students = $gradefilter->filter_userids_by_condition($condition, array_keys($studentfilter->get_all_students()));
        if (empty($students)) {
            throw new \Exception(get_string('nousers', 'report_analytics'));
        }
        return $students;
    }

}
