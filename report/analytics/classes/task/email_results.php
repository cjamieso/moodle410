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

namespace report_analytics\task;

use report_analytics\scheduled_results_record;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->dirroot . '/course/format/lib.php');
require_once($CFG->dirroot . '/lib/weblib.php');

use stdClass;

/**
 * Task to run all pre-setup queries by the user and email the results.
 *
 * @package    report_analytics
 * @copyright  2017 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class email_results extends \core\task\scheduled_task {

    /** @const max number of emails to send in one processing */
    const MAX_RECORDS = 50;

    /**
     * Sets the name of the task (as it appears in the admin -> task space)
     *
     * @return string  the name of the task
     */
    public function get_name() {
        return get_string('emailresults', 'report_analytics');
    }

    /**
     * This task emails out the results to users.  If new results have come in
     * (i.e., resultstime > emailtime), then send those new results out to the
     * user.
     *
     * There is a cap set (MAX_RECORDS) so that only a set amount of records will
     * be processed each time the task is executed.
     */
    public function execute() {
        global $DB;

        $sent = 0;
        $records = $DB->get_records_select('report_analytics_results', "resultstime > emailtime");
        foreach ($records as $record) {
            try {
                $scheduledrecord = new scheduled_results_record($record);
                $userfrom = 'noreply@ualberta.ca';
                $course = get_course($scheduledrecord->get_courseid());
                if ($course->visible) {
                    $subject = "[$course->shortname] " . get_string('resultssubject', 'report_analytics');
                    $message = $this->format_results($scheduledrecord->get_courseid(), $scheduledrecord->get_filters(),
                        $scheduledrecord->get_results());
                    $messageraw = html_to_text($message);
                    $userids = $scheduledrecord->get_userids();
                    if (count($userids) > 0) {
                        foreach ($userids as $userid) {
                            $userto = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
                            if (isset($userto)) {
                                email_to_user($userto, 'noreply', $subject, $messageraw, $message, '', '', false);
                            }
                        }
                    }
                    $scheduledrecord->set_email_time(time());
                    $scheduledrecord->save();
                    if (++$sent >= self::MAX_RECORDS) {
                        return;
                    }
                }
            } catch (\Exception $e) {
                mtrace($record->id . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Format a set of results into a human readable format suitable for emailing.
     *
     * @param  int    $courseid  the ID of the course
     * @param  array  $filters   the filters used to generate the results
     * @param  array  $results   the results from applying the criteria
     * @return string  a human readable description of the results
     */
    protected function format_results($courseid, $filters, $results) {

        $message = '';
        for ($i = 0; $i < count($filters); $i++) {
            $message .= '<br /><b>' . get_string('conditions', 'report_analytics') . '</b>: ';
            $message .= $this->format_filter($courseid, $filters[$i]);
            if (isset($results[$i])) {
                if (count($results[$i]) > 0) {
                    $message .= '<br /><br /><i>' . get_string('usercriteriatitle', 'report_analytics') . '</i>:<br />';
                    $message .= $this->format_userlist($results[$i]);
                } else {
                    $message .= '<br /><br /><i>' . get_string('nouserscriteria', 'report_analytics') . '</i><br />';
                }
            }
        }
        $message .= $this->get_footer($courseid);
        return $message;
    }

    /**
     * Format a set of criteria into a human readable format.
     *
     * @param  int    $courseid  the ID of the course
     * @param  array  $filter    the filters used to generate the results
     * @return string  a human readable description of the criteria used
     */
    protected function format_filter($courseid, $filter) {

        $message = '|';
        $criteria = $filter->criteria;
        foreach ($criteria as $criterion) {
            $message .= ' ';
            $classname = "\\report_analytics\\" . $criterion->type . "filter";
            if (class_exists($classname)) {
                $f = new $classname($courseid);
                $message .= $f->format_condition($criterion);
                $message .= ' |';
            }
        }
        return $message;
    }

    /**
     * Format a list of users into a table suitable for emailing to a user.
     *
     * @param  array  $users  the list of users
     * @return string  the HTML for the table
     */
    protected function format_userlist($users) {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');
        $data = new stdClass();
        $data->users = $users;
        return $renderer->render_from_template('report_analytics/userlistforemail', $data);
    }

    /**
     * This function appends information to the footer of the email.  In particular
     * it indicates to the user how to turn off the notifications.
     *
     * @param  int  $courseid  the ID of the course
     * @return string  the footer to append to the message
     */
    private function get_footer($courseid) {
        global $CFG;

        $footer = "<br/><br/>----------------------<br/>" . get_string('emailnotification', 'report_analytics');
        $footer .= "<br/>" . get_string('emailremove', 'report_analytics');
        $footer .= "$CFG->wwwroot" . "/report/analytics/scheduled.php?id=" . $courseid;
        return $footer;
    }

}
