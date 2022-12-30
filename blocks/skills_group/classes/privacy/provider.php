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

namespace block_skills_group\privacy;

defined('MOODLE_INTERNAL') || die();

use \block_skills_group\skills_group_student;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\writer;

class provider implements
     \core_privacy\local\metadata\provider,
     \core_privacy\local\request\core_userlist_provider,
     \core_privacy\local\request\plugin\provider {

    /**
     * Gets information about user data stored in database by the plugin.
     * The plugin also creates groups, so this link needs to be added as well.
     *
     * @param  $collection  collection of metadata about user
     * @return updated collection
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_subsystem_link('core_group', [], 'privacy:metadata:core_group');

        $collection->add_database_table(
            'skills_group_student',
             [
                'userid' => 'privacy:metadata:skills_group_student:userid',
                'groupingid' => 'privacy:metadata:skills_group_student:groupingid',
                'finalizegroup' => 'privacy:metadata:skills_group_student:finalizegroup',
             ],
            'privacy:metadata:skills_group_student'
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
                INNER JOIN {groupings} g ON g.courseid = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {skills_group_student} sgs ON sgs.groupingid = g.id
                WHERE sgs.userid = :userid";

        $params = array('contextlevel' => CONTEXT_COURSE, 'userid' => $userid);

        $contextlist = new contextlist();
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

        $sql = "SELECT sgs.userid
                FROM {skills_group_student} sgs
                INNER JOIN {groupings} g ON sgs.groupingid = g.id
                INNER JOIN {context} c ON g.courseid = c.instanceid AND c.contextlevel = :contextlevel
                WHERE c.id = :contextid";

        $params = array('contextlevel' => CONTEXT_COURSE, 'contextid' => $context->id);
        $userlist->add_from_sql('userid', $sql, $params);
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
            $sgs = new skills_group_student($context->instanceid, $userid);
            $subcontext = get_string('pluginname', 'block_skills_group') . ': ' . get_string('lockstatus', 'block_skills_group');
            writer::with_context($context)
                ->export_data([$subcontext], (object) ['lock' => $sgs->get_lock_choice()]);
        }
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param  context  $context  context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }
        $sql = "SELECT g.id
                FROM {groupings} g
                INNER JOIN {context} c ON g.courseid = c.instanceid AND c.contextlevel = :contextlevel
                WHERE c.id = :contextid";
        $params = array('contextlevel' => CONTEXT_COURSE, 'contextid' => $context->id);
        $groupingids = $DB->get_records_sql($sql, $params);
        foreach ($groupingids as $groupingid) {
            if ($DB->count_records('skills_group_student', array('groupingid' => $groupingid->id)) > 0) {
                $DB->delete_records('skills_group_student', array('groupingid' => $groupingid->id));
            }
        }
    }

    /**
     * Delete data in a list of contexts for one particular user.
     *
     * @param  approved_contextlist  $contextlist  the list of contexts to remove data for the user
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $sql = "SELECT g.id
                    FROM {groupings} g
                    INNER JOIN {context} c ON g.courseid = c.instanceid AND c.contextlevel = :contextlevel
                    WHERE c.id = :contextid";
            $params = array('contextlevel' => CONTEXT_COURSE, 'contextid' => $context->id);
            $groupingids = $DB->get_records_sql($sql, $params);
            foreach ($groupingids as $groupingid) {
                if ($DB->count_records('skills_group_student', array('groupingid' => $groupingid->id, 'userid' => $userid)) > 0) {
                    $DB->delete_records('skills_group_student', array('groupingid' => $groupingid->id, 'userid' => $userid));
                }
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param  approved_userlist  $userlist  list of users to delete in a particular context
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        // Grab all groupingids - there might be multiple in a single course.
        $sql = "SELECT g.id
                FROM {groupings} g
                INNER JOIN {context} c ON g.courseid = c.instanceid AND c.contextlevel = :contextlevel
                WHERE c.id = :contextid";
        $params = array('contextlevel' => CONTEXT_COURSE, 'contextid' => $context->id);
        $groupingids = $DB->get_records_sql($sql, $params);
        $userids = $userlist->get_userids();

        foreach ($groupingids as $groupingid) {
            foreach ($userids as $userid) {
                if ($DB->count_records('skills_group_student', array('groupingid' => $groupingid->id, 'userid' => $userid)) > 0) {
                    $DB->delete_records('skills_group_student', array('groupingid' => $groupingid->id, 'userid' => $userid));
                }
            }
        }
    }

}
