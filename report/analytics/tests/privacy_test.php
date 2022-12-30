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

use report_analytics\scheduled_results_record;
use report_analytics\privacy\provider;
use core_privacy\tests\provider_testcase;
use core_privacy\local\request\writer;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Test class for: provider.php (privacy API)
 *
 * @package    report_analytics
 * @copyright  2019 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_analytics_privacy_testcase extends provider_testcase {

    /** @var int This is the test courses */
    protected $courses;
    /** @var array Holds the test teachers */
    protected $teachers = array();
    /** Max number of test teachers */
    const NUMBER_OF_TEACHERS = 3;
    /** Max number of test courses */
    const NUMBER_OF_COURSES = 2;

    /**
     * Set up the database for testing.
     */
    public function setUp(): void {

        $this->resetAfterTest(true);
        $this->create_course();

        $record = new scheduled_results_record($this->courses[0]->id);
        $course1users = array($this->teachers[0]->id, $this->teachers[1]->id);
        $record->set_userids($course1users);
        $record->set_filters($this->courses[0]->shortname);
        $record->set_email_time(0);
        $record->set_results_time(0);
        $record->save();

        $record = new scheduled_results_record($this->courses[1]->id);
        $course2users = array($this->teachers[1]->id, $this->teachers[2]->id);
        $record->set_userids($course2users);
        $record->set_filters($this->courses[1]->shortname);
        $record->set_email_time(1);
        $record->set_results_time(1);
        $record->save();
    }

    /**
     * This function creates the courses that are used for testing.  Test users (as
     * teachers) are added to the course as well.
     */
    protected function create_course() {

        for ($i = 0; $i < self::NUMBER_OF_COURSES; $i++) {
            $this->courses[] = $this->getDataGenerator()->create_course();
        }
        for ($i = 0; $i < self::NUMBER_OF_TEACHERS; $i++) {
            $this->teachers[] = $this->getDataGenerator()->create_user();
        }
        $this->enroll_users();

    }

    /**
     * This function enrolls the test users as teachers.
     */
    protected function enroll_users() {
        global $DB;

        // Get role IDs by shortname.
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->assertNotEmpty($teacherrole);
        // Enroll users in courses.
        foreach ($this->courses as $course) {
            foreach ($this->teachers as $user) {
                $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole->id, 'manual');
            }
        }
    }

    /**
     * Tests retrieving valid contexts for a given user.
     */
    public function test_get_contexts_for_userid() {

        $contexts = array(context_course::instance($this->courses[0]->id), context_course::instance($this->courses[1]->id));

        // Test 1: user 0, has record in course 0.
        $contextlist = provider::get_contexts_for_userid($this->teachers[0]->id);
        $this->assertEquals($contextlist->get_contextids(), [$contexts[0]->id]);

        // Test 2: user 1, has records in both courses.
        $contextlist = provider::get_contexts_for_userid($this->teachers[1]->id);
        $this->assertEquals($contextlist->get_contextids(), [$contexts[0]->id, $contexts[1]->id]);

        // Test 3: user 2, has records in course 1.
        $contextlist = provider::get_contexts_for_userid($this->teachers[2]->id);
        $this->assertEquals($contextlist->get_contextids(), [$contexts[1]->id]);
    }

    /**
     * Test that the correct userids are returned for a specific context.
     */
    public function test_get_users_in_context() {

        $contexts = array(context_course::instance($this->courses[0]->id), context_course::instance($this->courses[1]->id));
        $userlist1 = new \core_privacy\local\request\userlist($contexts[0], 'report_analytics');
        $userlist2 = new \core_privacy\local\request\userlist($contexts[1], 'report_analytics');

        // Test 1: users 0, 1 are in course 0.
        provider::get_users_in_context($userlist1);
        $this->assertEquals($userlist1->get_userids(), [$this->teachers[0]->id, $this->teachers[1]->id]);

        // Test 2: users 1, 2 are in course 1.
        provider::get_users_in_context($userlist2);
        $this->assertEquals($userlist2->get_userids(), [$this->teachers[1]->id, $this->teachers[2]->id]);
    }

    /**
     * Test exporting data for a user.
     */
    public function test_export_user_data() {

        $expectedfilters = [$this->courses[0]->shortname, $this->courses[1]->shortname];
        $expectedmetadata = [(object) ['emailtime' => 0, 'resultstime' => 0], (object) ['emailtime' => 1, 'resultstime' => 1]];

        $contextlist = provider::get_contexts_for_userid($this->teachers[1]->id);
        $approvedcontextlist = new approved_contextlist($this->teachers[1], 'report_analytics', $contextlist->get_contextids());
        provider::export_user_data($approvedcontextlist);
        $i = 0;
        foreach ($contextlist->get_contexts() as $context) {
            $writer = writer::with_context($context);
            $subcontext = [get_string('analytics', 'report_analytics') . ': ' . get_string('schedulereport', 'report_analytics')];
            $data = $writer->get_data($subcontext);
            $this->assertEquals($data->filters, $expectedfilters[$i]);
            $metadata = $writer->get_metadata($subcontext, 'times');
            $this->assertEquals($metadata, $expectedmetadata[$i]);
            $i++;
        }
    }

    /**
     * Test deleting data for all users that exist in a given context.
     */
    public function test_delete_data_for_all_users_in_context() {

        provider::delete_data_for_all_users_in_context(context_course::instance($this->courses[0]->id));

        // Test 1: ensure entries are deleted for course 1.
        $record = new scheduled_results_record($this->courses[0]->id);
        $this->assertFalse((bool) $record->exists());

        // Test 2: ensure entries are not deleted for course 2.
        $record = new scheduled_results_record($this->courses[1]->id);
        $this->assertTrue((bool) $record->exists());
    }

    /**
     * Test deleting data for a particular user (all contexts).
     */
    public function test_delete_data_for_user() {

        $contextlist = provider::get_contexts_for_userid($this->teachers[1]->id);
        $approvedcontextlist = new approved_contextlist($this->teachers[1], 'report_analytics', $contextlist->get_contextids());
        provider::delete_data_for_user($approvedcontextlist);

        // Test 1: ensure entries are deleted for course 1.
        $record = new scheduled_results_record($this->courses[0]->id);
        $this->assertEquals($record->get_userids(), [$this->teachers[0]->id]);

        // Test 2: ensure entries are not deleted for course 2.
        $record = new scheduled_results_record($this->courses[1]->id);
        $this->assertEquals($record->get_userids(), [$this->teachers[2]->id]);
    }

    /**
     * Test the deletion of a data for users in a particular context.
     */
    public function test_delete_data_for_users() {

        $userlist = new approved_userlist(context_course::instance($this->courses[0]->id), 'report_analytics',
            [$this->teachers[0]->id, $this->teachers[1]->id]);
        provider::delete_data_for_users($userlist);

        // Test 1: ensure entries are deleted for course 1.
        $record = new scheduled_results_record($this->courses[0]->id);
        $this->assertEmpty($record->get_userids());

        // Test 2: ensure entries are not deleted for course 2.
        $record = new scheduled_results_record($this->courses[1]->id);
        $this->assertEquals($record->get_userids(), [$this->teachers[1]->id, $this->teachers[2]->id]);
    }

}
