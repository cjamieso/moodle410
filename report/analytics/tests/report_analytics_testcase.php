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

use report_analytics\analytics_helper;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/lib/moodlelib.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 * This is the base class for report_analytics testing.
 *
 * @package    report_analytics
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_analytics_testcase extends \advanced_testcase {

    /** @var int This is the course ID of the test course that is setup */
    protected $courseid;
    /** @var array Holds the test groups */
    protected $groups = array();
    /** @var array Holds the test users */
    protected $users = array();
    /** @var array Holds the test sections */
    protected $sections = array();
    /** @var array Holds the course activities */
    protected $activities = array();
    /** @var array Holds test activity names */
    protected $activitynames = array('Test Page 1', 'Test Page 2', 'Test Page 3');
    /** @var array Holds the course grade items */
    protected $gradeitems = array();
    /** @var array Holds grade item names */
    protected $gradenames = array('Manual Grade Item', 'Grade Item with Scale', 'Text Grade Item');
    /** @var object Holds grade scale used for tests */
    protected $scale;
    /** @var array Holds the course activities */
    protected $forums = array();
    /** Number of forums */
    const NUMBER_OF_FORUMS = 2;
    /** Max number of test users */
    const NUMBER_OF_USERS = 15;
    /** Max number of groups */
    const NUMBER_OF_GROUPS = 5;
    /** Number of users to place in a group */
    const USERS_PER_GROUP = 3;
    /** Number of sections */
    const NUMBER_OF_SECTIONS = 5;
    /** ID of admin user (for login) */
    const ADMIN_USERID = 2;

    /**
     * The setup function is used to place the DB in its initial state.
     *
     */
    protected function setUp(): void {

        // This needs to be here for the dummy test below.
        $this->resetAfterTest(true);
        // Create all of the test data.
        $this->create_course();
        $this->create_groups();
        $this->create_activities();

        parent::setUp();
    }

    /**
     * Dummy test to avoid a warning.
     *
     */
    public function test_courseid() {
        $this->assertNotNull($this->courseid);
    }

    /**
     * This function creates the course that is used for testing.  Students are
     * enrolled in the course for testing.
     *
     */
    protected function create_course() {

        $course = $this->getDataGenerator()->create_course(array('numsections' => self::NUMBER_OF_SECTIONS));
        $this->courseid = $course->id;

        for ($i = 0; $i < self::NUMBER_OF_USERS; $i++) {
            $this->users[] = $this->getDataGenerator()->create_user();
        }
        $this->enroll_users();

        for ($i = 0; $i < self::NUMBER_OF_SECTIONS; $i++) {
            $this->sections[] = $this->getDataGenerator()->create_course_section(array('course' => $this->courseid,
                'section' => $i + 1));
        }
    }

    /**
     * This function enrolls the test users.
     *
     */
    protected function enroll_users() {
        global $DB;

        // Get role IDs by shortname.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);
        // Enroll users.
        foreach ($this->users as $user) {
            $this->getDataGenerator()->enrol_user($user->id, $this->courseid, $studentrole->id, 'manual');
        }
    }

    /**
     * This function creates the groups for testing.
     *
     */
    protected function create_groups() {

        for ($i = 0; $i < self::NUMBER_OF_GROUPS; $i++) {
            $this->groups[$i] = $this->getDataGenerator()->create_group(array('courseid' => $this->courseid));
            for ($j = 0; $j < self::USERS_PER_GROUP; $j++) {
                if (isset($this->users[$i * self::USERS_PER_GROUP + $j]->id)) {
                    groups_add_member($this->groups[$i]->id, $this->users[$i * self::USERS_PER_GROUP + $j]->id);
                }
            }
        }
        // Add one empty group at the end to use for testing some exceptions.
        $this->groups[self::NUMBER_OF_GROUPS] = $this->getDataGenerator()->create_group(array('courseid' => $this->courseid));
    }

    /**
     * Returns a list of userids for each user in the group.
     *
     * @param      integer  $index  the index of the group to retrieve (starting from 0)
     * @return     array  the list of userids in the specified group
     */
    protected function get_users_in_group($index) {

        $userids = array();
        for ($i = 0; $i < self::USERS_PER_GROUP; $i++) {
            if (isset($this->users[$index * self::USERS_PER_GROUP + $i]->id)) {
                $userids[] = $this->users[$index * self::USERS_PER_GROUP + $i]->id;
            }
        }
        return $userids;
    }

    /**
     * Create some activities in the course.  The names for the activities are specified in
     * the activitynames member variable.
     */
    protected function create_activities() {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        foreach ($this->activitynames as $name) {
            $this->activities[] = $generator->create_instance(array('course' => $this->courseid, 'section' => 1, 'name' => $name));
        }
    }

    /**
     * Create some a forum in the course.
     */
    protected function create_forums() {

        $record = new \stdClass();
        $record->course = $this->courseid;
        for ($i = 0; $i < self::NUMBER_OF_FORUMS; $i++) {
            $this->forums[] = $this->getDataGenerator()->create_module('forum', $record);
        }

        $this->create_posts();
    }

    /**
     * Let user #1 create a discussion in each forum that exists.  They also create one
     * additional post that is six words in length.
     */
    protected function create_posts() {

        foreach ($this->forums as $forum) {
            $record = new \stdClass();
            $record->course = $this->courseid;
            $record->userid = $this->users[0]->id;
            $record->forum = $forum->id;
            $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);
            $record->discussion = $discussion->id;
            $record->message = \html_writer::tag('p', 'Posting strangely a six word post.');
            $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);
        }
    }

    /**
     * Create three grade items to test with.  One value, one scale, one text.
     *
     * Assign a few grades to users to test with.
     */
    protected function create_grades() {
        global $DB;

        $gradetype = array(GRADE_TYPE_VALUE, GRADE_TYPE_SCALE, GRADE_TYPE_TEXT);
        $grades = array(array(4, 4, 4, 1, 1, 1, 7, 7, 7, 10, 10, 10, 13, 13, 13),
            array(1, 2, 3, 1, 2, 3, 1, 2, 3, 1, 2, 3, 1, 2, 3),
            array('bad', 'bad', 'neutral', 'neutral', 'good', 'good', 'bad', 'neutral', 'good', 'z', 'z', 'z', 'z', 'z', 'z'));

        $this->scale = $this->getDataGenerator()->create_scale(array('name' => 'testscale', 'courseid' => $this->courseid,
            'userid' => self::ADMIN_USERID, 'scale' => 'Bad, Neutral, Good'));

        for ($i = 0; $i < 3; $i++) {
            $scaleid = ($gradetype[$i] == GRADE_TYPE_SCALE) ? $this->scale->id : null;
            $this->gradeitems[] = $this->getDataGenerator()->create_grade_item(array('courseid' => $this->courseid,
                '$itemname' => $this->gradenames[$i], 'gradetype' => $gradetype[$i], 'scaleid' => $scaleid));
            for ($j = 0; $j < self::NUMBER_OF_USERS; $j++) {
                $grade = new stdClass();
                $grade->itemid = $this->gradeitems[$i]->id;
                $grade->userid = $this->users[$j]->id;
                if ($gradetype[$i] == GRADE_TYPE_TEXT) {
                    $grade->feedback = $grades[$i][$j];
                } else {
                    $grade->rawgrade = $grades[$i][$j];
                    $grade->finalgrade = $grades[$i][$j];
                }
                $grade->timecreated = time();
                $grade->timemodified = time();
                $grade->id = $DB->insert_record('grade_grades', $grade);
            }
        }
        grade_regrade_final_grades($this->courseid);
    }

    /**
     * Create an array containing a set of user IDs from a list of keys in the
     * user array.
     *
     * @param  array  $keys  the keys in the user array to use
     * @return array  the user IDs corresponding to the keys
     */
    protected function create_userids_from_keys($keys) {

        $expected = array();
        foreach ($keys as $key) {
            $expected[] = $this->users[$key]->id;
        }
        return $expected;
    }

    /**
     * Verify that the list of actual users contains all of the expected users.
     *
     * @param  array  $expected  the expected list of user IDs
     * @param  array  $actual    the actual results (includes user IDs and other fields)
     */
    protected function verify_users_array($expected, $actual) {

        $actualids = array();
        foreach ($actual as $user) {
            $actualids[] = $user['id'];
        }
        sort($actualids);
        $this->assertEquals($expected, $actualids);
    }

    /**
     * Logs the admin user into the system for testing.
     */
    protected function login_admin_user() {
        $u = user_get_users_by_id(array(self::ADMIN_USERID));
        $user = clone($u[self::ADMIN_USERID]);
        $this->login_user($user);
    }

    /**
     * Logs the spcified user into the system.  This code was taken largely from
     * /lib/tests/moodlelib_test.php.
     *
     * @param      object  $user   user object of user to login
     */
    protected function login_user($user) {
        $this->redirectEvents();
        $this->setCurrentTimeStart();
        // Hide session header errors -> /lib/tests/moodlelib_test.php.
        @complete_user_login($user);
    }

    /**
     * Creates a criterion - action|grade.
     *
     * @param  string     $type      the type of criterion to create
     * @param  int|array  $operand   the criterion operand
     * @param  string     $operator  the criterion operator (>, <, =)
     * @param  int        $value     the criterion value to compare against
     * @return stdClass  the criterion formatted as an object
     */
    protected function create_criterion($type, $operand, $operator, $value) {

        $condition = new stdClass();
        $condition->type = $type;
        $condition->operand = ($type === 'grade') ? $operand : (object) $operand;
        $condition->operator = $operator;
        $condition->value = $value;
        return $condition;
    }

    /**
     * Add the events data to the logstore so that more complex retrieval testing
     * can be performed.
     *
     * The date is loaded directly into the logstore with some placeholder IDs for
     * the users/courses/contexts in the dummy data.  These IDs are swapped in the
     * functions below.
     */
    protected function add_events_data() {

        $file = __DIR__ . '/../tests/fixtures/events_data.xml';
        $dataset = $this->dataset_from_files(array($file));
        $dataset->to_database();
        $this->switch_userids();
        $this->switch_courseids();
        $this->switch_cmids();
    }

    /**
     * Perform the userid swap in the dummy data.  The admin userid is swapped,
     * then a series of test userids.
     *
     * Original data: {feedback -> block -> core}
     * 2   ->  9985 -> admin {3 - 0 - 5}
     * 3   ->  9986 -> 0 {0 - 0 - 5}
     * 4   ->  9987 -> 1 {0 - 2 - 6}
     * 6   ->  9988 -> 2 {0 - 3 - 11}
     * 9   ->  9989 -> 3 {0 - 4 - 11}
     * 10  ->  9990 -> 4 {0 - 2 - 5}
     * 11  ->  9991 -> 5 {0 - 2 - 11}
     * 15  ->  9992 -> 6 {3 - 2 - 10}
     * 16  ->  9993 -> 7 {2 - 0 - 2}
     * 17  ->  9994 -> 8 {2 - 0 - 2}
     * 18  ->  9995 -> 9 {2 - 0 - 3}
     * 19  ->  9996 -> 10 {2 - 2 - 11}
     * 20  ->  9997 -> 11 {2 - 0 - 2}
     * 21  ->  9998 -> 12 {2 - 0 - 2}
     * 22  ->  9999 -> 13 {2 - 1 - 8}
     */
    protected function switch_userids() {
        $userids = array(2 => 9985);

        for ($i = 0; $i < 14; $i++) {
            $userids[$this->users[$i]->id] = 9986 + $i;
        }

        $this->switch_ids($userids, 'userid');
    }

    /**
     * Perform the courseid swap in the dummy data.  There is only one test course
     * but there is no restriction on adding more later.
     *
     * Original data:
     * 4  ->  9999
     */
    protected function switch_courseids() {
        $courseids = array($this->courseid => 9999);
        $this->switch_ids($courseids, 'courseid');
    }

    /**
     * Perform the cmid swap in the dummy data.  The first cmid corresponds to the
     * course ID, while the rest are activity cmids.  The original activities were
     * both feedbacks, but they could be anything.
     *
     * Original data:
     * 47  ->  9997
     * 48  ->  9998
     * 4   ->  9999
     */
    protected function switch_cmids() {
        $cmids = array($this->courseid => 9999);

        for ($i = 0; $i < 2; $i++) {
            $cmids[$this->activities[$i]->cmid] = 9997 + $i;
        }

        $this->switch_ids($cmids, 'contextinstanceid');
    }

    /**
     * This function performs the IDs swaps.  Find records with the old ID, change to
     * the new ID, then update the record.
     *
     * @param      array  $ids    array of IDs in the form new ID => old ID
     * @param      string $field  field in DB to perform the swap on
     */
    protected function switch_ids($ids, $field) {
        global $DB;

        foreach ($ids as $newid => $oldid) {
            $records = $DB->get_records('logstore_standard_log', array($field => $oldid));
            foreach ($records as $r) {
                $r->$field = $newid;
                $DB->update_record('logstore_standard_log', $r);
            }
        }
    }

}
