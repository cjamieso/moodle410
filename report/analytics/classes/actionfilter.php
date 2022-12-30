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
 * actionfilter class
 *
 * This class is used to setup an action filter.
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class actionfilter extends filter {

    /** @var array default settings for options. */
    protected $optionsdefaults = array('types' => 'all', 'action' => 'multiple');
    /** @var array list of possible actions for filter. */
    protected $actions;

    /**
     * Store the course ID for later use.
     *
     * @param  int  $courseid  the ID of the course to work with
     * @param  array  $options   options for generating the filter
     */
    public function __construct($courseid, $options = array()) {

        parent::__construct($courseid, $options);
        $this->actions = array('a' => get_string('allactions', 'report_analytics'),
            'r' => get_string('reads', 'report_analytics'),
            'w' => get_string('writes', 'report_analytics'));
        $this->forumevents = array('\mod_forum\event\discussion_viewed' => get_string('eventdiscussionviewed', 'mod_forum'),
            '\mod_forum\event\post_created' => get_string('eventpostcreated', 'mod_forum'),
            '\mod_forum\event\discussion_created' => get_string('eventdiscussioncreated', 'mod_forum'));
        $this->events = array('\mod_assign\event\assessable_submitted' => get_string('pluginname', 'mod_assign') . ' ' .
            get_string('submission', 'mod_assign'),
            '\mod_assign\event\submission_status_viewed' => get_string('submissionstatus', 'mod_assign') . ' ' .
            get_string('viewed', 'mod_quiz'),
            '\mod_quiz\event\attempt_started' => get_string('eventquizattemptstarted', 'mod_quiz'),
            '\mod_quiz\event\attempt_submitted' => get_string('eventquizattemptsubmitted', 'mod_quiz'),
            '\mod_quiz\event\attempt_reviewed' => get_string('eventattemptreviewed', 'mod_quiz'),
            '\mod_quiz\event\attempt_viewed' => get_string('eventattemptviewed', 'mod_quiz'),
            '\mod_feedback\event\response_submitted' => get_string('pluginname', 'mod_feedback') . ' ' .
            get_string('eventresponsesubmitted', 'mod_feedback'));
    }

    /**
     * Returns valid data for for an action filter.
     * Forums require only forum related events.
     * Multiple select does not allow user to choose all/read/write (defaults to read/write).
     * Single select allows users to choose the one event, so all are displayed.
     *
     * @return array  containing valid types of actions for filter.
     */
    public function get_filter_data() {

        if ($this->options['types'] == 'forum') {
            return $this->forumevents;
        }
        if ($this->options['action'] === 'multiple') {
            return array_merge($this->events, $this->forumevents);
        } else {
            return array_merge($this->actions, $this->events, $this->forumevents);
        }
    }

    /**
     * Returns the text label corresponding to the key in the action filter.
     *
     * @param  string  $key  the key in the actions matrix to retrieve the value of
     * @return string  text label corresponding to the key in the action filter.
     */
    public function get_action_label($key) {

        $all = array_merge($this->actions, $this->events, $this->forumevents);
        if (is_array($key) && count($key) == 1) {
            $key = $key[0];
        }
        if (isset($all[$key])) {
            return $all[$key];
        } else {
            throw new \Exception(get_string('actionnotfound', 'report_analytics'));
        }
    }

    /**
     * Check to particular action/event condition against a list of users.
     *
     * @param  array  $condition  the action condition {operand, operator, value}
     * @param  array  $userids    a list of user IDs for the condition to be checked against
     * @param  array  $filters    a list of filters specified by the user
     * @return array  list of users satisfying given action condition
     */
    public function filter_userids_by_condition($condition, $userids, $filters) {

        $users = array();
        if (!isset($filters)) {
            $newfilters = new stdClass;
        } else {
            $newfilters = clone $filters;
        }
        $newfilters->activities = array($condition->operand->cmid);
        $newfilters->action = $condition->operand->actionid;
        // Note: student results are aggregated in the existing query, so we must retrieve students one-by-one to check.
        foreach ($userids as $userid) {
            $newfilters->students = $userid;
            $report = new report_activities($this->courseid, $newfilters);
            $events = $report->get_events_by_activity();
            $uservalue = $this->get_user_value($condition->operand->actionid, $events);
            if ($uservalue !== false) {
                if ($this->check_condition($uservalue, $condition->operator, $condition->value)) {
                    $users[] = $userid;
                }
            }
        }
        return $users;
    }

    /**
     * Parses an events array to search for the user's value of a particular
     * activity.
     *
     * @param  array  $actionid  the shortened version of the action (e.g., 'r')
     * @param  array  $events    the events array
     * @return int|boolean  the user's value for the activity (or false if not found)
     */
    protected function get_user_value($actionid, $events) {

        $label = $this->get_action_label($actionid);
        foreach ($events[0]['values'] as $action) {
            if ($action->name == $label) {
                return $action->value;
            }
        }
        return false;
    }

    /**
     * Format an action/event condition in a manner that is suitable to display to a user.
     *
     * @param  array  $condition  the action/event condition {operand, operator, value}
     * @return string  event/action condition represented as a string
     */
    public function format_condition($condition) {

        $activityfilter = new activityfilter($this->courseid);
        $activitylabel = $activityfilter->get_label($condition->operand->cmid);
        $actionlabel = $this->get_action_label($condition->operand->actionid);
        $operators = $this->get_operator_data();
        $operatorstring = $operators[$condition->operator];
        return $activitylabel . ', ' . $actionlabel . ' ' . $operatorstring . ' ' . $condition->value;
    }

}
