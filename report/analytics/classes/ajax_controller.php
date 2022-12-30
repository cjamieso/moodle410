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
class ajax_controller {

    /** @var int the ID of the course */
    protected $courseid;
    /** @var bool whether the user is viewing as an instructor (T) or not (F) */
    protected $instructor;
    /** @var array the types of requests to log */
    protected $logevents = array('get_chart_data');

    /**
     * Store course ID for later use.
     */
    public function __construct() {
        $this->courseid = required_param('courseid', PARAM_INT);
    }

    /**
     * This is function verifies that the user access to this page.  Specifically,
     * this function checks that the requesting user has capabilities to view this
     * report as an instructor or student.  More detailed checks may be performed
     * later depending on the action.
     *
     * @throws Exception  if sesskey is incorrect or user does not have correct permissions
     */
    protected function verify_access() {

        if (!confirm_sesskey(required_param("sesskey", PARAM_TEXT))) {
            throw new \Exception(get_string('badsesskey', 'report_analytics'));
        }
        $context = \context_course::instance($this->courseid);
        if (!has_capability('report/analytics:view', $context) && !has_capability('report/analytics:studentview', $context)) {
            throw new \Exception(get_string('nopermission', 'report_analytics'));
        }
    }

    /**
     * This is function dispatches the request based on its type.
     *
     * @param  int  $requesttype  The type of the ajax request.
     * @throws Exception  if user has no permission or request does not exist
     */
    public function perform_request($requesttype) {

        $this->verify_access();
        $reqverify = $requesttype . '_verify';
        // Check for additional validation if it exists.
        if (method_exists($this, $reqverify) && !$this->$reqverify()) {
            throw new \Exception(get_string('nopermission', 'report_analytics'));
        }
        if (!method_exists($this, $requesttype)) {
            throw new \Exception(get_string('badrequest', 'report_analytics'));
        }
        $result = $this->$requesttype();
        if (array_search($requesttype, $this->logevents) !== false) {
            $graphtype = strtolower(required_param('graphtype', PARAM_TEXT));
            $graphname = get_string($graphtype . 'name', 'report_analytics');
            $params = array(
                'context' => \context_course::instance($this->courseid),
                'objectid' => 1,
                'other' => array('request' => $requesttype, 'graphtype' => $graphname),
                'courseid' => $this->courseid
            );
            $event = \report_analytics\event\analytics_viewed::create($params);
            $event->trigger();
        }
        if (isset($result)) {
            echo json_encode($result);
        }
    }

    /**
     * Check to see if the user has instructor access.  If this is the first call
     * (variable not set), then retrieve the value.
     *
     * @return bool  T/F indicating if the user has instructor access
     */
    protected function has_instructor_access() {

        if (!isset($this->instructor)) {
            $context = \context_course::instance($this->courseid);
            $this->instructor = (has_capability('report/analytics:view', $context)) ? true : false;
        }
        return $this->instructor;
    }

    /**
     * Retrieve the filters for the chart, which was passed as a json variable.
     * The filters are decoded and then passed to an object, which will set
     * appropriate defaults.
     *
     * @return user_filters  The filters.
     */
    protected function get_filters() {
        $filters = $this->get_json_variable('filters');
        $filters = new user_filters($filters, array('instructor' => $this->has_instructor_access()));
        return $filters;
    }

    /**
     * Decode an optional param that is json formatted to an array.
     *
     * @param  string  $param  The posted paramater to retrieve
     * @return array  the json-decoded version of the data (or zero length array)
     */
    protected function get_json_variable($param) {

        $temp = optional_param($param, array(), PARAM_TEXT);
        return (!is_array($temp)) ? json_decode($temp) : $temp;
    }

    /**
     * Retrieves the data for the given chart based on the graphtype parameter.
     *
     * @return array  {result of request, message for javascript}
     */
    protected function get_chart_data() {
        global $PAGE;

        $filters = $this->get_filters();
        $graphtype = required_param('graphtype', PARAM_TEXT);
        $classname = "\\report_analytics\\" . strtolower($graphtype);
        $chart = new $classname($this->courseid, array('instructor' => $this->has_instructor_access()));
        $data = $chart->ajax_get_data($filters);
        $title = $chart->get_title($filters);
        $renderable = $this->get_renderable();
        $renderer = $PAGE->get_renderer('report_analytics', 'chart');
        $toolbar = $renderer->render_toolbar($renderable);
        return array('result' => true, 'message' => array('data' => $data, 'title' => $title, 'toolbar' => $toolbar));
    }

    /**
     * Grab the appropriate graph renderer and return it to the caller.
     *
     * @return object  the renderer for the page
     */
    protected function get_renderable() {

        $filters = $this->get_json_variable('filters');
        $graphtype = strtolower(required_param('graphtype', PARAM_TEXT));
        $classname = "\\report_analytics\\output\\" . $graphtype . '_renderable';
        $renderable = new $classname($this->courseid, array('instructor' => $this->has_instructor_access()), $filters);
        return $renderable;
    }

    /**
     * This function adds a graph to the page based on the given type.
     *
     * @throws Exception  if specified course is invalid
     * @return array  {result of request, message for javascript}
     */
    protected function add_graph() {
        global $PAGE;

        if (!get_course($this->courseid)) {
            throw new \Exception(get_string('invalidcourse', 'report_analytics'));
        }
        $rendererable = $this->get_renderable();
        $renderer = $PAGE->get_renderer('report_analytics', 'chart');
        $output = $renderer->render_chart($rendererable);
        return array('result' => true, 'message' => $output);
    }

    /**
     * This function retrieves a list of mods in a section or matching an activity class.
     *
     * @return array  {result of request, message for javascript}
     */
    protected function get_mods() {

        $type = required_param('type', PARAM_TEXT);
        $data = required_param('data', PARAM_TEXT);
        $activityfilter = new activityfilter($this->courseid);

        if ($type == 'section') {
            return array('result' => true, 'message' => $activityfilter->get_mods_in_section($data));
        } else if ($type == 'activity_class') {
            return array('result' => true, 'message' => array_keys($activityfilter->get_mods_of_class($data)));
        }
    }

    /**
     * Export the data from a chart into a desired format.  This will export to either excel
     * or a csv (depending on context).  I've added a manual exit() to force a close after
     * the file is written out.
     */
    protected function export() {

        $filters = $this->get_filters();
        $graphtype = strtolower(required_param('graphtype', PARAM_TEXT));
        $classname = "\\report_analytics\\" . $graphtype;
        $chart = new $classname($this->courseid, array('instructor' => $this->has_instructor_access()));
        $chart->export($filters);
    }

    /**
     * Retrieve and return the words used for the creation of a word cloud.
     *
     * @return array  {result of request, message for javascript}
     */
    protected function word_cloud() {

        $filters = $this->get_filters();
        $graphtype = strtolower(required_param('graphtype', PARAM_TEXT));
        $classname = "\\report_analytics\\" . $graphtype;
        if ($graphtype != 'userpostschart') {
            throw new \Exception(get_string('invalidchart', 'report_analytics'));
        }
        $chart = new $classname($this->courseid, array('instructor' => $this->has_instructor_access()));
        $words = $chart->word_cloud($filters);
        return array('result' => true, 'message' => $words);
    }

    /**
     * Saves the criteria for a scheduled report.
     *
     * @return array  {result of request, message for javascript}
     */
    protected function save_criteria() {

        $filters = required_param("filters", PARAM_TEXT);
        $userids = required_param("userids", PARAM_TEXT);
        $record = new \report_analytics\scheduled_results_record($this->courseid);
        $record->set_filters($filters);
        $record->set_userids($userids);
        $record->save();
        return array('result' => true, 'message' => get_string('savecriteriasuccess', 'report_analytics'));
    }

}
