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
 * activity_query class
 *
 * This class performs a DB query to retrieve data about activities. The 'crud'
 * field is used for view/interactions, while the 'eventname' field is used for
 * queries looking for specifid events.
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_query {

    /** @var int the course id. */
    protected $courseid;
    /** @var \core\log\sql_SELECT_reader instance. */
    protected $logreader;
    /** @var  string Log reader table name. */
    protected $logtable;
    /** @var  array options for generating the report. */
    protected $options;
    /** @var  array default options for generating the report. */
    protected $optionsdefaults = array('time' => true, 'events' => false);
    /** @var string the type of query being performed. */
    protected $querytype;

    /**
     * Analytics report constructor.
     *
     * Retrieve events log data to be used by other methods.
     *
     * @param  int    $courseid  the ID of the course
     * @param  array  $options   options used to perform the query
     */
    public function __construct($courseid, $options = array()) {

        $this->courseid = $courseid;

        $logreader = get_log_manager()->get_readers();
        $logreader = reset($logreader);
        $this->logreader = $logreader;
        $this->options = array_merge($this->optionsdefaults, $options);
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            $this->options['time'] = false;
            $this->logtable = 'logstore_standard_log';
        } else {
            $this->logtable = $logreader->get_internal_log_table_name();
        }
        $this->querytype = ($this->options['events'] === true) ? 'eventname' : 'rw';
    }

    /**
     * Gets the activity data.
     *
     * @param  array  $items    the master list of components or activities to retrieve
     * @param  array  $filters  the other filters supplied by the user
     * @return array  the overall event data for javascript
     */
    public function get_activity_data($items, $filters) {

        $sorted = $this->bin_activities($items);
        $events = $this->create_events_list($items, $filters->action);
        // Retrieve activity data first.
        $this->build_query($sorted['activity'], 'activity', $filters, $events);
        $this->build_query($sorted['activity_class'], 'activity_class', $filters, $events);
        foreach ($sorted['section'] as $section) {
            $this->build_query($section, 'section', $filters, $events);
        }
        return array_values($events);
    }

    /**
     * Sort the list of requested activity entries into the three major types that exist.
     * The user may request activities, activity classes, or sections.
     *
     * @param  array  $activities  the master list of components or activities to retrieve
     * @return array  list of activities sorted into the three types {activity|activity_class|section}
     */
    private function bin_activities($activities) {

        $sorted = array('activity' => array(), 'activity_class' => array(), 'section' => array());
        foreach ($activities as $activity) {
            if (is_numeric($activity)) {
                $sorted['activity'][] = $activity;
            } else {
                $temp = strval($activity);
                if (substr($temp, 0, 1) == 's') {
                    $sorted['section'][] = $activity;
                } else {
                    $sorted['activity_class'][] = $activity;
                }
            }
        }
        return $sorted;
    }

    /**
     * Create an array full of placeholder zero values (for each action/event requested) for each
     * entry in the list of activities.  These placeholder values also include the label and
     * type of the activity entry.
     *
     * @param  array  $items    the master list of components or activities to retrieve
     * @param  array  $actions  array of action(s) to retrieve
     * @return array  placeholder array filled with the action/event fields for each activity set to 0
     */
    private function create_events_list($items, $actions) {

        $events = array();
        $actionfilter = new actionfilter($this->courseid);
        $activityfilter = new activityfilter($this->courseid);

        foreach ($items as $item) {
            $label = $activityfilter->get_label($item);
            $type = $activityfilter->get_type($item);
            $events[$label] = array('label' => $label, 'type' => $type);
            $events[$label]['values'] = array();
            foreach ($actions as $action) {
                $actionlabel = $actionfilter->get_action_label($action);
                $action = new \stdClass();
                $action->name = $actionlabel;
                $action->value = 0;
                $events[$label]['values'][$actionlabel] = $action;
            }
        }
        return $events;
    }

    /**
     * Get event count of reads/writes for a list of activities or activity classes.
     *
     * Note: there's a sample IN query found in \lib\accesslib.php, function get_assignable_roles()
     *
     * @param  array  $items    the master list of components or activities to retrieve
     * @param  string $type     the type of query being performed {activity|activity_class|section}
     * @param  array  $filters  the various filters from javascript (student, activity, etc).
     * @param  array  $events   activity class data {label, reads, writes} for each activity/class
     */
    protected function build_query($items, $type, $filters, &$events) {

        if (empty($items)) {
            return;
        }
        list($sqlselectfrom, $sqlgroup, $params) = $this->build_basic_query($items, $type, $filters);
        if (isset($sqlselectfrom)) {
            list($sqlstudent, $userparams) = $this->build_student_query($filters);
            $params = array_merge($params, $userparams);
            list($sqlaction, $crudparams) = $this->build_action_query($filters);
            $params = array_merge($params, $crudparams);
            list($sqldate, $dateparams) = $this->build_date_query($filters);
            $params = array_merge($params, $dateparams);
            $sqlselectfromwhere = $sqlselectfrom . $sqlstudent . $sqlaction . $sqldate;
            $start = microtime(true);
            $results = $this->query_db($sqlselectfromwhere, $sqlgroup, $params);
            $end = microtime(true);
            $time = ($this->options['time'] === true) ? $end - $start : null;
            $this->aggregate($results, $items, $type, $time, $events);
        }
    }

    /**
     * Build a basic DB query based on the type of request.  The query is split into
     * two parts -> so that student, action, and date filters can be spliced in.
     * The info portion contains both the label and the type of query that was created.
     * The type can be: {activity|activity class|section}
     *
     * @param  string  $items    the desired activity info to retrieve
     * @param  string  $type     the type of query being performed {activity|activity_class|section}
     * @param  array   $filters  the various filters from javascript (student, activity, etc).
     * @return array  {selectfromwhere clause, groupbyclause, DB params}
     */
    protected function build_basic_query($items, $type, $filters) {
        global $DB;

        list($ids, $field) = $this->get_activity_query($type, $items);
        if (empty($ids)) {
            return null;
        }
        $sqlselectfrom = $this->get_select($field, $type, $filters);
        list($fromsql, $fromparams) = $this->get_from($filters);
        list($modsql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'mod');
        $sqlselectfrom .= " FROM " . $fromsql . "
                WHERE courseid = :courseid AND anonymous = 0 and $field " . $modsql . " ";
        // Bugfix: in very rare cases, a course and activity can have the same instanceid.
        if ($field == 'contextinstanceid') {
            $sqlselectfrom .= " AND contextlevel = 70";
        }
        $sqlgroup = "GROUP BY " . $this->querytype;
        if ($type !== 'section') {
            $sqlgroup .= ", " . $field;
        }
        $params = array_merge($fromparams, $params, array('courseid' => $this->courseid));
        return array($sqlselectfrom, $sqlgroup, $params);
    }

    /**
     * Setup the select query.
     *
     * @param  string  $field    the field to retrieve the activities from the DB
     * @param  string  $type     the type of query being performed {activity|activity_class|section}
     * @param  array   $filters  the various filters from javascript (student, activity, etc).
     * @return string  the select clause to use
     */
    protected function get_select($field, $type, $filters) {

        $sqlselect = "SELECT row_number() over (order by " . $this->querytype . "), " . $this->querytype . ",";
        if ($type !== 'section') {
            $sqlselect .= " " . $field . " as activity,";
        }
        if (isset($filters->unique) && $filters->unique === true) {
            $sqlselect .= " count(distinct(userid))";
        } else {
            $sqlselect .= " count(*)";
        }
        return $sqlselect;
    }

    /**
     * Transform the logtable to add a temporary column that indicates 'r' or 'w' based
     * on the crud parameter.
     *
     * @param  array  $filters  the various filters from javascript (student, activity, etc).
     * @return array  array containing the from clause and the needed DB params
     */
    protected function get_from($filters) {
        global $DB;

        if ($this->querytype == 'rw') {
            if (array_search('a', $filters->action) === false) {
                list($writesql, $writeparams) = $DB->get_in_or_equal(array('c', 'u', 'd'), SQL_PARAMS_NAMED, 'crud');
                $fromsql = "(SELECT *, CASE WHEN crud " . $writesql . " THEN 'w' WHEN crud = 'r' THEN 'r' END as ";
            } else {
                list($writesql, $writeparams) = $DB->get_in_or_equal(array('c', 'r', 'u', 'd'), SQL_PARAMS_NAMED, 'crud');
                $fromsql = "(SELECT *, CASE WHEN crud " . $writesql . " THEN 'a' END as ";
            }
            $fromsql .= $this->querytype . " FROM {" . $this->logtable . "}) as newtable";
        } else {
            $fromsql = "{" . $this->logtable . "}";
            $writeparams = array();
        }
        return array($fromsql, $writeparams);
    }

    /**
     * Determines the component or context ID that must be used to query the DB and
     * returns this to the user.  Additionally, the user is given information about
     * the proper label of the data and its type {activity|activity class|section}.
     *
     * @param  string  $type   the type of query being performed {activity|activity_class|section}
     * @param  string  $items  the desired activity info to retrieve
     * @return array  {component or context ID(s), field from DB}
     */
    protected function get_activity_query($type, $items) {

        if ($type === 'activity') {
            $ids = $items;
            $field = 'contextinstanceid';
        } else {
            if ($type === 'activity_class') {
                $ids = $items;
                $field = 'component';
            } else {
                $temp = strval($items);
                $sectionnumber = substr($temp, 1);
                $activityfilter = new activityfilter($this->courseid);
                $ids = $activityfilter->get_mods_in_section($sectionnumber);
                $field = 'contextinstanceid';
            }
        }
        return array($ids, $field);
    }

    /**
     * Build a query to retrieve results for particular students only.
     *
     * @param  array  $filters  the various filters from javascript (student, activity, etc).
     * @return array, array  SQL for retrieving students and params array of IDs to retrieve
     */
    protected function build_student_query($filters) {
        global $DB;

        $sqlstudent = '';
        $userparams = array();
        if (isset($filters->students) && !is_array($filters->students)) {
            $filters->students = (array)$filters->students;
        }
        if (!empty($filters->students)) {
            $studentfilter = new studentfilter($this->courseid);
            $userids = $studentfilter->parse_groups($filters->students);
            if (count($userids) == 0) {
                throw new \Exception(get_string('emptygroup', 'report_analytics'));
            } else {
                list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userid');
                $sqlstudent = " AND userid " . $usersql . " ";
            }
        }
        return array($sqlstudent, $userparams);
    }

    /**
     * Build a query to retrieve results for particular actions only.
     *
     * @param  array  $filters  the various filters from javascript (student, activity, etc).
     * @return array, array  SQL for retrieving actions and params array of actions to retrieve
     */
    protected function build_action_query($filters) {
        global $DB;

        $sqlaction = '';
        $params = array();
        if (array_search('a', $filters->action) === false) {
            list($sql, $params) = $DB->get_in_or_equal($filters->action, SQL_PARAMS_NAMED, 'action');
            $sqlaction = " AND " . $this->querytype . " " . $sql . " ";
        }
        return array($sqlaction, $params);
    }

    /**
     * Build a query to retrieve results for particular dates only.
     *
     * @param  array  $filters  the various filters from javascript (student, activity, etc).
     * @return array, array  SQL for retrieving dates and params array of dates to retrieve
     */
    protected function build_date_query($filters) {

        $params = array();
        $sqldate = '';
        if (isset($filters->date->from) && isset($filters->date->to)) {
            $from = \DateTime::createFromFormat('Y-m-d H:i', $filters->date->from);
            $to = \DateTime::createFromFormat('Y-m-d H:i', $filters->date->to);
            $sqldate = " AND timecreated > :datefrom AND timecreated < :dateto ";
            $params["datefrom"] = $from->getTimestamp();
            $params["dateto"] = $to->getTimestamp();
        }
        return array($sqldate, $params);
    }

    /**
     * Queries the database according to the constructed query.
     *
     * Note: I've chosen to ignore CLI (command line) events that are generated while
     * behat tests were running.  In my own testing, I've found that these events were
     * generated inconsistently and were always supplemental.  To keep the testing
     * results consistent across all devices, I ignore them only while testing.
     *
     * @param  string  $sqlselectfromwhere  the select clause
     * @param  string  $sqlgroup            the group by and order by clause
     * @param  string  $params              the array of values to complete the query
     * @return array  the results of the query from the database
     */
    protected function query_db($sqlselectfromwhere, $sqlgroup, $params) {
        global $DB;

        $sqlbehat = (defined('BEHAT_SITE_RUNNING')) ? " AND origin <> 'cli' " : "";
        return $DB->get_records_sql($sqlselectfromwhere . $sqlbehat . $sqlgroup, $params);
    }

    /**
     * This method creates the formatted array depending on the action(s) that
     * were requested.
     *
     * @param  array   $results  the array returned from the DB
     * @param  array   $items    the list of acitivites retrieved by the query
     * @param  array   $type     the type of activity being retrieved {activity|activity_class|section}
     * @param  int     $time     the total time taken by the query
     * @param  array   $events   the aggregated list of results from the DB
     */
    public function aggregate($results, $items, $type, $time, &$events) {

        $actionfilter = new actionfilter($this->courseid);
        foreach ($results as $result) {
            $action = $result->{$this->querytype};
            $actionlabel = $actionfilter->get_action_label($action);
            $item = ($type == 'section') ? $items : $result->activity;
            $activityfilter = new activityfilter($this->courseid);
            $label = $activityfilter->get_label($item);
            if (isset($result->count)) {
                $events[$label]['values'][$actionlabel]->value = $result->count;
            }
            if (isset($time)) {
                $events[$label]['time'] = $time;
            }
        }
    }

}
