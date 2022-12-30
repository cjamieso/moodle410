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

namespace report_analytics\privacy;

defined('MOODLE_INTERNAL') || die();

use \report_analytics\scheduled_results_record;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\writer;

/**
 * privacy provider class
 *
 * This class is implement GDPR in the analytics plugin.
 *
 * @package    report_analytics
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
     \core_privacy\local\metadata\provider,
     \core_privacy\local\request\core_userlist_provider,
     \core_privacy\local\request\plugin\provider {

    /**
     * Gets information about user data stored in database by the plugin.
     *
     * @param  $collection  collection of metadata about user
     * @return updated collection
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table(
            'report_analytics_results',
             [
                'userids' => 'privacy:metadata:report_analytics_results:userids',
                'courseid' => 'privacy:metadata:report_analytics_results:courseid',
                'filters' => 'privacy:metadata:report_analytics_results:filters',
                'results' => 'privacy:metadata:report_analytics_results:results',
                'resultstime' => 'privacy:metadata:report_analytics_results:resultstime',
                'emailtime' => 'privacy:metadata:report_analytics_results:emailtime',
             ],
            'privacy:metadata:report_analytics_results'
        );

        return $collection;
    }


    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param  int  $userid  the ID of the user to search for
     * @return the list of contexts with stored information for the user
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        $sql = "SELECT c.id
                FROM {context} c
                INNER JOIN {report_analytics_results} ra ON ra.courseid = c.instanceid AND c.contextlevel = :contextlevel
                WHERE ra.userids LIKE :userid";

        $params = array('contextlevel' => CONTEXT_COURSE, 'userid' => '%"' . $userid . '"%');

        $contextlist = new \core_privacy\local\request\contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context
     *
     * @param  userlist  $userlist  the userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {

        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        $record = new scheduled_results_record($context->instanceid);
        $userlist->add_users($record->get_userids());
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param  approved_contextlist  $contextlist the approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $record = new scheduled_results_record($context->instanceid);
            $subcontext = get_string('analytics', 'report_analytics') . ': ' . get_string('schedulereport', 'report_analytics');
            writer::with_context($context)
                ->export_data([$subcontext], (object) ['filters' => $record->get_filters()])
                ->export_metadata([$subcontext], 'times', (object) ['emailtime' => $record->get_email_time(),
                    'resultstime' => $record->get_results_time()], new \lang_string('privacy:export:times', 'report_analytics'));
        }
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param  context  $context  context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }
        $record = new scheduled_results_record($context->instanceid);
        $record->delete();
    }

    /**
     * Delete data in a list of contexts for one particular user.
     *
     * @param  approved_contextlist  $contextlist  the list of contexts to remove data for the user
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $record = new scheduled_results_record($context->instanceid);
            $userids = $record->get_userids();
            if (($key = array_search($userid, $userids)) !== false) {
                unset($userids[$key]);
                $record->set_userids(array_values($userids));
                $record->save();
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param  approved_userlist  $userlist  list of users to delete in a particular context
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $record = new scheduled_results_record($context->instanceid);
        $userids = $record->get_userids();

        foreach ($userlist->get_userids() as $removeid) {
            if (($key = array_search($removeid, $userids)) !== false) {
                unset($userids[$key]);
            }
        }
        $record->set_userids($userids);
        $record->save();
    }

}
