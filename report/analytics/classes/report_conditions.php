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
 * report_conditions class
 *
 * This class compares all students to a list of conditions (criteria) to generate
 * a list of all students that match the given criteria.
 *
 * @package    report_analytics
 * @copyright  2016 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_conditions {

    /** @var int the course id. */
    protected $courseid;
    /** @var array filters to be used to generate the report */
    protected $filters;

    /**
     * Conditions report constructor.  Save courseid and filters for later use.
     *
     * @param  int    $courseid  the ID of the course
     * @param  array  $filters   the various filters from javascript (student, activity, etc).
     */
    public function __construct($courseid, $filters) {

        $this->courseid = $courseid;
        $this->filters = $filters;
    }

    /**
     * Gets a list of users that match the set of conditions (criteria).  This method
     * starts with all users being valid targets and then slowly narrows down the list
     * one criteria at a time.
     *
     * @return array  the users that meet all of the criteria
     */
    public function get_users_by_condition() {

        // This starts will all users as valid members.
        $filter = new studentfilter($this->courseid, array('groups' => false));
        $userids = array_keys($filter->get_all_students());
        foreach ($this->filters->criteria as $criterion) {
            $classname = "\\report_analytics\\" . $criterion->type . "filter";
            if (class_exists($classname)) {
                $filter = new $classname($this->courseid);
                $userids = $filter->filter_userids_by_condition($criterion, $userids, $this->filters);
            }
        }
        return $this->get_user_details($userids);
    }

    /**
     * Retrieves the details for the list of users.  Retrieves:
     * {id, firstname, lastname, email, profileimageurl}
     *
     * @param  array  $userids  the IDs of the users to retrieve
     * @return array  the details for the specified users
     */
    protected function get_user_details($userids) {
        global $CFG;

        require_once($CFG->dirroot . "/user/lib.php");
        $users = user_get_users_by_id($userids);
        $formatted = array();
        foreach ($users as $user) {
            $details = $this->strip_fields($user);
            $formatted[] = $details;
        }
        usort($formatted, array($this, 'sort_users'));
        return $formatted;
    }

    /**
     * Strip the excess fields out of a user object.  This information will be
     * sent to javascript via JSON, so we wish to keep the data as light as
     * possible.
     *
     * Moodle has a similar function, but requires the permission moodle/site:viewfullnames
     * in order to format the array with the firstname and lastname.  Oddly, it will let one
     * view the "fullname" field without it, but I need the lastname to sort the list by
     * lastname.
     *
     * @param  object  $user  the user object from the database
     * @return array  the slimmed down user object
     */
    protected function strip_fields($user) {
        global $PAGE;

        $newuser = array();
        $fields = array('id', 'firstname', 'lastname', 'email');
        foreach ($fields as $field) {
            $newuser[$field] = $user->$field;
        }
        $userpicture = new \user_picture($user);
        $newuser['profileimageurlsmall'] = $userpicture->get_url($PAGE)->out(false);
        return $newuser;
    }

    /**
     * Custom sort for userfield records.
     *
     * @param  array  $a  array #1 to sort
     * @param  array  $b  array #2 to sort
     *
     * @return int  equal -> 0, less than -> -1, greater than -> 1
     */
    protected static function sort_users($a, $b) {

        $lname = $a['lastname'] . ', ' . $a['firstname'];
        $rname = $b['lastname'] . ', ' . $b['firstname'];
        if ($lname == $rname) {
            return 0;
        }
        return ($lname < $rname) ? -1 : 1;
    }
}
