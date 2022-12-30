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

use block_skills_group\skills_group_student;
use block_skills_group\skills_group_setting;
use block_skills_group\settings_record;
use block_skills_group\privacy\provider;
use core_privacy\tests\provider_testcase;
use core_privacy\local\request\writer;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Test class for: provider.php (privacy API)
 *
 * @package    report_analytics
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_skills_group_privacy_testcase extends provider_testcase {

    /** @var int This is the test courses */
    protected $courses;
    /** @var array Holds the test students */
    protected $users = array();
    /** @var array Holds the test groupings */
    protected $groupings = array();
    /** Max number of test students */
    const NUMBER_OF_STUDENTS = 3;
    /** Max number of test courses */
    const NUMBER_OF_COURSES = 2;
    /** Max number of test groupings */
    const NUMBER_OF_GROUPINGS = 2;

    /**
     * Set up the database for testing.
     */
    public function setUp(): void {

        $this->resetAfterTest(true);
        $this->create_course();
        $this->create_groupings();

        $record = new settings_record();
        $record->groupingid = $this->groupings[0]->id;
        $sgsetting = new skills_group_setting($this->courses[0]->id);
        $sgsetting->update_record($record);

        // Student 0 + 1 are locked in for grouping 0.
        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[0]->id);
        $sgstudent->set_lock_choice(true);
        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[1]->id);
        $sgstudent->set_lock_choice(true);

        $record->groupingid = $this->groupings[1]->id;
        $sgsetting->update_record($record);

        // Student 0 locked in for grouping 1 (student 1 makes no selection -> not locked).
        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[0]->id);
        $sgstudent->set_lock_choice(true);

        $record->groupingid = $this->groupings[2]->id;
        $sgsetting = new skills_group_setting($this->courses[1]->id);
        $sgsetting->update_record($record);

        // Student 1 + 2 are locked in for grouping 2.
        $sgstudent = new skills_group_student($this->courses[1]->id, $this->users[1]->id);
        $sgstudent->set_lock_choice(true);
        $sgstudent = new skills_group_student($this->courses[1]->id, $this->users[2]->id);
        $sgstudent->set_lock_choice(true);

        $record->groupingid = $this->groupings[3]->id;
        $sgsetting->update_record($record);

        // Student 2 locked in for grouping 3 (student 1 makes no selection -> no locked).
        $sgstudent = new skills_group_student($this->courses[1]->id, $this->users[2]->id);
        $sgstudent->set_lock_choice(true);
    }

    /**
     * This function creates the courses that are used for testing.  Test users (as
     * students) are added to the course as well.
     */
    protected function create_course() {

        for ($i = 0; $i < self::NUMBER_OF_COURSES; $i++) {
            $this->courses[] = $this->getDataGenerator()->create_course();
        }
        for ($i = 0; $i < self::NUMBER_OF_STUDENTS; $i++) {
            $this->users[] = $this->getDataGenerator()->create_user();
        }
        $this->enroll_users();

    }

    /**
     * This function enrolls the test users as students.
     */
    protected function enroll_users() {
        global $DB;

        // Get role IDs by shortname.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);
        // Enroll users in courses.
        foreach ($this->courses as $course) {
            foreach ($this->users as $user) {
                $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id, 'manual');
            }
        }
    }

    /**
     * This function creates the groupings to be used while testing.
     */
    protected function create_groupings() {

        foreach ($this->courses as $course) {
            for ($i = 0; $i < self::NUMBER_OF_GROUPINGS; $i++) {
                $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
                $this->groupings[] = $grouping;
                $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
                groups_assign_grouping($grouping->id, $group->id);
                groups_add_member($group->id, $this->users[1]->id);
                if (($course->id & 1) == 0) {
                    groups_add_member($group->id, $this->users[0]->id);
                } else {
                    groups_add_member($group->id, $this->users[2]->id);
                }
            }
        }
    }

    /**
     * Tests retrieving valid contexts for a given user.
     */
    public function test_get_contexts_for_userid() {

        $contexts = array(context_course::instance($this->courses[0]->id), context_course::instance($this->courses[1]->id));

        // Test 1: user 0, has record in course 0.
        $contextlist = provider::get_contexts_for_userid($this->users[0]->id);
        $this->assertEquals($contextlist->get_contextids(), [$contexts[0]->id]);

        // Test 2: user 1, has records in both courses.
        $contextlist = provider::get_contexts_for_userid($this->users[1]->id);
        $this->assertEquals($contextlist->get_contextids(), [$contexts[0]->id, $contexts[1]->id]);

        // Test 3: user 2, has records in course 1.
        $contextlist = provider::get_contexts_for_userid($this->users[2]->id);
        $this->assertEquals($contextlist->get_contextids(), [$contexts[1]->id]);
    }

    /**
     * Test that the correct userids are returned for a specific context.
     */
    public function test_get_users_in_context() {

        $contexts = array(context_course::instance($this->courses[0]->id), context_course::instance($this->courses[1]->id));
        $userlist1 = new userlist($contexts[0], 'block_skills_group');
        $userlist2 = new userlist($contexts[1], 'block_skills_group');

        // Test 1: users 0, 1 have records in course 0.
        provider::get_users_in_context($userlist1);
        $this->assertEquals($userlist1->get_userids(), [$this->users[0]->id, $this->users[1]->id]);

        // Test 2: users 1, 2 have records in course 1.
        provider::get_users_in_context($userlist2);
        $this->assertEquals($userlist2->get_userids(), [$this->users[1]->id, $this->users[2]->id]);
    }

    /**
     * Test exporting data for a user.
     */
    public function test_export_user_data() {

        $expectedlocks = [true];

        $contextlist = provider::get_contexts_for_userid($this->users[2]->id);
        $approvedcontextlist = new approved_contextlist($this->users[2], 'block_skills_group', $contextlist->get_contextids());
        provider::export_user_data($approvedcontextlist);
        $i = 0;
        foreach ($contextlist->get_contexts() as $context) {
            $writer = writer::with_context($context);
            $subcontext = [get_string('pluginname', 'block_skills_group') . ': ' . get_string('lockstatus',
                'block_skills_group')];
            $data = $writer->get_data($subcontext);
            $this->assertEquals($data->lock, $expectedlocks[$i]);
            $i++;
        }
    }

    /**
     * Test deleting data for all users that exist in a given context.
     */
    public function test_delete_data_for_all_users_in_context() {

        provider::delete_data_for_all_users_in_context(context_course::instance($this->courses[0]->id));

        // Test 1: ensure entries are deleted for course 1.
        $record = new settings_record();
        $record->groupingid = $this->groupings[0]->id;
        $sgsetting = new skills_group_setting($this->courses[0]->id);
        $sgsetting->update_record($record);

        // Check both groupings to ensure that all records are cleared (if plugin used multiple times).
        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[0]->id);
        $this->assertFalse((bool) $sgstudent->get_lock_choice());
        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[1]->id);
        $this->assertFalse((bool) $sgstudent->get_lock_choice());
        $record->groupingid = $this->groupings[1]->id;
        $sgsetting->update_record($record);
        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[0]->id);
        $this->assertFalse((bool) $sgstudent->get_lock_choice());
        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[1]->id);
        $this->assertFalse((bool) $sgstudent->get_lock_choice());

        // Test 2: ensure entries are not deleted for course 2.
        $record = new settings_record();
        $record->groupingid = $this->groupings[2]->id;
        $sgsetting = new skills_group_setting($this->courses[1]->id);
        $sgsetting->update_record($record);

        $sgstudent = new skills_group_student($this->courses[1]->id, $this->users[1]->id);
        $this->assertTrue((bool) $sgstudent->get_lock_choice());
        $sgstudent = new skills_group_student($this->courses[1]->id, $this->users[2]->id);
        $this->assertTrue((bool) $sgstudent->get_lock_choice());
        $record->groupingid = $this->groupings[3]->id;
        $sgsetting->update_record($record);
        $sgstudent = new skills_group_student($this->courses[1]->id, $this->users[2]->id);
        $this->assertTrue((bool) $sgstudent->get_lock_choice());
    }

    /**
     * Test deleting data for a particular user (all contexts).
     */
    public function test_delete_data_for_user() {

        $contextlist = provider::get_contexts_for_userid($this->users[0]->id);
        $approvedcontextlist = new approved_contextlist($this->users[0], 'block_skills_group', $contextlist->get_contextids());
        provider::delete_data_for_user($approvedcontextlist);

        // Test 1: ensure entries are deleted for course 1 (student 0).
        $record = new settings_record();
        $record->groupingid = $this->groupings[0]->id;
        $sgsetting = new skills_group_setting($this->courses[0]->id);
        $sgsetting->update_record($record);

        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[0]->id);
        $this->assertFalse((bool) $sgstudent->get_lock_choice());
        $record->groupingid = $this->groupings[1]->id;
        $sgsetting->update_record($record);
        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[0]->id);
        $this->assertFalse((bool) $sgstudent->get_lock_choice());

        // Test 2: ensure entries are not deleted for course 2 - student 0 has no entries.
        $record->groupingid = $this->groupings[2]->id;
        $sgsetting = new skills_group_setting($this->courses[1]->id);
        $sgsetting->update_record($record);

        $sgstudent = new skills_group_student($this->courses[1]->id, $this->users[1]->id);
        $this->assertTrue((bool) $sgstudent->get_lock_choice());
        $sgstudent = new skills_group_student($this->courses[1]->id, $this->users[2]->id);
        $this->assertTrue((bool) $sgstudent->get_lock_choice());
        $record->groupingid = $this->groupings[3]->id;
        $sgsetting->update_record($record);
        $sgstudent = new skills_group_student($this->courses[1]->id, $this->users[2]->id);
        $this->assertTrue((bool) $sgstudent->get_lock_choice());
    }

    /**
     * Test the deletion of a data for users in a particular context.
     */
    public function test_delete_data_for_users() {

        $userlist = new approved_userlist(context_course::instance($this->courses[0]->id), 'block_skills_group',
            [$this->users[0]->id, $this->users[1]->id]);
        provider::delete_data_for_users($userlist);

        // Test 1: ensure entries are deleted for course 1.
        $record = new settings_record();
        $record->groupingid = $this->groupings[0]->id;
        $sgsetting = new skills_group_setting($this->courses[0]->id);
        $sgsetting->update_record($record);

        // Check both groupings to ensure that all records are cleared (if plugin used multiple times).
        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[0]->id);
        $this->assertFalse((bool) $sgstudent->get_lock_choice());
        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[1]->id);
        $this->assertFalse((bool) $sgstudent->get_lock_choice());
        $record->groupingid = $this->groupings[1]->id;
        $sgsetting->update_record($record);
        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[0]->id);
        $this->assertFalse((bool) $sgstudent->get_lock_choice());
        $sgstudent = new skills_group_student($this->courses[0]->id, $this->users[1]->id);
        $this->assertFalse((bool) $sgstudent->get_lock_choice());

        // Test 2: ensure entries are not deleted for course 2.
        $record = new settings_record();
        $record->groupingid = $this->groupings[2]->id;
        $sgsetting = new skills_group_setting($this->courses[1]->id);
        $sgsetting->update_record($record);

        $sgstudent = new skills_group_student($this->courses[1]->id, $this->users[1]->id);
        $this->assertTrue((bool) $sgstudent->get_lock_choice());
        $sgstudent = new skills_group_student($this->courses[1]->id, $this->users[2]->id);
        $this->assertTrue((bool) $sgstudent->get_lock_choice());
        $record->groupingid = $this->groupings[3]->id;
        $sgsetting->update_record($record);
        $sgstudent = new skills_group_student($this->courses[1]->id, $this->users[2]->id);
        $this->assertTrue((bool) $sgstudent->get_lock_choice());
    }

}
