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

define('ACTIVITY_TYPES_INDEX', 0);
define('SECTIONS_INDEX', 1);
define('ACTIVITY_INDEX', 2);

/**
 * filter class
 *
 * This class retrieves all data from the database and returns it to the caller
 * using a series of public functions.
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class filter {

    /** @var int the ID of the course that the filter should apply to. */
    protected $courseid;
    /** @var array default settings for options. */
    protected $optionsdefaults = array();
    /** @var array settings for creating the data. */
    protected $options;
    /** @const text mapping for less than */
    const LESS_THAN = 'lt';
    /** @const text mapping for equal to */
    const EQUAL = 'eq';
    /** @const text mapping for greater than */
    const GREATER_THAN = 'gt';

    /**
     * Store the course ID and options for later use.
     *
     * @param  int    $courseid  the ID of the course to work with
     * @param  array  $options   options for generating the filter
     */
    public function __construct($courseid, $options = array()) {

        $this->courseid = $courseid;
        $this->options = array_merge($this->optionsdefaults, $options);
    }

    /** Retrieve filter data */
    abstract protected function get_filter_data();

    /**
     * Return a list of valid operators for student->grade comparison.
     *
     * It might make sense to also add less than & equal, as well as greater
     * than & equal, but I've omitted them for the first build.
     *
     * @return array  valid operators for student->grade comparison
     */
    public function get_operator_data() {
        return array(self::LESS_THAN => get_string('lessthan', 'report_analytics'),
            self::EQUAL => get_string('equal', 'report_analytics'),
            self::GREATER_THAN => get_string('greaterthan', 'report_analytics'));
    }

    /**
     * Checks a condition to see if the user meets the condition.
     *
     * @param  integer  $uservalue  this value (for the current user) is checked against the condition value
     * @param  string   $operator   the operator {lt, eq, gt} to apply to the two values
     * @param  integer  $value      the condition's value
     * @return bool  T if user meets condition, F if user does not
     */
    public function check_condition($uservalue, $operator, $value) {

        if (!isset($uservalue)) {
            return false;
        }
        if ($operator == self::LESS_THAN && $uservalue < $value) {
            return true;
        } else if ($operator == self::EQUAL && $uservalue == $value) {
            return true;
        } else if ($operator == self::GREATER_THAN && $uservalue > $value) {
            return true;
        }
        return false;
    }

}
