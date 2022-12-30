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
 * report_activities class
 *
 * This class retrieves all data from the database and returns it to the caller
 * using a series of public functions.
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_activities {

    /** @var int the course id. */
    protected $courseid;
    /** @var array filters to be used to generate the report */
    protected $filters;
    /** @var object the class to perform the DB query. */
    protected $querymanager;
    /** @const number of months in year. */
    const MONTHS_IN_YEAR = 12;
    /** @const number of months in year. */
    const DEFAULT_MONTHS = 4;
    /** @const top/bottom % of class to use for averages. */
    const AVERAGE_PERCENT = 0.15;

    /**
     * Analytics report constructor.
     *
     * Retrieve events log data to be used by other methods.
     *
     * @param  int    $courseid  the ID of the course
     * @param  array  $filters   the various filters from javascript (student, activity, etc).
     */
    public function __construct($courseid, $filters) {

        $this->courseid = $courseid;
        $this->filters = $filters;
    }

    /**
     * Get event count for each activity or activity class in a particular course.
     *
     * @return array  activity class data {label, reads, writes} for each activity/class
     */
    public function get_events_by_activity() {

        $activities = $this->check_activity_list($this->filters->activities);

        if (!is_array($this->filters->action)) {
            $this->filters->action = array($this->filters->action);
        }
        $this->set_query_manager();
        $events = $this->querymanager->get_activity_data($activities, $this->filters);

        if (isset($this->filters->average) && $this->filters->average != 'none') {
            $oldstudents = $this->filters->students;
            $this->filters->students = $this->get_average_students();
            $averageevents = $this->querymanager->get_activity_data($activities, $this->filters);
            $this->merge_average($events, $averageevents, count($this->filters->students));
            $this->filters->students = $oldstudents;
        }

        return $events;
    }

    /**
     * If the list of activities is blank, set an appropriate default.
     *
     * @param  array  $activities The master list of activities or classes to retrieve.
     * @return array  updated list of activities, using activity classes if nothing specified
     */
    protected function check_activity_list($activities) {

        if (empty($activities)) {
            $activityfilter = new activityfilter($this->courseid);
            $activities = $activityfilter->get_default_activities();
        }
        return $activities;
    }

    /**
     * Sets the query manager.  If a base action type is found, then we use the
     * non-event based query manager.  Otherwise, user has requested events, so use
     * the event based query manager.
     */
    protected function set_query_manager() {

        $baseactions = array('a', 'r', 'w');
        foreach ($this->filters->action as $action) {
            if (array_search($action, $baseactions) !== false) {
                $this->querymanager = new activity_query($this->courseid, array('events' => false));
                return;
            }
        }
        $this->querymanager = new activity_query($this->courseid, array('events' => true));
        return;
    }

    /**
     * Get a list of students to perform the average aggregations for.  Check and return
     * the top or bottom 15% first, otherwise, return a list of all students.
     *
     * @return array  the user IDs of the students to perform average aggregations for
     */
    private function get_average_students() {

        $studentfilter = new studentfilter($this->courseid, array('gradesort' => true));
        $students = array_keys($studentfilter->get_all_students());
        $partial = round(self::AVERAGE_PERCENT * count($students));
        if ($this->filters->average == averagefilter::TOP15) {
            return array_slice($students, 0, $partial);
        }
        if ($this->filters->average == averagefilter::BOTTOM15) {
            return array_slice($students, -$partial);
        }
        return $students;
    }

    /**
     * Merges the results from two activity arrays.  It is assumed that array1 has
     * all of the keys.  Additionally, the result is stored in array1 (passed by ref).
     *
     * @param  array  $array1  activity results (passed by reference)
     * @param  array  $array2  average activity results
     * @param  array  $n       total number of students
     */
    protected function merge_average(&$array1, $array2, $n) {

        $ignorekeys = array('label', 'type', 'time');
        foreach ($array1 as &$a1) {
            $label = $a1['label'];
            // Find corresponding entry in $array2.
            foreach ($array2 as &$a2) {
                if ($a2['label'] == $label) {
                    // Add new keys to separate array so that the loop doesn't continue forever.
                    $averages = array();
                    foreach ($a1['values'] as $key => $value) {
                        if (array_search($key, $ignorekeys) === false) {
                            $averagekey = get_string('average', 'report_analytics') . ' ' . $key;
                            $averages[$averagekey] = new \stdClass();
                            $averages[$averagekey]->name = $averagekey;
                            $averages[$averagekey]->value = $a2['values'][$key]->value / $n;
                        }
                    }
                    $a1['values'] = array_merge($a1['values'], $averages);
                }
            }
        }
    }

    /**
     * Get data for a user (or group) by activity for over time.
     *
     * @return array  user data {labels (as array), monthly data sorted by activity (as array)}
     */
    public function get_monthly_user_activity_data() {

        $af = new actionfilter($this->courseid);
        $actionlabel = $af->get_action_label($this->filters->action);
        $data = array();

        $originaldate = $this->filters->date;
        if (!isset($this->filters->date)) {
            $this->filters->date = $this->get_default_dates();
        }
        $dates = $this->create_bins();

        foreach ($dates as $datekey => $datevalue) {
            $this->filters->date = $datevalue;
            $result = $this->get_events_by_activity();
            foreach ($result as $r) {
                $data[] = array('label' => $r['label'], 'type' => $r['type'], 'date' => $datevalue->label,
                    'count' => $r['values'][$actionlabel]->value);
            }
        }
        $this->filters->date = $originaldate;
        return $data;
    }

    /**
     * This function creates the given number of bins over time, split into equally divided intervals.
     *
     * @return array  array of bins, each line as an array with {label, to, from}
     */
    protected function create_bins() {

        $from = \DateTime::createFromFormat('Y-m-d H:i', $this->filters->date->from)->getTimestamp();
        $to = \DateTime::createFromFormat('Y-m-d H:i', $this->filters->date->to)->getTimestamp();
        $diff = $to - $from;
        $step = floor($diff / $this->filters->bins);

        $dates = array();
        for ($i = 0; $i < $this->filters->bins; $i++) {
            $f = \DateTime::createFromFormat("U", $from + $i * $step);
            // Unix timestamp ignores timezone -> set manually.
            $f->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $t = \DateTime::createFromFormat("U", $from + ($i + 1) * $step);
            // The last bucket should be set to the end date: accommodates for floor() above.
            if ($i == ($this->filters->bins - 1)) {
                $t = \DateTime::createFromFormat("U", $to);
            }
            // Unix timestamp ignores timezone -> set manually.
            $t->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $temp = new \stdClass();
            // The label takes the initial timestamp.
            $temp->label = $f->format('Y-m-d H:i');
            $temp->from = $f->format('Y-m-d H:i');
            $temp->to = $t->format('Y-m-d H:i');
            $dates[] = $temp;
        }
        return $dates;
    }

    /**
     * If not dates are specified, create the default start and end points here.
     *
     * @return array  pair of dates denoting start (from) and end (to)
     */
    protected function get_default_dates() {

        $year = intval(date("Y"));
        $month = intval(date("m"));
        $day = intval(date("d"));
        $fromyear = ($month < self::DEFAULT_MONTHS) ? intval($year - 1) : $year;
        $frommonth = ($month - self::DEFAULT_MONTHS + self::MONTHS_IN_YEAR) % self::MONTHS_IN_YEAR;
        $return = new \stdClass();
        $return->from = $fromyear . '-' . $frommonth . '-' . $day . ' 00:00';
        $return->to = $year . '-' . $month . '-' . $day . ' 23:59';
        return $return;
    }

}
