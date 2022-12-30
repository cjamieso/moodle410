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

/**
 * This is the main controller class that handles all of the ajax requests.
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_filters {

    /** Default number of bins for activity over time report */
    const DEFAULT_BINS = 16;
    /** @var array activities to retrieve data for */
    public $activities = null;
    /** @var array students to retrieve data for */
    public $students = null;
    /** @var object a grade condition if used instead of a student filter */
    public $grade = null;
    /** @var object the dates to retrieve data for {to, from} */
    public $date = null;
    /** @var string the type of average to display as a comparison */
    public $average = null;
    /** @var bool whether to generate unique (by student) results (true) or all results (false) */
    public $unique = false;
    /** @var int the number of time divisions to include */
    public $bins = self::DEFAULT_BINS;
    /** @var string|array action to retrieve data for */
    public $action = null;
    /** @var object min/max number of words to include in posts */
    public $words = null;
    /** @var array the criteria to filter users with */
    public $criteria = null;
    /** @var array settings needed to create the chart (for example: student vs. instructor). */
    protected $options;
    /** @var array default settings for options. */
    protected $optionsdefaults = array('instructor' => false);

    /**
     * Construct a user filters object from a set of filters.
     *
     * @param  object  $filters  the filters (json-decoded object)
     * @param  array   $options  options for the filters
     */
    public function __construct($filters = null, $options = array()) {

        $this->options = array_merge($this->optionsdefaults, $options);
        $types = array('activities', 'students', 'grade', 'date', 'average', 'unique', 'bins', 'action', 'words', 'criteria');
        foreach ($types as $type) {
            $this->set_filter($type, $filters);
        }
    }

    /**
     * Sets a filter based on the filter type.  The filters variable can be either
     * an object or an array.
     *
     * Note: student filter always set, even if result is empty.
     *
     * @param  string        $filtertype  the type of the filter to set
     * @param  array|object  $filters     the variable containing all of the filters
     */
    protected function set_filter($filtertype, $filters) {

        if (is_object($filters)) {
            $function = 'set_' . $filtertype;
            $temp = (isset($filters->$filtertype)) ? $filters->$filtertype : null;
            // Passing "null" is OK, the function itself will set a sensible default if needed.
            $this->$function($temp);
            return;
        }
        if (is_array($filters)) {
            $function = 'set_' . $filtertype;
            $temp = (isset($filters[$filtertype])) ? $filters[$filtertype] : null;
            // Passing "null" is OK, the function itself will set a sensible default if needed.
            $this->$function($temp);
        }
    }

    /**
     * Sets the activites filter.
     *
     * Currently, students can only see their own data in the various activities,
     * so there's not a substantial risk if a student were to request an invalid
     * activity.
     *
     * @param  array  $activities  activities to use in the filter
     */
    public function set_activities($activities) {
        if (isset($activities)) {
            $this->activities = $activities;
        }
    }

    /**
     * Sets the students filter.  This is forcibly set to the user's ID if they
     * are a student.
     *
     * @param  array  $students  students to use in the filter
     */
    public function set_students($students) {
        global $USER;

        if ($this->options['instructor'] === false) {
            $this->students = $USER->id;
            return;
        }
        if (isset($students)) {
            $this->students = $students;
        }
    }

    /**
     * Sets the grade filter.
     *
     * @param  array  $grade  the grade condition to use in the filter
     */
    public function set_grade($grade) {
        if (isset($grade)) {
            $this->grade = $grade;
        }
    }

    /**
     * Sets the date filter.  The date is validated prior to being set.
     *
     * @param  object  $date  the date to use in the filter
     * @throws Exception  if the date given by the user is not valid
     */
    public function set_date($date) {
        if (isset($date)) {
            if ($this->validate_date($date) === false) {
                throw new \Exception(get_string('baddate', 'report_analytics'));
            }
            $this->date = $date;
        }
    }

    /**
     * Validate the dates in an array.  Use DateTime to convert the date and check for its
     * validity.  The particular format is hardcoded.
     *
     * @param  array  $date  array containing all dates in 'Y-m-d H:i' format
     * @return boolean  T if date is valid, F otherwise
     */
    protected function validate_date($date) {

        $dates = array();
        if (isset($date->to)) {
            $dates[] = $date->to;
        }
        if (isset($date->from)) {
            $dates[] = $date->from;
        }
        foreach ($date as $d) {
            // Attempt conversion.
            $check = \DateTime::createFromFormat('Y-m-d H:i', $d);
            // Check for error and return false.
            if (!($check && $check->format('Y-m-d H:i') == $d)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Sets the average filter.
     *
     * @param  string  $average  the type of average to show as a comparison
     */
    public function set_average($average) {
        if (isset($average)) {
            $this->average = $average;
        }
    }

    /**
     * Sets the unique filter.
     *
     * @param  bool  $unique  should the filter show unique results instead?
     */
    public function set_unique($unique) {
        if (isset($unique)) {
            $this->unique = $unique;
        }
    }

    /**
     * Sets the bins/granularity filter.
     *
     * @param  int  $bins  the number of bins to use in the timeline report
     */
    public function set_bins($bins) {
        if (isset($bins)) {
            $this->bins = $bins;
        }
    }

    /**
     * Sets the action/events filter.
     *
     * @param  array  $action  the action/events to include in the report
     */
    public function set_action($action) {
        if (isset($action)) {
            $this->action = $action;
        }
    }

    /**
     * Sets the words (min/max) filter.
     *
     * @param  array  $words  the min/max number of words to allow in posts
     */
    public function set_words($words) {
        if (isset($words)) {
            $this->words = $words;
        } else {
            $this->words = new \stdClass();
            $this->words->minwords = 0;
            $this->words->maxwords = 9999;
        }
    }

    /**
     * Sets the criteria filter
     *
     * @param  array  $criteria  the criteria to filter users with
     */
    public function set_criteria($criteria) {
        if (isset($criteria)) {
            $this->criteria = $criteria;
        }
    }

}
