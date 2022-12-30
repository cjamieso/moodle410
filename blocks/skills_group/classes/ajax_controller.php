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
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/blocks/skills_group/locallib.php');

/**
 * This is the main controller class that handles all of the ajax requests.
 *
 * @package    block_skills_group
 * @copyright  2014 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ajax_controller {

    /** Hold the courseid */
    private $courseid;

    /**
     * Empty constructor for the time being.
     *
     */
    public function __construct() {
    }

    /**
     * This is function verifies that the user has basic access to this page.  More detailed checks
     * may be performed later depending on the action.
     *
     * @param int $requesttype The type of the ajax request.
     *
     */
    public function verify_access($requesttype) {
        $this->courseid = required_param('courseid', PARAM_INT);
        // Require users to be logged in, but do not redirect to login page -> we'll tell the user manually.
        try {
            require_login($this->courseid, false, null, false, true);
        } catch (Exception $e) {
            echo(json_encode(array('result' => 'false', 'text' => get_string('nologin', BLOCK_SG_LANG_TABLE))));
            return false;
        }
        if (!confirm_sesskey(required_param("sesskey", PARAM_TEXT))) {
            echo(json_encode(array('result' => 'false', 'text' => get_string('badsesskey', BLOCK_SG_LANG_TABLE))));
            return false;
        }
        return true;
    }

    /**
     * This is function dispatches the request based on its type.
     *
     * @param int $requesttype The type of the ajax request.
     *
     */
    public function perform_request($requesttype) {
        switch ($requesttype) {
            case 'join_group':
                $this->join_group();
                break;
            case 'get_chart_data':
                $this->get_chart_data();
                break;
            case 'export':
                $this->export();
                break;
        }
    }

    /**
     * This function allows the user to join a group if they are not already part
     * of a group.
     *
     */
    private function join_group() {
        global $USER;

        $groupid = required_param('groupid', PARAM_INT);
        $this->courseid = required_param('courseid', PARAM_INT);

        $sgs = new skills_group_setting($this->courseid);
        $sgrouping = new skills_grouping($this->courseid);
        $sgroup = new skills_group($groupid);

        if ($sgroup->count_members() < $sgs->get_group_size()) {
            if ($sgroup->get_allow_others_to_join() === true || $sgs->get_instructorgroups() === true) {
                if ($sgrouping->check_for_user_in_grouping($USER->id) === false) {
                    groups_add_member($groupid, $USER->id);
                    // Logging join group action.
                    $params = array(
                        'context' => \context_course::instance($this->courseid),
                        'objectid' => $groupid,
                        'courseid' => $this->courseid,
                        'userid' => $USER->id
                        );
                    $event = \block_skills_group\event\skillsgroup_joined::create($params);
                    $event->trigger();
                    echo(json_encode(array('result' => 'true', 'text' => get_string('groupjoinsuccess', BLOCK_SG_LANG_TABLE))));
                } else {
                    echo(json_encode(array('result' => 'false', 'text' => get_string('alreadyingroup', BLOCK_SG_LANG_TABLE))));
                }
            } else {
                echo(json_encode(array('result' => 'false', 'text' => get_string('invalidgroup', BLOCK_SG_LANG_TABLE))));
            }
        } else {
                echo(json_encode(array('result' => 'false', 'text' => get_string('toomanymembers', BLOCK_SG_LANG_TABLE))));
        }
    }

    /**
     * Retrieves the data for the given chart based on the graphtype parameter.
     */
    private function get_chart_data() {

        $filters = $this->get_json_variable('filters');
        $chart = new chart($this->courseid);
        list($result, $data) = $chart->ajax_get_data($filters);
        $title = $chart->get_title($filters);
        $renderer = $this->get_renderer();
        $toolbar = $renderer->get_toolbar($filters);
        $encoded = array('result' => $result, 'data' => $data, 'title' => $title, 'toolbar' => $toolbar);
        echo(json_encode($encoded));
    }

    /**
     * Decode an optional param that is json formatted to an array.
     *
     * @param  string  $param  The posted paramater to retrieve
     * @return array  the json-decoded version of the data (or zero length array)
     */
    private function get_json_variable($param) {

        $temp = optional_param($param, array(), PARAM_TEXT);
        return (!is_array($temp)) ? json_decode($temp, true) : $temp;
    }

    /**
     * Grab the appropriate graph renderer and return it to the caller.
     *
     * @return  object  the renderer for the page
     */
    private function get_renderer() {
        global $PAGE;

        $chartrenderable = new \block_skills_group\output\chart_renderable($this->courseid);
        $renderer = $PAGE->get_renderer('block_skills_group', 'chart');
        $renderer->render($chartrenderable);
        return $renderer;
    }

    /**
     * Export the data from a chart into a desired format.  The "posts" chart uses this
     * to dump the data into an excel file.
     */
    private function export() {

        $filters = $this->get_json_variable('filters');
        $chart = new chart($this->courseid);
        $chart->export($filters);
    }
}